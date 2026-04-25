"""API client functions for Rasa actions."""
import os
from typing import Any, Dict, List, Optional, Text

import requests

from .utils import _parse_budget_text_to_range, _parse_size


def _shop_product_url(slug: str) -> str:
    base = os.getenv("SHOP_WEB_BASE_URL", "http://localhost:8080").rstrip("/")
    return f"{base}/shop/products/{slug}"


def _shop_products_list_url(*, search: Optional[str] = None, category_id: Optional[int] = None) -> str:
    base = os.getenv("SHOP_WEB_BASE_URL", "http://localhost:8080").rstrip("/")
    url = f"{base}/shop/products"
    qs: List[str] = []
    if search:
        qs.append(f"search={requests.utils.quote(str(search))}")
    if category_id is not None:
        qs.append(f"category_id={int(category_id)}")
    return url + (("?" + "&".join(qs)) if qs else "")


def _product_to_card(p: dict) -> dict:
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


def _format_vnd(amount: Optional[int]) -> str:
    if amount is None:
        return ""
    return f"{amount:,}".replace(",", ".") + "đ"


def _fetch_facets() -> dict:
    api = os.getenv("SHOP_API_BASE_URL", "http://nginx").rstrip("/")
    res = requests.get(f"{api}/api/v1/products/facets", timeout=8)
    res.raise_for_status()
    payload = res.json()
    return payload if isinstance(payload, dict) else {}


def _fetch_products(
    *,
    search: Optional[str],
    size: Optional[str],
    price_range: Optional[str],
    category_ids: Optional[List[int]] = None,
    limit: int = 5,
) -> List[dict]:
    api = os.getenv("SHOP_API_BASE_URL", "http://nginx").rstrip("/")
    min_vnd, max_vnd = _parse_budget_text_to_range(price_range)

    params: Dict[str, Any] = {"per_page": 12, "sort": "popular"}
    if search:
        params["search"] = search
    if size:
        params["size"] = size
    if category_ids:
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


def _infer_brand_from_text(text: Optional[str]) -> Optional[str]:
    from typing import Dict, Text

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
            return name or slug
    return None


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

        if t in name or t in slug or name in t or slug in t:
            out.append(cid_int)

    return list(dict.fromkeys(out))


def _infer_occasion_from_text(text: Optional[str]) -> Optional[str]:
    if not text:
        return None

    t = (text or "").strip().lower()

    gift_birthday_keywords = ["sinh nhật", "birthday", "bday", "sn", "quà sinh nhật", "tặng sn", "mừng sn", "mừng birthday", "sinh nhật bạn gái", "sinh nhật người yêu", "sn bạn gái", "sn người yêu"]
    gift_anniversary_keywords = ["kỷ niệm", "anniversary", " anniversary", "ngày kỷ niệm", "tặng kỷ niệm", "ở bên nhau", "kỷ niệm ngày cưới", "ngày cưới"]
    gift_general_keywords = ["tặng quà", "quà tặng", "tặng người yêu", "tặng bạn", "tặng mẹ",
                              "tặng cha", "tặng ông bà", "tặng anh trai", "tặng em gái",
                              "tặng crush", "tặng sếp", "tặng thầy cô", "quà", "tặng", "mua tặng"]

    if any(kw in t for kw in gift_birthday_keywords):
        return "birthday_gift"
    if any(kw in t for kw in gift_anniversary_keywords):
        return "anniversary_gift"
    if any(kw in t for kw in gift_general_keywords):
        return "gift"

    # Valentine keywords - expanded to include product descriptions
    valentine_keywords = [
        "valentine", "valentine's", "lễ tình nhân", "ngày lễ tình nhân", "ngày tình nhân",
        "ngày 14/2", "14/2", "lễ tình", "tặng người yêu", "vợ", "chồng",
        "người yêu", "bạn trai", "bạn gái", "yêu", "tình", "romantic",
        "quà tặng người yêu", "quà cho người yêu", "valentine's day"
    ]
    if any(kw in t for kw in valentine_keywords):
        return "valentine"

    interview_keywords = ["phỏng vấn", "xin việc", "công sở", "văn phòng", "đi làm",
                         "đi họp", "quan trọng", "formal", "đi làm", "đi làm hàng ngày"]
    if any(kw in t for kw in interview_keywords):
        return "interview"

    casual_keywords = ["dạo phố", "đi chơi", "cuối tuần", "thường ngày", "casual",
                       "đi cafe", "mall", "walking", "cafe", "phố", "đi chơi thôi",
                       "đi xem phim", "hẹn hò", "đi chơi", "ra phố", "năng động"]
    if any(kw in t for kw in casual_keywords):
        return "casual"

    travel_keywords = ["du lịch", "phượt", "đi xa", "resort", "biển", "trekking",
                       "travel", "tour", "nghỉ mát", "đi biển", "vacation"]
    if any(kw in t for kw in travel_keywords):
        return "travel"

    party_keywords = ["tiệc", "party", "club", "bar", "đi bar", "đêm", "sang trọng",
                      "đi tiệc", "đi club", "đi party", "nightout", "concert", "festival", "đi concert"]
    if any(kw in t for kw in party_keywords):
        return "party"

    sports_keywords = ["chạy bộ", "tập gym", "đá bóng", "thể thao", "running",
                      "yoga", "cardio", "cầu lông", "tennis", "bóng rổ", "gym", "crossfit"]
    if any(kw in t for kw in sports_keywords):
        # Check specific sports first (more specific = higher priority)
        if any(kw in t for kw in ["đá bóng", "bóng đá", "football"]):
            return "football"
        if any(kw in t for kw in ["chạy bộ", "running", "jogging", "marathon"]):
            return "running"
        if any(kw in t for kw in ["gym", "tập gym", "crossfit", "fitness", "nâng tạ", "workout"]):
            return "gym"
        return "sports"

    return None


