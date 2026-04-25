# 🥿 BMC Shoes - Website Bán Giày

**BMC Shoes** là website bán giày trực tuyến được xây dựng theo kiến trúc **RESTful API + Single Page Application (SPA)**, tích hợp **chatbot tư vấn giày** thông minh sử dụng **Rasa**.

Dự án sử dụng **Laravel 12** để xây dựng Backend API, **Vue 3** để phát triển Frontend và **Rasa (Pro + SDK)** để xử lý hội thoại/chatbot. Toàn bộ hệ thống được container hóa bằng **Docker** giúp dễ dàng cài đặt và chạy trên nhiều môi trường khác nhau.

---

# 🚀 Tech Stack

| Technology | Description                           |
| ---------- | ------------------------------------- |
| Laravel 12 | Backend RESTful API                   |
| Vue 3      | Frontend SPA (Composition API)        |
| Vite       | Build tool & Hot Reload               |
| Rasa Pro   | Chatbot (NLU + Dialogue)              |
| Rasa SDK   | Custom actions gọi API sản phẩm       |
| MySQL 8    | Relational Database                   |
| Nginx      | Web Server                           |
| Docker     | Containerized development environment|
| phpMyAdmin | Database management                   |
| Mailpit    | Email testing (dev environment)       |

---

# ✨ Tính Năng Đầy Đủ

## 🛒 Dành Cho Khách Hàng

### 1. Xác thực & Tài khoản
- **Đăng ký tài khoản** với email, mật khẩu, ngày sinh, địa chỉ
- **Đăng nhập/Đăng xuất** với session-based authentication
- **Quản lý hồ sơ cá nhân**: cập nhật tên, ngày sinh, địa chỉ
- **Upload avatar** với preview và xóa avatar
- **Đổi mật khẩu** với xác minh mật khẩu hiện tại
- **Khóa tài khoản** bởi admin (is_active)

### 2. Trang chủ & Banner
- **Banner quảng cáo** xoay vòng trên trang chủ (hỗ trợ desktop/mobile)
- **Sản phẩm nổi bật** (Featured Products)
- **Danh mục sản phẩm** với hình ảnh
- **Thương hiệu** nổi bật

### 3. Danh sách & Tìm kiếm sản phẩm
- **Grid sản phẩm** với phân trang
- **Tìm kiếm theo từ khóa** (tên sản phẩm)
- **Bộ lọc đa dạng**:
  - Lọc theo **danh mục** (Category)
  - Lọc theo **thương hiệu** (Brand)
  - Lọc theo **khoảng giá** (Price range)
  - Lọc theo **size giày** (Shoe size)
  - Lọc theo **màu sắc** (Color)
- **Sắp xếp** theo giá, tên, sản phẩm mới
- **Facet Search** - tìm kiếm theo nhiều tiêu chí kết hợp
- **Phân trang** sản phẩm

### 4. Chi tiết sản phẩm
- **Hình ảnh sản phẩm** với gallery nhiều ảnh theo màu
- **Chọn màu sắc** và **kích thước** (biến thể sản phẩm)
- **Giá sản phẩm** với hiển thị giảm giá
- **Thông tin tồn kho** theo từng biến thể
- **Mô tả sản phẩm** chi tiết
- **Đánh giá sản phẩm** với rating và bình luận
- **Thống kê đánh giá**: điểm trung bình, phân bố rating
- **Sản phẩm liên quan**

### 5. Giỏ hàng (Shopping Cart)
- **Thêm sản phẩm** vào giỏ với chọn màu/size
- **Tăng/giảm số lượng** sản phẩm
- **Xóa sản phẩm** khỏi giỏ
- **Xóa toàn bộ** giỏ hàng
- **Hiển thị tổng tiền** theo thời gian thực
- **Kiểm tra tồn kho** trước khi thêm

### 6. Đặt hàng & Thanh toán

#### Quy trình đặt hàng:
- **Nhập thông tin giao hàng**: tên, SĐT, email, địa chỉ, ghi chú
- **Áp dụng mã giảm giá**: nhập và kiểm tra coupon trước khi đặt
- **Chọn phương thức thanh toán**:
  - **COD** (Cash on Delivery) - Thanh toán khi nhận hàng
  - **VNPay** - Thanh toán qua cổng VNPay
  - **MoMo** - Thanh toán qua ví MoMo
