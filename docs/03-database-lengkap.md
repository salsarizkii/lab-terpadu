# DATABASE LAB TERPADU — Dokumentasi Lengkap

## 1. Diagram Relasi (ERD)

```
                              ┌──────────────────────┐
                              │       roles          │
                              │──────────────────────│
                              │ id (PK)              │
                              │ name (UNIQUE)        │◄────────────── users.role (string)
                              │ description          │
                              │ is_system (bool)     │
                              │ requires_verification│
                              └──────────┬───────────┘
                                         │
                                ┌────────┴────────┐
                                │ role_permission │
                                │────────────────│
                                │ role_id (FK)   │       ┌──────────────────┐
                                │ permission_id  │──────>│   permissions    │
                                └────────────────┘       │──────────────────│
                                                         │ id (PK)          │
                                                         │ name (UNIQUE)    │
                                                         │ display_name     │
                                                         │ group            │
                                                         └──────────────────┘

┌──────────────────────┐
│       users          │
│──────────────────────│
│ id (PK)              │
│ name                 │
│ email (UNIQUE)       │
│ password (hashed)    │
│ role (string)───────────► roles.name
│ phone                │
│ api_token (UNIQUE)   │
│ is_verified (bool)   │
└──────┬───────────────┘
       │
       │ user_id                        analyst_id
       │ (applicant)                    (assigned)
       ▼                                  ▼
┌──────────────────────────────────────────────────────────────────────┐
│                          berita_acaras                               │
│──────────────────────────────────────────────────────────────────────│
│ id (PK)                                                              │
│ user_id (FK → users)              analyst_id (FK → users, nullable)  │
│ status: draft|pending|revision|approved|rejected                     │
│ nama_pemohon, nim, prodi_fakultas_instansi, dosen_pembimbing         │
│ telp_hp, email, alamat, judul_penelitian                             │
│ akses_lab, bahan_contoh_uji, kemasan_packing, permintaan_analisa     │
│ jumlah_sampel, kode_nama_sampel, keterangan_sampel, keterangan       │
│ pengukuran_per_sampel, sisa_sampel, upload_akses, masa_berlaku_akses │
│ status_pelanggan, status_berkas, biaya (decimal)                     │
│ diskon_diizinkan (bool), form_data (JSON), form_template_id (FK)     │
│ rekap_analis_data (JSON), rekap_analis_template_id (FK)              │
│ formula_id (FK → formulas, nullable), custom_items (JSON)            │
└──┬──────────┬──────────┬──────────┬──────────┬──────────┬───────────┘
   │          │          │          │          │          │
   │          │          │          │          │          │
   ▼          ▼          ▼          ▼          ▼          ▼
┌──────┐ ┌──────┐ ┌──────────┐ ┌──────┐ ┌──────────┐ ┌──────────────────────┐
│paymen│ │sample│ │berita_ac │ │certif│ │pengujians│ │berita_acara_analysts │
│ts    │ │s     │ │ara_revis │ │icates│ │          │ │──────────────────────│
│──────│ │──────│ │ions      │ │──────│ │──────────│ │berita_acara_id (FK)  │
│id(PK)│ │id(PK)│ │──────────│ │id(PK)│ │id (PK)   │ │user_id (FK → users)  │
│ba_id │ │ba_id │ │id (PK)   │ │ba_id │ │ba_id(FK) │ │                      │
│(FK)  │ │(FK)  │ │ba_id(FK) │ │(FK)  │ │nama      │ │(many-to-many pivot)  │
│amount│ │name  │ │note      │ │file_ │ │agenda    │ └──────────────────────┘
│status│ │status│ │prev_data │ │path  │ │...10 cols│
│proof │ │result│ │created_by│ │downl │ │          │
│discou│ │unit  │ │          │ │oaded │ │          │
│nt... │ │qty   │ │          │ │_at   │ │          │
└──────┘ └──────┘ └──────────┘ └──────┘ └──────────┘

┌──────────────────────┐     ┌──────────────────────┐     ┌──────────────────────┐
│   form_templates     │     │     form_fields      │     │      formulas        │
│──────────────────────│     │──────────────────────│     │──────────────────────│
│ id (PK)              │     │ id (PK)              │     │ id (PK)              │
│ name                 │     │ form_template_id(FK) │     │ name                 │
│ type (enum)          │     │ label                │     │ expression           │
│ description          │     │ type (14 types)      │     │ unit                 │
│ is_active (bool)     │     │ options (JSON)       │     │ variables (JSON)     │
│ created_by (FK)      │     │ is_required (bool)   │     │ created_by (FK)      │
└──────────────────────┘     │ sort_order           │     └──────────────────────┘
                             │ placeholder          │
                             │ allow_other (bool)   │     ┌──────────────────────┐
                             │ locked (bool)        │     │  instrument_prices   │
                             └──────────────────────┘     │──────────────────────│
                                                          │ id (PK)              │
                                                          │ name                 │
                                                          │ unit                 │
                                                          │ price_regular        │
                                                          │ price_uns            │
                                                          │ note                 │
                                                          │ sort_order           │
                                                          └──────────────────────┘
```

