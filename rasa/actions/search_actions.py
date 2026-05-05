"""Search-related actions."""
import os
from typing import Any, Dict, List, Optional, Text

from rasa_sdk import Action, Tracker
from rasa_sdk.events import SlotSet
from rasa_sdk.executor import CollectingDispatcher

from .api_client import (
    _fetch_products,
    _fetch_products_by_occasion,
    _fetch_products_with_attributes,
    _fetch_facets,
    _infer_brand_from_text,
    _infer_category_ids_from_text,
    _infer_occasion_from_text,
    _product_to_card,
    _shop_products_list_url,
)
from .constants import OCCASION_SCENE_MAP, _get_advice_for_purpose
from .utils import _clean_search_query, _get_entity, _infer_color_from_text, _parse_size, _parse_budget_text_to_range


_SHOE_STYLE_TERMS = {
    "màu": ["màu", "color", "trắng", "đen", "nâu", "xanh", "đỏ", "hồng", "be", "xám", "vàng", "xanh navy"],
    "material": ["da", "da lộn", "suede", "vải", "canvas", "mesh", "lưới", "nỉ", "cao su"],
    "style": ["basic", "tối giản", "retro", "streetwear", "sporty", "formal", "casual", "trendy", "classic", "năng động"],
}


def _normalize_text(value: Optional[str]) -> str:
    return (value or "").strip().lower()


def _match_any(text: str, keywords: List[str]) -> bool:
    return any(k in text for k in keywords)


class ActionSuggestShoes(Action):

    def name(self) -> Text:
        return "action_suggest_shoes"

    def run(
        self, dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:

        purpose = tracker.get_slot("purpose")
        size = tracker.get_slot("shoe_size")
        price = tracker.get_slot("price_range")

        occasion = _infer_occasion_from_text(purpose)
        data = []
        fallback_called = False

        color = tracker.get_slot("color")
        material = tracker.get_slot("material")
        style = tracker.get_slot("style")

        try:
            if occasion:
                data = _fetch_products_by_occasion(occasion, limit=5, price_range=price)
            else:
                cat_ids = _infer_category_ids_from_text(purpose)
                search_query = None if cat_ids else purpose
                data = _fetch_products_with_attributes(
                    search=search_query,
                    size=size,
                    price_range=price,
                    category_ids=cat_ids or None,
                    color=color,
                    material=material,
                    style=style,
                    limit=5,
                )
        except Exception:
            data = []

        if not data:
            from .api_client import _fallback_near_match
            data = _fallback_near_match(
                search=purpose,
                size=size,
                price_range=price,
                color=color,
                material=material,
                style=style,
                limit=5,
            )

        if not data:
            dispatcher.utter_message(
                text="Mình chưa tìm được sản phẩm phù hợp với yêu cầu của bạn 😢 Bạn thử điều chỉnh tầm giá hoặc mô tả nhu cầu cụ thể hơn nhé."
            )
            return [SlotSet("purpose", None), SlotSet("shoe_size", None), SlotSet("price_range", None)]

        if occasion and not fallback_called:
            scene = OCCASION_SCENE_MAP.get(occasion, {})
            advice_text = scene.get("advice", _get_advice_for_purpose(purpose))
        else:
            advice_text = _get_advice_for_purpose(purpose)
            if not purpose and (tracker.latest_message or {}).get("text"):
                advice_text = "⚽ **Tư vấn:** Với giày đá bóng, việc chọn loại đinh phù hợp với mặt sân (như đinh TF cho sân cỏ nhân tạo, đinh FG cho cỏ tự nhiên) và form giày ôm chân là rất quan trọng. Mời bạn tham khảo:"

        if color or material or style:
            extras = []
            if color:
                extras.append(f"màu {color}")
            if material:
                extras.append(f"chất liệu {material}")
            if style:
                extras.append(f"style {style}")
            advice_text = f"{advice_text} (lọc theo {' / '.join(extras)})"

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": advice_text,
                "items": [_product_to_card(p) for p in data[:5]],
            }
        )
        dispatcher.utter_message(text="Bạn có muốn lọc thêm theo thương hiệu, size, hoặc thay đổi tầm giá không? Mình sẵn sàng hỗ trợ bạn!")

        return [SlotSet("purpose", None), SlotSet("shoe_size", None), SlotSet("price_range", None)]


