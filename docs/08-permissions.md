# 08. Permissions & RBAC

Role-Based Access Control — granular permission management.

## Architecture

```
┌──────────┐     ┌──────────────────┐     ┌──────────────┐
│   User   │────<│  role_permission  │>────│  Permission  │
│          │     │                  │     │              │
│ role (FK)│     │ role_id          │     │ id           │
│          │     │ permission_id    │     │ name         │
└────┬─────┘     └──────────────────┘     │ display_name │
     │                                    │ group        │
     │                                    └──────────────┘
     │
     │  users.role = 'admin'  (string column, NOT FK)
     │  roles table = RBAC management UI
     │
     └── Hybrid system: string column + pivot table
```

## Permission List (26)

Sumber: `database/seeders/PermissionsSeeder.php`.

### Group: Umum (1)

| Name | Display Name |
|---|---|
| `dashboard` | Dashboard |

### Group: Berita Acara (5)

| Name | Display Name |
|---|---|
| `berita_acara_create` | Buat Berita Acara |
| `berita_acara_verify` | Verifikasi Berita Acara |
| `berita_acara_assign` | Menugaskan Analis |
| `berita_acara_riwayat_personal` | Personal Riwayat Berita Acara |
| `berita_acara_riwayat_semua` | Riwayat Seluruh Berita Acara |

### Group: Pembayaran (5)

| Name | Display Name |
|---|---|
| `payment_upload` | Upload Bukti Bayar |
| `payment_discount` | Ajukan Diskon Pembayaran |
| `payment_confirm` | Konfirmasi Pembayaran |
| `payment_riwayat_personal` | Personal Riwayat Pembayaran |
| `payment_riwayat_semua` | Riwayat Seluruh Pembayaran |

### Group: Hasil Pengujian (3)

| Name | Display Name |
|---|---|
| `hasil_pengujian_rekap` | Buat Rekap Analis |
| `hasil_pengujian_riwayat_personal` | Personal Riwayat Rekap Analisis |
| `hasil_pengujian_riwayat_semua` | Riwayat Seluruh Rekap Analisis |

### Group: Sertifikat (3)

| Name | Display Name |
|---|---|
| `certificate_view` | Melihat Sertifikat |
| `certificate_download` | Download Sertifikat |
| `certificate_upload` | Upload Sertifikat |

### Group: Pengguna (2)

| Name | Display Name |
|---|---|
| `user_view` | Melihat Pengguna |
| `user_verify` | Verifikasi Pengguna |

### Group: Form (4)

| Name | Display Name |
|---|---|
| `form_manage_berita_acara` | Kelola Form Berita Acara |
| `form_manage_rekap_analis` | Kelola Form Rekap Analis |
| `form_manage_perhitungan_biaya` | Kelola Form Perhitungan Biaya |
| `form_manage_kelola_harga_instrumen` | Kelola Form Harga Instrumen |

### Group: Diskon (3)

| Name | Display Name |
|---|---|
| `discount_verifikasi` | Verifikasi Diskon |
| `discount_riwayat_semua` | Riwayat Seluruh Diskon |
| `discount_riwayat_personal` | Personal Riwayat Diskon |

## Default Role → Permission Mapping

Sumber: `database/seeders/DatabaseSeeder.php` (per-install baru via `migrate:fresh --seed`).

| Role | Permissions |
|---|---|
| `superadmin` | Semua 26 permission (plus bypass middleware) |
| `admin` | `berita_acara_assign`, `berita_acara_create`, `berita_acara_riwayat_personal`, `berita_acara_riwayat_semua`, `berita_acara_verify`, `certificate_download`, `certificate_upload`, `certificate_view`, `dashboard`, `form_manage_berita_acara`, `form_manage_kelola_harga_instrumen`, `form_manage_perhitungan_biaya`, `payment_confirm`, `payment_riwayat_personal`, `payment_riwayat_semua` |
| `penyelia` | `berita_acara_assign`, `berita_acara_riwayat_personal`, `berita_acara_riwayat_semua`, `berita_acara_verify`, `dashboard`, `hasil_pengujian_rekap`, `hasil_pengujian_riwayat_personal`, `hasil_pengujian_riwayat_semua` |
| `manager_teknis` | `berita_acara_riwayat_personal`, `berita_acara_riwayat_semua`, `dashboard`, `hasil_pengujian_rekap`, `hasil_pengujian_riwayat_personal`, `hasil_pengujian_riwayat_semua`, `payment_confirm`, `payment_riwayat_personal`, `payment_riwayat_semua`, `payment_upload` |
| `analyst` | `dashboard`, `form_manage_kelola_harga_instrumen`, `form_manage_perhitungan_biaya`, `form_manage_rekap_analis`, `hasil_pengujian_rekap`, `hasil_pengujian_riwayat_personal` |
| `user` | `dashboard`, `berita_acara_create`, `berita_acara_riwayat_personal`, `hasil_pengujian_riwayat_personal`, `payment_upload`, `payment_discount`, `payment_riwayat_personal`, `discount_riwayat_personal`, `certificate_view`, `certificate_download` |

