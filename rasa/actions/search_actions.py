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
    _fetch_trending_products as _api_fetch_trending_products,
    _fetch_facets,
    _infer_brand_from_text,
    _infer_category_ids_from_text,
    _infer_occasion_from_text,
    _product_to_card,
    _shop_products_list_url,
)
from .constants import OCCASION_SCENE_MAP, _get_advice_for_purpose, _group_advice
from .utils import _clean_search_query, _get_entity, _infer_color_from_text, _parse_size, _parse_budget_text_to_range


_SHOE_STYLE_TERMS = {
    "màu": ["màu", "color", "trắng", "đen", "nâu", "xanh", "đỏ", "hồng", "be", "xám", "vàng", "xanh navy"],
    "material": ["da", "da lộn", "suede", "vải", "canvas", "mesh", "lưới", "nỉ", "cao su"],
    "style": ["basic", "tối giản", "retro", "streetwear", "sporty", "formal", "casual", "trendy", "classic", "năng động"],
}

_TRENDING_KEYWORDS = {
    "best_selling": ["bán chạy nhất", "bán chạy", "bán nhiều nhất", "hot nhất", "best seller", "best-selling", "top bán chạy", "sản phẩm bán chạy"],
    "most_viewed": ["xem nhiều nhất", "lượt xem nhiều nhất", "được xem nhiều nhất", "view nhiều nhất", "most viewed", "top view", "sản phẩm xem nhiều"],
}


def _normalize_text(value: Optional[str]) -> str:
    return (value or "").strip().lower()


def _match_any(text: str, keywords: List[str]) -> bool:
    return any(k in text for k in keywords)


def _build_search_hints(text: str, entities: List[Dict[str, Any]]) -> Dict[str, Optional[str]]:
    brand = _get_entity(entities, "brand") or _infer_brand_from_text(text)
    color = _get_entity(entities, "color") or _infer_color_from_text(text)
    material = _get_entity(entities, "material")
    style = _get_entity(entities, "style")
    purpose = _get_entity(entities, "purpose")
    size = _get_entity(entities, "shoe_size") or _parse_size(text)
    price_range = _get_entity(entities, "price_range") or text
    return {
        "brand": brand,
        "color": color,
        "material": material,
        "style": style,
        "purpose": purpose,
        "size": size,
        "price_range": price_range,
    }


def _detect_trending_mode(text: str) -> Optional[str]:
    t = _normalize_text(text)
    for mode, keywords in _TRENDING_KEYWORDS.items():
        if _match_any(t, keywords):
            return mode
    return None


def _fetch_trending_products(mode: str, limit: int = 5) -> List[dict]:
    try:
        items = _api_fetch_trending_products(mode=mode, limit=limit)
        if items:
            return items
    except Exception as exc:
        print(f"[trending-debug] api fetch failed mode={mode}: {exc}")

    try:
        sort = "popular"
        fallback = _fetch_products(search=None, size=None, price_range=None, limit=limit, sort=sort)
        print(f"[trending-debug] fallback list returned {len(fallback)} items for mode={mode}")
        return fallback
    except Exception as exc:
        print(f"[trending-debug] fallback fetch failed mode={mode}: {exc}")
        return []