class ActionSearchProducts(Action):

    def name(self) -> Text:
        return "action_search_products"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        entities = (tracker.latest_message or {}).get("entities") or []

        ent_brand = _get_entity(entities, "brand")
        ent_size = _get_entity(entities, "shoe_size")
        ent_purpose = _get_entity(entities, "purpose")
        ent_price_range = _get_entity(entities, "price_range")
        ent_color = _get_entity(entities, "color") or tracker.get_slot("color")
        ent_material = _get_entity(entities, "material") or tracker.get_slot("material")
        ent_style = _get_entity(entities, "style") or tracker.get_slot("style")

        size = ent_size or _parse_size(text)
        price_range = ent_price_range or text
        parsed_price = _parse_budget_text_to_range(price_range)

        brand = ent_brand or _infer_brand_from_text(text)
        inferred_color = ent_color or _infer_color_from_text(text)
        search = brand or _clean_search_query(text)
        cat_ids = _infer_category_ids_from_text(ent_purpose or text)

        if ent_purpose:
            search = None

        occasion = _infer_occasion_from_text(text)
        items = []

        # Prefer explicit price range if the text actually looks like budget input.
        has_price_text = parsed_price != (None, None)
        if has_price_text and not brand and not ent_purpose:
            # When the user is mainly giving a budget, avoid treating the full sentence
            # as a keyword search because it can drown out the actual price filter.
            search = None

        try:
            if occasion:
                items = _fetch_products_by_occasion(occasion, limit=5, price_range=price_range)
            else:
                items = _fetch_products_with_attributes(
                    search=search,
                    size=size,
                    price_range=price_range if has_price_text else None,
                    category_ids=cat_ids or None,
                    color=inferred_color,
                    material=ent_material,
                    style=ent_style,
                    limit=5,
                )
        except Exception:
            items = []

        if not items:
            from .api_client import _fallback_near_match
            items = _fallback_near_match(
                search=search,
                size=size,
                price_range=price_range if has_price_text else None,
                category_ids=cat_ids or None,
                color=inferred_color,
                material=ent_material,
                style=ent_style,
                limit=5,
            )

        if not items and not occasion and parsed_price == (None, None) and size:
            try:
                items = _fetch_products(search=search, size=None, price_range=None, category_ids=cat_ids or None, limit=5)
            except Exception:
                items = []

        if not items and brand and inferred_color:
            try:
                items = _fetch_products_with_attributes(
                    search=brand,
                    size=size,
                    price_range=price_range if has_price_text else None,
                    category_ids=cat_ids or None,
                    color=inferred_color,
                    material=ent_material,
                    style=ent_style,
                    limit=5,
                )
            except Exception:
                items = []

        if not items:
            if brand:
                try:
                    facets = _fetch_facets()
                    available = facets.get("brands") or []
                except Exception:
                    available = []

                if available and all(str(b.get("name") or "").strip().lower() != brand.strip().lower() for b in available):
                    try:
                        similar = _fetch_products_with_attributes(
                            search=None,
                            size=size,
                            price_range=price_range,
                            category_ids=cat_ids or None,
                            color=inferred_color,
                            material=ent_material,
                            style=ent_style,
                            limit=5,
                        )
                    except Exception:
                        similar = []

                    dispatcher.utter_message(text=f"Hiện shop chưa có sản phẩm brand \"{brand}\" 😢")

                    if similar:
                        dispatcher.utter_message(
                            json_message={
                                "type": "products",
                                "title": "Mình gợi ý một số mẫu tương tự theo size/tầm giá và phong cách bạn chọn:",
                                "items": [_product_to_card(p) for p in similar[:5]],
                            }
                        )
                        dispatcher.utter_message(
                            text="Nếu bạn muốn, mình có thể lọc lại theo nhu cầu khác (ví dụ: Nike/giày thể thao/đi làm...)."
                        )
                        return []

                    dispatcher.utter_message(
                        json_message={
                            "type": "chips",
                            "title": "Bạn có thể xem các brand đang có:",
                            "items": [
                                {"label": b.get("name") or "Brand", "href": _shop_products_list_url(search=b.get("name") or "")}
                                for b in available[:12]
                            ],
                        }
                    )
                    dispatcher.utter_message(text="Bạn muốn mình đổi sang brand nào hoặc nới tầm giá/size không?")
                    return []

            dispatcher.utter_message(
                text="Mình chưa tìm được sản phẩm phù hợp theo yêu cầu này 😢 Bạn thử nói rõ hơn (brand/mục đích/size/tầm giá/màu/chất liệu/style) nhé."
            )
            return []

        if occasion:
            scene = OCCASION_SCENE_MAP.get(occasion, {})
            advice_text = scene.get("advice", _get_advice_for_purpose(ent_purpose or text))
        else:
            advice_text = _get_advice_for_purpose(ent_purpose or text)

        cards = [_product_to_card(p) for p in items[:5]]
        enriched_lines = []
        for c in cards[:3]:
            extra = []
            if c.get("price_text"):
                extra.append(f"giá {c['price_text']}")
            if c.get("sizes"):
                extra.append(f"size {'/'.join(c['sizes'][:4])}")
            if c.get("colors"):
                extra.append(f"màu {'/'.join(c['colors'][:3])}")
            if extra:
                enriched_lines.append(f"- {c['name']}: " + ", ".join(extra))

        filter_bits = []
        if ent_color:
            filter_bits.append(f"màu {ent_color}")
        elif inferred_color:
            filter_bits.append(f"màu {inferred_color}")
        if ent_material:
            filter_bits.append(f"chất liệu {ent_material}")
        if ent_style:
            filter_bits.append(f"style {ent_style}")
        if filter_bits:
            advice_text = f"{advice_text} — lọc theo {', '.join(filter_bits)}"

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": advice_text,
                "items": cards,
            }
        )
        if enriched_lines:
            dispatcher.utter_message(text="Một vài thông tin nhanh mình lấy được từ sản phẩm:\n" + "\n".join(enriched_lines))
        dispatcher.utter_message(text="Bạn muốn mình lọc thêm theo size hoặc tầm giá cụ thể không?")
        return []


