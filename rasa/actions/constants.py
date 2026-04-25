"""Constants and configuration for Rasa actions."""

OCCASION_SCENE_MAP = {
    "valentine": {
        "description": "💕 Lãng mạn cho ngày Valentine",
        "colors": ["đỏ", "hồng", "đen", "trắng", "tím"],
        "style_keywords": ["thời trang", "sang trọng", "lịch sự", "nữ tính", "lãng mạn", "đỏ", "hồng", "valentine", "tình nhân"],
        "price_range": "1-5m",
        "advice": "💕 Đây là những mẫu giày lãng mạn, thời trang - phù hợp cho ngày Valentine và các dịp đặc biệt!",
    },
    "birthday_gift": {
        "description": "🎂 Quà sinh nhật ý nghĩa",
        "colors": ["trắng", "đen", "hồng", "đỏ", "vàng"],
        "style_keywords": ["thời trang", "đẹp", "sang trọng", "nữ tính", "lịch sự", "năng động", "all white", "lifestyle"],
        "price_range": "1-3m",
        "advice": "🎂 Đây là những mẫu giày làm quà sinh nhật cực kỳ ý nghĩa! Mình gợi ý những mẫu đẹp, dễ phối đồ nhé!",
    },
    "anniversary_gift": {
        "description": "💝 Quà kỷ niệm ngày yêu",
        "colors": ["đỏ", "hồng", "trắng", "đen", "vàng"],
        "style_keywords": ["lãng mạn", "thời trang", "sang trọng", "nữ tính", "cặp đôi", "đỏ", "hồng"],
        "price_range": "1-4m",
        "advice": "💝 Đây là những mẫu giày lãng mạn, phù hợp cho dịp kỷ niệm ngày yêu! Chúc cặp đôi hạnh phúc!",
    },
    "gift": {
        "description": "🎁 Quà tặng ý nghĩa",
        "colors": ["trắng", "đen", "be", "nâu"],
        "style_keywords": ["thời trang", "đẹp", "sang trọng", "dễ phối đồ", "lịch sự", "all white", "clean"],
        "price_range": "1-3m",
        "advice": "🎁 Đây là những mẫu giày làm quà tặng cực kỳ ý nghĩa! Mình gợi ý những mẫu đẹp nhất nhé!",
    },
    "interview": {
        "description": "💼 Chuyên nghiệp cho phỏng vấn & công sở",
        "colors": ["đen", "nâu", "xám", "trắng"],
        "style_keywords": ["lịch sự", "formal", "công sở", "văn phòng", "chuyên nghiệp"],
        "price_range": "1-2m",
        "advice": "💼 Những mẫu giày thanh lịch, chuyên nghiệp - giúp bạn tự tin hơn trong buổi phỏng vấn!",
    },
    "casual": {
        "description": "🌟 Năng động cho dạo phố & đi chơi",
        "colors": ["trắng", "đen", "xám", "be", "nâu nhạt"],
        "style_keywords": ["casual", "thoải mái", "năng động", "trẻ trung", "dễ phối đồ"],
        "price_range": "lt1m",
        "advice": "🌟 Những mẫu giày casual, dễ phối đồ - hoàn hảo cho ngày nghỉ và cuối tuần!",
    },
    "travel": {
        "description": "✈️ Thoải mái cho du lịch & đi phượt",
        "colors": ["trắng", "đen", "be", "xám", "nâu"],
        "style_keywords": ["thoải mái", "nhẹ", "đi bộ", "du lịch", "phượt", " trekking"],
        "price_range": "1-2m",
        "advice": "✈️ Giày nhẹ, thoải mái, phù hợp đi bộ nhiều - lý tưởng cho chuyến đi xa!",
    },
    "party": {
        "description": "✨ Sang trọng cho party & tiệc",
        "colors": ["đen", "vàng", "bạc", "đỏ", "hồng"],
        "style_keywords": ["thời trang", "sang trọng", "nổi bật", "party", "club"],
        "price_range": "1-3m",
        "advice": "✨ Những mẫu giày thời trang, nổi bật - giúp bạn tỏa sáng trong các bữa tiệc!",
    },
    "football": {
        "description": "⚽ Chuyên dụng cho đá bóng",
        "colors": ["đen", "trắng", "xám", "đỏ", "xanh", "vàng"],
        "style_keywords": ["đá bóng", "bóng đá", "football", "sân cỏ", "fg", "tf", "sg", "mercurial", "predator", "copa"],
        "price_range": "1-5m",
        "advice": "⚽ Những mẫu giày đá bóng chuyên dụng - hỗ trợ tốt cho việc chơi bóng trên sân!",
    },
    "running": {
        "description": "🏃 Nhẹ nhàng cho chạy bộ",
        "colors": ["đen", "trắng", "xám", "đỏ", "xanh", "cam"],
        "style_keywords": ["chạy bộ", "running", "jogging", "marathon", "tập cardio"],
        "price_range": "1-4m",
        "advice": "🏃 Những mẫu giày chạy bộ nhẹ, đệm tốt - giúp bạn thoải mái trên mỗi bước chạy!",
    },
    "gym": {
        "description": "🏋️ Hỗ trợ tập gym & fitness",
        "colors": ["đen", "trắng", "xám", "đỏ"],
        "style_keywords": ["gym", "tập gym", "fitness", "crossfit", "training", "nâng tạ", "workout"],
        "price_range": "1-3m",
        "advice": "🏋️ Những mẫu giày gym có đế bám sàn tốt, hỗ trợ thăng bằng - phù hợp cho việc tập luyện!",
    },
    "sports": {
        "description": "⚽ Năng động cho thể thao & tập luyện",
        "colors": ["đen", "trắng", "xám", "đỏ", "xanh"],
        "style_keywords": ["thể thao", "sport", "tập luyện", "năng động"],
        "price_range": "1-3m",
        "advice": "⚽ Những mẫu giày thể thao đa dụng - phù hợp cho nhiều hoạt động thể chất!",
    },
}