- **Tạo đơn hàng** với mã đơn tự động
- **Tự động trừ tồn kho** khi thanh toán thành công

#### Tích hợp thanh toán:
- **VNPay**:
  - Tạo URL thanh toán với HMAC-SHA512 signature
  - Xử lý return URL và IPN (Instant Payment Notification)
  - Hỗ trợ nhiều ngân hàng
- **MoMo**:
  - Tạo payment với signature HMAC-SHA256
  - Hỗ trợ QR Code và Deep Link
  - Xử lý return và IPN callback
- **COD**: Xác nhận đơn và trừ tồn kho

### 7. Theo dõi đơn hàng
- **Danh sách đơn hàng** của tôi với phân trang
- **Chi tiết đơn hàng**: mã, sản phẩm, tổng tiền, trạng thái
- **Trạng thái đơn hàng**:
  - `pending` - Chờ xử lý
  - `confirmed` - Đã xác nhận
  - `paid` - Đã thanh toán
  - `processing` - Đang xử lý
  - `shipping` - Đang giao
  - `completed` - Hoàn thành
  - `cancelled` - Đã hủy
- **Thanh toán lại** nếu thanh toán online thất bại
- **Trang thành công** sau khi đặt hàng

### 8. Đánh giá sản phẩm
- **Viết đánh giá** với rating 1-5 sao và bình luận
- **Chỉ đánh giá 1 lần** cho mỗi sản phẩm
- **Xác minh mua hàng** (Verified Purchase) nếu đã mua sản phẩm
- **Sửa/Xóa** đánh giá của mình
- **Xem đánh giá** của người khác (đã duyệt)
- **Lọc đánh giá** theo rating

### 9. Mã Giảm Giá (Coupons)
- **Khám phá mã giảm giá**: trang danh sách coupon với thống kê
- **Nhận mã giảm giá**: claim coupon về tài khoản
- **Theo dõi mã của tôi**: xem các coupon đã nhận với trạng thái
- **Áp dụng mã**: nhập mã coupon tại checkout
- **Các loại coupon**:
  - `percentage` - Giảm theo % (có giới hạn tối đa)
  - `fixed` - Giảm số tiền cố định
- **Điều kiện sử dụng**:
  - Đơn hàng tối thiểu (min_order_amount)
  - Giới hạn sử dụng (usage_limit, per_user_limit)
  - Thời gian hiệu lực (starts_at, expires_at)
- **Áp dụng theo**: tất cả sản phẩm, danh mục, thương hiệu cụ thể
- **Trạng thái coupon**: đã nhận, đã sử dụng, đã hết hạn, chưa có hiệu lực

### 10. 🤖 Chatbot tư vấn giày (Rasa)
- **Hội thoại tự nhiên** với chatbot
- **Hiểu intent**: tìm kiếm sản phẩm, hỏi giá, hỏi size, khuyến mãi...
- **Trích xuất entity**:
  - `brand` - Thương hiệu (Nike, Adidas...)
  - `purpose` - Mục đích (chạy bộ, đi bộ, bóng rổ...)
  - `shoe_size` - Kích thước giày
  - `price_range` - Khoảng giá
- **Tìm kiếm sản phẩm** theo bộ lọc từ chatbot
- **Hiển thị sản phẩm** dạng card trong chat
- **Gợi ý thương hiệu/danh mục** dạng chips có thể bấm
- **Custom Actions** gọi API Laravel để lấy dữ liệu sản phẩm

---

## 🔧 Dành Cho Quản Trị Viên (Admin)

### 1. Dashboard
- **Tổng quan thống kê**:
  - Doanh thu (và % tăng trưởng)
  - Số đơn hàng (và % tăng trưởng)
  - Số khách hàng mới (và % tăng trưởng)
  - Số sản phẩm (và % tăng trưởng)
- **Biểu đồ doanh thu** theo ngày/tháng
- **Top 5 sản phẩm** bán chạy
- **Đơn hàng gần đây** (5 đơn mới nhất)
- **Phân bố trạng thái đơn hàng** (biểu đồ tròn)
- **Khách hàng mới** đăng ký trong kỳ
- **Bộ lọc thời gian**: 7 ngày, 30 ngày, 12 tháng
- **So sánh** với kỳ trước
- **Cache** dữ liệu 60 giây