class ActionSearchByOccasion(Action):

    def name(self) -> Text:
        return "action_search_by_occasion"

    def _clarify_prompt(self, field: Text) -> Text:
        if field == "size":
            return "Bạn đang quan tâm size đúng không? Nếu có, hãy gửi size hoặc chiều dài bàn chân, mình sẽ tư vấn tiếp ngay."
        if field == "price":
            return "Bạn đang quan tâm giá đúng không? Hãy gửi tầm giá mong muốn, mình sẽ lọc đúng mẫu cho bạn."
        if field == "comfort":
            return "Bạn đang quan tâm độ êm đúng không? Mình sẽ giải thích thêm về độ êm và mức độ thoải mái của mẫu này."
        return "Bạn đang quan tâm điều nào nhất ở đôi này: size, giá hay độ êm?"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        entities = (tracker.latest_message or {}).get("entities") or []
        expected = tracker.get_slot("clarify_expected")

        if expected:
            if expected == "size":
                size = _parse_size(text)
                if not size:
                    size = _parse_size(tracker.get_slot("last_product_query"))
                if not size:
                    dispatcher.utter_message(text="Bạn gửi giúp mình size hoặc số cm bàn chân nhé, mình sẽ tiếp tục lọc đúng mẫu cho bạn.")
                    return []
                try:
                    items = _fetch_products(search=None, size=size, price_range=tracker.get_slot("price_range"), limit=5)
                except Exception:
                    items = []
                if items:
                    dispatcher.utter_message(text=f"Mình đã lọc theo size {size} cho bạn đây:")
                    dispatcher.utter_message(json_message={"type": "products", "title": f"👟 Gợi ý theo size {size}", "items": [_product_to_card(p) for p in items[:5]]})
                    return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None)]
                dispatcher.utter_message(text=f"Mình chưa tìm được mẫu phù hợp theo size {size}. Bạn có muốn nới tầm giá hoặc đổi brand không?")
                return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None)]

            if expected == "price":
                price_text = text or tracker.get_slot("last_product_query")
                min_vnd, max_vnd = _parse_budget_text_to_range(price_text)
                if min_vnd is None and max_vnd is None:
                    dispatcher.utter_message(text="Bạn gửi giúp mình tầm giá nhé, ví dụ: dưới 1 triệu, 1-2 triệu, hoặc 2-3 triệu.")
                    return []
                try:
                    items = _fetch_products(search=None, size=None, price_range=price_text, limit=5)
                except Exception:
                    items = []
                if items:
                    dispatcher.utter_message(text="Mình đã lọc theo tầm giá bạn vừa chọn:")
                    dispatcher.utter_message(json_message={"type": "products", "title": "💰 Gợi ý theo ngân sách", "items": [_product_to_card(p) for p in items[:5]]})
                    return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None)]
                dispatcher.utter_message(text="Mình chưa tìm được sản phẩm trong tầm giá này. Bạn muốn mình nới ngân sách hay đổi sang dòng khác không?")
                return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None)]

            if expected == "comfort":
                query = tracker.get_slot("last_product_query") or text
                dispatcher.utter_message(text=f"Về độ êm của mẫu này, mình sẽ ưu tiên giày có đệm tốt, form vừa chân và đế hỗ trợ ổn định. Nếu bạn muốn, mình có thể lọc luôn các mẫu êm hơn theo nhu cầu '{query}'.")
                return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None)]

        ent_occasion = _get_entity(entities, "occasion")
        occasion = ent_occasion or _infer_occasion_from_text(text)

        if not occasion:
            if _infer_category_ids_from_text(text) or _infer_brand_from_text(text):
                occasion = _infer_occasion_from_text(text) or "sports"
            else:
                dispatcher.utter_message(text=self._clarify_prompt("general"))
                return [SlotSet("clarify_expected", "general"), SlotSet("last_product_query", text), SlotSet("clarify_question", self._clarify_prompt("general"))]

        scene = OCCASION_SCENE_MAP.get(occasion, {})
        advice = scene.get("advice", "Mình đã tìm được một số mẫu giày phù hợp cho bạn:")

        try:
            items = _fetch_products_by_occasion(occasion, limit=5, price_range=tracker.get_slot("price_range"))
        except Exception:
            items = []

        if not items:
            try:
                items = _fetch_products_by_occasion(occasion, limit=5, price_range=None)
            except Exception:
                items = []

        if not items:
            dispatcher.utter_message(text=f"Mình chưa tìm được giày phù hợp cho dịp này 😢 Bạn thử mô tả cụ thể hơn (brand/size/tầm giá) nhé, mình sẽ tìm cho bạn!")
            return []

        cards = [_product_to_card(p) for p in items[:5]]
        if occasion in {"football", "running", "gym", "sports"}:
            advice = advice + " Mình chỉ hiển thị các mẫu đúng nhóm thể thao để bạn dễ chọn hơn."

        dispatcher.utter_message(json_message={"type": "products", "title": advice, "items": cards})
        dispatcher.utter_message(text="Bạn có muốn lọc thêm theo thương hiệu, size hoặc thay đổi tầm giá không? Mình sẵn sàng hỗ trợ bạn!")
        return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None)]