---

## 2. Seluruh Tabel & Kolom

### 2.1 `users` — Pengguna Sistem

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `name` | varchar(255) | - | Nama lengkap |
| `email` | varchar(255) | - | Email (UNIQUE) |
| `email_verified_at` | timestamp | null | Timestamp verifikasi email (opsional) |
| `password` | varchar(255) | - | Password (bcrypt hashed) |
| `role` | varchar(255) | `'user'` | String role: superadmin/admin/analyst/user |
| `phone` | varchar(255) | null | Nomor telepon |
| `api_token` | varchar(60) | null | Token auth custom (UNIQUE) |
| `is_verified` | boolean | false | Status verifikasi user oleh admin |
| `remember_token` | varchar(100) | null | Laravel remember me |
| `created_at` | timestamp | - | Waktu pembuatan |
| `updated_at` | timestamp | - | Waktu update |

**Relationships:**
- `roleModel()` → belongsTo `Role` (via `role` string = `roles.name`)
- `beritaAcaras()` → hasMany `BeritaAcara` (sebagai pemohon / `user_id`)
- `assignedBeritaAcaras()` → hasMany `BeritaAcara` (sebagai analyst / `analyst_id`)

**Seed:** 1 superadmin (`superadmin@lab.com` / `password`)

---

### 2.2 `roles` — RBAC Roles

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `name` | varchar(255) | - | Nama role (UNIQUE) |
| `description` | varchar(255) | null | Deskripsi role |
| `is_system` | boolean | false | System role, tidak bisa dihapus |
| `requires_verification` | boolean | false | Butuh verifikasi manual admin |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**Relationships:**
- `users()` → hasMany `User` (via `role` = `name`)
- `permissions()` → belongsToMany `Permission` (pivot: `role_permission`)

**Casts:** `is_system` → boolean, `requires_verification` → boolean

---

### 2.3 `permissions` — Daftar Izin

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `name` | varchar(100) | - | Nama permission (UNIQUE) |
| `display_name` | varchar(200) | - | Nama tampilan |
| `group` | varchar(100) | - | Grup permission |
| `description` | text | null | Deskripsi |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**22 Permissions dalam 8 grup:**

| Grup | Permissions |
|---|---|
| `berita_acara` | ba_create, ba_read, ba_update, ba_delete, ba_verify |
| `pembayaran` | payment_read, payment_confirm |
| `sample` | sample_read, sample_update |
| `sertifikat` | certificate_read, certificate_download |
| `pengujian` | pengujian_read, pengujian_create, pengujian_update |
| `user` | user_read, user_verify, user_manage |
| `role` | role_read, (superadmin only) |
| `form_template` | form_read, form_manage |

