# 🥿 Shoe Shop

**Shoe Shop** là website bán giày được xây dựng theo kiến trúc **RESTful API + Single Page Application (SPA)**, tích hợp **chatbot tư vấn giày** dùng **Rasa**.

Dự án sử dụng **Laravel 12** để xây dựng Backend API, **Vue 3** để phát triển Frontend và **Rasa (Pro + SDK)** để xử lý hội thoại/chatbot.
Toàn bộ hệ thống được container hóa bằng **Docker** giúp dễ dàng cài đặt và chạy trên nhiều môi trường khác nhau.

---

# 🚀 Tech Stack

| Technology | Description                           |
| ---------- | ------------------------------------- |
| Laravel 12 | Backend RESTful API                   |
| Vue 3      | Frontend SPA                          |
| Rasa Pro   | Chatbot (NLU + Dialogue)              |
| Rasa SDK   | Custom actions gọi API sản phẩm       |
| Vite       | Frontend build tool & Hot Reload      |
| Docker     | Containerized development environment |
| MySQL 8    | Relational Database                   |
| Nginx      | Web Server                            |
| phpMyAdmin | Database management                   |

---

# 📋 System Requirements

Trước khi chạy project, cần cài đặt các công cụ sau:

* **Docker Desktop** (WSL2 enabled)
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
docker compose run --rm rasa rasa train
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

# 📂 Project Structure

```
shoe-shop
│
├── app/                # Laravel backend
├── bootstrap/
├── config/
├── database/           # Migrations & Seeders
├── docker/             # Docker configuration
├── public/
├── resources/          # Vue frontend
├── routes/             # API routes
├── storage/
├── tests/
│
├── docker-compose.yml
├── vite.config.js
├── package.json
└── README.md
```

---

# ✨ Features

* User authentication
* Product catalog
* Shopping cart
* Order management
* Online payment integration
* Admin dashboard
* Order history
* Customer management

---

# 🧪 Development Commands

Laravel commands:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan route:list
```

Clear cache:

```bash
docker compose exec app php artisan optimize:clear
```

---

# 📌 Access URLs

| Service    | URL                   |
| ---------- | --------------------- |
| Website    | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |

---

# 👨‍💻 Author

**BMC Shoes Project**

Built with ❤️ using:

* Laravel
* Vue
* Docker

---

# 📜 License

This project is for **learning and educational purposes**.
