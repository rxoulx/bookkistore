# Dockerized Book Store

Đồ án giữa kỳ Docker. Ứng dụng Laravel quản lý sách với 5 chức năng: danh sách, thêm, xem, sửa và xóa. Trọng tâm là triển khai 3 container và chứng minh dữ liệu PostgreSQL tồn tại sau khi xóa container DB.

## Kiến trúc

```text
Browser → nginx (host port 8080 → container port 80)
                 → web (Laravel, PHP-FPM port 9000)
                 → db (PostgreSQL port 5432)
                         ↓
                  named volume pgdata
```

Ba service `nginx`, `web`, `db` cùng dùng custom bridge network `bookstore-net` và gọi nhau bằng tên service. Source `./web/src` là bind mount. `pgdata` là named volume chứa dữ liệu PostgreSQL. Volume `vendor` giữ thư viện PHP khi source được bind mount vào container.

## Môi trường đã kiểm chứng

- Ubuntu chạy trong VMware
- Docker Engine 29.9.0
- Docker Compose v5.6.0
- PHP 8.2.34
- PostgreSQL image `postgres:16-alpine`
- nginx image `nginx:alpine`

## Chạy dự án

Tại thư mục dự án:

```bash
cp .env.example .env
```

Sửa `DB_PASSWORD` trong `.env` thành mật khẩu muốn dùng. Tạo `APP_KEY`:

```bash
KEY="$(openssl rand -base64 32)"
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$KEY|" .env
unset KEY
```

Build và chạy:

```bash
sudo docker compose config --quiet
sudo docker compose up --build -d
sudo docker compose exec web php artisan migrate --force
sudo docker compose ps
```

Mở `http://localhost:8080` trong trình duyệt của Ubuntu VM.

Lần chạy trên máy mới cần Internet để tải image và cài thư viện PHP. Source code có thể copy hoặc clone sang máy khác rồi build lại. Named volume trên VM cũ không tự đi theo source code.

## Lệnh Docker dùng trong dự án

| Mục đích | Lệnh |
|---|---|
| Xem 3 service | `sudo docker compose ps` |
| Dừng service | `sudo docker compose stop db` |
| Chạy lại service | `sudo docker compose start db` |
| Khởi động lại web | `sudo docker compose restart web` |
| Xóa container DB đã dừng | `sudo docker compose rm -f db` |
| Chạy lệnh trong DB | `sudo docker compose exec db psql -U bookstore_user -d bookstore` |
| Xem log | `sudo docker compose logs web` |
| Xem cấu hình container DB | `sudo docker inspect "$(sudo docker compose ps -q db)"` |
| Xem volume | `sudo docker volume ls` |
| Dừng và xóa container/network của dự án | `sudo docker compose down` |

**Không dùng `docker compose down -v` khi cần giữ dữ liệu sách**, vì `-v` xóa cả volume.

## Kết quả chạy thật trên Ubuntu VM

### 1. Ứng dụng và database chạy được

Lệnh `php artisan migrate --force` chạy thành công, gồm migration `2026_01_01_000000_create_books_table`.

Kiểm tra HTTP:

```text
curl -L -s -o /dev/null -w 'HTTP %{http_code}\n' http://localhost:8080
HTTP 200
```

Sau khi thêm sách qua giao diện, truy vấn PostgreSQL trả về:

```text
id |         title          | price
---+------------------------+-------
 1 | Nhat ky cua khang rong | 666.00
(1 row)
```

### 2. Dừng DB và xác định nguyên nhân lỗi

Đã chạy:

```bash
sudo docker compose stop db
curl -L -s -o /dev/null -w 'HTTP %{http_code}\n' http://localhost:8080
```

Kết quả: `HTTP 500`.

Log Laravel thực tế:

```text
SQLSTATE[08006] [7] could not translate host name "db" to address:
Temporary failure in name resolution
```

Ứng dụng cần truy vấn bảng `books`, nhưng service `db` đã dừng. Đây là lỗi được tạo có chủ đích để kiểm tra cách xác định nguyên nhân hệ thống không hoạt động.

### 3. Xóa container DB nhưng dữ liệu vẫn còn

Đã chạy:

```bash
sudo docker compose rm -f db
sudo docker compose up -d db
sudo docker compose exec db \
  psql -U bookstore_user -d bookstore \
  -c "SELECT id, title, price FROM books;"
```

Container `bookstore-db-1` bị xóa rồi tạo lại. Truy vấn sau đó vẫn trả về sách `Nhat ky cua khang rong`, giá `666.00`, một dòng dữ liệu. HTTP trở lại `200`. Điều này chứng minh dữ liệu nằm trong named volume `pgdata`, ngoài vòng đời container DB.

### 4. Khởi động lại toàn bộ

Đã chạy:

```bash
sudo docker compose down
sudo docker compose up -d
sudo docker compose ps
```

Kết quả: `db` ở trạng thái `healthy`, `web` và `nginx` chạy. HTTP trả `200`; truy vấn PostgreSQL vẫn thấy sách `Nhat ky cua khang rong`.

## Tình huống lỗi và cách xử lý

1. **DB chưa sẵn sàng lúc khởi động:** `db` có `healthcheck`; `web` có `depends_on: condition: service_healthy`, nên Compose chờ DB healthy rồi mới khởi động web. Trong lần cold start đã thử, DB healthy và trang trả HTTP 200.
2. **DB dừng khi hệ thống đang chạy:** trang trả HTTP 500; log thực tế báo không phân giải được tên `db`. Kiểm tra bằng `docker compose ps`, `docker compose logs db` và log Laravel; chạy lại DB bằng `docker compose up -d db`.
3. **Nguy cơ mất dữ liệu khi xóa container DB:** PostgreSQL dùng named volume `pgdata`. Thử xóa và tạo lại container DB đã xác nhận sách vẫn còn. Tránh `down -v` nếu muốn giữ dữ liệu.

Trong lúc cấu hình ban đầu, `APP_KEY` sai độ dài từng làm Laravel trả HTTP 500 với log `Unsupported cipher or incorrect key length`. Tạo lại khóa hợp lệ trong `.env` và tạo lại container `web` đã đưa trang về HTTP 200.

## Mang đến trường

- Nếu dùng chính laptop và Ubuntu VM này: bật VM, vào thư mục dự án, chạy `sudo docker compose up -d`, mở `http://localhost:8080` trong VM. Volume và sách demo vẫn ở VM.
- Nếu dùng máy Ubuntu khác: chuyển source code cùng `.env.example`, tạo `.env` và `APP_KEY` mới theo hướng dẫn trên, rồi `sudo docker compose up --build -d` và chạy migration. Dữ liệu sách ở VM tại nhà không tự chuyển theo; nhập dữ liệu demo mới hoặc xuất/nhập database riêng.