---

### 2.4 `role_permission` — Pivot Role↔Permission

| Kolom | Tipe | Deskripsi |
|---|---|---|
| `id` | bigint (PK) | auto |
| `role_id` | bigint (FK → roles.id) | Cascade on delete |
| `permission_id` | bigint (FK → permissions.id) | Cascade on delete |

**Unique constraint:** `(role_id, permission_id)`

---

### 2.5 `berita_acaras` — Berita Acara / Pengajuan Lab

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `user_id` | bigint (FK → users) | - | Pemohon |
| `analyst_id` | bigint (FK → users) | null | Analyst yang ditugaskan |
| `status` | enum | `'draft'` | draft, pending, revision, approved, rejected |
| `nama_pemohon` | varchar(255) | null | Nama pemohon |
| `nim` | varchar(50) | null | NIM / NIP |
| `prodi_fakultas_instansi` | varchar(255) | null | Prodi / Fakultas / Instansi |
| `dosen_pembimbing` | varchar(255) | null | Dosen pembimbing |
| `telp_hp` | varchar(20) | null | Telepon / HP |
| `email` | varchar(255) | null | Email pemohon |
| `alamat` | text | null | Alamat |
| `judul_penelitian` | text | null | Judul penelitian |
| `akses_lab` | text | null | Info akses lab (file path) |
| `bahan_contoh_uji` | text | null | Bahan / contoh uji |
| `kemasan_packing` | varchar(255) | null | Jenis kemasan |
| `permintaan_analisa` | text | null | Permintaan analisa |
| `jumlah_sampel` | varchar(50) | null | Jumlah hasil pengujian |
| `kode_nama_sampel` | varchar(255) | null | Kode / nama hasil pengujian |
| `keterangan_sampel` | text | null | Keterangan hasil pengujian |
| `keterangan_tambahan` | text | null | Keterangan tambahan |
| `keterangan` | text | null | Keterangan umum |
| `pengukuran_per_sampel` | varchar(255) | null | Pengukuran per hasil pengujian |
| `sisa_sampel` | varchar(255) | null | Sisa hasil pengujian |
| `upload_akses` | varchar(255) | null | Upload file akses |
| `masa_berlaku_akses` | varchar(255) | null | Masa berlaku akses |
| `status_pelanggan` | varchar(100) | null | Status pelanggan (umum/UNS/non-UNS) |
| `status_berkas` | varchar(100) | null | Status berkas |
| `biaya` | decimal(15,2) | null | Biaya total |
| `diskon_diizinkan` | boolean | false | Admin izinkan diskon |
| `form_data` | json | null | Data form dinamis (hasil isian FormTemplate) |
| `form_template_id` | bigint (FK → form_templates) | null | Template form yang digunakan |
| `rekap_analis_data` | json | null | Data rekap analis (JSON) |
| `rekap_analis_template_id` | bigint (FK → form_templates) | null | Template rekap analis |
| `formula_id` | bigint (FK → formulas) | null | Formula perhitungan biaya |
| `custom_items` | json | null | Item biaya kustom |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**Relationships:**
- `user()` → belongsTo `User` (pemohon, via `user_id`)
- `analyst()` → belongsTo `User` (analyst, via `analyst_id`)
- `analysts()` → belongsToMany `User` (pivot: `berita_acara_analysts`, multi-analyst)
- `revisions()` → hasMany `BeritaAcaraRevision`
- `payment()` → hasOne `Payment`
- `samples()` → hasMany `Sample`
- `certificate()` → hasOne `Certificate`
- `pengujians()` → hasMany `Pengujian`
- `formula()` → belongsTo `Formula`

**Casts:** `diskon_diizinkan` → boolean, `form_data` → array, `rekap_analis_data` → array, `custom_items` → array

---

