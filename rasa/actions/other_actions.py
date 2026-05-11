"""Other utility actions (promo, comparison, guides)."""
from typing import Any, Dict, List, Text

from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher

from rasa_sdk.events import SlotSet

from .api_client import _fetch_products, _product_to_card
from .constants import _get_advice_for_purpose


SIZE_GUIDE_TEXT = """📏 Hướng dẫn chọn SIZE giày chuẩn
Cách đo:
1. Chuẩn bị 1 tờ giấy A4 đặt trên sàn phẳng
2. Đặt chân lên giấy, cân bằng trọng lượng
3. Dùng bút đánh dấu điểm đầu mũi chân dài nhất và điểm gót chân
4. Đo khoảng cách giữa 2 điểm đó (cm)
Bảng size BMC Shoes:
- Size 35: 22.5 cm
- Size 36: 23.0 cm
- Size 37: 23.5 cm
- Size 38: 24.0 cm
- Size 39: 24.5 cm
- Size 40: 25.0 cm
- Size 41: 25.5 cm
- Size 42: 26.0 cm
- Size 43: 26.5 cm
- Size 44: 27.0 cm
- Size 45: 27.5 cm
- Size 46: 28.0 cm
💡 Mẹo: Nên đo vào cuối ngày vì chân sẽ hơi phồng. Nếu chân rộng hơn bình thường, nên chọn size lớn hơn 0.5.

Bạn muốn mình tìm giày theo size nào?"""


def _size_guide_brief() -> str:
    return "Bạn có thể đo chiều dài bàn chân bằng cm, rồi đối chiếu bảng size của shop. Nếu bạn gửi mình số cm, mình sẽ gợi ý size gần đúng ngay."


class ActionSearchPromo(Action):

    def name(self) -> Text:
        return "action_search_promo"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        t = text.lower()

        promo_type = "sale"
        if any(k in t for k in ["flash", "flash sale", "deal", "sốc", "hot"]):
            promo_type = "flash_sale"
        elif any(k in t for k in ["voucher", "mã", "giảm"]):
            promo_type = "voucher"

        try:
            items = _fetch_products(search=None, size=None, price_range=None, limit=12)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(
                text="Hiện tại chưa có chương trình khuyến mãi đặc biệt nào 😢 Bạn thử quay lại sau nhé, hoặc xem các sản phẩm bình thường?"
            )
            return []

        sale_items = [p for p in items if p.get("has_sale")]
        best_items = sale_items or items
        title = "🏷️ Đây là các sản phẩm đang được GIẢM GIÁ tại BMC Shoes:" if sale_items else "🏷️ Đây là một số sản phẩm nổi bật bạn có thể tham khảo:" 

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": title,
                "items": [_product_to_card(p) for p in best_items[:6]],
            }
        )
        if promo_type == "voucher":
            dispatcher.utter_message(text="Nếu bạn muốn, mình có thể tiếp tục lọc các mẫu đang sale theo brand, size hoặc tầm giá.")
        return []


class ActionCompareProducts(Action):

    def name(self) -> Text:
        return "action_compare_products"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        t = text.lower()

        if any(k in t for k in ["nike", "adidas", "puma", "converse", "vans", "asics", "new balance", "reebok"]):
            dispatcher.utter_message(
                text="Mình chưa có màn hình so sánh trực tiếp ngay trong chat, nhưng mình có thể giúp bạn chọn nhanh theo nhu cầu: độ êm, độ bền, chạy bộ, đi làm hay thời trang. Bạn muốn so sánh theo tiêu chí nào?"
            )
            return []

        dispatcher.utter_message(
            text="Để so sánh sản phẩm, bạn có thể truy cập trang so sánh trên website của BMC Shoes. Tại đó, bạn có thể chọn tối đa 3-4 sản phẩm và so sánh chi tiết về giá, thông số kỹ thuật, đánh giá từ khách hàng."
        )
        dispatcher.utter_message(
            text="Bạn muốn mình tìm và so sánh cụ thể những mẫu nào? Hoặc mình gợi ý sản phẩm theo nhu cầu của bạn trước?"
        )
        return []


