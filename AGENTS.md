# AGENTS.md

Panduan untuk agen yang bekerja di codebase ini.

## Project Overview

**UPT Lab Terpadu** - Sistem Manajemen Laboratorium untuk pengelolaan pengujian sampel penelitian.

| Layer | Path | Tech |
|---|---|---|
| Frontend | `frontend/` | Vue 3 + TypeScript + Pinia + Vite |
| Backend | `backend/` | Laravel 12 (PHP 8.2+) + SQLite |
| Docs | `docs/` | Dokumentasi lengkap |

## Cara Menjalankan

```bash
npm install && npm run dev          # Run both
npm run dev:frontend                # Frontend only (port: vite default)
npm run dev:backend                 # Backend only (port: 8000)
```

## Dokumentasi

Baca docs ini sebelum mengubah apapun:

| Dokumen | Path |
|---|---|
| Index & Alur Bisnis | `docs/00-index.md` |
| Frontend (komponen, routing, API, types) | `docs/01-frontend.md` |
| Backend (routes, models, controllers, schema) | `docs/02-backend.md` |

## Aturan Penting

### JANGAN

- Jangan asal tambah dependency. Cek `package.json` / `composer.json` dulu.
- Jangan ubah auth flow tanpa memahami `ApiTokenMiddleware`.
- Jangan hapus field di migration tanpa update model fillable.
- Jangan hardcode role string - gunakan constant atau参照 ke `authStore`.
- Jangan bypass route guard tanpa alasan jelas.

### HARUS

- Selalu cek types di `frontend/src/types/index.ts` sebelum ubah interface.
- Selalu cek `api/` modules sebelum tambah endpoint baru.
- Ikuti naming convention: kebab-case untuk route path, camelCase untuk JS, snake_case untuk PHP/DB.
- Update docs setelah perubahan besar.
- Cek relationships di model sebelum query.

## Codebase Quirks

1. **Auth token** - Custom `api_token` di column `users.api_token`, bukan Sanctum/Passport.
2. **Role system** - Hybrid: `users.role` = string column, `roles` table = RBAC management UI. Middleware pakai string.
3. **Superadmin bypass** - Superadmin lewat semua role check di middleware.
4. **No UI library** - Semua styling pakai custom CSS variables di `design-system.css`.
5. **FormData** - Beberapa endpoint pakai multipart (file upload) - jangan set Content-Type manual.
6. **`_method=PUT`** - Update endpoint kadang pakai POST + `_method=PUT` untuk FormData compatibility.

## File yang Sering Diubah

| File | Kapan |
|---|---|
| `frontend/src/views/*.vue` | Tambah/ubah halaman |
| `frontend/src/components/**/*.vue` | Tambah/ubah komponen |
| `frontend/src/api/*.ts` | Tambah/ubah API calls |
| `frontend/src/stores/*.ts` | Tambah/ubah state management |
| `frontend/src/types/index.ts` | Ubah TypeScript interfaces |
| `backend/app/Http/Controllers/Api/*.php` | Tambah/ubah endpoint logic |
| `backend/app/Models/*.php` | Ubah model relationships/casts |
| `backend/routes/api.php` | Tambah/ubah routes |
| `backend/database/migrations/*.php` | Ubah schema |

## Workflow Check

Sebelum commit:
1. Cek tidak ada error di frontend (`npm run dev:frontend` jalan tanpa error)
2. Cek tidak ada error di backend (`php artisan route:list` jalan)
3. Update `docs/` jika ada perubahan struktur