class ActionSearchByBrand(Action):

    def name(self) -> Text:
        return "action_search_by_brand"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        entities = (tracker.latest_message or {}).get("entities") or []

        ent_brand = _get_entity(entities, "brand")
        brand = ent_brand or _infer_brand_from_text(text)

        if not brand:
            dispatcher.utter_message(
                text="Bạn muốn tìm giày thương hiệu nào? Shop có Nike, Adidas, Puma, New Balance, Converse, Vans, Fila và nhiều thương hiệu khác."
            )
            return []

        try:
            items = _fetch_products(search=brand, limit=8)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(
                text=f"Hiện shop chưa có sản phẩm brand '{brand}' 😢 Bạn thử xem các thương hiệu khác hoặc nói rõ hơn nhu cầu nhé."
            )
            return []

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": f"👟 Đây là các sản phẩm {brand} đang có tại BMC Shoes:",
                "items": [_product_to_card(p) for p in items[:6]],
            }
        )
        dispatcher.utter_message(
            text="Bạn có muốn lọc thêm theo size, tầm giá hoặc xem brand khác không? Mình sẵn sàng hỗ trợ bạn!"
        )
        return []


class ActionSearchByPrice(Action):

    def name(self) -> Text:
        return "action_search_by_price"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        from .api_client import _format_vnd
        from .utils import _parse_budget_text_to_range

        text = (tracker.latest_message or {}).get("text") or ""
        entities = (tracker.latest_message or {}).get("entities") or []

        ent_price = _get_entity(entities, "price_range")
        price_range = ent_price or text

        min_vnd, max_vnd = _parse_budget_text_to_range(price_range)

        if min_vnd and max_vnd:
            title_price = f"tầm giá {_format_vnd(min_vnd)} - {_format_vnd(max_vnd)}"
        elif min_vnd:
            title_price = f"trên {_format_vnd(min_vnd)}"
        elif max_vnd:
            title_price = f"dưới {_format_vnd(max_vnd)}"
        else:
            title_price = "phù hợp"

        try:
            items = _fetch_products(
                price_range=price_range,
                search=None,
                size=None,
                limit=8
            )
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(
                text=f"Trong tầm giá {title_price}, hiện mình chưa tìm được sản phẩm nào 😢 Bạn thử nới rộng tầm giá hoặc mô tả nhu cầu cụ thể hơn nhé (ví dụ: giày chạy bộ, giày đi làm, Nike, Adidas...)"
            )
            return []

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": f"💰 Đây là các sản phẩm {title_price} mình tìm được cho bạn:",
                "items": [_product_to_card(p) for p in items[:6]],
            }
        )
        dispatcher.utter_message(
            text="Bạn có muốn lọc thêm theo thương hiệu, size hoặc loại giày cụ thể nào không? Mình sẵn sàng hỗ trợ bạn!"
        )
        return []