class ActionGuideSize(Action):

    def name(self) -> Text:
        return "action_guide_size"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:

        dispatcher.utter_message(text=SIZE_GUIDE_TEXT)
        return []


class ActionFAQ(Action):

    def name(self) -> Text:
        return "action_faq"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        t = text.lower()
        last_bot_message = (tracker.latest_bot_utterance or {}).get("text") or ""
        last_bot_lower = last_bot_message.lower()

        if any(k in t for k in ["size", "đo chân", "bàn chân", "cm", "chọn size", "hướng dẫn", "đo size", "cách chọn size"]):
            dispatcher.utter_message(text=_size_guide_brief())
            dispatcher.utter_message(text="Nếu bạn muốn, mình có thể hướng dẫn chi tiết cách đo chân để chọn size chuẩn hơn. Còn nếu bạn đang xem một đôi giày cụ thể, mình sẽ lọc size phù hợp cho đôi đó luôn.")
            return [SlotSet("clarify_expected", "size"), SlotSet("last_product_query", text), SlotSet("clarify_question", "Bạn đang quan tâm size đúng không?")]
        if any(k in t for k in ["sale", "giảm giá", "khuyến mãi", "voucher", "freeship"]):
            dispatcher.utter_message(text="Hiện tại mình có thể tìm các sản phẩm đang sale hoặc lọc theo tầm giá. Nếu bạn muốn, hãy gửi tên brand, loại giày hoặc mức giá bạn mong muốn.")
            return [SlotSet("clarify_expected", "price"), SlotSet("last_product_query", text), SlotSet("clarify_question", "Bạn đang quan tâm giá đúng không?")]
        if any(k in t for k in ["da lộn", "suede", "nubuck"]):
            dispatcher.utter_message(text="🧴 Hướng dẫn bảo quản giày DA LỘN / SUEDE:\n\n1. Không dùng nước trực tiếp để chà mạnh lên bề mặt\n2. Dùng bàn chải suede chải nhẹ theo một chiều\n3. Xử lý vết bẩn khô bằng gôm tẩy suede hoặc khăn khô mềm\n4. Xịt chống nước/chống bám bẩn trước khi sử dụng\n5. Phơi khô tự nhiên nếu giày bị ẩm, tránh nắng gắt và máy sấy\n6. Nhét shoe tree hoặc giấy để giữ form khi không dùng\n\nBạn cần tư vấn thêm sản phẩm chăm sóc giày nào không?")
            return [SlotSet("clarify_expected", "care"), SlotSet("last_product_query", text), SlotSet("clarify_question", "Bạn đang quan tâm cách bảo quản giày da lộn đúng không?")]
        if any(k in t for k in ["da", "vải", "canvas", "fabric", "bảo quản", "vệ sinh"]) or any(k in last_bot_lower for k in ["bảo quản giày da", "bảo quản giày vải", "vệ sinh giày vải", "vệ sinh giày da"]):
            if any(k in t for k in ["vải", "canvas", "fabric"]):
                dispatcher.utter_message(text="🧼 Hướng dẫn vệ sinh giày VẢI/CANVAS:\n\n1. Giặt tay bằng nước ấm + xà phòng nhẹ, tránh giặt máy\n2. Chải nhẹ bằng bàn chải mềm các vết bẩn\n3. Xả sạch và để khô tự nhiên trong bóng râm (tránh nắng gắt)\n4. Có thể cho giấy báo vào trong giày để giữ form khi phơi\n5. Không ngâm nước quá lâu sẽ làm hỏng keo dán\n6. Lưu trữ nơi khô ráo, thoáng mát\n\nBạn cần tư vấn thêm sản phẩm nào không?")
                return [SlotSet("clarify_expected", "care"), SlotSet("followup_topic", "care"), SlotSet("last_product_query", text), SlotSet("clarify_question", "Bạn đang quan tâm cách vệ sinh giày vải đúng không?")]
            dispatcher.utter_message(text="Mình có thể tư vấn cách vệ sinh và bảo quản theo từng chất liệu như da, vải canvas, da lộn hoặc giày thể thao. Bạn cứ nói rõ loại giày nhé.")
            return [SlotSet("clarify_expected", "care"), SlotSet("followup_topic", "care"), SlotSet("last_product_query", text), SlotSet("clarify_question", "Bạn đang quan tâm cách bảo quản hay cách vệ sinh của mẫu này?")]
        if any(k in t for k in ["nam", "nữ", "gender", "giới tính"]):
            dispatcher.utter_message(text="Mình có thể lọc giày theo nam, nữ hoặc unisex. Bạn chỉ cần nói rõ nhu cầu, mình sẽ gợi ý theo đúng nhóm phù hợp.")
            return [SlotSet("clarify_expected", "general"), SlotSet("followup_topic", "general"), SlotSet("last_product_query", text), SlotSet("clarify_question", "Bạn muốn lọc theo nam, nữ hay unisex?")]

        if any(k in last_bot_lower for k in ["bảo quản giày da", "vệ sinh giày vải", "đổi size", "đổi mẫu"]):
            if any(k in t for k in ["giày vải", "vải", "canvas"]):
                dispatcher.utter_message(text="🧼 Hướng dẫn vệ sinh giày VẢI/CANVAS:\n\n1. Giặt tay bằng nước ấm + xà phòng nhẹ, tránh giặt máy\n2. Chải nhẹ bằng bàn chải mềm các vết bẩn\n3. Xả sạch và để khô tự nhiên trong bóng râm\n4. Cho giấy báo vào trong giày để giữ form khi phơi\n5. Không ngâm nước quá lâu\n\nNếu bạn muốn, mình có thể tiếp tục hướng dẫn cách bảo quản giày da nữa.")
                return [SlotSet("clarify_expected", "care"), SlotSet("followup_topic", "care"), SlotSet("last_product_query", text), SlotSet("clarify_question", "Bạn muốn mình nói tiếp về giày da hay đổi size/đổi mẫu?")]
            if any(k in t for k in ["giày da", "da", "leather"]):
                dispatcher.utter_message(text="🧴 Hướng dẫn bảo quản giày DA:\n\n1. Lau sạch sau mỗi lần sử dụng bằng khăn ẩm\n2. Để khô tự nhiên, tránh nắng gắt và nhiệt cao\n3. Dùng kem/sáp dưỡng da chuyên dụng 1-2 lần/tuần\n4. Cất trong hộp giày hoặc túi vải, có miếng lót giữ form\n5. Có thể dùng xịt chống nước chuyên dụng cho da\n\nNếu bạn muốn, mình cũng có thể hướng dẫn luôn về đổi size/đổi mẫu.")
                return [SlotSet("clarify_expected", "care"), SlotSet("followup_topic", "care"), SlotSet("last_product_query", text), SlotSet("clarify_question", "Bạn muốn hỏi tiếp về giày vải hay đổi size/đổi mẫu?")]
            if any(k in t for k in ["đổi size", "đổi mẫu", "đổi trả", "trả hàng"]):
                dispatcher.utter_message(text="Shop đổi size/đổi mẫu miễn phí trong 7 ngày nếu sản phẩm còn nguyên tem mác và chưa qua sử dụng. Bạn muốn đổi size hay đổi mẫu ạ?")
                return [SlotSet("clarify_expected", "return"), SlotSet("followup_topic", "return"), SlotSet("last_product_query", text), SlotSet("clarify_question", "Bạn cần đổi size hay muốn đổi mẫu?")]

        dispatcher.utter_message(text="Mình có thể hỗ trợ tư vấn size, giá, sale, chất liệu, brand và mục đích sử dụng. Bạn hỏi tự nhiên như đang chat với nhân viên tư vấn nhé!")
        return [SlotSet("clarify_expected", "general"), SlotSet("followup_topic", "general"), SlotSet("last_product_query", text), SlotSet("clarify_question", "Bạn đang quan tâm size, giá, độ êm hay brand của mẫu này?")]


