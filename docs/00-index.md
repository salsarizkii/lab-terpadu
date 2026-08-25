# LAB TERPADU - Dokumentasi Proyek

Sistem Manajemen Laboratorium Terpadu (LIMS) untuk pengelolaan pengujian hasil pengujian penelitian.

## 01. Arsitektur

```
┌─────────────────────────────────────────────────────┐
│                    Frontend                         │
│  Vue 3 + TypeScript + Pinia + Vite                 │
│  Port: 5173 (default)                              │
├─────────────────────────────────────────────────────┤
│                    Backend API                      │
│  Laravel 12 (PHP 8.2+)                             │
│  Port: 8000                                        │
├─────────────────────────────────────────────────────┤
│                    Database                         │
│  MySQL (XAMPP) — db_lab_terpadu                    │
└─────────────────────────────────────────────────────┘
```

| Layer | Path | Tech Stack |
|---|---|---|
| Frontend | `frontend/` | Vue 3 + TypeScript + Pinia + Vite |
| Backend | `backend/` | Laravel 12 (PHP 8.2+) + MySQL |
| Docs | `docs/` | Dokumentasi lengkap |

## 02. Cara Menjalankan

```bash
# Install semua dependencies
npm install && npm run dev

# Atau terpisah:
npm run dev:frontend    # Frontend only
npm run dev:backend     # Backend only (port 8000)
```

### Prasyarat
- Node.js 18+
- PHP 8.2+
- MySQL (XAMPP) — buat database `db_lab_terpadu`

### Setup Database
```bash
cd backend
cp .env.example .env
# Edit .env — set DB_DATABASE=db_lab_terpadu
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### Default Akun
| Email | Password | Role |
|---|---|---|
| `superadmin@lab.com` | `password` | superadmin |

> Role `admin`, `analyst`, `user` harus dibuat via AdminRoles UI.

## 03. Dokumentasi

| Dokumen | Isi |
|---|---|
| [01-frontend.md](./01-frontend.md) | Struktur frontend, komponen, routing, state, API layer |
| [02-backend.md](./02-backend.md) | Struktur backend, controller, model, route, middleware |
| [03-database.md](./03-database.md) | Schema lengkap semua tabel + relasi |
| [04-api-reference.md](./04-api-reference.md) | Semua endpoint API detail |
| [05-auth.md](./05-auth.md) | Flow autentikasi, token, middleware |
| [06-business-logic.md](./06-business-logic.md) | Status flow, aturan bisnis |
| [07-form-builder.md](./07-form-builder.md) | Sistem form template dynamic |
| [08-permissions.md](./08-permissions.md) | RBAC — role, permission, akses |
| [09-deployment.md](./09-deployment.md) | Setup & deployment |

## 04. Alur Bisnis (Verifikasi Bertingkat)

```
[User] → [Admin] → [Penyelia] → [Analis] → [Manager Teknis] → [User]
  submit     verif       verif      rekap        verif rekap     bayar
```

1. **Pengajuan** — User membuat & mengirim Berita Acara (form pengajuan lab 20+ field) → `pending`
2. **Verifikasi Admin** — Admin setujui (wajib nomor agenda) → `admin_verified`
3. **Verifikasi Penyelia** — Penyelia setujui → `penyelia_verified`
4. **Rekap Analis** — Analis proses hasil pengujian & simpan rekap → `rekap_ready`
5. **Verifikasi Manager Teknis** — Manager setujui rekap → `completed` (kembali ke user)
6. **Pembayaran & Sertifikat** — User bayar → admin konfirmasi → sertifikat

Setiap verifikator bisa juga menolak (rejected) atau minta revisi (revision → user perbaiki → alur diulang dari admin).

## 05. Role System

| Role | Akses | Keterangan |
|---|---|---|
| `superadmin` | Full access | Bypass semua role check, kelola role/user |
| `admin` | Verifikasi BA (pending→admin_verified), assign analis/penyelia/manager, konfirmasi pembayaran | CRUD form template |
| `penyelia` | Verifikasi BA (admin_verified→penyelia_verified) | Halaman `/penyelia/berita-acara` |
| `analyst` | Lihat tugas, isi hasil pengujian, buat rekap analis | Input pengujian |
| `manager_teknis` | Verifikasi rekap (rekap_ready→completed) | Halaman `/manager-teknis/berita-acara` |
| `user` | Buat pengajuan, bayar setelah completed, download sertifikat | Akses terbatas |

## 06. Codebase Quirks

1. **Auth token** — Custom `api_token` di column `users.api_token`, bukan Sanctum/Passport
2. **Role system** — Hybrid: `users.role` = string column, `roles` table = RBAC management UI
3. **Superadmin bypass** — Superadmin lewat semua role check di middleware
4. **No UI library** — Semua styling pakai custom CSS variables di `design-system.css`
5. **FormData** — Beberapa endpoint pakai multipart (file upload) — jangan set Content-Type manual
6. **`_method=PUT`** — Update endpoint kadang pakai POST + `_method=PUT` untuk FormData compatibility

## 07. File yang Sering Diubah

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

## 08. Naming Convention

| Context | Convention | Contoh |
|---|---|---|
| Route path | kebab-case | `/berita-acara` |
| JavaScript variable | camelCase | `beritaAcara` |
| PHP/DB column | snake_case | `berita_acara_id` |
| PHP/DB table | snake_case (plural) | `berita_acaras` |
| TypeScript interface | PascalCase | `BeritaAcara` |
| Vue component | PascalCase | `BeritaAcaraList.vue` |
