import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

// Auth
import Login from "../pages/auth/Login.vue";
import Register from "../pages/auth/Register.vue";

// Layouts
import ShopLayout from "../pages/shop/Shop.vue";
import AdminLayout from "../pages/admin/AdminDashboard.vue";

// Shop pages
import ShopHome from "../pages/shop/Home.vue";
import ShopProductList from "../pages/shop/products/ProductList.vue";
import ShopProductDetail from "../pages/shop/products/ProductDetail.vue";
import Cart from "../pages/shop/carts/Cart.vue";
import Checkout from "../pages/shop/orders/Checkout.vue";
import OrderSuccess from "../pages/shop/orders/OrderSuccess.vue";
import MyOrders from "../pages/shop/orders/MyOrders.vue";
import ProfileEdit from "../pages/shop/ProfileEdit.vue";
import Coupons from "../pages/shop/coupons/Coupons.vue";
import MyCoupons from "../pages/shop/coupons/MyCoupons.vue";

// Admin pages
import ProductList from "../pages/admin/products/ProductList.vue";
import ProductCreate from "../pages/admin/products/ProductCreate.vue";
import ProductEdit from "../pages/admin/products/ProductEdit.vue";
import ProductDetail from "../pages/admin/products/ProductDetail.vue";

import CategoryList from "../pages/admin/categories/CategoryList.vue";
import CategoryCreate from "../pages/admin/categories/CategoryCreate.vue";
import CategoryEdit from "../pages/admin/categories/CategoryEdit.vue";
import CategoryDetail from "../pages/admin/categories/CategoryDetail.vue";

import BrandList from "../pages/admin/brands/BrandList.vue";
import BrandCreate from "../pages/admin/brands/BrandCreate.vue";
import BrandEdit from "../pages/admin/brands/BrandEdit.vue";
import BrandDetail from "../pages/admin/brands/BrandDetail.vue";

import UserList from "../pages/admin/users/UserList.vue";
import UserForm from "../pages/admin/users/UserForm.vue";
import UserDetail from "../pages/admin/users/UserDetail.vue";

import OrderList from "../pages/admin/orders/OrderList.vue";
import OrderDetail from "../pages/admin/orders/OrderDetail.vue";
import Dashboard from "../pages/admin/dashboard/AdminDashboardView.vue";

import BannerList from "../pages/admin/banners/BannerList.vue";
import BannerCreate from "../pages/admin/banners/BannerCreate.vue";
import BannerEdit from "../pages/admin/banners/BannerEdit.vue";

import ReviewsList from "../pages/admin/reviews/ReviewsList.vue";
import ReviewDetail from "../pages/admin/reviews/ReviewDetail.vue";

import CouponList from "../pages/admin/coupons/CouponList.vue";
import CouponCreate from "../pages/admin/coupons/CouponCreate.vue";
import CouponEdit from "../pages/admin/coupons/CouponEdit.vue";
import CouponDetail from "../pages/admin/coupons/CouponDetail.vue";

const routes = [
  { path: "/", redirect: "/shop" },

  { path: "/login", component: Login, meta: { guestOnly: true } },
  { path: "/register", component: Register, meta: { guestOnly: true } },

  // SHOP
  {
    path: "/shop",
    component: ShopLayout,
    children: [
      { path: "", component: ShopHome },
      { path: "products", component: ShopProductList },
      { path: "products/:slug", component: ShopProductDetail },
      { path: "cart", component: Cart, meta: { requiresAuth: true } },
      { path: "checkout", component: Checkout },
      { path: "orders/success/:id", component: OrderSuccess },
      { path: "orders", component: MyOrders, meta: { requiresAuth: true } },
      { path: "profile", component: ProfileEdit, meta: { requiresAuth: true } },
      { path: "coupons", component: Coupons },
      { path: "my-coupons", component: MyCoupons, meta: { requiresAuth: true } },
    ],
  },

  // ADMIN
  {
    path: "/admin",
    component: AdminLayout,
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      { path: "", redirect: "/admin/dashboard" },

      { path: "dashboard", component: Dashboard },

      // products
      { path: "products", component: ProductList },
      { path: "products/create", component: ProductCreate },
      { path: "products/:id", component: ProductDetail },
      { path: "products/:id/edit", component: ProductEdit },

      // categories
      { path: "categories", component: CategoryList },
      { path: "categories/create", component: CategoryCreate },
      { path: "categories/:id", component: CategoryDetail },
      { path: "categories/:id/edit", component: CategoryEdit },

      // brands
      { path: "brands", component: BrandList },
      { path: "brands/create", component: BrandCreate },
      { path: "brands/:id", component: BrandDetail },
      { path: "brands/:id/edit", component: BrandEdit },

      // users
      { path: "users", name: "admin.users.list", component: UserList },
      { path: "users/:id", name: "admin.users.view", component: UserDetail },
      { path: "users/:id/edit", name: "admin.users.edit", component: UserForm },

      // orders
      { path: "orders", component: OrderList },
      { path: "orders/:id", component: OrderDetail },

      // banners
      { path: "banners", component: BannerList },
      { path: "banners/create", component: BannerCreate },
      { path: "banners/:id/edit", component: BannerEdit },

      // reviews
      { path: "reviews", component: ReviewsList },
      { path: "reviews/:id", component: ReviewDetail },

      // coupons
      { path: "coupons", component: CouponList },
      { path: "coupons/create", component: CouponCreate },
      { path: "coupons/:id", component: CouponDetail },
      { path: "coupons/:id/edit", component: CouponEdit },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();

  if (!auth.loaded) {
    try {
      await auth.fetchMe();
    } catch (e) {}
  }

  if (to.meta.guestOnly && auth.isLoggedIn) {
    return auth.isAdmin ? "/admin" : "/shop";
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return "/shop";
  }

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { path: "/login", query: { redirect: to.fullPath } };
  }

  return true;
});

export default router;