### 2. Quản lý Sản phẩm
- **Danh sách sản phẩm** với tìm kiếm và lọc
- **Tạo sản phẩm mới**:
  - Thông tin cơ bản: tên, slug, SKU, mô tả, thumbnail
  - Liên kết thương hiệu và danh mục
  - **Biến thể sản phẩm** (Variants):
    - Màu sắc
    - Kích thước (size)
    - SKU riêng
    - Giá gốc / Giá sale
    - Tồn kho
    - Trạng thái hoạt động
    - Nhiều hình ảnh theo biến thể
  - Sản phẩm nổi bật (featured)
  - Trạng thái (active/inactive)
- **Chỉnh sửa sản phẩm**:
  - Cập nhật thông tin cơ bản
  - Thêm/sửa/xóa biến thể
  - Cập nhật hình ảnh biến thể
  - Tự động tính lại giá cơ bản
- **Xóa sản phẩm**
- **Quản lý tồn kho** theo từng biến thể

### 3. Quản lý Danh mục (Categories)
- **Danh sách danh mục** với hình ảnh
- **Tạo danh mục**: tên, slug, mô tả, hình ảnh, trạng thái
- **Chỉnh sửa danh mục**
- **Xóa danh mục**

### 4. Quản lý Thương hiệu (Brands)
- **Danh sách thương hiệu**
- **Tạo thương hiệu**: tên, slug, logo, mô tả, trạng thái
- **Chỉnh sửa thương hiệu**
- **Xóa thương hiệu**

### 5. Quản lý Banner
- **Danh sách banner** trang chủ
- **Tạo banner**: hình ảnh, tiêu đề, mô tả, liên kết, vị trí
- **Chỉnh sửa banner**
- **Xóa banner**
- **Sắp xếp thứ tự** banner

### 6. Quản lý Đơn hàng
- **Danh sách đơn hàng** với:
  - Tìm kiếm (mã, tên, SĐT, email)
  - Lọc theo trạng thái đơn hàng
  - Lọc theo trạng thái thanh toán
  - Sắp xếp (ngày, giá, trạng thái)
  - Phân trang
- **Chi tiết đơn hàng**:
  - Thông tin khách hàng
  - Danh sách sản phẩm
  - Thông tin thanh toán
  - Trạng thái thanh toán
- **Cập nhật trạng thái đơn hàng**:
  - `pending` → `confirmed` / `cancelled`
  - `confirmed` → `processing` / `cancelled`
  - `processing` → `shipping` / `cancelled`
  - `shipping` → `completed`
- **Tự động trừ tồn kho** khi xác nhận đơn COD
- **Gửi email thông báo** khi cập nhật trạng thái
- **Kiểm tra quyền** chuyển trạng thái hợp lệ

### 7. Quản lý Đánh giá (Reviews)
- **Danh sách đánh giá** tất cả sản phẩm
- **Xem chi tiết** đánh giá
- **Duyệt đánh giá** (approve/reject)
- **Xóa đánh giá**
- **Lọc theo** sản phẩm, trạng thái duyệt

### 8. Quản lý Người dùng (Users)
- **Danh sách khách hàng**
- **Tìm kiếm** theo tên, email
- **Xem chi tiết** thông tin khách hàng
- **Quản lý tài khoản**:
  - Khóa/Mở khóa tài khoản (is_active)
  - Xem lịch sử đơn hàng của khách

### 9. Quản lý Mã Giảm Giá (Coupons)
- **Danh sách mã giảm giá** với tìm kiếm và lọc
- **Tạo mã giảm giá**:
  - Mã coupon, tên, mô tả
  - Loại: `percentage` (%) hoặc `fixed` (số tiền cố định)
  - Giá trị giảm và giới hạn tối đa (cho %)
  - Đơn hàng tối thiểu
  - Giới hạn sử dụng: tổng số lần và mỗi user
  - Thời gian hiệu lực (bắt đầu và hết hạn)
  - Phạm vi áp dụng: tất cả, sản phẩm, danh mục, thương hiệu cụ thể
  - Trạng thái hoạt động
- **Chỉnh sửa mã giảm giá**
- **Xóa mã giảm giá**

### 10. Upload & Media
- **Upload hình ảnh** sản phẩm
- **Quản lý thư viện media**
- **Hỗ trợ nhiều định dạng** (jpg, png, webp...)
- **Validate kích thước** file

---

# 📋 System Requirements

Trước khi chạy project, cần cài đặt các công cụ sau:

