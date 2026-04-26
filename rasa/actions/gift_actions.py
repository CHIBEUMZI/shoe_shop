"""Gift recommendation search action."""
import os
from typing import Any, Dict, List, Optional, Text

import requests

from rasa_sdk import Action, Tracker
from rasa_sdk.events import SlotSet
from rasa_sdk.executor import CollectingDispatcher

from .api_client import _fetch_products, _product_to_card
from .utils import _parse_budget_text_to_range, _format_vnd


GIFT_OCCASION_MAP = {
    "sinh nhật": "birthday_gift",
    "sinhnhat": "birthday_gift",
    "birthday": "birthday_gift",
    "sn": "birthday_gift",
    "mừng sn": "birthday_gift",
    "kỷ niệm": "anniversary_gift",
    "ky Niem": "anniversary_gift",
    "kỷ niệm ngày yêu": "anniversary_gift",
    "anniversary": "anniversary_gift",
    "ngày kỷ niệm": "anniversary_gift",
    "valentine": "valentine",
    "valentine's": "valentine",
    "14/2": "valentine",
    "ngày 14/2": "valentine",
    "lễ tình nhân": "valentine",
    "ngày lễ tình nhân": "valentine",
    "ngày tình nhân": "valentine",
    "tình nhân": "valentine",
    "noel": "valentine",
    "tết": "casual",
    "tet": "casual",
    "trung thu": "valentine",
    "8/3": "valentine",
    "ngày 8/3": "valentine",
    "20/10": "valentine",
    "ngày 20/10": "valentine",
    "tốt nghiệp": "casual",
    "sinh nhật bạn gái": "birthday_gift",
    "sinh nhật người yêu": "birthday_gift",
    "kỷ niệm ngày cưới": "anniversary_gift",
}


GIFT_GENDER_MAP = {
    "nam": "nam",
    "bạn trai": "nam",
    "boyfriend": "nam",
    "anh ấy": "nam",
    "nữ": "nữ",
    "bạn gái": "nữ",
    "girlfriend": "nữ",
    "cô ấy": "nữ",
}


def _infer_gift_occasion(text: Optional[str]) -> Optional[str]:
    if not text:
        return None
    t = text.lower().strip()
    for keyword, occasion in GIFT_OCCASION_MAP.items():
        if keyword in t:
            return occasion
    return None


def _infer_gender(text: Optional[str]) -> Optional[str]:
    if not text:
        return None
    t = text.lower().strip()
    for keyword, gender in GIFT_GENDER_MAP.items():
        if keyword in t:
            return gender
    return None


def _search_gift_shoes(
    occasion: Optional[str] = None,
    gender: Optional[str] = None,
    size: Optional[str] = None,
    price_range: Optional[str] = None,
    limit: int = 6,
) -> List[dict]:
    api = os.getenv("SHOP_API_BASE_URL", "http://nginx").rstrip("/")

    min_vnd, max_vnd = _parse_budget_text_to_range(price_range)

    occasion_key = _infer_gift_occasion(occasion) if occasion else None

    def _build_params(
        include_occasion: bool = True,
        include_gender: bool = True,
        include_size: bool = True,
        include_price: bool = True,
    ) -> Dict[str, Any]:
        params: Dict[str, Any] = {"per_page": 12, "sort": "popular"}

        if include_gender and gender and gender != "khong_biet":
            # Try to find products matching gender keyword
            gender_keyword = "nữ" if gender == "nữ" else "nam"
            params["search"] = gender_keyword

        if include_occasion and occasion_key:
            params["occasion"] = [occasion_key]

        if include_size and size and size != "pho_bien":
            params["size"] = size

        if include_price:
            if min_vnd is not None:
                params["price_min"] = min_vnd
            if max_vnd is not None:
                params["price_max"] = max_vnd

        return params

    def _fetch(params: Dict[str, Any]) -> List[dict]:
        try:
            res = requests.get(f"{api}/api/v1/products", params=params, timeout=8)
            res.raise_for_status()
            payload = res.json()
            items = payload.get("data") if isinstance(payload, dict) else None
            if isinstance(items, list) and items:
                return items[:limit]
        except Exception:
            pass
        return []

    # Strategy: Progressive relaxation of filters
    # Start strict, then gradually loosen constraints

    # Try 1: Full search with all filters
    params1 = _build_params(include_occasion=True, include_gender=True, include_size=True, include_price=True)
    items = _fetch(params1)
    if items:
        return items

    # Try 2: Keep gender + size + price, try style_keywords instead of occasion filter
    if occasion_key:
        style_map = {
            "valentine": ["thời trang", "lãng mạn", "đỏ", "hồng", "nữ tính", "sang trọng"],
            "birthday_gift": ["thời trang", "đẹp", "sang trọng", "nữ tính"],
            "anniversary_gift": ["lãng mạn", "thời trang", "sang trọng", "cặp đôi"],
            "gift": ["thời trang", "đẹp", "sang trọng", "dễ phối đồ"],
            "casual": ["casual", "thoải mái", "năng động"],
            "interview": ["lịch sự", "formal", "công sở"],
            "sports": ["thể thao", "chạy bộ", "gym"],
            "travel": ["du lịch", "thoải mái", "nhẹ"],
            "party": ["thời trang", "sang trọng", "nổi bật"],
        }
        style_keywords = style_map.get(occasion_key, [])
        for kw in style_keywords:
            params2 = _build_params(include_occasion=False, include_gender=True, include_size=True, include_price=True)
            params2["search"] = kw
            items = _fetch(params2)
            if items:
                return items

    # Try 3: Remove size filter - often the most restrictive
    if size and size != "pho_bien":
        params3 = _build_params(include_occasion=True, include_gender=True, include_size=False, include_price=True)
        items = _fetch(params3)
        if items:
            return items

        # Try without occasion but with style keywords
        if occasion_key:
            for kw in style_keywords:
                params3b = _build_params(include_occasion=False, include_gender=True, include_size=False, include_price=True)
                params3b["search"] = kw
                items = _fetch(params3b)
                if items:
                    return items

    # Try 4: Remove price filter entirely (user might be too restrictive)
    if min_vnd is not None or max_vnd is not None:
        params4 = _build_params(include_occasion=True, include_gender=True, include_size=True, include_price=False)
        items = _fetch(params4)
        if items:
            return items

        # Try with style keywords and no price
        if occasion_key:
            for kw in style_keywords:
                params4b = _build_params(include_occasion=False, include_gender=True, include_size=True, include_price=False)
                params4b["search"] = kw
                items = _fetch(params4b)
                if items:
                    return items

    # Try 5: Remove gender filter - focus on occasion/size/price
    if gender and gender != "khong_biet":
        params5 = _build_params(include_occasion=True, include_gender=False, include_size=True, include_price=True)
        items = _fetch(params5)
        if items:
            return items

        # Try with style keywords
        if occasion_key:
            for kw in style_keywords:
                params5b = _build_params(include_occasion=False, include_gender=False, include_size=True, include_price=True)
                params5b["search"] = kw
                items = _fetch(params5b)
                if items:
                    return items

    # Try 6: Relax all filters - just gender + occasion
    params6 = _build_params(include_occasion=True, include_gender=True, include_size=False, include_price=False)
    items = _fetch(params6)
    if items:
        return items

    # Try 7: Only gender filter
    if gender and gender != "khong_biet":
        params7 = _build_params(include_occasion=False, include_gender=True, include_size=False, include_price=False)
        items = _fetch(params7)
        if items:
            return items

    # Try 8: Only occasion/size filter (no gender, no price)
    params8 = _build_params(include_occasion=True, include_gender=False, include_size=True, include_price=False)
    items = _fetch(params8)
    if items:
        return items

    # Try 9: Only occasion filter (no gender, no size, no price)
    params9 = _build_params(include_occasion=True, include_gender=False, include_size=False, include_price=False)
    items = _fetch(params9)
    if items:
        return items

    # Try 10: Just style keywords
    if occasion_key:
        for kw in style_keywords:
            params10 = {"per_page": 12, "sort": "popular", "search": kw}
            items = _fetch(params10)
            if items:
                return items

    # Try 11: Get popular products - guaranteed fallback
    params11 = {"per_page": 12, "sort": "popular"}
    items = _fetch(params11)
    if items:
        return items

    # Try 12: Get newest products
    params12 = {"per_page": 12, "sort": "latest"}
    items = _fetch(params12)
    if items:
        return items

    return []


