# 🥿 Shoe Shop

**Shoe Shop** là website bán giày được xây dựng theo kiến trúc **RESTful API + Single Page Application (SPA)**.

Dự án sử dụng **Laravel 12** để xây dựng Backend API và **Vue 3** để phát triển Frontend.
Toàn bộ hệ thống được container hóa bằng **Docker** giúp dễ dàng cài đặt và chạy trên nhiều môi trường khác nhau.

---

# 🚀 Tech Stack

| Technology | Description                           |
| ---------- | ------------------------------------- |
| Laravel 12 | Backend RESTful API                   |
| Vue 3      | Frontend SPA                          |
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

Build và chạy toàn bộ hệ thống:

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
pma_shoe_shop
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

**Shoe Shop Project**

Built with ❤️ using:

* Laravel
* Vue
* Docker

---

# 📜 License

This project is for **learning and educational purposes**.