## Role Management

### Default Roles

| Role | is_system | Users | Keterangan |
|---|---|---|---|
| `superadmin` | true | 1 (seeded) | All (bypass) |
| `admin` | false | seeded | 15 permission (lihat tabel di atas) |
| `analyst` | false | seeded | 6 permission |
| `user` | false | seeded | 10 permission |
| `penyelia` | false | seeded | 8 permission |
| `manager_teknis` | false | seeded | 10 permission |

### Role CRUD Rules

- Hanya superadmin yang bisa mengelola role (`/admin/roles`, `meta.superadminOnly`)
- System roles (`is_system = true`) tidak bisa dihapus
- System roles tidak dapat diubah namanya
- Nama role unik
- Permissions di-sync via pivot `role_permission`

## Superadmin Bypass

```php
// ApiTokenMiddleware
if ($user->role === 'superadmin') {
    return $next($request);  // Skip all role checks
}
```

Superadmin:
- Bisa akses SEMUA routes (admin, analyst, user)
- Bisa mengelola role dan permission
- Tidak bisa dihapus atau diubah rolenya
- Punya akses penuh implisit (tidak bergantung pada permission role)

### Override Verifikasi (Penyelia & Manager Teknis)

`ApiTokenMiddleware`/`PermissionMiddleware` sudah bypass superadmin, tapi controller
`VerificationController::ensureRole()` dulu masih hard-check role. Sekarang `ensureRole()`
selalu mengizinkan superadmin, sehingga superadmin dapat:

- Membuka & memproses antrian `/penyelia/berita-acara` dan `/manager-teknis/berita-acara`
- Melakukan verifikasi dari halaman detail BA admin (panel "Override Super Admin") saat
  status `admin_verified` (tahap penyelia) atau `rekap_ready` (tahap manager teknis)

Aksi override ditandai `(override superadmin)` di ActivityLog. Nama petugas pada
PDF/DOCX (`{{ nama_penyelia }}`, `{{ nama_manager_teknis }}`) tetap memakai petugas
asli yang ditugaskan; verifikator superadmin hanya dipakai sebagai fallback bila
tidak ada penugasan.

### Switch Role ("Aktif sebagai") — Frontend

Dropdown user di Navbar memiliki bagian "Aktif sebagai" (hanya untuk akun superadmin).
Konsep seperti memilih channel pada satu akun: akun tetap superadmin (login + backend
bypass tidak berubah), tapi tampilan mengikuti role yang dipilih.

- `authStore`: `activeRole` (persisted di localStorage), `rolePermissions` (snapshot dari
  `GET /admin/roles`, di-refresh setiap dropdown dibuka), dan `effectiveRole`
- `hasPermission()` memakai permission role aktif saat superadmin di-switch; saat aktif
  sebagai `superadmin` tetap bypass penuh
- Menu sidebar, redirect `/dashboard`, guard route (`superadminOnly`), label role, dan
  panel aksi mengikuti role aktif
- Logout / login non-superadmin mereset switch (localStorage dibersihkan)

## Permission Dependencies (AdminRoles.vue)

Permission dependen nonaktif (disabled) sampai prerequisite-nya dicentang:

| Permission | Prerequisite (minimal salah satu untuk OR) |
|---|---|
| `berita_acara_verify` | `berita_acara_riwayat_semua` |
| `berita_acara_assign` | `berita_acara_verify` |
| `hasil_pengujian_rekap` | `hasil_pengujian_riwayat_personal` **OR** `hasil_pengujian_riwayat_semua` |
| `payment_upload` | `payment_riwayat_personal` |
| `payment_discount` | `payment_upload` |
| `payment_confirm` | `payment_riwayat_semua` |
| `user_verify` | `user_view` |
| `certificate_download` | `certificate_view` |
| `discount_verifikasi` | `discount_riwayat_semua` |

Saat prerequisite di-uncheck, permission dependennya otomatis ikut ter-uncheck.

## Permission Seeding

```php
// PermissionsSeeder.php
// 26 permissions, dibuat via firstOrCreate (idempotent)
// DatabaseSeeder.php sync permission ke 6 role default
```