### 2.6 `berita_acara_revisions` — Riwayat Revisi BA

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `berita_acara_id` | bigint (FK → berita_acaras) | - | BA yang direvisi |
| `note` | text | null | Catatan revisi dari admin |
| `previous_form_data` | json | null | Data form sebelum revisi (snapshot) |
| `created_by` | bigint (FK → users) | - | Siapa yang merevisi (admin) |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**Relationships:**
- `beritaAcara()` → belongsTo `BeritaAcara`
- `creator()` → belongsTo `User`

---

### 2.7 `berita_acara_analysts` — Pivot Multi-Analyst

| Kolom | Tipe | Deskripsi |
|---|---|---|
| `id` | bigint (PK) | auto |
| `berita_acara_id` | bigint (FK → berita_acaras) | Cascade on delete |
| `user_id` | bigint (FK → users) | Cascade on delete |

**Unique constraint:** `(berita_acara_id, user_id)`

---

### 2.8 `payments` — Pembayaran

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `berita_acara_id` | bigint (FK → berita_acaras, UNIQUE) | - | BA terkait (1:1) |
| `amount` | decimal(15,2) | - | Jumlah pembayaran |
| `discount_request` | decimal(15,2) | null | Diskon yang diminta user |
| `discount_reason` | text | null | Alasan diskon |
| `discount_status` | varchar(20) | null | Status permintaan diskon |
| `discount_approved` | decimal(15,2) | null | Diskon yang disetujui admin |
| `discount_type` | varchar(10) | null | Tipe diskon: percent/nominal |
| `discount_percent` | decimal(5,2) | null | Persentase diskon |
| `discount_nominal` | decimal(15,2) | null | Nominal diskon |
| `status` | enum | `'pending'` | pending, approved, rejected |
| `payment_proof` | varchar(255) | null | File path bukti bayar |
| `notes` | text | null | Catatan |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**Relationships:**
- `beritaAcara()` → belongsTo `BeritaAcara`

---

### 2.9 `samples` — Hasil Pengujian

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `berita_acara_id` | bigint (FK → berita_acaras) | - | BA terkait |
| `name` | varchar(255) | - | Nama hasil pengujian |
| `description` | text | null | Deskripsi hasil pengujian |
| `quantity` | varchar(50) | null | Jumlah |
| `unit` | varchar(50) | null | Satuan (kg, liter, dll.) |
| `test_result` | text | null | Hasil pengujian (diisi analyst) |
| `status` | enum | `'processing'` | pending, processing, ready, taken |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**Relationships:**
- `beritaAcara()` → belongsTo `BeritaAcara`

---

### 2.10 `certificates` — Sertifikat

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `berita_acara_id` | bigint (FK → berita_acaras, UNIQUE) | - | BA terkait (1:1) |
| `file_path` | varchar(255) | null | Path file sertifikat |
| `certificate_number` | varchar(100) | null | Nomor sertifikat |
| `issued_at` | timestamp | null | Tanggal terbit |
| `downloaded_at` | timestamp | null | Waktu download pertama |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**Relationships:**
- `beritaAcara()` → belongsTo `BeritaAcara`

**Casts:** `downloaded_at` → datetime

---

### 2.11 `pengujians` — Data Pengujian

| Kolom | Tipe | Deskripsi |
|---|---|---|
| `id` | bigint (PK) | auto |
| `berita_acara_id` | bigint (FK → berita_acaras, cascade) | BA terkait |
| `nama` | varchar(255) | Nama pengujian |
| `agenda` | varchar(255) | Agenda |
| `jumlah_pengujian` | varchar(255) | Jumlah pengujian |
| `nama_pengujian` | varchar(255) | Nama pengujian |
| `tanggal_permohonan` | varchar(255) | Tanggal permohonan |
| `tanggal_pengujian` | varchar(255) | Tanggal pengujian |
| `analis` | varchar(255) | Analis pelaksana |
| `pengambilan_hasil` | varchar(255) | Info pengambilan hasil |
| `nama_file_pengujian` | varchar(255) | Nama file pengujian |
| `keterangan_pengujian` | text | Keterangan |
| `verifikator` | varchar(255) | Verifikator |
| `created_at` | timestamp | - |
| `updated_at` | timestamp | - |

