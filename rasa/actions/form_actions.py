"""Form validation actions."""
from typing import Any, Dict, Text

from rasa_sdk import FormValidationAction, Tracker
from rasa_sdk.events import SlotSet
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.types import DomainDict

from .utils import _clean_search_query, _parse_size


class ValidateShoeRecommendationForm(FormValidationAction):

    def name(self) -> Text:
        return "validate_shoe_recommendation_form"

    def validate_purpose(
        self,
        value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        if not value or len(value.strip()) < 2:
            dispatcher.utter_message(text="Bạn nói rõ hơn giúp mình bạn cần giày để làm gì nhé.")
            return {"purpose": None}

        v_lower = value.lower()
        if any(w in v_lower for w in ["không biết", "chưa rõ", "chưa nghĩ ra", "tư vấn", "gợi ý"]):
            dispatcher.utter_message(text="Để mình gợi ý nhé! Bạn dự định mua giày để đi làm, đi học, chơi thể thao hay đi dạo phố?")
            return {"purpose": None}

        cleaned = _clean_search_query(value) or value.strip()
        return {"purpose": cleaned}

    def validate_shoe_size(
        self,
        value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        parsed = _parse_size(value)
        if not parsed:
            v_lower = value.lower()
            if any(w in v_lower for w in ["không biết", "chưa rõ", "tư vấn"]):
                dispatcher.utter_message(text="Bạn có thể áng chừng size (ví dụ khoảng 39, 40) hoặc tự đo chiều dài bàn chân báo mình để chọn chuẩn nhé!")
                return {"shoe_size": None}
            dispatcher.utter_message(text="Bạn cho mình xin size dạng số nhé (ví dụ: 38, 39, 40, 41, 42).")
            return {"shoe_size": None}
        return {"shoe_size": parsed}

    def validate_price_range(
        self,
        value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        v_lower = value.lower() if value else ""
        if any(w in v_lower for w in ["không biết", "sao cũng được", "tư vấn"]):
            return {"price_range": "không giới hạn"}

        return {"price_range": value.strip() if value else value}
