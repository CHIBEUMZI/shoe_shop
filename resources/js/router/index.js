import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

// Auth
const Login = () => import("../pages/auth/Login.vue");
const Register = () => import("../pages/auth/Register.vue");
const ForgotPassword = () => import("../pages/auth/ForgotPassword.vue");
const ResetPassword = () => import("../pages/auth/ResetPassword.vue");

// Layouts
const ShopLayout = () => import("../pages/shop/Shop.vue");
const AdminLayout = () => import("../pages/admin/AdminDashboard.vue");

// Shop pages - lazy loaded
const ShopHome = () => import("../pages/shop/Home.vue");
const ShopProductList = () => import("../pages/shop/products/ProductList.vue");
const ShopProductDetail = () => import("../pages/shop/products/ProductDetail.vue");
const Cart = () => import("../pages/shop/carts/Cart.vue");
const Checkout = () => import("../pages/shop/orders/Checkout.vue");
const OrderSuccess = () => import("../pages/shop/orders/OrderSuccess.vue");
const MyOrders = () => import("../pages/shop/orders/MyOrders.vue");
const ProfileEdit = () => import("../pages/shop/ProfileEdit.vue");
const Coupons = () => import("../pages/shop/coupons/Coupons.vue");
const MyCoupons = () => import("../pages/shop/coupons/MyCoupons.vue");

// Admin pages - lazy loaded
const ProductList = () => import("../pages/admin/products/ProductList.vue");
const ProductCreate = () => import("../pages/admin/products/ProductCreate.vue");
const ProductEdit = () => import("../pages/admin/products/ProductEdit.vue");
const ProductDetail = () => import("../pages/admin/products/ProductDetail.vue");

const CategoryList = () => import("../pages/admin/categories/CategoryList.vue");
const CategoryCreate = () => import("../pages/admin/categories/CategoryCreate.vue");
const CategoryEdit = () => import("../pages/admin/categories/CategoryEdit.vue");
const CategoryDetail = () => import("../pages/admin/categories/CategoryDetail.vue");

const BrandList = () => import("../pages/admin/brands/BrandList.vue");
const BrandCreate = () => import("../pages/admin/brands/BrandCreate.vue");
const BrandEdit = () => import("../pages/admin/brands/BrandEdit.vue");
const BrandDetail = () => import("../pages/admin/brands/BrandDetail.vue");

const UserList = () => import("../pages/admin/users/UserList.vue");
const UserForm = () => import("../pages/admin/users/UserForm.vue");
const UserDetail = () => import("../pages/admin/users/UserDetail.vue");

const OrderList = () => import("../pages/admin/orders/OrderList.vue");
const OrderDetail = () => import("../pages/admin/orders/OrderDetail.vue");
const Dashboard = () => import("../pages/admin/dashboard/AdminDashboardView.vue");

const BannerList = () => import("../pages/admin/banners/BannerList.vue");
const BannerCreate = () => import("../pages/admin/banners/BannerCreate.vue");
const BannerEdit = () => import("../pages/admin/banners/BannerEdit.vue");

const ReviewsList = () => import("../pages/admin/reviews/ReviewsList.vue");
const ReviewDetail = () => import("../pages/admin/reviews/ReviewDetail.vue");

const CouponList = () => import("../pages/admin/coupons/CouponList.vue");
const CouponCreate = () => import("../pages/admin/coupons/CouponCreate.vue");
const CouponEdit = () => import("../pages/admin/coupons/CouponEdit.vue");
const CouponDetail = () => import("../pages/admin/coupons/CouponDetail.vue");

const routes = [
  { path: "/", redirect: "/shop" },

  { path: "/login", component: Login, meta: { guestOnly: true } },
  { path: "/register", component: Register, meta: { guestOnly: true } },
  { path: "/forgot-password", component: ForgotPassword, meta: { guestOnly: true } },
  { path: "/reset-password", component: ResetPassword, meta: { guestOnly: true } },

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