"""Utility functions for Rasa actions."""
import re
from typing import Any, List, Optional, Tuple

import requests


def _format_vnd(amount: Optional[int]) -> str:
    if amount is None:
        return ""
    return f"{amount:,}".replace(",", ".") + "đ"


def _parse_budget_text_to_range(text: Optional[str]) -> Tuple[Optional[int], Optional[int]]:
    if not text:
        return (None, None)

    t = text.lower()
    t = t.replace("–", "-").replace("—", "-")

    def to_vnd(num_str: str, unit: str) -> Optional[int]:
        try:
            num = float(num_str.replace(",", "."))
        except Exception:
            return None
        unit = (unit or "").strip()
        if unit in ("k", "nghìn", "nghin"):
            return int(num * 1_000)
        if unit in ("tr", "triệu", "trieu", "m", "million"):
            return int(num * 1_000_000)
        return int(num)

    m = re.search(r"(dưới|duoi|<)\s*([0-9]+(?:[.,][0-9]+)?)\s*(triệu|trieu|tr|m|k|nghìn|nghin)?", t)
    if m:
        max_vnd = to_vnd(m.group(2), m.group(3) or "tr")
        return (None, max_vnd)

    m = re.search(r"(trên|tren|>)\s*([0-9]+(?:[.,][0-9]+)?)\s*(triệu|trieu|tr|m|k|nghìn|nghin)?", t)
    if m:
        min_vnd = to_vnd(m.group(2), m.group(3) or "tr")
        return (min_vnd, None)

    m = re.search(
        r"([0-9]+(?:[.,][0-9]+)?)\s*(triệu|trieu|tr|m|k|nghìn|nghin)?\s*(?:-|đến|den|tới|toi)\s*([0-9]+(?:[.,][0-9]+)?)\s*(triệu|trieu|tr|m|k|nghìn|nghin)?",
        t,
    )
    if m:
        left_num = float(m.group(1).replace(",", "."))
        right_num = float(m.group(3).replace(",", "."))
        left_unit = m.group(2)
        right_unit = m.group(4)

        if not left_unit and not right_unit:
            if max(left_num, right_num) <= 20:
                left_unit = right_unit = "tr"
            elif max(left_num, right_num) <= 2000:
                left_unit = right_unit = "k"
            else:
                left_unit = right_unit = ""

        a = to_vnd(m.group(1), left_unit or "tr")
        b = to_vnd(m.group(3), right_unit or left_unit or "tr")
        return (a, b)

    m = re.search(r"([0-9]+(?:[.,][0-9]+)?)\s*(triệu|trieu|tr|m|k|nghìn|nghin)", t)
    if m:
        v = to_vnd(m.group(1), m.group(2))
        if v is not None:
            return (int(v * 0.8), int(v * 1.2))

    return (None, None)


def _parse_size(text: Optional[str]) -> Optional[str]:
    if not text:
        return None

    raw = text.lower().replace(",", ".")

    # Foot length in cm should be prioritized over bare numeric matches.
    cm_match = re.search(r"([0-9]{2}(?:\.[0-9])?)\s*cm\b", raw)
    if cm_match:
        cm = float(cm_match.group(1))
        size_map = [
            (23.0, 36),
            (23.5, 37),
            (24.0, 38),
            (24.5, 39),
            (25.0, 40),
            (25.5, 41),
            (26.0, 42),
            (26.5, 43),
            (27.0, 44),
            (27.5, 45),
        ]
        for threshold, size in size_map:
            if cm <= threshold:
                return str(size)
        return "46"

    # Direct shoe size mention: "size 41" or "41"
    m = re.search(r"\bsize\s*([0-9]{2})\b", raw)
    if m:
        return m.group(1)

    m = re.search(r"\b([0-9]{2})\b", raw)
    if m:
        return m.group(1)

    return None


def _clean_search_query(text: str) -> Optional[str]:
    t = (text or "").lower().strip()
    if not t:
        return None

    t = re.sub(
        r"\b(tìm|tim|cho|mình|toi|tôi|m\u00ecnh|gi\u00fay|giup|xem|g\u1ee3i\s*y|g\u1ee3i|t\u01b0\s*v\u1ea5n|tu\s*van|shop|c\u00f3|kh\u00f4ng|khong|c\u1ea7n|can|mu\u1ed1n|muon|th\u00edch|thich|mua)\b",
        " ",
        t,
    )
    t = re.sub(r"\bgi\u00e0y\b", " ", t)
    t = re.sub(r"\bsize\s*[0-9]{2}\b", " ", t)
    t = re.sub(
        r"(d\u01b0\u1edbi|duoi|tr\u00ean|tren|t\u1ea7m|kho\u1ea3ng|t\u1eeb|tu|t\u1edbi|toi|\u0111\u1ebfn|den)\s*[0-9]+(?:[.,][0-9]+)?\s*(tri\u1ec7u|trieu|tr|m|k|ngh\u00ecn|nghin)?",
        " ",
        t,
    )
    t = re.sub(r"\b[0-9]+(?:[.,][0-9]+)?\b", " ", t)

    t = re.sub(r"\s+", " ", t).strip()
    return t or None


def _infer_color_from_text(text: Optional[str]) -> Optional[str]:
    t = (text or "").lower().strip()
    if not t:
        return None

    # DB stores colors in English, so return normalized English values.
    color_map = {
        "black": ["đen", "black"],
        "white": ["trắng", "trang", "white"],
        "brown": ["nâu", "brown"],
        "gray": ["xám", "gray", "grey"],
        "beige": ["be", "cream", "nude", "kem", "beige"],
        "red": ["đỏ", "do", "red"],
        "navy": ["xanh navy", "navy"],
        "blue": ["xanh dương", "xanh blue", "blue"],
        "green": ["xanh lá", "xanh mint", "mint", "green"],
        "pink": ["hồng", "pink"],
        "yellow": ["vàng", "yellow"],
    }

    for color, keywords in color_map.items():
        if any(k in t for k in keywords):
            return color
    return None


def _get_entity(entities: List[dict], name: str) -> Optional[str]:
    for e in entities or []:
        if e.get("entity") == name and e.get("value") is not None:
            return str(e.get("value"))
    return None