**Relationships:**
- `beritaAcara()` → belongsTo `BeritaAcara`

---

### 2.12 `form_templates` — Template Form

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `name` | varchar(255) | - | Nama template |
| `type` | enum | - | berita_acara, rekap_analis, perhitungan_biaya, kelola_harga_instrumen |
| `description` | text | null | Deskripsi |
| `is_active` | boolean | true | Status aktif |
| `created_by` | bigint (FK → users) | - | Pembuat |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**Relationships:**
- `fields()` → hasMany `FormField` (ordered by `sort_order`)
- `creator()` → belongsTo `User`

**Casts:** `is_active` → boolean

---

### 2.13 `form_fields` — Field Form

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `form_template_id` | bigint (FK → form_templates, cascade) | - | Template terkait |
| `label` | varchar(255) | - | Label field |
| `type` | enum | - | 14 tipe field (lihat bawah) |
| `options` | json | null | Array opsi `[{label, is_other}]` |
| `is_required` | boolean | false | Wajib diisi |
| `sort_order` | int | 0 | Urutan tampil |
| `placeholder` | varchar(255) | null | Placeholder text |
| `allow_other` | boolean | false | Izin opsi "lainnya" (legacy) |
| `locked` | boolean | false | Field terkunci (tidak bisa diedit admin) |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**14 Tipe Field:**
- **Input:** text, textarea, number, email, phone, date, time, file
- **Pilihan:** select, radio, checkbox
- **Layout:** heading, paragraph, divider

**Relationships:**
- `template()` → belongsTo `FormTemplate`

**Casts:** `options` → array, `is_required` → boolean, `locked` → boolean

---

### 2.14 `formulas` — Rumus Perhitungan Biaya

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `name` | varchar(200) | - | Nama formula |
| `description` | text | null | Deskripsi |
| `expression` | text | - | Ekspresi perhitungan |
| `unit` | varchar(100) | `'Rupiah'` | Satuan output |
| `variables` | json | - | Variabel input (JSON) |
| `created_by` | bigint (FK → users) | - | Pembuat |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**Relationships:**
- `creator()` → belongsTo `User`

**Casts:** `variables` → array

---

### 2.15 `instrument_prices` — Harga Instrumen

| Kolom | Tipe | Default | Deskripsi |
|---|---|---|---|
| `id` | bigint (PK) | auto | Primary key |
| `name` | varchar(255) | - | Nama instrumen |
| `unit` | varchar(255) | null | Satuan |
| `price_regular` | decimal(15,0) | 0 | Harga reguler |
| `price_uns` | decimal(15,0) | null | Harga UNS (internal) |
| `note` | text | null | Catatan |
| `sort_order` | int | 0 | Urutan tampil |
| `created_at` | timestamp | - | - |
| `updated_at` | timestamp | - | - |

**Casts:** `price_regular` → integer, `price_uns` → integer, `sort_order` → integer

---

### 2.16 Tabel Pendukung Laravel

| Tabel | Fungsi |
|---|---|
| `cache` | Laravel cache store (key PK, value, expiration) |
| `cache_locks` | Laravel atomic lock |
| `jobs` | Laravel queue jobs |
| `job_batches` | Laravel batch jobs |
| `failed_jobs` | Laravel failed jobs |
| `sessions` | Laravel sessions (extra) |
| `password_reset_tokens` | Laravel password reset |

---

## 3. Relasi Lengkap

