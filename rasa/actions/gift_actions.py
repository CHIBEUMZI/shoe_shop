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
    "bố": "nam",
    "cha": "nam",
    "ba": "nam",
    "nữ": "nữ",
    "bạn gái": "nữ",
    "girlfriend": "nữ",
    "cô ấy": "nữ",
    "mẹ": "nữ",
    "má": "nữ",
    "bạn": "khong_biet",
    "bạn bè": "khong_biet",
    "bạn thân": "khong_biet",
}

GIFT_RECIPIENT_PROFILES = {
    "bố": {
        "terms": ["bố", "nam", "công sở", "lịch sự", "derby", "oxford", "chelsea boots", "đen", "nâu"],
        "styles": ["lịch sự", "formal", "công sở", "phỏng vấn", "smart casual"],
        "preferred_products": ["sereno-brogues-oxford-of37", "classy-chelsea-boots-bo14", "giay-da-derby-nam-e-chunky-gnta51-5103-d", "sir-classic-oxford-of34"],
        "blocked_keywords": ["nữ", "pink", "hồng", "mẹ", "bạn gái", "nữ tính"],
    },
    "mẹ": {
        "terms": ["mẹ", "nữ", "nữ tính", "thanh lịch", "nhẹ nhàng", "sneaker nữ", "trắng", "hồng", "be"],
        "styles": ["nữ tính", "thanh lịch", "nhẹ nhàng", "dễ phối", "casual"],
        "preferred_products": ["giay-puma-skye-clean-pink", "giay-nike-air-force-1-shadow-infinite-lilac", "giay-nike-air-force-1-07-m-all-white-cw2288-111", "giay-nike-air-force-1-07-white-gum"],
        "blocked_keywords": ["nam", "derby", "oxford", "chelsea boots", "bố", "công sở nam"],
    },
    "bạn bè": {
        "terms": ["bạn bè", "trẻ trung", "dễ phối", "casual", "sneaker", "basic"],
        "styles": ["casual", "dễ phối đồ", "basic", "thời trang"],
        "preferred_products": ["giay-nike-air-force-1-07-m-all-white-cw2288-111", "giay-puma-army-trainer-white-black", "giay-puma-rs-x-the-unity-collection", "giay-new-balance-530-retro-running-navy"],
        "blocked_keywords": [],
    },
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
    recipient_label: Optional[str] = None,
    size: Optional[str] = None,
    price_range: Optional[str] = None,
    limit: int = 6,
) -> List[dict]:
    api = os.getenv("SHOP_API_BASE_URL", "http://nginx").rstrip("/")

    min_vnd, max_vnd = _parse_budget_text_to_range(price_range)

    occasion_key = _infer_gift_occasion(occasion) if occasion else None
    profile = GIFT_RECIPIENT_PROFILES.get(recipient_label or "", {})
    recipient_search_terms = profile.get("terms", [])
    blocked_keywords = profile.get("blocked_keywords", [])
    preferred_products = profile.get("preferred_products", [])

    def _build_params(
        include_occasion: bool = True,
        include_gender: bool = True,
        include_size: bool = True,
        include_price: bool = True,
    ) -> Dict[str, Any]:
        params: Dict[str, Any] = {"per_page": 12, "sort": "popular"}

        if include_gender and recipient_search_terms:
            params["search"] = recipient_search_terms[0]
        elif include_gender and gender and gender != "khong_biet":
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

    def _blob(p: dict) -> str:
        parts = [
            str(p.get("name") or ""),
            str(p.get("slug") or ""),
            str(p.get("short_description") or ""),
            str(p.get("description") or ""),
        ]
        for v in p.get("variants") or []:
            if isinstance(v, dict):
                parts.extend([str(v.get("name") or ""), str(v.get("color") or ""), str(v.get("size") or "")])
        return " ".join(parts).lower()

    def _score(p: dict) -> int:
        blob = _blob(p)
        slug = str(p.get("slug") or "").lower()
        score = 0

        for kw in recipient_search_terms:
            if kw.lower() in blob:
                score += 12

        for kw in preferred_products:
            if kw.lower() == slug:
                score += 50

        for kw in blocked_keywords:
            if kw.lower() in blob:
                score -= 40

        if occasion_key == "birthday_gift":
            score += 2 if any(k in blob for k in ["sinh nhật", "birthday", "quà tặng", "gift"]) else 0
        if gender == "nam" and any(k in blob for k in ["nam", "derby", "oxford", "chelsea", "công sở"]):
            score += 4
        if gender == "nữ" and any(k in blob for k in ["nữ", "pink", "hồng", "thanh lịch", "nữ tính", "be"]):
            score += 4
        price_val = p.get("base_sale_price") or p.get("base_price")
        try:
            price_int = int(price_val) if price_val is not None else None
        except Exception:
            price_int = None
        if price_int is not None:
            if min_vnd is not None and price_int >= min_vnd:
                score += 2
            if max_vnd is not None and price_int <= max_vnd:
                score += 2
        score += int(p.get("views") or 0) // 20
        return score

    # Strategy: Progressive relaxation of filters
    # Start strict, then gradually loosen constraints

    # Try 1: Full search with all filters
    params1 = _build_params(include_occasion=True, include_gender=True, include_size=True, include_price=True)
    items = _fetch(params1)
    if items:
        items = sorted(items, key=_score, reverse=True)
        return items

    # Football gifts often come through the gift flow with a generic occasion
    # such as sinh nhật / quà tặng. Add a sports-specific attempt so football
    # shoes are not missed when the user already gave size + budget.
    if occasion_key in {"birthday_gift", "anniversary_gift", "gift", "casual"} and (
        (gender == "nam") or (recipient_label in {"bố", "bạn bè", "nam"})
    ):
        football_terms = ["giày đá bóng", "đá bóng", "bóng đá", "football", "soccer", "tf", "fg", "sg"]
        for term in football_terms:
            params_football = _build_params(include_occasion=False, include_gender=False, include_size=True, include_price=True)
            params_football["search"] = term
            items = _fetch(params_football)
            if items:
                return sorted(items, key=_score, reverse=True)

    if recipient_search_terms:
        for term in recipient_search_terms[:4]:
            params_recipient = _build_params(include_occasion=True, include_gender=False, include_size=True, include_price=True)
            params_recipient["search"] = term
            items = _fetch(params_recipient)
            if items:
                return sorted(items, key=_score, reverse=True)

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
                return sorted(items, key=_score, reverse=True)

    # Try 3: Remove size filter - often the most restrictive
    if size and size != "pho_bien":
        params3 = _build_params(include_occasion=True, include_gender=True, include_size=False, include_price=True)
        items = _fetch(params3)
        if items:
            return sorted(items, key=_score, reverse=True)

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
        return sorted(items, key=_score, reverse=True)

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
        return sorted(items, key=_score, reverse=True)

    # Try 12: Get newest products
    params12 = {"per_page": 12, "sort": "latest"}
    items = _fetch(params12)
    if items:
        return sorted(items, key=_score, reverse=True)

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
        gift_target_group = tracker.get_slot("gift_target_group")
        gift_shoe_size = tracker.get_slot("gift_shoe_size")
        gift_price_range = tracker.get_slot("gift_price_range")
        intent_name = (tracker.latest_message.get("intent") or {}).get("name")

        occasion_mapped = _infer_gift_occasion(gift_occasion or "")
        occasion_display = gift_occasion or "đặc biệt"
        gender_display = "nam" if recipient_gender == "nam" else "nữ" if recipient_gender == "nữ" else "cả nam và nữ"
        recipient_label = self._build_recipient_label(intent_name, recipient_gender)
        if gift_target_group == "father":
            recipient_label = "bố"
            gender_display = "nam"
        elif gift_target_group == "mother":
            recipient_label = "mẹ"
            gender_display = "nữ"
        elif gift_target_group == "friend":
            recipient_label = "bạn bè"

        recipient_group = {
            "bố": "father",
            "mẹ": "mother",
            "bạn bè": "friend",
        }.get(recipient_label)

        items = _search_gift_shoes(
            occasion=gift_occasion,
            gender=recipient_gender,
            recipient_label=recipient_label,
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

        title = self._build_title(occasion_display, recipient_label, gender_display, occasion_mapped, gift_price_range)
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

    def _build_recipient_label(self, intent_name: Optional[str], recipient_gender: Optional[str]) -> str:
        if intent_name == "ask_gift_for_father":
            return "bố"
        if intent_name == "ask_gift_for_mother":
            return "mẹ"
        if intent_name == "ask_gift_for_friend":
            return "bạn bè"
        if recipient_gender == "nam":
            return "nam"
        if recipient_gender == "nữ":
            return "nữ"
        return "người nhận"

    def _build_title(
        self,
        occasion: str,
        recipient_label: str,
        gender: str,
        occasion_key: Optional[str],
        price_range: Optional[str],
    ) -> str:
        is_family_recipient = recipient_label in {"bố", "mẹ"}
        if recipient_label == "bố":
            base = "👔 Đây là những đôi giày lịch sự và thoải mái rất hợp để tặng bố"
        elif recipient_label == "mẹ":
            base = "🌷 Đây là những đôi giày êm ái và tinh tế rất hợp để tặng mẹ"
        elif recipient_label == "bạn bè":
            base = "✨ Đây là những đôi giày trẻ trung và dễ phối rất hợp để tặng bạn bè"
        elif occasion_key == "birthday_gift":
            base = "🎂 Đây là những đôi giày tuyệt vời làm quà sinh nhật"
        elif occasion_key == "anniversary_gift":
            base = "💝 Đây là những đôi giày lãng mạn làm quà kỷ niệm ngày yêu"
        elif occasion_key == "valentine":
            base = "💕 Đây là những đôi giày lãng mạn cho ngày Valentine"
        else:
            base = f"🎁 Đây là những đôi giày tuyệt đẹp làm quà tặng"

        if recipient_label == "bố" and "nam" not in base.lower():
            base += " cho nam"
        elif recipient_label == "mẹ" and "nữ" not in base.lower():
            base += " cho nữ"

        if is_family_recipient and "sinh nhật" not in base.lower():
            base = f"🎂 {base} cho dịp sinh nhật"

        if recipient_label not in {"người nhận", "bạn bè"} and recipient_label not in base:
            base += f" cho {recipient_label}"
        elif recipient_label == "bạn bè":
            base += " cho bạn bè"

        if gender and gender != "cả nam và nữ" and recipient_label == "người nhận":
            base += f" cho {gender}"

        if price_range and price_range != "1-3tr":
            base += f" tầm giá {price_range}"

        base += " mình tìm được cho bạn!"
        return base
