import os
import re
from typing import Any, Text, Dict, List, Optional, Tuple

import requests

from rasa_sdk import Action, Tracker, FormValidationAction
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.types import DomainDict


def _format_vnd(amount: Optional[int]) -> str:
    if amount is None:
        return ""
    return f"{amount:,}".replace(",", ".") + "đ"


def _parse_budget_text_to_range(text: Optional[str]) -> Tuple[Optional[int], Optional[int]]:
    if not text:
        return (None, None)

    t = text.lower()
    t = t.replace("–", "-").replace("—", "-")

    def to_vnd(num_str: str, unit: str) -> Optional[int]:
        try:
            num = float(num_str.replace(",", "."))
        except Exception:
            return None
        unit = (unit or "").strip()
        if unit in ("k", "nghìn", "nghin"):
            return int(num * 1_000)
        if unit in ("tr", "triệu", "trieu", "m", "million"):
            return int(num * 1_000_000)
        return int(num)

    m = re.search(r"(dưới|duoi|<)\s*([0-9]+(?:[.,][0-9]+)?)\s*(triệu|trieu|tr|m|k|nghìn|nghin)?", t)
    if m:
        max_vnd = to_vnd(m.group(2), m.group(3) or "tr")
        return (None, max_vnd)

    m = re.search(r"(trên|tren|>)\s*([0-9]+(?:[.,][0-9]+)?)\s*(triệu|trieu|tr|m|k|nghìn|nghin)?", t)
    if m:
        min_vnd = to_vnd(m.group(2), m.group(3) or "tr")
        return (min_vnd, None)

    m = re.search(
        r"([0-9]+(?:[.,][0-9]+)?)\s*(triệu|trieu|tr|m|k|nghìn|nghin)?\s*(?:-|đến|den|tới|toi)\s*([0-9]+(?:[.,][0-9]+)?)\s*(triệu|trieu|tr|m|k|nghìn|nghin)?",
        t,
    )
    if m:
        unit1 = m.group(2) or m.group(4) or "tr"
        unit2 = m.group(4) or m.group(2) or "tr"
        a = to_vnd(m.group(1), unit1)
        b = to_vnd(m.group(3), unit2)
        return (a, b)

    m = re.search(r"([0-9]+(?:[.,][0-9]+)?)\s*(triệu|trieu|tr|m|k|nghìn|nghin)", t)
    if m:
        v = to_vnd(m.group(1), m.group(2))
        if v is not None:
            return (int(v * 0.8), int(v * 1.2))

    return (None, None)


def _parse_size(text: Optional[str]) -> Optional[str]:
    if not text:
        return None
    m = re.search(r"\bsize\s*([0-9]{2})\b", text.lower())
    if m:
        return m.group(1)
    m = re.search(r"\b([0-9]{2})\b", text)
    if m:
        return m.group(1)
    return None


def _shop_product_url(slug: str) -> str:
    base = os.getenv("SHOP_WEB_BASE_URL", "http://localhost:8080").rstrip("/")
    return f"{base}/shop/products/{slug}"


def _clean_search_query(text: str) -> Optional[str]:
    t = (text or "").lower().strip()
    if not t:
        return None

    # Remove common filler words
    t = re.sub(
        r"\b(tìm|tim|cho|mình|toi|tôi|m\u00ecnh|gi\u00fay|giup|xem|g\u1ee3i\s*y|g\u1ee3i|t\u01b0\s*v\u1ea5n|tu\s*van|shop|c\u00f3|kh\u00f4ng|khong|c\u1ea7n|can|mu\u1ed1n|muon|th\u00edch|thich|mua|gi\u00e1|gia)\b",
        " ",
        t,
    )
    t = re.sub(r"\bgi\u00e0y\b", " ", t)
    t = re.sub(r"\bsize\s*[0-9]{2}\b", " ", t)
    t = re.sub(
        r"(d\u01b0\u1edbi|duoi|tr\u00ean|tren|t\u1ea7m|kho\u1ea3ng|t\u1eeb|tu|t\u1edbi|toi|\u0111\u1ebfn|den)\s*[0-9]+(?:[.,][0-9]+)?\s*(tri\u1ec7u|trieu|tr|m|k|ngh\u00ecn|nghin)?",
        " ",
        t,
    )
    t = re.sub(r"\b[0-9]+(?:[.,][0-9]+)?\b", " ", t)

    t = re.sub(r"\s+", " ", t).strip()
    return t or None


def _infer_brand_from_text(text: Optional[str]) -> Optional[str]:
    t = (text or "").strip().lower()
    if not t:
        return None
    try:
        facets = _fetch_facets()
        brands = facets.get("brands") or []
    except Exception:
        brands = []

    for b in brands:
        name = str(b.get("name") or "").strip()
        slug = str(b.get("slug") or "").strip()
        if not name and not slug:
            continue
        if (name and name.lower() in t) or (slug and slug.lower() in t):
            # Use name as it works well with backend search
            return name or slug
    return None


