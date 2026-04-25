"""List-related actions for brands and categories."""
from typing import Any, Dict, List, Text

from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher

from .api_client import _fetch_facets, _shop_products_list_url


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