class ActionSearchBySize(Action):

    def name(self) -> Text:
        return "action_search_by_size"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        entities = (tracker.latest_message or {}).get("entities") or []

        ent_size = _get_entity(entities, "shoe_size")
        size = ent_size or _parse_size(text)

        if not size:
            dispatcher.utter_message(
                text="Bạn cho mình biết size giày bạn mang là bao nhiêu nhé (ví dụ: 38, 39, 40, 41, 42). Nếu không biết size, bạn có thể đo chiều dài bàn chân và tra bảng size trên website."
            )
            return []

        try:
            items = _fetch_products(search=None, size=size, price_range=None, limit=8)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(
                text=f"Size {size} hiện đang hết hàng hoặc không có trong kho 😢 Bạn có thể thử size khác gần với {size} hoặc cho mình biết nhu cầu cụ thể hơn để mình gợi ý."
            )
            return []

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": f"👟 Các sản phẩm size {size} mình tìm được cho bạn:",
                "items": [_product_to_card(p) for p in items[:6]],
            }
        )
        dispatcher.utter_message(
            text=f"Bạn có muốn lọc thêm theo thương hiệu, tầm giá hoặc thử size khác không? Mình sẵn sàng hỗ trợ bạn!"
        )
        return []


class ActionSearchWomen(Action):

    def name(self) -> Text:
        return "action_search_women"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        size = _parse_size(text)
        color = tracker.get_slot("color")
        material = tracker.get_slot("material")
        style = tracker.get_slot("style")

        try:
            cat_ids = _infer_category_ids_from_text("nữ nữ tính thời trang")
            items = _fetch_products_with_attributes(search="nữ", size=size, category_ids=cat_ids or None, color=color, material=material, style=style, limit=8)
            if not items:
                items = _fallback_near_match(search="nữ", size=size, category_ids=cat_ids or None, color=color, material=material, style=style, limit=8)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(
                text="Mình chưa tìm được giày nữ phù hợp 😢 Bạn thử mô tả cụ thể hơn (loại giày, màu sắc, chất liệu, thương hiệu, tầm giá) nhé."
            )
            return []

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": "👠 Đây là các sản phẩm dành cho NỮ tại BMC Shoes:",
                "items": [_product_to_card(p) for p in items[:6]],
            }
        )
        dispatcher.utter_message(
            text="Bạn muốn tìm giày cao gót, sneaker nữ, giày búp bê hay loại nào khác? Hoặc mình lọc theo size/tầm giá cụ thể?"
        )
        return []


class ActionSearchMen(Action):

    def name(self) -> Text:
        return "action_search_men"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        size = _parse_size(text)
        color = tracker.get_slot("color")
        material = tracker.get_slot("material")
        style = tracker.get_slot("style")

        try:
            items = _fetch_products_with_attributes(search="nam", size=size, color=color, material=material, style=style, limit=8)
            if not items:
                items = _fallback_near_match(search="nam", size=size, color=color, material=material, style=style, limit=8)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(
                text="Mình chưa tìm được giày nam phù hợp 😢 Bạn thử mô tả cụ thể hơn (loại giày, màu sắc, chất liệu, thương hiệu, tầm giá) nhé."
            )
            return []

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": "👞 Đây là các sản phẩm dành cho NAM tại BMC Shoes:",
                "items": [_product_to_card(p) for p in items[:6]],
            }
        )
        dispatcher.utter_message(
            text="Bạn muốn tìm giày công sở, sneaker, giày thể thao hay loại nào khác? Hoặc mình lọc theo size/tầm giá cụ thể?"
        )
        return []