def _get_entity(entities: List[Dict[Text, Any]], name: str) -> Optional[str]:
    for e in entities or []:
        if e.get("entity") == name and e.get("value") is not None:
            return str(e.get("value"))
    return None


def _fetch_products(
    *,
    search: Optional[str],
    size: Optional[str],
    price_range: Optional[str],
    category_ids: Optional[List[int]] = None,
    limit: int = 5,
) -> List[Dict[Text, Any]]:
    api = os.getenv("SHOP_API_BASE_URL", "http://nginx").rstrip("/")
    min_vnd, max_vnd = _parse_budget_text_to_range(price_range)

    params: Dict[str, Any] = {"per_page": 12, "sort": "popular"}
    if search:
        params["search"] = search
    if size:
        params["size"] = size
    if category_ids:
        # backend supports scalar or array; use array for consistency
        params["category"] = category_ids
    if min_vnd is not None:
        params["price_min"] = min_vnd
    if max_vnd is not None:
        params["price_max"] = max_vnd

    res = requests.get(f"{api}/api/v1/products", params=params, timeout=8)
    res.raise_for_status()
    payload = res.json()
    items = payload.get("data") if isinstance(payload, dict) else None
    if not isinstance(items, list):
        return []
    return items[:limit]


def _infer_category_ids_from_text(text: Optional[str]) -> List[int]:
    t = (text or "").strip().lower()
    if not t:
        return []
    try:
        facets = _fetch_facets()
        cats = facets.get("categories") or []
    except Exception:
        cats = []

    out: List[int] = []
    for c in cats:
        name = str(c.get("name") or "").lower()
        slug = str(c.get("slug") or "").lower()
        cid = c.get("id")
        try:
            cid_int = int(cid) if cid is not None else None
        except Exception:
            cid_int = None
        if cid_int is None:
            continue

        # match by substring either way (e.g. "thể thao" in "giày thể thao")
        if t in name or t in slug or name in t or slug in t:
            out.append(cid_int)

    # de-dup
    return list(dict.fromkeys(out))


def _product_to_card(p: Dict[Text, Any]) -> Dict[Text, Any]:
    name = p.get("name") or "Sản phẩm"
    slug = p.get("slug") or ""
    thumb = p.get("thumbnail") or ""

    price_val = p.get("base_sale_price") or p.get("base_price")
    try:
        price_int = int(price_val) if price_val is not None else None
    except Exception:
        price_int = None

    return {
        "name": name,
        "slug": slug,
        "url": _shop_product_url(slug) if slug else os.getenv("SHOP_WEB_BASE_URL", "http://localhost:8080"),
        "thumbnail": thumb,
        "price": price_int,
        "price_text": _format_vnd(price_int) if price_int is not None else "",
    }


def _shop_products_list_url(*, search: Optional[str] = None, category_id: Optional[int] = None) -> str:
    base = os.getenv("SHOP_WEB_BASE_URL", "http://localhost:8080").rstrip("/")
    url = f"{base}/shop/products"
    qs: List[str] = []
    if search:
        qs.append(f"search={requests.utils.quote(str(search))}")
    if category_id is not None:
        qs.append(f"category_id={int(category_id)}")
    return url + (("?" + "&".join(qs)) if qs else "")


