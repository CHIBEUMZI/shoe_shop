"""Search-related actions."""
import os
from typing import Any, Dict, List, Text

from rasa_sdk import Action, Tracker
from rasa_sdk.events import SlotSet
from rasa_sdk.executor import CollectingDispatcher

from .api_client import (
    _fetch_products,
    _fetch_products_by_occasion,
    _fetch_facets,
    _infer_brand_from_text,
    _infer_category_ids_from_text,
    _infer_occasion_from_text,
    _product_to_card,
    _shop_products_list_url,
)
from .constants import OCCASION_SCENE_MAP, _get_advice_for_purpose
from .utils import _clean_search_query, _get_entity, _parse_size


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

        try:
            if occasion:
                data = _fetch_products_by_occasion(occasion, limit=5, price_range=price)
                if not data:
                    data = _fetch_products(search=None, size=size, price_range=price, category_ids=None, limit=5)
                    fallback_called = True
            else:
                cat_ids = _infer_category_ids_from_text(purpose)
                search_query = None if cat_ids else purpose
                data = _fetch_products(search=search_query, size=size, price_range=price, category_ids=cat_ids or None, limit=5)
                if not data and size:
                    data = _fetch_products(search=search_query, size=None, price_range=price, category_ids=cat_ids or None, limit=5)
                if not data and price:
                    data = _fetch_products(search=search_query, size=None, price_range=None, category_ids=cat_ids or None, limit=5)
        except Exception:
            data = []

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

        size = ent_size or _parse_size(text)
        price_range = ent_price_range or text

        brand = ent_brand or _infer_brand_from_text(text)
        search = brand or _clean_search_query(text)
        cat_ids = _infer_category_ids_from_text(ent_purpose or text)

        occasion = _infer_occasion_from_text(text)
        items = []
        fallback_called = False

        try:
            if occasion:
                items = _fetch_products_by_occasion(occasion, limit=5, price_range=price_range)
                if not items:
                    items = _fetch_products(search=None, size=size, price_range=price_range, category_ids=None, limit=5)
                    fallback_called = True
            else:
                items = _fetch_products(search=search, size=size, price_range=price_range, category_ids=cat_ids or None, limit=5)
                if not items and size:
                    items = _fetch_products(search=search, size=None, price_range=price_range, category_ids=cat_ids or None, limit=5)
                if not items and price_range:
                    items = _fetch_products(search=search, size=None, price_range=None, category_ids=cat_ids or None, limit=5)
                if not items and cat_ids:
                    items = _fetch_products(search=None, size=size, price_range=price_range, category_ids=cat_ids, limit=5)
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
                        similar = _fetch_products(
                            search=None,
                            size=size,
                            price_range=price_range,
                            category_ids=cat_ids or None,
                            limit=5,
                        )
                        if not similar and size:
                            similar = _fetch_products(
                                search=None,
                                size=None,
                                price_range=price_range,
                                category_ids=cat_ids or None,
                                limit=5,
                            )
                        if not similar and (price_range or cat_ids):
                            similar = _fetch_products(
                                search=None,
                                size=None,
                                price_range=price_range,
                                category_ids=None,
                                limit=5,
                            )
                        if not similar and price_range:
                            similar = _fetch_products(
                                search=None,
                                size=None,
                                price_range=None,
                                category_ids=cat_ids or None,
                                limit=5,
                            )
                    except Exception:
                        similar = []

                    dispatcher.utter_message(text=f"Hiện shop chưa có sản phẩm brand \"{brand}\" 😢")

                    if similar:
                        dispatcher.utter_message(
                            json_message={
                                "type": "products",
                                "title": "Mình gợi ý một số mẫu tương tự theo size/tầm giá bạn chọn:",
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
                text="Mình chưa tìm được sản phẩm phù hợp theo yêu cầu này 😢 Bạn thử nói rõ hơn (brand/mục đích/size/tầm giá) nhé."
            )
            return []

        if occasion and not fallback_called:
            scene = OCCASION_SCENE_MAP.get(occasion, {})
            advice_text = scene.get("advice", _get_advice_for_purpose(ent_purpose or text))
        else:
            advice_text = _get_advice_for_purpose(ent_purpose or text)

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": advice_text,
                "items": [_product_to_card(p) for p in items[:5]],
            }
        )
        dispatcher.utter_message(text="Bạn muốn mình lọc thêm theo size hoặc tầm giá cụ thể không?")
        return []


class ActionSearchByOccasion(Action):

    def name(self) -> Text:
        return "action_search_by_occasion"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        text = (tracker.latest_message or {}).get("text") or ""
        entities = (tracker.latest_message or {}).get("entities") or []

        ent_occasion = _get_entity(entities, "occasion")
        occasion = ent_occasion or _infer_occasion_from_text(text)

        if not occasion:
            dispatcher.utter_message(
                text="Mình chưa hiểu bạn muốn tìm giày cho dịp gì. Bạn có thể mô tả cụ thể hơn không (ví dụ: đi chơi Valentine, phỏng vấn, dạo phố...)?"
            )
            return []

        scene = OCCASION_SCENE_MAP.get(occasion, {})
        advice = scene.get("advice", "Mình đã tìm được một số mẫu giày phù hợp cho bạn:")

        try:
            items = _fetch_products_by_occasion(occasion, limit=5, price_range=tracker.get_slot("price_range"))
        except Exception:
            items = []

        if not items:
            # Try without price filter
            try:
                items = _fetch_products_by_occasion(occasion, limit=5, price_range=None)
            except Exception:
                items = []

        if not items:
            dispatcher.utter_message(
                text=f"Mình chưa tìm được giày phù hợp cho dịp này 😢 Bạn thử mô tả cụ thể hơn (brand/size/tầm giá) nhé, mình sẽ tìm cho bạn!"
            )
            return []

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": advice,
                "items": [_product_to_card(p) for p in items[:5]],
            }
        )
        dispatcher.utter_message(
            text="Bạn có muốn lọc thêm theo thương hiệu, size hoặc thay đổi tầm giá không? Mình sẵn sàng hỗ trợ bạn!"
        )

        return []


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

        try:
            cat_ids = _infer_category_ids_from_text("nữ nữ tính thời trang")
            items = _fetch_products(search="nữ", size=size, price_range=None, category_ids=cat_ids or None, limit=8)
            if not items:
                items = _fetch_products(search=None, size=size, price_range=None, limit=8)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(
                text="Mình chưa tìm được giày nữ phù hợp 😢 Bạn thử mô tả cụ thể hơn (loại giày, thương hiệu, tầm giá) nhé."
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

        try:
            items = _fetch_products(search="nam", size=size, price_range=None, limit=8)
            if not items:
                items = _fetch_products(search=None, size=size, price_range=None, limit=8)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(
                text="Mình chưa tìm được giày nam phù hợp 😢 Bạn thử mô tả cụ thể hơn (loại giày, thương hiệu, tầm giá) nhé."
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