def _color_summary(items: List[dict]) -> str:
    counts: Dict[str, int] = {}
    for item in items or []:
        colors = item.get("colors") if isinstance(item, dict) else None
        if not colors:
            card = _product_to_card(item)
            colors = card.get("colors") or []
        for color in colors or []:
            key = str(color).strip().lower()
            if not key:
                continue
            counts[key] = counts.get(key, 0) + 1
    if not counts:
        return ""
    top = sorted(counts.items(), key=lambda kv: (-kv[1], kv[0]))[:3]
    return ", ".join([f"{name} ({count})" for name, count in top])


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
                text="Mình chưa tư vấn được mẫu phù hợp lúc này 😢 Bạn vui lòng liên hệ trực tiếp shop qua Zalo số 0327264556 để được hỗ trợ nhanh nhất nhé."
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
        hints = _build_search_hints(text, entities)

        ent_brand = hints["brand"]
        ent_size = hints["size"]
        ent_purpose = hints["purpose"]
        ent_price_range = hints["price_range"]
        ent_color = hints["color"] or tracker.get_slot("color")
        ent_material = hints["material"] or tracker.get_slot("material")
        ent_style = hints["style"] or tracker.get_slot("style")

        purpose_slot = tracker.get_slot("purpose")
        size = ent_size
        price_range = ent_price_range
        parsed_price = _parse_budget_text_to_range(price_range)

        brand = ent_brand
        inferred_color = ent_color
        search = brand or _clean_search_query(text)
        active_purpose = ent_purpose or purpose_slot or tracker.get_slot("last_product_query")
        cat_ids = _infer_category_ids_from_text(active_purpose or text)

        trending_mode = _detect_trending_mode(text)
        if trending_mode:
            search = None
            active_purpose = None

        if ent_purpose:
            search = None

        occasion = _infer_occasion_from_text(active_purpose or text)
        items = []

        # Prefer explicit price range if the text actually looks like budget input.
        has_price_text = parsed_price != (None, None)
        if has_price_text and not brand and not ent_purpose:
            # When the user is mainly giving a budget, avoid treating the full sentence
            # as a keyword search because it can drown out the actual price filter.
            search = None

        if not search and not cat_ids and (ent_material or ent_style or inferred_color):
            search = _clean_search_query(active_purpose or text)

        if trending_mode:
            items = _fetch_trending_products(trending_mode, limit=5)

        try:
            if not items and occasion == "football":
                items = _fetch_products_with_attributes(
                    search="giày đá bóng",
                    size=size,
                    price_range=price_range if has_price_text else None,
                    category_ids=cat_ids or _infer_category_ids_from_text("giày đá bóng") or None,
                    color=inferred_color,
                    material=ent_material,
                    style=ent_style,
                    limit=5,
                )
                if not items:
                    items = _fetch_products_by_occasion("football", limit=5, price_range=price_range if has_price_text else None)
            elif occasion:
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
            fallback_search = "giày đá bóng" if occasion == "football" else search or active_purpose or text
            fallback_material = ent_material or ("da lộn" if "suede" in text.lower() else None)
            items = _fallback_near_match(
                search=fallback_search,
                size=size,
                price_range=price_range if has_price_text else None,
                category_ids=(cat_ids or _infer_category_ids_from_text("giày đá bóng") or None) if occasion == "football" else cat_ids or None,
                color=inferred_color,
                material=fallback_material,
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

        if trending_mode == "best_selling":
            advice_text = "🔥 Đây là 5 mẫu giày bán chạy nhất shop đang gợi ý cho bạn:"
        elif trending_mode == "most_viewed":
            advice_text = "👀 Đây là 5 mẫu giày được xem nhiều nhất hiện tại:"
        elif occasion:
            scene = OCCASION_SCENE_MAP.get(occasion, {})
            advice_text = scene.get("advice", _get_advice_for_purpose(active_purpose or text))
        else:
            advice_text = _get_advice_for_purpose(active_purpose or text)

        if brand:
            advice_text = f"🏷️ **Tư vấn theo brand {brand}:** Mình đã ưu tiên các mẫu cùng brand, rồi mới lọc tiếp theo size, màu và chất liệu để ra gợi ý sát nhu cầu nhất."
        elif ent_material:
            mat = ent_material.lower()
            if any(k in mat for k in ["da lộn", "suede", "nubuck"]):
                advice_text = _group_advice("da")
            elif any(k in mat for k in ["vải", "canvas", "mesh", "lưới", "nỉ"]):
                advice_text = _group_advice("vai")
            elif any(k in mat for k in ["da", "leather", "pu"]):
                advice_text = _group_advice("da")
        elif occasion == "football":
            advice_text = _group_advice("da_bong")
        elif active_purpose:
            p = active_purpose.lower()
            if any(k in p for k in ["đi làm", "công sở", "văn phòng", "phỏng vấn"]):
                advice_text = _group_advice("cong_so")
            elif any(k in p for k in ["chạy bộ", "running", "jogging"]):
                advice_text = _group_advice("the_thao")
            elif any(k in p for k in ["đi chơi", "dạo phố", "casual", "sneaker"]):
                advice_text = _group_advice("vai")

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
        if trending_mode == "best_selling":
            filter_bits.append("sắp xếp theo sản phẩm bán chạy")
        elif trending_mode == "most_viewed":
            filter_bits.append("sắp xếp theo lượt xem")
        if ent_color:
            filter_bits.append(f"màu {ent_color}")
        elif inferred_color:
            filter_bits.append(f"màu {inferred_color}")
        if ent_material:
            filter_bits.append(f"chất liệu {ent_material}")
        if ent_style:
            filter_bits.append(f"style {ent_style}")
        if occasion == "football":
            filter_bits = ["giày đá bóng chuyên dụng", "TF/FG/SG hoặc sân cỏ"] + filter_bits
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
        dispatcher.utter_message(text="Bạn muốn mình lọc thêm theo size, màu hoặc tầm giá cụ thể không?")
        return []


class ActionSearchByOccasion(Action):

    def name(self) -> Text:
        return "action_search_by_occasion"

    def _clarify_prompt(self, field: Text, occasion: Optional[str] = None) -> Text:
        if occasion == "interview":
            if field == "size":
                return "Bạn đi làm môi trường công sở hay casual vậy? Nếu có size bàn chân, mình sẽ lọc mẫu vừa chân và lịch sự hơn cho bạn."
            if field == "price":
                return "Bạn muốn ngân sách khoảng bao nhiêu cho đôi giày đi làm? Mình sẽ ưu tiên các mẫu phù hợp để mang cả ngày mà vẫn lịch sự."
            if field == "comfort":
                return "Bạn ưu tiên đôi êm để đi làm cả ngày đúng không? Mình sẽ gợi ý các mẫu đệm tốt và form thoải mái hơn."
            return "Bạn thích giày da hay sneaker cho môi trường công sở? Mình sẽ lọc đúng gu cho bạn."
        if occasion == "casual":
            if field == "size":
                return "Bạn muốn form ôm chân hay thoải mái hơn cho đi chơi hằng ngày? Nếu có size, mình lọc nhanh cho bạn."
            if field == "price":
                return "Bạn thích mức giá nào cho đôi giày đi chơi? Mình sẽ chọn các mẫu dễ phối đồ và hợp ngân sách."
            if field == "comfort":
                return "Bạn ưu tiên sự thoải mái để đi dạo phố cả ngày đúng không? Mình sẽ tìm mẫu êm hơn cho bạn."
            return "Bạn thích kiểu basic, retro hay năng động để mình gợi ý chuẩn hơn?"
        if occasion == "travel":
            if field == "size":
                return "Bạn có size chân hoặc form chân đặc biệt không? Đi du lịch thì mình ưu tiên mẫu vừa chân và đi lâu không mỏi."
            if field == "price":
                return "Bạn muốn đầu tư khoảng bao nhiêu cho đôi giày du lịch? Mình sẽ ưu tiên mẫu nhẹ, êm và bền."
            if field == "comfort":
                return "Bạn ưu tiên đi êm cả ngày đúng không? Mình sẽ chọn các mẫu thoải mái hơn cho chuyến đi xa."
            return "Bạn thích mẫu nhẹ, thoáng hay bền chắc cho chuyến đi?"
        if occasion == "party":
            if field == "size":
                return "Bạn muốn form ôm chân hay nổi bật hơn cho buổi tiệc? Nếu có size, mình lọc đúng mẫu luôn."
            if field == "price":
                return "Bạn muốn tầm giá nào cho đôi giày đi tiệc? Mình sẽ ưu tiên mẫu sang và nổi bật hơn."
            if field == "comfort":
                return "Bạn cần mẫu vừa đẹp vừa dễ chịu để đi tiệc lâu đúng không? Mình sẽ chọn mẫu cân bằng giữa style và độ êm."
            return "Bạn thích đôi giày nổi bật, sang trọng hay đơn giản tinh tế cho buổi tiệc?"
        if occasion == "valentine":
            if field == "size":
                return "Bạn có size của người nhận quà chưa? Mình sẽ lọc những mẫu vừa đẹp vừa dễ tặng hơn."
            if field == "price":
                return "Bạn muốn quà Valentine trong khoảng ngân sách nào? Mình sẽ chọn mẫu lãng mạn và hợp túi tiền."
            if field == "comfort":
                return "Bạn ưu tiên món quà đẹp hay dễ mang hằng ngày hơn? Mình sẽ gợi ý mẫu phù hợp nhất."
            return "Bạn muốn mẫu lãng mạn, cá tính hay dễ phối đồ để tặng dịp Valentine?"
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
        active_purpose = tracker.get_slot("purpose") or tracker.get_slot("last_product_query") or text
        active_occasion = _infer_occasion_from_text(active_purpose)

        if expected:
            if expected == "size":
                size = _parse_size(text)
                if not size:
                    size = _parse_size(tracker.get_slot("last_product_query"))
                if not size:
                    dispatcher.utter_message(text=self._clarify_prompt("size", active_occasion))
                    return []
                try:
                    if active_occasion == "football":
                        items = _fetch_products_with_attributes(search="giày đá bóng", size=size, price_range=tracker.get_slot("price_range"), category_ids=_infer_category_ids_from_text("giày đá bóng"), limit=5)
                    else:
                        items = _fetch_products(search=None, size=size, price_range=tracker.get_slot("price_range"), limit=5)
                except Exception:
                    items = []
                if items:
                    dispatcher.utter_message(text=f"Mình đã lọc theo size {size} cho bạn đây:")
                    dispatcher.utter_message(json_message={"type": "products", "title": f"👟 Gợi ý theo size {size}", "items": [_product_to_card(p) for p in items[:5]]})
                    return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None), SlotSet("purpose", active_purpose if active_occasion == "football" else None)]
                dispatcher.utter_message(text=f"Mình chưa tìm được mẫu phù hợp theo size {size}. Bạn có muốn nới tầm giá hoặc đổi brand không?")
                return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None), SlotSet("purpose", active_purpose if active_occasion == "football" else None)]

            if expected == "price":
                price_text = text or tracker.get_slot("last_product_query")
                min_vnd, max_vnd = _parse_budget_text_to_range(price_text)
                if min_vnd is None and max_vnd is None:
                    dispatcher.utter_message(text=self._clarify_prompt("price", active_occasion))
                    return []
                try:
                    if active_occasion == "football":
                        items = _fetch_products_with_attributes(search="giày đá bóng", size=None, price_range=price_text, category_ids=_infer_category_ids_from_text("giày đá bóng"), limit=5)
                    else:
                        items = _fetch_products(search=None, size=None, price_range=price_text, limit=5)
                except Exception:
                    items = []
                if items:
                    dispatcher.utter_message(text="Mình đã lọc theo tầm giá bạn vừa chọn:")
                    dispatcher.utter_message(json_message={"type": "products", "title": "💰 Gợi ý theo ngân sách", "items": [_product_to_card(p) for p in items[:5]]})
                    return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None), SlotSet("purpose", active_purpose if active_occasion == "football" else None)]
                dispatcher.utter_message(text="Mình chưa tìm được sản phẩm trong tầm giá này. Bạn muốn mình nới ngân sách hay đổi sang dòng khác không?")
                return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None), SlotSet("purpose", active_purpose if active_occasion == "football" else None)]

            if expected == "comfort":
                query = tracker.get_slot("last_product_query") or text
                dispatcher.utter_message(text=self._clarify_prompt("comfort", active_occasion) + f" Nếu bạn muốn, mình có thể lọc luôn các mẫu êm hơn theo nhu cầu '{query}'.")
                return [SlotSet("clarify_expected", None), SlotSet("last_product_query", None), SlotSet("clarify_question", None), SlotSet("purpose", active_purpose if active_occasion == "football" else None)]

        ent_occasion = _get_entity(entities, "occasion")
        occasion = ent_occasion or _infer_occasion_from_text(text)

        if occasion in {"valentine", "birthday_gift", "anniversary_gift", "gift"}:
            advice = _group_advice("da") if occasion == "valentine" else OCCASION_SCENE_MAP.get(occasion, {}).get("advice", "Mình đã tìm được một số mẫu giày phù hợp cho bạn:")
            try:
                items = _fetch_products_by_occasion(occasion, limit=5, price_range=tracker.get_slot("price_range"))
            except Exception:
                items = []
            if not items:
                dispatcher.utter_message(text=self._clarify_prompt("price", occasion))
                return []
            dispatcher.utter_message(json_message={"type": "products", "title": advice, "items": [_product_to_card(p) for p in items[:5]]})
            dispatcher.utter_message(text=self._clarify_prompt("comfort", occasion))
            return []

        if occasion in {"interview", "casual", "travel", "party", "football", "running", "gym", "sports"}:
            scene = OCCASION_SCENE_MAP.get(occasion, {})
            advice = scene.get("advice", "Mình đã tìm được một số mẫu giày phù hợp cho bạn:")
            try:
                items = _fetch_products_by_occasion(occasion, limit=5, price_range=tracker.get_slot("price_range"))
            except Exception:
                items = []
            if not items:
                dispatcher.utter_message(text=self._clarify_prompt("size", occasion))
                return []
            cards = [_product_to_card(p) for p in items[:5]]
            if occasion in {"football", "running", "gym", "sports"}:
                advice = advice + " Mình chỉ hiển thị các mẫu đúng nhóm thể thao để bạn dễ chọn hơn."
            dispatcher.utter_message(json_message={"type": "products", "title": advice, "items": cards})
            dispatcher.utter_message(text=self._clarify_prompt("price", occasion))
            return []

        scene = OCCASION_SCENE_MAP.get(occasion, {})
        advice = scene.get("advice", "Mình đã tìm được một số mẫu giày phù hợp cho bạn:")

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


