"""Gift recommendation form validation action."""
import re
from typing import Any, Dict, List, Text

from rasa_sdk import Action, FormValidationAction, Tracker
from rasa_sdk.events import EventType, FollowupAction, SlotSet
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.types import DomainDict

from .utils import _clean_search_query, _parse_size


class ActionGiftGoBack(Action):
    """Action to go back to previous step in gift form."""

    def name(self) -> Text:
        return "action_gift_go_back"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> List[EventType]:
        # Reset all gift form slots
        return [
            SlotSet("gift_occasion", None),
            SlotSet("recipient_gender", None),
            SlotSet("gift_shoe_size", None),
            SlotSet("gift_price_range", None),
            FollowupAction("gift_recommendation_form"),
        ]


class ValidateGiftRecommendationForm(FormValidationAction):

    def name(self) -> Text:
        return "validate_gift_recommendation_form"

    def validate_gift_occasion(
        self,
        value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        if not value or len(value.strip()) < 2:
            return {"gift_occasion": None}

        v_lower = value.lower()
        if any(w in v_lower for w in ["không biết", "chưa rõ", "chưa nghĩ ra", "tư vấn", "gợi ý", "gì cũng được", "dịp nào cũng được"]):
            return {"gift_occasion": "sinhnhat"}

        cleaned = _clean_search_query(value) or value.strip()
        return {"gift_occasion": cleaned}

    def validate_recipient_gender(
        self,
        value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        if not value or len(value.strip()) < 1:
            return {"recipient_gender": None}

        v_lower = value.lower()

        if any(w in v_lower for w in ["nam", "bạn trai", "boyfriend", "anh ấy", "男朋友", "trai", "nam giới", "con trai"]):
            return {"recipient_gender": "nam"}
        elif any(w in v_lower for w in ["nữ", "bạn gái", "girlfriend", "cô ấy", "女朋友", "gái", "nữ giới", "con gái", "em gái", "em"]):
            return {"recipient_gender": "nữ"}

        if any(w in v_lower for w in ["không biết", "chưa rõ", "tư vấn", "gợi ý", "nam nữ", "dại gì"]):
            return {"recipient_gender": "khong_biet"}

        return {"recipient_gender": None}

    def validate_gift_shoe_size(
        self,
        value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        parsed = _parse_size(value)
        if not parsed:
            v_lower = value.lower()
            if any(w in v_lower for w in ["không biết", "chưa rõ", "tư vấn", "gợi ý", "không rõ", "bỏ qua", "skip"]):
                # Hiển thị guide nhưng KHÔNG chấp nhận - form sẽ chờ user nhập size
                dispatcher.utter_message(
                    text="📏 **Hướng dẫn chọn SIZE giày chuẩn:**\n\n"
                    "**Cách đo:**\n"
                    "1. Đặt chân lên giấy A4, đánh dấu mũi chân và gót\n"
                    "2. Đo khoảng cách (cm)\n\n"
                    "**Bảng size:**\n"
                    "- Size 35-37: 22.5-23.5 cm\n"
                    "- Size 38-40: 24.0-25.0 cm\n"
                    "- Size 41-43: 25.5-26.5 cm\n"
                    "- Size 44-46: 27.0-28.0 cm\n\n"
                    "💡 Nên đo cuối ngày, chân sẽ hơi phồng.\n\n"
                    "Bạn ước lượng size của người nhận nhé (vd: size 38, 39, 40...):"
                )
                # Trả về None để form KHÔNG nhảy qua - chờ user nhập size
                return {"gift_shoe_size": None}
            return {"gift_shoe_size": None}
        return {"gift_shoe_size": parsed}

    def validate_gift_price_range(
        self,
        value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        v_lower = value.lower().strip() if value else ""

        # Keywords that mean "any/skip/default"
        skip_keywords = ["không biết", "sao cũng được", "tư vấn", "gợi ý", "bỏ qua", "skip", "ngẫu nhiên", "không rõ", "chưa biết", "gì cũng được"]
        if any(w in v_lower for w in skip_keywords):
            return {"gift_price_range": "1-3tr"}

        # If user already typed a budget expression, clean and keep it
        # Common patterns: "dưới 1 triệu", "1-2 triệu", "2-3 triệu", "tầm 1tr", etc.
        if v_lower:
            # Map rough patterns to a normalized value
            budget_map = {
                "dưới 500k": "dưới 500k",
                "dưới 500": "dưới 500k",
                "dưới 1 triệu": "dưới 1 triệu",
                "dưới 1tr": "dưới 1 triệu",
                "dưới 1": "dưới 1 triệu",
                "1-2 triệu": "1-2 triệu",
                "1-2tr": "1-2 triệu",
                "1 đến 2 triệu": "1-2 triệu",
                "1 đến 2": "1-2 triệu",
                "2-3 triệu": "2-3 triệu",
                "2-3tr": "2-3 triệu",
                "2 đến 3 triệu": "2-3 triệu",
                "2 đến 3": "2-3 triệu",
                "trên 3 triệu": "trên 3 triệu",
                "trên 3tr": "trên 3 triệu",
                "trên 3": "trên 3 triệu",
                "tầm 1 triệu": "tầm 1 triệu",
                "tầm 1tr": "tầm 1 triệu",
                "tầm 2 triệu": "tầm 2 triệu",
                "tầm 2tr": "tầm 2 triệu",
                "tầm 3 triệu": "tầm 3 triệu",
                "tầm 3tr": "tầm 3 triệu",
            }

            # Check exact match first
            if v_lower in budget_map:
                return {"gift_price_range": budget_map[v_lower]}

            # Fuzzy match: check if any key is contained in the input
            for pattern, normalized in budget_map.items():
                if pattern in v_lower or v_lower in pattern:
                    return {"gift_price_range": normalized}

            # If input looks like a budget expression (contains number + k/tr/triệu/đ)
            if re.search(r'\d+\s*(k|tr|triệu|đ|nghìn)', v_lower):
                return {"gift_price_range": value.strip()}

        # Empty or unrecognized → use default
        return {"gift_price_range": "1-3tr"}
