"""Other utility actions (promo, comparison, guides)."""
from typing import Any, Dict, List, Text

from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher

from .api_client import _fetch_products, _product_to_card


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
            items = _fetch_products(search=None, size=None, price_range=None, limit=8)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(
                text="Hiện tại chưa có chương trình khuyến mãi đặc biệt nào 😢 Bạn thử quay lại sau nhé, hoặc xem các sản phẩm bình thường?"
            )
            return []

        sale_items = [p for p in items if p.get("base_sale_price") and p.get("base_price")]

        if sale_items:
            dispatcher.utter_message(
                json_message={
                    "type": "products",
                    "title": "🏷️ Đây là các sản phẩm đang được GIẢM GIÁ tại BMC Shoes:",
                    "items": [_product_to_card(p) for p in sale_items[:6]],
                }
            )
            dispatcher.utter_message(
                text="Bạn có mã voucher? Mình có thể hướng dẫn bạn nhập mã voucher khi thanh toán. Hoặc bạn muốn lọc theo size/brand nào không?"
            )
        else:
            dispatcher.utter_message(
                text="Hiện tại chưa có sản phẩm giảm giá 😢 Nhưng shop thường xuyên có khuyến mãi, bạn nhớ theo dõi website nhé! Bạn cần tư vấn giày nào không?"
            )
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

        guide_text = """📏 **Hướng dẫn chọn SIZE giày chuẩn:**

**Cách đo:**
1. Chuẩn bị 1 tờ giấy A4 đặt trên sàn phẳng
2. Đặt chân lên giấy, cân bằng trọng lượng
3. Dùng bút đánh dấu điểm đầu mũi chân dài nhất và điểm gót chân
4. Đo khoảng cách giữa 2 điểm đó (cm)

**Bảng size BMC Shoes:**
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

💡 **Mẹo:** Nên đo vào cuối ngày vì chân sẽ hơi phồng. Nếu chân rộng hơn bình thường, nên chọn size lớn hơn 0.5.

Bạn muốn mình tìm giày theo size nào?"""

        dispatcher.utter_message(text=guide_text)
        return []


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

        if any(k in t for k in ["da", "leather", "suede", "nubuck"]):
            guide_text = """🧴 **Hướng dẫn bảo quản giày DA:**

1. **Lau sạch** sau mỗi lần sử dụng bằng khăn ẩm
2. **Sấy khô** tự nhiên, tránh phơi nắng gắt hoặc sấy lửa
3. **Sử dụng kem/sáp dưỡng da** chuyên dụng 1-2 lần/tuần
4. **Lưu trữ** trong hộp giày hoặc túi vải, có miếng lót giữ form
5. **Với da lộn (suede)**: dùng bàn chải chuyên dụng chải nhẹ theo chiều lông
6. **Chống ẩm** bằng xịt chống nước chuyên dụng cho da

Bạn cần tư vấn thêm sản phẩm chăm sóc giày nào không?"""
        elif any(k in t for k in ["vải", "canvas", "fabric", "vải"]):
            guide_text = """🧼 **Hướng dẫn vệ sinh giày VẢI/CANVAS:**

1. **Giặt tay** bằng nước ấm + xà phòng nhẹ, tránh giặt máy
2. **Chải nhẹ** bằng bàn chải mềm các vết bẩn
3. **Xả sạch** và **để khô tự nhiên** trong bóng râm (tránh nắng gắt)
4. **Có thể** cho giấy báo vào trong giày để giữ form khi phơi
5. **Không ngâm** nước quá lâu sẽ làm hỏng keo dán
6. **Lưu trữ** nơi khô ráo, thoáng mát

Bạn cần tư vấn thêm sản phẩm nào không?"""
        elif any(k in t for k in ["giày thể thao", "running", "chạy bộ", "đá bóng"]):
            guide_text = """🏃 **Hướng dẫn bảo quản giày THỂ THAO:**

1. **Vệ sinh sau mỗi buổi tập**: Lau sạch bùn, cỏ, mồ hôi
2. **Tháo laces & insole** trước khi vệ sinh
3. **Để khô** hoàn toàn trước khi cất, tránh ẩm mốc
4. **Thay laces** định kỳ nếu đã cũ hoặc giãn
5. **Tránh giặt máy** sẽ làm hỏng đệm và form giày
6. **Sử dụng** 2-3 đôi luân phiên để kéo dài tuổi thọ giày
7. **Thay giày** sau 500-800km chạy bộ để đảm bảo đệm tốt

Bạn muốn tìm thêm giày thể thao nào không?"""
        else:
            guide_text = """🧴 **Hướng dẫn bảo quản giày CHUNG:**

1. **Lau sạch** giày sau mỗi lần sử dụng
2. **Để khô** tự nhiên, tránh phơi nắng gắt hoặc sấy lửa
3. **Lưu trữ** trong hộp giày hoặc túi vải
4. **Sử dụng** miếng lót giày để giữ form và hút ẩm
5. **Luân phiên** nhiều đôi để giày có thời gian "nghỉ"
6. **Kiểm tra** đế giày định kỳ, thay khi mòn

Bạn cần tư vấn thêm sản phẩm nào không?"""

        dispatcher.utter_message(text=guide_text)
        return []