class ActionSearchBestSelling(Action):

    def name(self) -> Text:
        return "action_search_best_selling"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        try:
            items = _fetch_trending_products("best_selling", limit=8)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(text="Mình chưa lấy được danh sách sản phẩm bán chạy lúc này 😢 Bạn thử lại sau nhé.")
            return []

        colors = _color_summary(items)
        title = "🔥 Đây là các sản phẩm bán chạy nhất shop đang có:"
        if colors:
            title = f"{title} Màu bán chạy nổi bật: {colors}."
        dispatcher.utter_message(json_message={"type": "products", "title": title, "items": [_product_to_card(p) for p in items[:6]]})
        dispatcher.utter_message(text="Bạn muốn mình lọc tiếp theo brand, size hay xem nhóm màu nào đang hot nhất không?")
        return []


class ActionSearchMostViewed(Action):

    def name(self) -> Text:
        return "action_search_most_viewed"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        try:
            items = _fetch_trending_products("most_viewed", limit=8)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(text="Mình chưa lấy được danh sách sản phẩm xem nhiều lúc này 😢 Bạn thử lại sau nhé.")
            return []

        colors = _color_summary(items)
        title = "👀 Đây là các sản phẩm được xem nhiều nhất:"
        if colors:
            title = f"{title} Màu được quan tâm nhiều: {colors}."
        dispatcher.utter_message(json_message={"type": "products", "title": title, "items": [_product_to_card(p) for p in items[:6]]})
        dispatcher.utter_message(text="Nếu bạn muốn, mình có thể gợi ý theo màu, brand hoặc tầm giá tương tự các mẫu đang hot.")
        return []


