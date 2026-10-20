# UPT Lab Terpadu

Sistem Manajemen Laboratorium — pengelolaan pengajuan pengujian, berita acara, rekap analis, verifikasi berjenjang (admin → penyelia → manager teknis), pembayaran, diskon, hingga sertifikat.

| Layer | Path | Teknologi |
|---|---|---|
| Frontend | `frontend/` | Vue 3 + TypeScript + Pinia + Vite |
| Backend | `backend/` | Laravel 12 (PHP 8.2+) |
| Database | — | MySQL 8 (Docker) / SQLite (dev lokal) |
| Docker | `docker-compose.yml`, `docker/` | Nginx + PHP-FPM + MySQL + Chromium |

---

## Deploy dengan Docker (cara tercepat)

Prasyarat: Docker Engine 24+ dengan Docker Compose v2.

```bash
# 1. Clone
git clone https://github.com/salsarizkii/lab-terpadu.git lab-terpadu
cd lab-terpadu

# 2. (Opsional) Buat .env — semua punya default, jadi bisa langsung lewat
cp .env.example .env

# 3. Build & jalankan
docker compose up -d --build
```

Setelah selesai:

- Aplikasi: `http://localhost` (ubah `APP_PORT` di `.env` jika port 80 dipakai)
- Container pertama kali otomatis: generate `APP_KEY`, buat tabel (`migrate`), dan jalankan seeder (roles, permission, harga instrumen, form template, akun demo)
- MySQL tersimpan di volume `mysql_data`, upload file di volume `backend_storage` (aman saat `down`/`up` ulang)

Akun demo bawaan seeder (password semua: `password`):

| Role | Email |
|---|---|
| Superadmin | `superadmin@lab.com` |
| Admin | `admin@lab.com` |
| Analis | `analyst@lab.com` |
| User | `user@lab.com` |
| Penyelia | `penyelia@lab.com` |
| Manager Teknis | `manager@lab.com` |

> Catatan: produksi sebaiknya hapus akun demo setelah seed pertama, atau tambahkan `APP_SEED` guard sesuai kebutuhan.

### Update versi

```bash
git pull
docker compose up -d --build
```

### Log & backup

```bash
docker compose logs -f backend frontend
# Backup database MySQL:
docker compose exec mysql sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" db_lab_terpadu' > backup.sql
```

### Troubleshooting Docker

| Gejala | Solusi |
|---|---|
| Port 80 sudah dipakai | Set `APP_PORT=8080` di `.env`, akses `http://localhost:8080` |
| `APP_KEY` error / aplikasi lambat pertama kali | Tunggu entrypoint backend selesai: `docker compose logs -f backend` |
| Ingin reset total | `docker compose down -v` lalu `docker compose up -d --build` (semua data terhapus) |

---

## Development lokal

Prasyarat: Node 20+, PHP 8.2+, Composer.

```bash
# 1. Backend
cd backend
cp .env.example .env

# Buat file database SQLite (default DB_CONNECTION=sqlite)
# Linux/macOS:
touch database/database.sqlite
# Windows PowerShell:
# New-Item -ItemType File database/database.sqlite

composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
cd ..

# 2. Frontend + backend (dev server ganda)
npm install
npm run dev            # frontend (vite) + backend (artisan serve, port 8000)
```

Atau jalankan terpisah: `npm run dev:frontend` / `npm run dev:backend`.

Kredensial akun demo sama dengan seeder di atas.

---

## Dokumentasi

| Dokumen | Path |
|---|---|
| Index & alur bisnis | `docs/00-index.md` |
| Frontend | `docs/01-frontend.md` |
| Backend | `docs/02-backend.md` |
| Manual penggunaan | `docs/MANUAL-PENGGUNAAN.md` |

## Struktur penting

```
├── backend/          # Laravel API
│   └── app/Http/Controllers/Api/
├── frontend/         # Vue 3 SPA
├── docker/           # Dockerfile + nginx + entrypoint
├── file/             # Template dokumen .docx (berita acara, lembar kendali, dll)
├── docs/             # Dokumentasi
└── docker-compose.yml
```