* **Docker Desktop** (WSL2 enabled trên Windows)
* **Node.js ≥ 18**
* **Git**

Kiểm tra cài đặt:

```bash
docker -v
docker compose version
node -v
npm -v
```

---

# 📥 Clone Project

Clone source code từ repository:

```bash
git clone <repository-url>
cd shoe-shop
```

---

# ⚙️ Environment Setup

Tạo file `.env` từ file mẫu:

```bash
cp .env.example .env
```

Mở file `.env` và cấu hình database:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=shoe_shop
DB_USERNAME=user
DB_PASSWORD=123456
```

Cấu hình thanh toán (tùy chọn):

```env
# VNPay
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_TMN_CODE=your_tmn_code
VNPAY_HASH_SECRET=your_hash_secret
VNPAY_RETURN_URL=http://localhost:8080/payment/vnpay/return
VNPAY_FRONTEND_SUCCESS_URL=http://localhost:8080/payment/success
VNPAY_FRONTEND_FAIL_URL=http://localhost:8080/payment/fail

# MoMo
MOMO_ENDPOINT=https://test-payment.momo.vn/v2/gateway/api/create
MOMO_PARTNER_CODE=your_partner_code
MOMO_ACCESS_KEY=your_access_key
MOMO_SECRET_KEY=your_secret_key
MOMO_REDIRECT_URL=http://localhost:8080/payment/momo/return
MOMO_IPN_URL=http://localhost:8080/api/v1/payments/momo/ipn
MOMO_FRONTEND_SUCCESS_URL=http://localhost:8080/payment/success
MOMO_FRONTEND_FAIL_URL=http://localhost:8080/payment/fail

# Rasa Chatbot
RASA_URL=http://rasa:5005/webhooks/rest/webhook
```

---

# 🐳 Start Docker Containers

Build và chạy toàn bộ hệ thống (API + Frontend + Chatbot):

```bash
docker compose up -d --build
```

Kiểm tra container đang chạy:

```bash
docker compose ps
```

Các container cần chạy:

```
app_shoe_shop
db_shoe_shop
queue_shoe_shop
nginx_shoe_shop
phpmyadmin_shoe_shop
rasa_shoe_shop
rasa_actions_shoe_shop
mailpit_shoe_shop
```

---

# ⚙️ Install Laravel

Chạy các lệnh sau bên trong container:

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Nếu có dữ liệu mẫu:

```bash
docker compose exec app php artisan db:seed
```

---

# 🎨 Install Frontend

Chạy trên **máy local (ngoài Docker)**:

```bash
npm install
```

---

# 🔥 Development Mode (Hot Reload)

Chạy frontend với Vite:

```bash
npm run dev
```

Sau đó mở trình duyệt:

```
http://localhost:8080
```

Chatbot trên website sẽ giao tiếp với Rasa qua API Laravel (`/api/v1/chatbot`).

---

# 🚀 Build Production

Build frontend:

```bash
npm run build
```

Sau đó truy cập:

```
http://localhost:8080
```

---

# 🗄 Database Management

Truy cập **phpMyAdmin**:

```
http://localhost:8081
```

Thông tin đăng nhập:

```
Server: db
User: user
Password: 123456
```

---

# 📧 Email Testing

Truy cập **Mailpit** (dev email testing):

```
http://localhost:8025
```

---

# 🔄 Reset Project (Fix lỗi nặng)

Nếu gặp lỗi database hoặc container:

```bash
docker compose down -v

docker compose up -d --build

docker compose exec app composer install
docker compose exec app php artisan migrate

npm install
npm run dev
```

---

# 🤖 Chatbot tư vấn giày (Rasa)

Thư mục chatbot nằm trong `rasa/` và được chạy bằng 2 service:

- `rasa_shoe_shop`: Rasa server (NLU + rules + stories)
- `rasa_actions_shoe_shop`: Rasa SDK action server (gọi API sản phẩm của shop)

## 1. Cấu hình môi trường

Trong file `.env` của Laravel:

```env
# URL Rasa REST webhook (từ bên trong container app)
RASA_URL=http://rasa:5005/webhooks/rest/webhook