class ActionTrendingColor(Action):

    def name(self) -> Text:
        return "action_trending_color"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        try:
            items = _fetch_trending_products("best_selling", limit=12)
        except Exception:
            items = []

        if not items:
            dispatcher.utter_message(text="Mình chưa lấy được dữ liệu màu hot lúc này 😢 Bạn thử lại sau nhé.")
            return []

        colors = _color_summary(items)
        if not colors:
            dispatcher.utter_message(text="Mình đã tìm được sản phẩm hot, nhưng chưa tổng hợp được màu nổi bật từ dữ liệu hiện tại.")
            return []

        lines = [f"- {idx + 1}. {part.strip()}" for idx, part in enumerate(colors.split(", "))]
        dispatcher.utter_message(text="🎨 Màu đang hot nhất hiện tại:")
        dispatcher.utter_message(text="\n".join(lines))
        dispatcher.utter_message(json_message={"type": "products", "title": "Một số sản phẩm đang góp phần tạo trend màu này:", "items": [_product_to_card(p) for p in items[:6]]})
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


class ActionShoeCareAdvice(Action):

    def name(self) -> Text:
        return "action_shoe_care_advice"

    def _care_title(self, material: str) -> str:
        material = _normalize_text(material)
        if not material:
            return "bảo quản giày"
        return f"bảo quản giày {material}"

    def _care_reply(self, material: str) -> str:
        m = _normalize_text(material)
        if "da lộn" in m or "nubuck" in m or "suede" in m:
            return (
                "🧴 **Hướng dẫn bảo quản giày da lộn / nubuck:**\n"
                "1. Dùng bàn chải mềm chuyên dụng để phủi bụi khô\n"
                "2. Tránh nước và tránh chà mạnh lên bề mặt\n"
                "3. Dùng xịt chống thấm chuyên cho da lộn/nubuck\n"
                "4. Nếu bị bẩn, dùng tẩy da lộn hoặc khăn khô sạch\n"
                "5. Bảo quản nơi khô thoáng, nhét giấy giữ form"
            )
        if any(k in m for k in ["vải", "canvas", "mesh", "lưới", "textile"]):
            return (
                "🧴 **Hướng dẫn bảo quản giày vải / lưới:**\n"
                "1. Phủi bụi sau mỗi lần sử dụng bằng bàn chải mềm\n"
                "2. Lau vết bẩn nhẹ bằng khăn ẩm và xà phòng loãng\n"
                "3. Không ngâm nước lâu, không phơi nắng gắt\n"
                "4. Nhét giấy/bảo quản form để giày không bị biến dạng\n"
                "5. Nếu ẩm, để khô tự nhiên ở nơi thoáng gió"
            )
        return (
            "🧴 **Hướng dẫn bảo quản giày da:**\n"
            "1. Lau sạch sau mỗi lần sử dụng bằng khăn ẩm\n"
            "2. Sấy khô tự nhiên, tránh phơi nắng gắt hoặc sấy lửa\n"
            "3. Sử dụng kem/sáp dưỡng da chuyên dụng 1-2 lần/tuần\n"
            "4. Lưu trữ trong hộp giày hoặc túi vải, có miếng lót giữ form\n"
            "5. Chống ẩm bằng xịt chống nước chuyên dụng cho da"
        )

    def run(self, dispatcher, tracker, domain):
        text = (tracker.latest_message or {}).get("text") or ""
        t = text.lower()
        current_material = tracker.get_slot("care_material") or tracker.get_slot("care_topic") or tracker.get_slot("material")

        if _match_any(t, ["da lộn", "nubuck", "suede"]):
            current_material = "da lộn / nubuck"
        elif _match_any(t, ["vải", "canvas", "mesh", "lưới"]):
            current_material = "vải / lưới"
        elif _match_any(t, ["da"]):
            current_material = "da"
        elif not current_material:
            current_material = "da"

        dispatcher.utter_message(text=self._care_reply(current_material))
        dispatcher.utter_message(text="Nếu bạn muốn, mình có thể tiếp tục so sánh cách bảo quản giữa da, vải, lưới và nubuck để bạn dễ nhớ hơn.")
        return [SlotSet("care_topic", current_material), SlotSet("care_material", current_material)]