class ActionSearchByEvent(Action):

    def name(self) -> Text:
        return "action_search_by_event"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        t = text.lower()

        event_map = {
            "sinh nhật": "birthday_gift",
            "birthday": "birthday_gift",
            "bday": "birthday_gift",
            "sn": "birthday_gift",
            "mừng sn": "birthday_gift",
            "kỷ niệm": "anniversary_gift",
            "anniversary": "anniversary_gift",
            "tặng": "gift",
            "quà": "gift",
            "tặng quà": "gift",
            "tặng người yêu": "valentine",
            "tặng bạn": "gift",
            "tặng mẹ": "gift",
            "tặng cha": "gift",
            "tốt nghiệp": "casual",
            "noel": "casual",
            "tết": "casual",
            "trung thu": "valentine",
            "ngày lễ": "valentine",
            "valentine": "valentine",
            "14/2": "valentine",
            "lễ tình nhân": "valentine",
        }

        occasion = None
        for keyword, occ in event_map.items():
            if keyword in t:
                occasion = occ
                break

        if occasion:
            return ActionSearchByOccasion().run(dispatcher, tracker, domain)

        dispatcher.utter_message(
            text="Bạn đang tìm giày cho dịp đặc biệt nào? Ví dụ: sinh nhật, kỷ niệm, tốt nghiệp, Noel, Tết... Mình sẽ gợi ý phù hợp nhé!"
        )
        return []


class ActionSearchWideFeet(Action):

    def name(self) -> Text:
        return "action_search_wide_feet"

    def run(self, dispatcher, tracker, domain):
        color = tracker.get_slot("color")
        material = tracker.get_slot("material")
        style = tracker.get_slot("style")
        try:
            items = _fetch_products_with_attributes(search="chân bè", size=None, price_range=None, color=color, material=material, style=style, limit=6)
            if not items:
                items = _fallback_near_match(search="chân bè", size=None, price_range=None, color=color, material=material, style=style, limit=6)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(text="Mình chưa tìm được mẫu tối ưu cho chân bè. Bạn có thể cho mình biết size, màu hoặc chất liệu để mình lọc sát hơn nhé.")
            return []

        dispatcher.utter_message(json_message={"type": "products", "title": "👣 Gợi ý cho chân bè / chân rộng", "items": [_product_to_card(p) for p in items]})
        dispatcher.utter_message(text="Nếu bạn muốn, mình có thể ưu tiên thêm form rộng, upper mềm hoặc mũi giày thoáng hơn.")
        return []


class ActionSearchWorkShoes(Action):

    def name(self) -> Text:
        return "action_search_work_shoes"

    def run(self, dispatcher, tracker, domain):
        color = tracker.get_slot("color") or "đen"
        material = tracker.get_slot("material") or "da"
        style = tracker.get_slot("style") or "basic"
        try:
            items = _fetch_products_with_attributes(search="công sở", size=None, price_range="1-3m", category_ids=_infer_category_ids_from_text("công sở văn phòng đi làm"), color=color, material=material, style=style, limit=6)
            if not items:
                items = _fallback_near_match(search="công sở", size=None, price_range="1-3m", category_ids=_infer_category_ids_from_text("công sở văn phòng đi làm"), color=color, material=material, style=style, limit=6)
        except Exception:
            items = []
        if not items:
            dispatcher.utter_message(text="Mình chưa tìm được mẫu phù hợp cho đi làm. Bạn cho mình biết phong cách bạn thích: lịch sự, tối giản hay sneaker công sở nhé.")
            return []
        dispatcher.utter_message(json_message={"type": "products", "title": "💼 Gợi ý giày đi làm / công sở", "items": [_product_to_card(p) for p in items]})
        dispatcher.utter_message(text="Bạn muốn mình ưu tiên màu đen, nâu hay form tối giản dễ phối đồ không?")
        return []


