"""Gift recommendation form validation action."""
from typing import Any, Dict, Text

from rasa_sdk import FormValidationAction, Tracker
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.types import DomainDict

from .utils import _clean_search_query, _parse_size


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
            dispatcher.utter_message(text="Bạn cho mình biết dịp tặng quà là gì nhé? Ví dụ: sinh nhật, kỷ niệm, Valentine...")
            return {"gift_occasion": None}

        v_lower = value.lower()
        if any(w in v_lower for w in ["không biết", "chưa rõ", "chưa nghĩ ra", "tư vấn", "gợi ý", "gì cũng được", "dịp nào cũng được"]):
            dispatcher.utter_message(text="Không sao! Mình sẽ gợi ý giày phù hợp cho nhiều dịp đặc biệt. Bạn cứ cho mình biết dịp nào bạn muốn tặng nhất nhé, hoặc bỏ qua cũng được.")
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
            dispatcher.utter_message(text="Bạn cho mình biết người nhận quà là nam hay nữ nhé?")
            return {"recipient_gender": None}

        v_lower = value.lower()

        if any(w in v_lower for w in ["nam", "bạn trai", "boyfriend", "anh ấy", "男朋友", "trai", "nam giới", "con trai"]):
            return {"recipient_gender": "nam"}
        elif any(w in v_lower for w in ["nữ", "bạn gái", "girlfriend", "cô ấy", "女朋友", "gái", "nữ giới", "con gái", "em gái", "em"]):
            return {"recipient_gender": "nữ"}

        if any(w in v_lower for w in ["không biết", "chưa rõ", "tư vấn", "gợi ý", "nam nữ", "dại gì"]):
            dispatcher.utter_message(text="Không sao cả! Mình sẽ gợi ý giày phù hợp cho cả nam và nữ nhé.")
            return {"recipient_gender": "khong_biet"}

        dispatcher.utter_message(text="Bạn cho mình biết người nhận quà là nam hay nữ vậy? (hoặc bạn có thể bỏ qua nếu không biết)")
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
                dispatcher.utter_message(text="Không sao! Mình sẽ gợi ý giày theo size phổ biến nhất cho bạn nhé.")
                return {"gift_shoe_size": "pho_bien"}
            dispatcher.utter_message(text="Bạn cho mình xin size giày dạng số nhé (ví dụ: 38, 39, 40, 41, 42). Nếu không biết, cứ nói mình sẽ tư vấn!")
            return {"gift_shoe_size": None}
        return {"gift_shoe_size": parsed}

    def validate_gift_price_range(
        self,
        value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        v_lower = value.lower() if value else ""
        if any(w in v_lower for w in ["không biết", "sao cũng được", "tư vấn", "gợi ý", "bỏ qua", "skip", "ngẫu nhiên"]):
            return {"gift_price_range": "1-3tr"}

        return {"gift_price_range": value.strip() if value else value}