```
User (1) ────────────< (N) BeritaAcara (user_id)          [pemohon]
User (1) ────────────< (N) BeritaAcara (analyst_id)       [analyst ditugaskan]
User (N) <───────────> (N) BeritaAcara (analysts pivot)   [multi-analyst]
User (N) ────────────── (N) Role (role string = name)     [hybrid RBAC]

BeritaAcara (1) ────── (1) Payment (berita_acara_id UNIQUE)
BeritaAcara (1) ───< (N) Sample (berita_acara_id)
BeritaAcara (1) ────── (1) Certificate (berita_acara_id UNIQUE)
BeritaAcara (1) ───< (N) BeritaAcaraRevision (berita_acara_id)
BeritaAcara (1) ───< (N) Pengujian (berita_acara_id)
BeritaAcara (1) ───> (1) Formula (formula_id, nullable)
BeritaAcara (1) ───> (1) FormTemplate (form_template_id, nullable)
BeritaAcara (1) ───> (1) FormTemplate (rekap_analis_template_id, nullable)

Role (1) <──────────> (N) Permission (role_permission pivot)
Role (1) ────< (N) User (role string)

FormTemplate (1) ───< (N) FormField (form_template_id, cascade)
FormTemplate (1) ────── (N) BeritaAcara (form_template_id, nullable)
FormTemplate (1) ────── (N) BeritaAcara (rekap_analis_template_id, nullable)
```

---

## 4. Alur Bisnis Detail

### 4.1 Step 1: Pengajuan (User)

```
USER                              SYSTEM
 │                                  │
 ├─ Buka form Berita Acara         │
 │   (FormTemplate tipe "berita_acara")
 │   ↓                              │
 ├─ Isi 20+ field + upload file    │
 │   ↓                              │
 ├─ Submit (status berubah draft → pending)
 │   ──────────────────────────────>│ POST /api/user/berita-acara
 │                                  ├─ Simpan field ke berita_acaras columns
 │                                  ├─ Simpan form_data (JSON) jika pakai dynamic form
 │                                  ├─ Status = "pending"
 │                                  └─ Return BA detail
```

### 4.2 Step 2: Pembayaran

```
ADMIN                             SYSTEM                           USER
 │                                  │                                │
 ├─ Lihat daftar BA pending        │                                │
 │   ──────────────────────────────>│ GET /api/admin/berita-acara   │
 │                                  │                                │
 ├─ (Opsional) Set diskon_diizinkan = true                          │
 │   ──────────────────────────────>│ PUT /status                  │
 │                                  │                                │
 ├─ Approve BA (pending → approved)│                                │
 │   ──────────────────────────────>│ PUT /status                  │
 │                                  │                                │
 │                                  │                                ├─ Lihat BA approved
 │                                  │                                ├─ Buka payment page
 │                                  │                                ├─ POST /api/user/payments
 │                                  │                                ├─ (Opsional) Request diskon
 │                                  │                                │   POST /discount
 │                                  │                                ├─ Upload bukti bayar
 │                                  │ <──────────────────────────────│   POST /upload-proof
 │                                  │                                │
 ├─ Lihat payment + bukti bayar    │                                │
 │   ──────────────────────────────>│ GET /api/admin/payments      │
 │                                  │                                │
 ├─ Konfirmasi (pending → approved)│                                │
 │   ──────────────────────────────>│ PUT /confirm                  │
 │                                  ├─ Payment status = "approved"  │
```

### 4.3 Step 3: Pengambilan Hasil Pengujian

