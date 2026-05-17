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


class ActionStartGiftRecommendationForm(Action):
    """Send a tailored opening message before starting the gift form."""

    def name(self) -> Text:
        return "action_start_gift_recommendation_form"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> List[EventType]:
        intent_name = tracker.latest_message.get("intent", {}).get("name")

        if intent_name == "ask_gift_for_father":
            dispatcher.utter_message(text="Tất nhiên rồi, mình sẽ gợi ý vài đôi giày phù hợp để tặng bố. Mình hỏi bạn vài thông tin nhé!")
            group = "father"
        elif intent_name == "ask_gift_for_mother":
            dispatcher.utter_message(text="Tất nhiên rồi, mình sẽ gợi ý vài đôi giày phù hợp để tặng mẹ. Mình hỏi bạn vài thông tin nhé!")
            group = "mother"
        elif intent_name == "ask_gift_for_friend":
            dispatcher.utter_message(text="Được chứ, mình sẽ gợi ý vài đôi giày hợp để tặng bạn bè. Mình hỏi bạn vài thông tin nhé!")
            group = "friend"
        else:
            dispatcher.utter_message(text="Được rồi, mình sẽ gợi ý vài đôi giày làm quà thật phù hợp. Mình hỏi bạn vài thông tin nhé!")
            group = "general"

        return [
            SlotSet("gift_target_group", group),
            FollowupAction("gift_recommendation_form"),
        ]


class ActionAskGiftOccasion(Action):
    def name(self) -> Text:
        return "action_ask_gift_recommendation_form_gift_occasion"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: DomainDict) -> List[EventType]:
        group = tracker.get_slot("gift_target_group")
        if group == "father":
            text = "Bạn muốn tặng bố vào dịp nào vậy? Ví dụ: sinh nhật, Ngày của Cha, lễ Tết, kỷ niệm hay chỉ là một món quà bất ngờ?"
        elif group == "mother":
            text = "Bạn muốn tặng mẹ vào dịp nào vậy? Ví dụ: sinh nhật, 8/3, 20/10, lễ Tết, hay một dịp thật đặc biệt?"
        elif group == "friend":
            text = "Bạn muốn tặng bạn bè vào dịp nào vậy? Ví dụ: sinh nhật, tốt nghiệp, kỷ niệm, lễ Tết, hay một dịp vui nào đó?"
        else:
            text = "Bạn muốn tặng giày vào dịp nào vậy? Ví dụ: sinh nhật, kỷ niệm, Valentine hay một dịp đặc biệt nào khác?"
        dispatcher.utter_message(text=text)
        return []


class ActionAskGiftPriceRange(Action):
    def name(self) -> Text:
        return "action_ask_gift_recommendation_form_gift_price_range"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: DomainDict) -> List[EventType]:
        group = tracker.get_slot("gift_target_group")
        if group == "father":
            text = "Bạn muốn dành khoảng ngân sách bao nhiêu cho bố? Mình sẽ ưu tiên những đôi lịch sự, êm chân và dễ mang cả ngày."
        elif group == "mother":
            text = "Bạn muốn dành khoảng ngân sách bao nhiêu cho mẹ? Mình sẽ chọn những đôi nhẹ nhàng, tinh tế và dễ phối đồ nhé."
        elif group == "friend":
            text = "Bạn muốn dành khoảng ngân sách bao nhiêu cho bạn bè? Mình sẽ gợi ý những mẫu trẻ trung, hợp trend và dễ đi hằng ngày."
        else:
            text = "Bạn dự định ngân sách cho đôi giày là bao nhiêu? Ví dụ: dưới 1 triệu, 1-2 triệu, 2-3 triệu, hoặc trên 3 triệu?"
        dispatcher.utter_message(text=text)
        return []


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
        result: Dict[Text, Any] = {"gift_occasion": cleaned}

        group = tracker.get_slot("gift_target_group")
        if group == "father":
            result["recipient_gender"] = "nam"
        elif group == "mother":
            result["recipient_gender"] = "nữ"

        return result

    def validate_recipient_gender(
        self,
        value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        group = tracker.get_slot("gift_target_group")
        if group in {"father", "mother"}:
            return {"recipient_gender": "nam" if group == "father" else "nữ"}

        if not value or len(value.strip()) < 1:
            return {"recipient_gender": None}

        v_lower = value.lower()

        if any(w in v_lower for w in ["nam", "bạn trai", "boyfriend", "anh ấy", "男朋友", "trai", "nam giới", "con trai"]):
            return {"recipient_gender": "nam"}
        elif any(w in v_lower for w in ["nữ", "bạn gái", "girlfriend", "cô ấy", "女朋友", "gái", "nữ giới", "con gái", "em gái"]):
            return {"recipient_gender": "nữ"}

        if any(w in v_lower for w in ["bạn bè", "bạn thân", "bạn", "đồng nghiệp", "không biết", "chưa rõ", "tư vấn", "gợi ý", "nam nữ", "dại gì"]):
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
                    text="📏 Hướng dẫn chọn SIZE giày chuẩn:\n"
                    "Cách đo:\n"
                    "1. Chuẩn bị 1 tờ giấy A4 đặt trên sàn phẳng\n"
                    "2. Đặt chân lên giấy, cân bằng trọng lượng\n"
                    "3. Dùng bút đánh dấu điểm đầu mũi chân dài nhất và điểm gót chân\n"
                    "4. Đo khoảng cách giữa 2 điểm đó (cm)\n"
                    "Bảng size BMC Shoes:\n"
                    "- Size 35: 22.5 cm\n"
                    "- Size 36: 23.0 cm\n"
                    "- Size 37: 23.5 cm\n"
                    "- Size 38: 24.0 cm\n"
                    "- Size 39: 24.5 cm\n"
                    "- Size 40: 25.0 cm\n"
                    "- Size 41: 25.5 cm\n"
                    "- Size 42: 26.0 cm\n"
                    "- Size 43: 26.5 cm\n"
                    "- Size 44: 27.0 cm\n"
                    "- Size 45: 27.5 cm\n"
                    "- Size 46: 28.0 cm\n"
                    "💡 Mẹo: Nên đo vào cuối ngày vì chân sẽ hơi phồng. Nếu chân rộng hơn bình thường, nên chọn size lớn hơn 0.5.\n\n"
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
                "500k-1 triệu": "500k-1 triệu",
                "500k - 1 triệu": "500k-1 triệu",
                "500k-1tr": "500k-1 triệu",
                "500k - 1tr": "500k-1 triệu",
                "500k đến 1 triệu": "500k-1 triệu",
                "500k đến 1tr": "500k-1 triệu",
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