def _fetch_facets() -> Dict[Text, Any]:
    api = os.getenv("SHOP_API_BASE_URL", "http://nginx").rstrip("/")
    res = requests.get(f"{api}/api/v1/products/facets", timeout=8)
    res.raise_for_status()
    payload = res.json()
    return payload if isinstance(payload, dict) else {}


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

        api = os.getenv("SHOP_API_BASE_URL", "http://nginx")

        try:

            cat_ids = _infer_category_ids_from_text(purpose)
            # If we can infer category, drop free-text search to avoid over-filtering.
            search_query = None if cat_ids else purpose
            data = _fetch_products(search=search_query, size=size, price_range=price, category_ids=cat_ids or None, limit=5)
            if not data and size:
                # Relax size if too strict
                data = _fetch_products(search=search_query, size=None, price_range=price, category_ids=cat_ids or None, limit=5)
            if not data and price:
                # Relax budget if still empty
                data = _fetch_products(search=search_query, size=None, price_range=None, category_ids=cat_ids or None, limit=5)
        except Exception:
            data = []

        if not data:
            dispatcher.utter_message(
                text="Mình chưa tìm được sản phẩm phù hợp 😢"
            )
            return []

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": "Mình gợi ý một số mẫu phù hợp:",
                "items": [_product_to_card(p) for p in data[:5]],
            }
        )
        dispatcher.utter_message(text="Bạn muốn mình lọc thêm theo thương hiệu hoặc tầm giá cụ thể không?")

        return []


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
        # Keep intent but clean filler words for better backend search matching.
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
        # Keep original text; budget parser extracts min/max later.
        return {"price_range": value.strip() if value else value}


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
        price_range = ent_price_range or text  # budget parser extracts min/max later

        # Extract a cleaner query so backend search matches better
        brand = ent_brand or _infer_brand_from_text(text)
        search = brand or _clean_search_query(text)
        cat_ids = _infer_category_ids_from_text(ent_purpose or text)

        try:
            items = _fetch_products(search=search, size=size, price_range=price_range, category_ids=cat_ids or None, limit=5)
            if not items and size:
                # Relax size constraint if too strict
                items = _fetch_products(search=search, size=None, price_range=price_range, category_ids=cat_ids or None, limit=5)
            if not items and price_range:
                # Relax budget constraint if still empty
                items = _fetch_products(search=search, size=None, price_range=None, category_ids=cat_ids or None, limit=5)
            if not items and cat_ids:
                # If category inferred, drop search noise
                items = _fetch_products(search=None, size=size, price_range=price_range, category_ids=cat_ids, limit=5)
        except Exception:
            items = []

        if not items:
            # If user asked for a specific brand that isn't available, guide them.
            if brand:
                try:
                    facets = _fetch_facets()
                    available = facets.get("brands") or []
                except Exception:
                    available = []

                if available and all(str(b.get("name") or "").strip().lower() != brand.strip().lower() for b in available):
                    # Try suggesting similar products by relaxing brand constraint but keeping other filters.
                    try:
                        similar = _fetch_products(
                            search=None,
                            size=size,
                            price_range=price_range,
                            category_ids=cat_ids or None,
                            limit=5,
                        )
                        if not similar and size:
                            # Relax size first (sizes may not exist in inventory)
                            similar = _fetch_products(
                                search=None,
                                size=None,
                                price_range=price_range,
                                category_ids=cat_ids or None,
                                limit=5,
                            )
                        if not similar and (price_range or cat_ids):
                            # Further relax category (keep price)
                            similar = _fetch_products(
                                search=None,
                                size=None,
                                price_range=price_range,
                                category_ids=None,
                                limit=5,
                            )
                        if not similar and price_range:
                            # Finally relax budget
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

        dispatcher.utter_message(
            json_message={
                "type": "products",
                "title": "Mình tìm được một số mẫu phù hợp:",
                "items": [_product_to_card(p) for p in items[:5]],
            }
        )
        dispatcher.utter_message(text="Bạn muốn mình lọc thêm theo size hoặc tầm giá cụ thể không?")
        return []


class ActionListBrands(Action):
    def name(self) -> Text:
        return "action_list_brands"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        try:
            facets = _fetch_facets()
            brands = facets.get("brands") or []
        except Exception:
            brands = []

        if not brands:
            dispatcher.utter_message(text="Hiện mình chưa tải được danh sách thương hiệu. Bạn thử lại giúp mình nhé.")
            return []

        chips = []
        for b in brands[:12]:
            name = b.get("name") or "Brand"
            chips.append({"label": name, "href": _shop_products_list_url(search=name)})

        dispatcher.utter_message(
            json_message={
                "type": "chips",
                "title": "Các thương hiệu nổi bật (bấm để xem sản phẩm):",
                "items": chips,
            }
        )
        dispatcher.utter_message(text="Bạn thích brand nào, hoặc muốn mình tìm theo tầm giá/size?")
        return []


class ActionListCategories(Action):
    def name(self) -> Text:
        return "action_list_categories"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Dict[Text, Any]]:
        try:
            facets = _fetch_facets()
            categories = facets.get("categories") or []
        except Exception:
            categories = []

        if not categories:
            dispatcher.utter_message(text="Hiện mình chưa tải được danh mục sản phẩm. Bạn thử lại giúp mình nhé.")
            return []

        chips = []
        for c in categories[:12]:
            name = c.get("name") or "Danh mục"
            cid = c.get("id")
            try:
                cid_int = int(cid) if cid is not None else None
            except Exception:
                cid_int = None
            chips.append(
                {
                    "label": name,
                    "href": _shop_products_list_url(category_id=cid_int) if cid_int is not None else _shop_products_list_url(search=name),
                }
            )

        dispatcher.utter_message(
            json_message={
                "type": "chips",
                "title": "Danh mục sản phẩm (bấm để xem):",
                "items": chips,
            }
        )
        dispatcher.utter_message(text="Bạn muốn chọn danh mục nào, hay mình tìm nhanh theo nhu cầu (chạy bộ/đi làm...)?")
        return []