# Base URL API + Web cho Rasa actions
SHOP_API_BASE_URL=http://nginx
SHOP_WEB_BASE_URL=http://localhost:8080
```

## 2. Train model Rasa

Khi thay đổi file trong thư mục `rasa/` (`nlu.yml`, `rules.yml`, `stories.yml`, `domain.yml`, `actions/actions.py`...), cần train lại model:

```bash
docker compose exec rasa rasa train
```

Model mới sẽ được lưu trong `rasa/models/*.tar.gz` và tự động được Rasa server load ở lần khởi động tiếp theo.

## 3. Khởi động / restart chatbot

Đảm bảo tất cả service (bao gồm chatbot) đang chạy:

```bash
docker compose up -d
```

Hoặc chỉ restart phần chatbot sau khi sửa code actions:

```bash
docker compose restart rasa rasa-actions
```

## 4. Cách hoạt động tổng quan

- Frontend (`ChatBot.vue`) gọi tới API Laravel:
  - `POST /api/v1/chatbot` với payload: `{ message: "..." }`
- Laravel (`ChatbotController`) proxy request sang Rasa REST channel (`RASA_URL`).
- Rasa:
  - Hiểu intent + entity (brand, purpose, shoe_size, price_range...)
  - Với intent `search_products`, gọi custom actions trong `rasa/actions/actions.py` để:
    - Gọi API sản phẩm (`/api/v1/products`) với các filter phù hợp
    - Trả về message dạng text hoặc `custom` (products, chips) cho frontend
- Frontend render lại thành:
  - tin nhắn text
  - card sản phẩm (ảnh + tên + giá + link)
  - chips brand/category có thể bấm để mở trang `/shop/products?...`

---

# 🏗️ Cấu trúc Database Models

```
Users
├── avatar, name, email, password
├── role (customer/admin)
├── is_active (boolean)
├── birth_date, address
└── orders, reviews, cart

Products
├── name, slug, sku
├── thumbnail, short_description, description
├── base_price, base_sale_price
├── status (active/inactive)
├── is_featured
├── brand_id, categories[]
└── variants[]

ProductVariants
├── product_id
├── color, size
├── sku, price, sale_price
├── stock
├── is_active
└── images[]

ProductVariantImages
├── product_variant_id
├── url
└── sort_order

Categories
├── name, slug
├── description
├── image
└── status

Brands
├── name, slug
├── logo
├── description
└── status

Banners
├── title, subtitle
├── image
├── link
├── position
└── status

Orders
├── user_id, coupon_id, coupon_code
├── code (auto-generated)
├── customer_name, phone, email, address, note
├── items[] (product_id, variant_id, quantity, price)
├── subtotal, shipping_fee, grand_total
├── status (pending/confirmed/processing/shipping/completed/cancelled)
├── payment_method (cod/vnpay/momo)
├── payment_status (pending/paid/failed)
├── paid_at, stock_deducted_at, mail_sent_at
└── payments[]

Payments
├── order_id
├── method, provider
├── status (pending/paid/failed)
├── amount
├── transaction_code
├── response_payload
└── paid_at

Reviews
├── user_id, product_id
├── rating (1-5)
├── comment
├── status (pending/approved/rejected)
├── verified_purchase (boolean)
└── created_at

Cart
├── user_id
└── items[]

CartItems
├── cart_id
├── product_id
├── product_variant_id
└── quantity

Coupons
├── code (unique), name, description
├── type (percentage/fixed)
├── value, max_discount
├── min_order_amount
├── usage_limit, used_count, per_user_limit
├── starts_at, expires_at
├── is_active
├── applicable_to (all/specific_products/specific_categories/specific_brands)
├── applicable_ids (JSON)
└── user_coupons[]

UserCoupons
├── user_id, coupon_id
├── claimed_at
├── used_at
└── coupon[]
```

---

# 📂 Project Structure

```
shoe-shop
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── Auth/           # Auth, Avatar
│   │   │   │   ├── Admin/          # Dashboard, Products, Orders, Users...
│   │   │   │   └── Public/         # Products, Cart, Orders, Reviews...
│   │   ├── Requests/               # Form Request validation
│   │   ├── Resources/              # API Resources (transformers)
│   │   └── Middleware/
│   ├── Models/                     # Eloquent Models
│   ├── Services/                   # Business logic services
│   ├── Mail/                       # Email templates
│   └── Providers/
│
├── bootstrap/
├── config/
├── database/
│   ├── migrations/                 # Database migrations
│   └── seeders/                    # Sample data seeders
│
├── docker/                         # Docker configuration
├── public/
├── rasa/                           # Chatbot Rasa
│   ├── actions/
│   │   └── actions.py              # Custom actions
│   ├── data/
│   │   ├── nlu.yml                 # NLU training data
│   │   ├── rules.yml               # Rules
│   │   └── stories.yml             # Stories
│   ├── domain.yml                  # Domain
│   └── models/                     # Trained models
│
├── resources/
│   └── js/
│       ├── components/
│       │   ├── shop/               # Shop components
│       │   └── admin/              # Admin components
│       ├── pages/
│       │   ├── shop/               # Shop pages
│       │   ├── admin/              # Admin pages
│       │   └── auth/               # Auth pages
│       ├── composables/            # Vue composables
│       └── router/                 # Vue Router
│
├── routes/
│   └── api.php                     # API routes
│
├── storage/
├── tests/
│
├── docker-compose.yml
├── vite.config.js
├── package.json
└── README.md
```

---

# 🌐 API Endpoints

## Authentication
| Method | Endpoint                    | Description              |
|--------|----------------------------|--------------------------|
| POST   | /api/v1/auth/register      | Đăng ký                 |
| POST   | /api/v1/auth/login         | Đăng nhập               |
| POST   | /api/v1/auth/logout        | Đăng xuất               |
| GET    | /api/v1/auth/me            | Thông tin user hiện tại |
| PUT    | /api/v1/auth/profile       | Cập nhật hồ sơ          |
| PUT    | /api/v1/auth/password      | Đổi mật khẩu            |
| POST   | /api/v1/auth/avatar        | Upload avatar           |
| DELETE | /api/v1/auth/avatar        | Xóa avatar              |

## Products (Public)
| Method | Endpoint                           | Description              |
|--------|-----------------------------------|--------------------------|
| GET    | /api/v1/products                  | Danh sách sản phẩm       |
| GET    | /api/v1/products/{slug}           | Chi tiết sản phẩm       |
| GET    | /api/v1/products/facets           | Tìm kiếm facet          |

## Categories & Brands (Public)
| Method | Endpoint                    | Description              |
|--------|----------------------------|--------------------------|
| GET    | /api/v1/categories         | Danh sách danh mục      |
| GET    | /api/v1/brands             | Danh sách thương hiệu   |
| GET    | /api/v1/banners            | Danh sách banner        |

## Cart & Orders
| Method | Endpoint                           | Description              |
|--------|-----------------------------------|--------------------------|
| GET    | /api/v1/cart                      | Xem giỏ hàng            |
| POST   | /api/v1/cart/items                | Thêm vào giỏ            |
| PUT    | /api/v1/cart/items/{id}           | Cập nhật số lượng       |
| DELETE | /api/v1/cart/items/{id}           | Xóa khỏi giỏ            |
| DELETE | /api/v1/cart                      | Xóa toàn bộ giỏ        |
| GET    | /api/v1/orders                   | Danh sách đơn hàng      |
| GET    | /api/v1/orders/{id}              | Chi tiết đơn hàng       |
| POST   | /api/v1/orders                   | Tạo đơn hàng            |
| POST   | /api/v1/orders/{id}/payment      | Tạo thanh toán online   |

## Reviews
| Method | Endpoint                           | Description              |
|--------|-----------------------------------|--------------------------|
| GET    | /api/v1/products/{id}/reviews     | Danh sách đánh giá      |
| POST   | /api/v1/reviews                  | Tạo đánh giá            |
| GET    | /api/v1/reviews/mine             | Đánh giá của tôi        |
| GET    | /api/v1/products/{id}/reviews/stats | Thống kê đánh giá    |

## Chatbot
| Method | Endpoint                    | Description              |
|--------|----------------------------|--------------------------|
| POST   | /api/v1/chatbot            | Gửi tin nhắn chatbot    |

## Coupons (Public)
| Method | Endpoint                    | Description              |
|--------|----------------------------|--------------------------|
| GET    | /api/v1/coupons/available  | Danh sách coupon khả dụng |
| POST   | /api/v1/coupons/validate   | Kiểm tra & tính giảm giá |
| POST   | /api/v1/coupons/claim      | Nhận mã giảm giá        |
| GET    | /api/v1/coupons/mine       | Coupon của tôi           |

## Admin - Dashboard
| Method | Endpoint                    | Description              |
|--------|----------------------------|--------------------------|
| GET    | /api/v1/admin/dashboard    | Thống kê dashboard      |

## Admin - Products
| Method | Endpoint                           | Description              |
|--------|-----------------------------------|--------------------------|
| GET    | /api/v1/admin/products            | Danh sách sản phẩm      |
| POST   | /api/v1/admin/products            | Tạo sản phẩm            |
| GET    | /api/v1/admin/products/{id}       | Chi tiết sản phẩm      |
| PUT    | /api/v1/admin/products/{id}      | Cập nhật sản phẩm      |
| DELETE | /api/v1/admin/products/{id}      | Xóa sản phẩm            |

## Admin - Categories & Brands
| Method | Endpoint                           | Description              |
|--------|-----------------------------------|--------------------------|
| GET    | /api/v1/admin/categories          | Danh sách danh mục      |
| POST   | /api/v1/admin/categories          | Tạo danh mục            |
| PUT    | /api/v1/admin/categories/{id}    | Cập nhật danh mục      |
| DELETE | /api/v1/admin/categories/{id}    | Xóa danh mục            |
| GET    | /api/v1/admin/brands             | Danh sách thương hiệu   |
| POST   | /api/v1/admin/brands             | Tạo thương hiệu         |
| PUT    | /api/v1/admin/brands/{id}        | Cập nhật thương hiệu   |
| DELETE | /api/v1/admin/brands/{id}        | Xóa thương hiệu         |

## Admin - Orders
| Method | Endpoint                           | Description              |
|--------|-----------------------------------|--------------------------|
| GET    | /api/v1/admin/orders              | Danh sách đơn hàng      |
| GET    | /api/v1/admin/orders/{id}         | Chi tiết đơn hàng       |
| PUT    | /api/v1/admin/orders/{id}/status | Cập nhật trạng thái    |

## Admin - Reviews & Users
| Method | Endpoint                           | Description              |
|--------|-----------------------------------|--------------------------|
| GET    | /api/v1/admin/reviews             | Danh sách đánh giá      |
| PUT    | /api/v1/admin/reviews/{id}        | Cập nhật đánh giá      |
| DELETE | /api/v1/admin/reviews/{id}        | Xóa đánh giá            |
| GET    | /api/v1/admin/users               | Danh sách khách hàng    |
| GET    | /api/v1/admin/users/{id}          | Chi tiết khách hàng     |
| PUT    | /api/v1/admin/users/{id}          | Cập nhật khách hàng    |

## Admin - Coupons
| Method | Endpoint                           | Description              |
|--------|-----------------------------------|--------------------------|
| GET    | /api/v1/admin/coupons             | Danh sách mã giảm giá  |
| POST   | /api/v1/admin/coupons             | Tạo mã giảm giá        |
| GET    | /api/v1/admin/coupons/{id}       | Chi tiết mã giảm giá  |
| PUT    | /api/v1/admin/coupons/{id}       | Cập nhật mã giảm giá  |
| DELETE | /api/v1/admin/coupons/{id}       | Xóa mã giảm giá        |

## Admin - Banners & Upload
| Method | Endpoint                           | Description              |
|--------|-----------------------------------|--------------------------|
| GET    | /api/v1/admin/banners              | Danh sách banner        |
| POST   | /api/v1/admin/banners              | Tạo banner              |
| PUT    | /api/v1/admin/banners/{id}         | Cập nhật banner         |
| DELETE | /api/v1/admin/banners/{id}        | Xóa banner               |
| POST   | /api/v1/admin/upload/image         | Upload hình ảnh         |

---

# 🧪 Development Commands

Laravel commands:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan route:list
docker compose exec app php artisan make:model ModelName
docker compose exec app php artisan make:controller ControllerName
```

Clear cache:

```bash
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan cache:clear
```

Rasa commands:

```bash
# Train model
docker compose run --rm rasa rasa train

# Run shell for testing
docker compose run --rm rasa rasa shell

# Evaluate model
docker compose run --rm rasa rasa test
```

---

# 📌 Access URLs

| Service    | URL                   |
| ---------- | --------------------- |
| Website    | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| Mailpit    | http://localhost:8025 |
| API Base   | http://localhost:8080/api/v1 |

---

# 👨‍💻 Tài khoản Demo

## Admin
```
Email: admin@gmail.com
Password: 123456
```

## Customer
```
Email: customer@example.com
Password: 123456
```

---

# 📜 License

This project is for **learning and educational purposes**.

---

Built with ❤️ using **Laravel**, **Vue 3**, **Rasa**, and **Docker**.