class ActionSuggestGiftShoes(Action):

    def name(self) -> Text:
        return "action_suggest_gift_shoes"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:

        gift_occasion = tracker.get_slot("gift_occasion")
        recipient_gender = tracker.get_slot("recipient_gender")
        gift_shoe_size = tracker.get_slot("gift_shoe_size")
        gift_price_range = tracker.get_slot("gift_price_range")

        occasion_mapped = _infer_gift_occasion(gift_occasion or "")
        occasion_display = gift_occasion or "đặc biệt"
        gender_display = "nam" if recipient_gender == "nam" else "nữ" if recipient_gender == "nữ" else "cả nam và nữ"

        items = _search_gift_shoes(
            occasion=gift_occasion,
            gender=recipient_gender,
            size=gift_shoe_size,
            price_range=gift_price_range,
            limit=6,
        )

        if not items:
            dispatcher.utter_message(
                text="Mình chưa tìm được giày phù hợp với yêu cầu của bạn 😢 Bạn thử điều chỉnh tầm giá hoặc mô tả nhu cầu cụ thể hơn nhé."
            )
            return [
                SlotSet("gift_occasion", None),
                SlotSet("recipient_gender", None),
                SlotSet("gift_shoe_size", None),
                SlotSet("gift_price_range", None),
            ]

        title = self._build_title(occasion_display, gender_display, occasion_mapped, gift_price_range)
        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": title,
                "items": [_product_to_card(p) for p in items[:6]],
            }
        )

        return [
            SlotSet("gift_occasion", None),
            SlotSet("recipient_gender", None),
            SlotSet("gift_shoe_size", None),
            SlotSet("gift_price_range", None),
        ]

    def _build_title(
        self,
        occasion: str,
        gender: str,
        occasion_key: Optional[str],
        price_range: Optional[str],
    ) -> str:
        if occasion_key == "birthday_gift":
            base = "🎂 Đây là những đôi giày tuyệt vời làm quà sinh nhật"
        elif occasion_key == "anniversary_gift":
            base = "💝 Đây là những đôi giày lãng mạn làm quà kỷ niệm ngày yêu"
        elif occasion_key == "valentine":
            base = "💕 Đây là những đôi giày lãng mạn cho ngày Valentine"
        else:
            base = f"🎁 Đây là những đôi giàng tuyệt đẹp làm quà tặng"

        if gender and gender != "cả nam và nữ":
            base += f" cho {gender}"

        if price_range and price_range != "1-3tr":
            base += f" tầm giá {price_range}"

        base += " mình tìm được cho bạn!"
        return base