```
ADMIN                             SYSTEM                           ANALYST
 │                                  │                                │
 ├─ Assign analyst ke BA            │                                │
 │   ──────────────────────────────>│ PUT /assign-analyst          │
 │                                  ├─ Set analyst_id               │
 │                                  ├─ (atau) Tambah ke pivot       │
 │                                  │   berita_acara_analysts       │
 │                                  │                                │
 │                                  │                                ├─ Lihat assigned BA
 │                                  │ <──────────────────────────────│ GET /api/analyst/berita-acara
 │                                  │                                │
 │                                  │                                ├─ Proses hasil pengujian
 │                                  │ <──────────────────────────────│ POST /pengujian
 │                                  │                                ├─ Isi data pengujian
 │                                  │                                │
 │                                  │                                ├─ Update sample result
 │                                  │ <──────────────────────────────│ PUT /samples/{id}
 │                                  │                                ├─ Isi test_result
 │                                  │                                ├─ Update status sample
 │                                  │                                │ (pending → processing → ready)
 │                                  │                                │
 │                                  │                                ├─ Tandai sample siap
 │                                  │ <──────────────────────────────│ PUT /samples/{id}/ready
 │                                  │                                │
 ├─ Lihat hasil pengujian ready             │                                │
 ├─ Tandai hasil pengujian diambil          │                                │
 │   ──────────────────────────────>│ PUT /samples/{id}/mark-taken  │
 │                                  ├─ Sample status = "taken"      │
```

### 4.4 Step 4: Sertifikat

```
SYSTEM                             USER
 │                                  │
 ├─ Generate sertifikat            │
 │   (admin/system upload file)    │
 │   Sertifikat tersimpan di       │
 │   certificates table             │
 │                                  │
 │                                  ├─ Lihat sertifikat tersedia
 │                                  ├─ Download
 │                                  │   ──────────────────────────────>
 │                                  │                                  ├─ GET /api/user/certificates/{id}/download
 │                                  │ <───────────────────────────────
 │                                  │                                  ├─ Return file
 │                                  │                                  ├─ Set downloaded_at
```

---

## 5. State Machine (Transisi Status)

### 5.1 Berita Acara Status

```
     ┌──────────┐
     │  draft    │  (default, user sedang isi form)
     └────┬─────┘
          │ user submit
          ▼
     ┌──────────┐
     │ pending   │  (menunggu review admin)
     └────┬─────┘
          │ admin review
          ├──────────────┬──────────────┐
          ▼              ▼              ▼
     ┌──────────┐  ┌──────────┐  ┌──────────┐
     │ approved  │  │ rejected  │  │ revision  │
     └──────────┘  └──────────┘  └─────┬────┘
          │                             │
          │ analyst proses              │ user re-submit
          │ pembayaran                  │ (revision → pending)
          ▼                             │
     ┌──────────┐                  ┌────┘
     │ selesai   │                  │
     └──────────┘                  ▼
                              ┌──────────┐
                              │ pending   │
                              └──────────┘

Transisi valid:
  pending  →  approved | rejected | revision
  revision →  pending
```

### 5.2 Sample Status

```
  ┌──────────┐
  │ pending   │  (hasil pengujian baru dibuat)
  └────┬─────┘
       │ (default sekarang 'processing' langsung)
       ▼
  ┌──────────┐
  │ processing│  (analyst sedang proses)
  └────┬─────┘
       │ analyst selesai
       ▼
  ┌──────────┐
  │ ready     │  (siap diambil)
  └────┬─────┘
       │ admin tandai
       ▼
  ┌──────────┐
  │ taken     │  (sudah diambil user)
  └──────────┘
```

### 5.3 Payment Status

```
  ┌──────────┐
  │ pending   │  (payment dibuat user)
  └────┬─────┘
       │ admin konfirmasi
       ├──────────────┐
       ▼              ▼
  ┌──────────┐  ┌──────────┐
  │ approved  │  │ rejected  │
  └──────────┘  └──────────┘
```

---

## 6. Hybrid RBAC System

```
┌──────────────────────────────────────────────────────────┐
│                    Cara Kerja RBAC                        │
│                                                          │
│  1. users.role = string column (superadmin/admin/        │
│     analyst/user) → untuk akses dasar                    │
│                                                          │
│  2. roles table = RBAC UI management                     │
│     role_permission pivot = fine-grained permissions     │
│                                                          │
│  3. Superadmin BYPASS semua role check                   │
│     (di ApiTokenMiddleware)                              │
│                                                          │
│  4. User.getPermissionsAttribute()                       │
│     → ambil dari roleModel.permissions                   │
│     → return list nama permission                        │
└──────────────────────────────────────────────────────────┘

Middleware pipeline:
  Request → ApiTokenMiddleware (auth + role + superadmin bypass)
          → EnsureUserVerified (cek is_verified, hanya user routes)
          → Controller
```

