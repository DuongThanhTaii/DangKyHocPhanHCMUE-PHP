# 🐳 Hướng dẫn chạy Docker

## Yêu cầu
- Docker & Docker Compose
- File `.env` trong `backend_php/` với cấu hình Database

---

## 🚀 Development Mode

```bash
# Chạy development environment
docker compose -f docker-compose.dev.yml up -d

# Xem logs
docker compose -f docker-compose.dev.yml logs -f

# Dừng
docker compose -f docker-compose.dev.yml down
```

**Sau khi chạy:**
- 🌐 Frontend: http://localhost:5173
- ⚙️ Backend API: http://localhost:8000
- 💾 Redis: localhost:6379

---

## 🧪 Testing Mode

```bash
# Chạy test environment
docker compose -f docker-compose.test.yml up -d

# Chạy PHPUnit tests
docker compose -f docker-compose.test.yml exec backend-php php vendor/bin/phpunit

# Dừng
docker compose -f docker-compose.test.yml down -v
```

**Ports test (khác production):**
- 🐘 PostgreSQL: localhost:5433
- 💾 Redis: localhost:6380

---

## 📦 Services

| Service | Dev Port | Test Port | Mô tả |
|---------|----------|-----------|-------|
| Backend PHP | 8000 | - | Laravel API |
| Frontend | 5173 | - | React + Vite |
| Redis | 6379 | 6380 | Cache & Locks |
| PostgreSQL | - | 5433 | Test DB |

---

## 🔧 Commands thường dùng

```bash
# Xem status containers
docker compose -f docker-compose.dev.yml ps

# Vào shell backend
docker compose -f docker-compose.dev.yml exec backend-php bash

# Chạy artisan commands
docker compose -f docker-compose.dev.yml exec backend-php php artisan migrate

# Clear cache
docker compose -f docker-compose.dev.yml exec backend-php php artisan cache:clear

# Rebuild containers
docker compose -f docker-compose.dev.yml up --build -d
```

---

## ⚠️ Troubleshooting

### Port đang được sử dụng
```bash
# Kiểm tra port
lsof -i :8000
lsof -i :5173

# Dừng tất cả containers
docker compose -f docker-compose.dev.yml down
```

### Redis connection issue
```bash
# Restart redis
docker compose -f docker-compose.dev.yml restart redis
```

### Backend không khởi động
```bash
# Xem logs
docker compose -f docker-compose.dev.yml logs backend-php

# Chạy composer install
docker compose -f docker-compose.dev.yml exec backend-php composer install
```