class ActionSearchSchoolShoes(Action):

    def name(self) -> Text:
        return "action_search_school_shoes"

    def run(self, dispatcher, tracker, domain):
        color = tracker.get_slot("color")
        material = tracker.get_slot("material") or "canvas"
        style = tracker.get_slot("style") or "basic"
        try:
            items = _fetch_products_with_attributes(search="học sinh sinh viên", size=None, price_range="<5000000", color=color, material=material, style=style, limit=6)
            if not items:
                items = _fallback_near_match(search="học sinh sinh viên", size=None, price_range="<5000000", color=color, material=material, style=style, limit=6)
        except Exception:
            items = []
        if not items:
            dispatcher.utter_message(text="Mình chưa tìm được mẫu phù hợp cho đi học. Nếu bạn cho mình size, màu hoặc chất liệu yêu thích, mình sẽ lọc chính xác hơn nhé.")
            return []
        dispatcher.utter_message(json_message={"type": "products", "title": "🎒 Gợi ý giày đi học / sinh viên", "items": [_product_to_card(p) for p in items]})
        dispatcher.utter_message(text="Bạn muốn kiểu dễ phối, bền, hay êm để đi cả ngày? Mình sẽ lọc tiếp cho bạn.")
        return []


class ActionSearchRunningShoes(Action):

    def name(self) -> Text:
        return "action_search_running_shoes"

    def run(self, dispatcher, tracker, domain):
        color = tracker.get_slot("color")
        material = tracker.get_slot("material") or "mesh"
        style = tracker.get_slot("style") or "sporty"
        try:
            items = _fetch_products_with_attributes(search="chạy bộ", size=None, price_range=None, color=color, material=material, style=style, limit=6)
            if not items:
                items = _fetch_products_by_occasion("running", limit=6)
            if not items:
                items = _fallback_near_match(search="chạy bộ", size=None, price_range=None, color=color, material=material, style=style, limit=6)
        except Exception:
            items = []
        if not items:
            dispatcher.utter_message(text="Mình chưa tìm được mẫu chạy bộ phù hợp. Bạn cho mình biết bạn cần êm hơn, nhẹ hơn hay thoáng hơn nhé.")
            return []
        dispatcher.utter_message(json_message={"type": "products", "title": "🏃 Gợi ý giày chạy bộ", "items": [_product_to_card(p) for p in items]})
        dispatcher.utter_message(text="Nếu bạn muốn, mình có thể ưu tiên giày chạy bộ êm, nhẹ hoặc cho chân bè.")
        return []


class ActionSearchStyleAdvice(Action):

    def name(self) -> Text:
        return "action_search_style_advice"

    def run(self, dispatcher, tracker, domain):
        text = (tracker.latest_message or {}).get("text") or ""
        color = tracker.get_slot("color")
        material = tracker.get_slot("material")
        style = tracker.get_slot("style")
        t = text.lower()
        if _match_any(t, _SHOE_STYLE_TERMS["màu"]):
            dispatcher.utter_message(text="Mình sẽ ưu tiên màu trung tính như trắng, đen, be nếu bạn muốn dễ phối đồ; còn nếu muốn nổi bật, có thể chọn đỏ, xanh hoặc hồng.")
        elif _match_any(t, _SHOE_STYLE_TERMS["material"]):
            dispatcher.utter_message(text="Nếu bạn thích thoáng và nhẹ thì chọn vải/lưới; nếu muốn bền và lịch sự thì da hoặc da lộn sẽ hợp hơn.")
        elif _match_any(t, _SHOE_STYLE_TERMS["style"]):
            dispatcher.utter_message(text="Với style tối giản/basic, hãy ưu tiên form gọn, màu trung tính. Nếu thích retro/streetwear, có thể chọn đế dày và phối màu nổi hơn.")
        else:
            dispatcher.utter_message(text="Bạn muốn mình tư vấn theo màu sắc, chất liệu hay phong cách để lọc giày đúng gu hơn không?")
            return []

        try:
            items = _fallback_near_match(search=_clean_search_query(text), color=color, material=material, style=style, limit=6)
        except Exception:
            items = []

        if items:
            dispatcher.utter_message(json_message={"type": "products", "title": "✨ Mình gợi ý thêm vài mẫu gần gu bạn", "items": [_product_to_card(p) for p in items]})
        return []