def _get_advice_for_purpose(purpose: str) -> str:
    if not purpose:
        return "Mình gợi ý một số mẫu giày phù hợp với yêu cầu của bạn:"

    if "chạy bộ" in purpose or "chạy" in purpose or "running" in purpose:
        return "🏃 **Tư vấn:** Đối với giày chạy bộ, bạn nên ưu tiên các mẫu có trọng lượng nhẹ, đế đệm êm (như bọt xốp) giúp giảm chấn và phần thân giày thoáng khí tốt. Dưới đây là các gợi ý tốt nhất cho bạn:"
    elif "đá bóng" in purpose or "bóng đá" in purpose or "football" in purpose:
        return "⚽ **Tư vấn:** Với giày đá bóng, việc chọn loại đinh phù hợp với mặt sân (như đinh TF cho sân cỏ nhân tạo, đinh FG cho cỏ tự nhiên) và form giày ôm chân là rất quan trọng để có cảm giác bóng tốt. Mời bạn tham khảo:"
    elif "đi làm" in purpose or "công sở" in purpose or "văn phòng" in purpose or "phỏng vấn" in purpose:
        return "💼 **Tư vấn:** Giày đi làm cần sự thoải mái để mang cả ngày, cộng thêm thiết kế thanh lịch và êm ái. Đây là một số mẫu rất phù hợp để kết hợp với trang phục công sở:"
    elif "đi chơi" in purpose or "dạo phố" in purpose or "thời trang" in purpose or "sneaker" in purpose:
        return "🌟 **Tư vấn:** Giày đi chơi thì kiểu dáng thời trang, dễ phối đồ và mang lại sự thoải mái năng động là ưu tiên hàng đầu. Vài gợi ý chuẩn gu cho bạn nè:"
    elif "tập gym" in purpose or "thể thao" in purpose or "training" in purpose or "crossfit" in purpose:
        return "🏋️ **Tư vấn:** Để tập luyện trong phòng gym (nâng tạ, cardio...), một đôi giày có đế bằng, bám sàn tốt và giữ thăng bằng cao là lựa chọn tối ưu nhất. Bạn xem thử nhé:"
    elif "đi bộ" in purpose or "walking" in purpose:
        return "🚶 **Tư vấn:** Giày đi bộ hằng ngày cần có phần đế êm, độ uốn dẻo linh hoạt và hỗ trợ vòm chân tốt để đi lâu không mỏi. Mình tìm được các mẫu này cho bạn:"
    elif "du lịch" in purpose or "phượt" in purpose or "travel" in purpose or "trekking" in purpose:
        return "✈️ **Tư vấn:** Giày đi du lịch cần nhẹ, thoải mái, bám tốt và chịu được địa hình đa dạng. Mình gợi ý cho bạn những mẫu lý tưởng cho chuyến đi sắp tới:"
    elif "valentine" in purpose or "tặng" in purpose or "quà" in purpose or "người yêu" in purpose or "tình nhân" in purpose:
        return "💕 **Tư vấn:** Valentine là dịp đặc biệt để thể hiện tình yêu! Mình gợi ý những mẫu giày vừa lãng mạn vừa thời trang, phù hợp để tặng:"
    elif "tiệc" in purpose or "party" in purpose or "club" in purpose or "đêm" in purpose:
        return "✨ **Tư vấn:** Để tỏa sáng trong bữa tiệc, bạn nên chọn giày thời trang, nổi bật và phù hợp với trang phục. Dưới đây là gợi ý của mình:"
    elif "caffe" in purpose or "cafe" in purpose or "mall" in purpose or "mua sắm" in purpose:
        return "🛍️ **Tư vấn:** Đi cafe hay shopping cả ngày thì cần đôi giày thoải mái, dễ phối đồ và không gây mỏi chân. Mình có vài gợi ý cho bạn:"
    elif "yoga" in purpose or "dancing" in purpose or "khiêu vũ" in purpose or "sen" in purpose:
        return "🧘 **Tư vấn:** Giày tập yoga/dancing cần đế mỏng, êm và có độ bám tốt để cảm nhận từng chuyển động. Mình tìm được mẫu phù hợp cho bạn:"

    return f"💡 **Tư vấn:** Dựa trên nhu cầu '{purpose}' của bạn, mình đã tìm thấy các mẫu giày thích hợp sau:"