class ActionCareGuide(Action):

    def name(self) -> Text:
        return "action_care_guide"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        t = text.lower()

        if any(k in t for k in ["da lộn", "suede", "nubuck"]):
            guide_text = """🧴 Hướng dẫn bảo quản giày DA LỘN / SUEDE:
1. Không dùng nước trực tiếp để chà mạnh lên bề mặt
2. Dùng bàn chải suede chải nhẹ theo một chiều
3. Xử lý vết bẩn khô bằng gôm tẩy suede hoặc khăn khô mềm
4. Xịt chống nước/chống bám bẩn trước khi sử dụng
5. Phơi khô tự nhiên nếu giày bị ẩm, tránh nắng gắt và máy sấy
6. Nhét shoe tree hoặc giấy để giữ form khi không dùng

Bạn cần tư vấn thêm sản phẩm chăm sóc giày nào không?"""
        elif any(k in t for k in ["da", "leather"]):
            guide_text = """🧴 Hướng dẫn bảo quản giày DA:
1. Lau sạch sau mỗi lần sử dụng bằng khăn ẩm
2. Sấy khô tự nhiên, tránh phơi nắng gắt hoặc sấy lửa
3. Sử dụng kem/sáp dưỡng da chuyên dụng 1-2 lần/tuần
4. Lưu trữ trong hộp giày hoặc túi vải, có miếng lót giữ form
5. Chống ẩm bằng xịt chống nước chuyên dụng cho da

Bạn cần tư vấn thêm sản phẩm chăm sóc giày nào không?"""
        elif any(k in t for k in ["vải", "canvas", "fabric"]):
            guide_text = """🧼 Hướng dẫn vệ sinh giày VẢI/CANVAS:
1. Giặt tay bằng nước ấm + xà phòng nhẹ, tránh giặt máy
2. Chải nhẹ bằng bàn chải mềm các vết bẩn
3. Xả sạch và để khô tự nhiên trong bóng râm (tránh nắng gắt)
4. Có thể cho giấy báo vào trong giày để giữ form khi phơi
5. Không ngâm nước quá lâu sẽ làm hỏng keo dán
6. Lưu trữ nơi khô ráo, thoáng mát

Bạn cần tư vấn thêm sản phẩm nào không?"""
        elif any(k in t for k in ["giày thể thao", "running", "chạy bộ", "đá bóng"]):
            guide_text = """🏃 Hướng dẫn bảo quản giày THỂ THAO:
1. Vệ sinh sau mỗi buổi tập: Lau sạch bùn, cỏ, mồ hôi
2. Tháo laces & insole trước khi vệ sinh
3. Để khô hoàn toàn trước khi cất, tránh ẩm mốc
4. Thay laces định kỳ nếu đã cũ hoặc giãn
5. Tránh giặt máy sẽ làm hỏng đệm và form giày
6. Sử dụng 2-3 đôi luân phiên để kéo dài tuổi thọ giày
7. Thay giày sau 500-800km chạy bộ để đảm bảo đệm tốt

Bạn muốn tìm thêm giày thể thao nào không?"""
        else:
            guide_text = """🧴 Hướng dẫn bảo quản giày CHUNG:
1. Lau sạch giày sau mỗi lần sử dụng
2. Để khô tự nhiên, tránh phơi nắng gắt hoặc sấy lửa
3. Lưu trữ trong hộp giày hoặc túi vải
4. Sử dụng miếng lót giày để giữ form và hút ẩm
5. Luân phiên nhiều đôi để giày có thời gian "nghỉ"
6. Kiểm tra đế giày định kỳ, thay khi mòn

Bạn cần tư vấn thêm sản phẩm nào không?"""

        dispatcher.utter_message(text=guide_text)
        return []