---

## 7. Form Builder — Dynamic Form System

```
┌─────────────────────┐
│   Admin/UI Editor   │
│   (FormBuilder.vue) │
└──────────┬──────────┘
           │ CRUD fields via PUT /form-templates/{id}/fields
           ▼
┌─────────────────────┐     ┌──────────────────────┐
│   form_templates    │     │     form_fields       │
│─────────────────────│     │──────────────────────│
│ id (PK)             │────<│ form_template_id (FK)│
│ name                │     │ label                │
│ type (enum)         │     │ type (14 types)      │
│ is_active           │     │ options (JSON)       │
└─────────────────────┘     │ is_required          │
                            │ sort_order           │
                            │ locked               │
                            └──────────────────────┘

Sync logic: DELETE all old fields → INSERT new fields (full replacement)
```

### Tipe Form Template:

| Type | Fungsi |
|---|---|
| `berita_acara` | Form pengajuan lab (diisi user) |
| `rekap_analis` | Form rekap analis (diisi analyst) |
| `perhitungan_biaya` | Form kalkulasi biaya |
| `kelola_harga_instrumen` | Form harga instrumen |

### Alur Dynamic Form:

```
1. Admin buat/edit FormTemplate + FormFields via FormBuilder UI
2. User/subject buka form → render field sesuai template
3. Data disimpan:
   - Untuk berita_acara: data juga masuk ke kolom-kolom fixed BA
   - Untuk dynamic: disimpan di form_data (JSON)
```

---

## 8. Autentikasi Flow

```
┌──────────┐          ┌──────────────┐          ┌──────────────┐
│ Frontend  │          │   Backend    │          │   Database   │
└────┬─────┘          └──────┬───────┘          └──────┬───────┘
     │ POST /auth/login      │                        │
     │ {email, password}      │                        │
     │ ───────────────────── >│ SELECT * FROM users    │
     │                        │ WHERE email = ?        │
     │                        │ ────────────────────── >│
     │                        │ <──────────────────────│
     │                        │ Hash::check(password)  │
     │                        │ api_token = Str::random(60)
     │                        │ UPDATE users SET api_token = ?
     │                        │ ────────────────────── >│
     │ { user, token }        │ <──────────────────────│
     │ <───────────────────── │                        │
     │                        │                        │
     │ Simpan di localStorage │                        │
     │ authStore.token = token│                        │
     │                        │                        │
     │ API calls:             │                        │
     │ Authorization: Bearer {token}                    │
     │ ───────────────────── >│ SELECT * FROM users    │
     │                        │ WHERE api_token = ?    │
     │                        │ ────────────────────── >│
     │                        │ <──────────────────────│
     │                        │ Cek role + bypass      │
     │ { data }               │ Attach user ke request │
     │ <───────────────────── │                        │
```

### Logout:

```
1. POST /auth/logout (dengan token)
2. Backend: set api_token = null
3. Frontend: clear localStorage + authStore
4. Redirect ke /login
```

---

## 9. Ringkasan Indeks Penting

| Tabel | Indeks | Kolom |
|---|---|---|
| users | UNIQUE | email |
| users | UNIQUE | api_token |
| role_permission | UNIQUE (composite) | role_id, permission_id |
| berita_acaras | INDEX | user_id |
| berita_acaras | INDEX | analyst_id |
| berita_acaras | INDEX | status |
| payments | INDEX | berita_acara_id |
| samples | INDEX | berita_acara_id |
| form_fields | INDEX | form_template_id |
| form_fields | INDEX | sort_order |