def _fetch_products_by_occasion(occasion: str, limit: int = 5, price_range: Optional[str] = None) -> List[dict]:
    from .constants import OCCASION_SCENE_MAP

    api = os.getenv("SHOP_API_BASE_URL", "http://nginx").rstrip("/")

    scene = OCCASION_SCENE_MAP.get(occasion, {})
    effective_price_range = price_range if price_range else scene.get("price_range", None)
    style_keywords = scene.get("style_keywords", [])

    params: Dict[str, Any] = {
        "per_page": 12,
        "sort": "popular",
    }

    min_vnd, max_vnd = _parse_budget_text_to_range(effective_price_range)
    if min_vnd is not None:
        params["price_min"] = min_vnd
    if max_vnd is not None:
        params["price_max"] = max_vnd

    def _get_items(p: dict) -> List[dict]:
        try:
            res = requests.get(f"{api}/api/v1/products", params=p, timeout=8)
            res.raise_for_status()
            payload = res.json()
            items = payload.get("data") if isinstance(payload, dict) else None
            return items if isinstance(items, list) else []
        except Exception:
            return []

    def _get_items_flexible(base_params: dict, search_term: Optional[str] = None) -> List[dict]:
        """Try with search term, fall back to no search if needed."""
        if search_term:
            items = _get_items({**base_params, "search": search_term})
            if items:
                return items
        return []

    try:
        # Try 1: occasion filter
        items = _get_items({**params, "occasion": [occasion]})
        if items:
            return items[:limit]

        # Try 2: style_keywords search (from constants)
        for kw in style_keywords:
            items = _get_items({**params, "search": kw})
            if items:
                return items[:limit]

        # Try 3: occasion name as search term
        items = _get_items({**params, "search": occasion})
        if items:
            return items[:limit]

        # Try 4: Relax price constraint - no price filter
        if min_vnd is not None or max_vnd is not None:
            params_no_price = {k: v for k, v in params.items() if k not in ("price_min", "price_max")}

            items = _get_items({**params_no_price, "occasion": [occasion]})
            if items:
                return items[:limit]

            for kw in style_keywords:
                items = _get_items({**params_no_price, "search": kw})
                if items:
                    return items[:limit]

        # Try 5: Relax occasion filter - no occasion, no price
        params_relaxed = {k: v for k, v in params.items() if k not in ("price_min", "price_max")}
        for kw in style_keywords:
            items = _get_items({**params_relaxed, "search": kw})
            if items:
                return items[:limit]

        # Try 6: Just popular products sorted by popularity
        items = _get_items({"per_page": 12, "sort": "popular"})
        if items:
            return items[:limit]

        return []
    except Exception:
        return []
