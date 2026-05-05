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

        if len(v_lower.split()) <= 3 and any(k in v_lower for k in ["đá bóng", "chạy bộ", "đi làm", "đi học", "đi chơi", "du lịch", "gym"]):
            return {"purpose": value.strip()}

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
            v_lower = value.lower() if value else ""
            if any(w in v_lower for w in ["không biết", "chưa rõ", "không rõ", "không biết size", "tư vấn", "hướng dẫn", "cách chọn", "đo chân", "chọn size"]):
                dispatcher.utter_message(
                    text="Không sao, mình hướng dẫn nhanh nhé: hãy đo chiều dài bàn chân bằng cm rồi gửi cho mình. Ví dụ 24.5cm thường tương ứng size 39; 25cm thường khoảng size 40; 25.5cm thường khoảng size 41."
                )
                return {"shoe_size": None}
            dispatcher.utter_message(text="Bạn cho mình xin size dạng số nhé (ví dụ: 38, 39, 40, 41, 42). Nếu chưa biết size, bạn chỉ cần gửi chiều dài bàn chân theo cm.")
            return {"shoe_size": None}

        # If the user sends a cm measurement, keep the parsed EU size.
        if value and "cm" in value.lower():
            dispatcher.utter_message(text=f"Mình quy đổi chiều dài chân của bạn sang size khoảng {parsed}. Mình sẽ tìm theo size này nhé.")

        return {"shoe_size": parsed}

    def validate_price_range(
        self,
        value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        from .utils import _parse_budget_text_to_range

        v_lower = value.lower() if value else ""
        if any(w in v_lower for w in ["không biết", "sao cũng được", "tư vấn"]):
            return {"price_range": "không giới hạn"}

        min_vnd, max_vnd = _parse_budget_text_to_range(value)
        if min_vnd is not None and max_vnd is not None:
            return {"price_range": f"{min_vnd}-{max_vnd}"}
        if min_vnd is not None:
            return {"price_range": f">={min_vnd}"}
        if max_vnd is not None:
            return {"price_range": f"<={max_vnd}"}

        dispatcher.utter_message(text="Bạn gửi giúp mình tầm giá rõ hơn nhé, ví dụ: dưới 1 triệu, 1-2 triệu, hoặc 2-3 triệu.")
        return {"price_range": None}
