# Seeders — Dokumentasi Lengkap

## Arsitektur Seeder

```
php artisan db:seed
        │
        ▼
┌──────────────────────────────────────────────┐
│           DatabaseSeeder.php                 │
│──────────────────────────────────────────────│
│  1. call PermissionsSeeder                   │
│  2. Buat 4 Role + assign permissions         │
│  3. Buat akun superadmin                     │
│  4. call InstrumentPriceSeeder               │
│  5. call FormTemplateSeeder                  │
└──────────────────────────────────────────────┘
```

---

## 1. `DatabaseSeeder.php` — Seeder Utama

**Path:** `backend/database/seeders/DatabaseSeeder.php`

**Urutan eksekusi:**

| Step | Aksi | Detail |
|---|---|---|
| 1 | `call(PermissionsSeeder::class)` | Insert 28 permissions ke tabel `permissions` |
| 2 | Buat role **superadmin** | `is_system: true`, sync **semua** 28 permissions |
| 3 | Buat role **admin** | `is_system: false`, `requires_verification: true`, sync **semua** 28 permissions |
| 4 | Buat role **analyst** | `is_system: false`, `requires_verification: true`, sync **10** permissions |
| 5 | Buat role **user** | `is_system: false`, `requires_verification: false`, sync **10** permissions |
| 6 | Buat akun superadmin | `superadmin@lab.com` / `password` |
| 7 | `call(InstrumentPriceSeeder::class)` | Insert 53 harga instrumen |
| 8 | `call(FormTemplateSeeder::class)` | Insert 4 form template + fields |

---

## 2. `PermissionsSeeder.php` — 28 Permissions

**Path:** `backend/database/seeders/PermissionsSeeder.php`

Menggunakan `Permission::firstOrCreate(['name' => ...])` — idempotent, aman dijalankan ulang.

### 8 Grup Permission

| Grup | Count | Permissions |
|---|---|---|
| **Umum** | 1 | `dashboard` |
| **Berita Acara** | 6 | `berita_acara_riwayat_personal`, `berita_acara_create`, `berita_acara_verify`, `berita_acara_assign`, `berita_acara_riwayat_personal`, `berita_acara_riwayat_semua` |
| **Pembayaran** | 4 | `payment_riwayat_personal`, `payment_upload`, `payment_discount`, `payment_confirm` |
| **Hasil Pengujian** | 6 | `hasil_pengujian_rekap`, `hasil_pengujian_rekap`, `hasil_pengujian_rekap`, `hasil_pengujian_rekap`, `hasil_pengujian_riwayat_personal`, `hasil_pengujian_riwayat_semua` |
| **Sertifikat** | 2 | `certificate_view`, `certificate_download` |
| **Pengguna** | 2 | `user_view`, `user_verify` |
| **Role** | 1 | `(superadmin only)` |
| **Form** | 4 | `form_manage_berita_acara`, `form_manage_rekap_analis`, `form_manage_perhitungan_biaya`, `form_manage_kelola_harga_instrumen` |
| **Diskon** | 3 | `discount_verifikasi`, `discount_riwayat_semua`, `discount_riwayat_personal` |

### Daftar Lengkap:

| # | name | display_name | group |
|---|---|---|---|
| 1 | `dashboard` | Dashboard | Umum |
| 2 | `berita_acara_riwayat_personal` | Melihat Berita Acara | Berita Acara |
| 3 | `berita_acara_create` | Buat Berita Acara | Berita Acara |
| 4 | `berita_acara_verify` | Verifikasi Berita Acara | Berita Acara |
| 5 | `berita_acara_assign` | Menugaskan Analis | Berita Acara |
| 6 | `berita_acara_riwayat_personal` | Personal Riwayat Berita Acara | Berita Acara |
| 7 | `berita_acara_riwayat_semua` | Riwayat Seluruh Berita Acara | Berita Acara |
| 8 | `payment_riwayat_personal` | Melihat Pembayaran | Pembayaran |
| 9 | `payment_upload` | Upload Bukti Bayar | Pembayaran |
| 10 | `payment_discount` | Diskon Pembayaran | Pembayaran |
| 11 | `payment_confirm` | Konfirmasi Pembayaran | Pembayaran |
| 12 | `hasil_pengujian_rekap` | Melihat Hasil Pengujian | Hasil Pengujian |
| 13 | `hasil_pengujian_rekap` | Input Data Hasil Pengujian | Hasil Pengujian |
| 14 | `hasil_pengujian_rekap` | Buat Rekap Analis | Hasil Pengujian |
| 15 | `hasil_pengujian_rekap` | Update Status Hasil Pengujian | Hasil Pengujian |
| 16 | `hasil_pengujian_riwayat_personal` | Personal Riwayat Rekap Analisis | Hasil Pengujian |
| 17 | `hasil_pengujian_riwayat_semua` | Riwayat Seluruh Rekap Analisis | Hasil Pengujian |
| 18 | `certificate_view` | Melihat Sertifikat | Sertifikat |
| 19 | `certificate_download` | Download Sertifikat | Sertifikat |
| 20 | `user_view` | Melihat Pengguna | Pengguna |
| 21 | `user_verify` | Verifikasi Pengguna | Pengguna |
| 22 | `(superadmin only)` | Kelola Role | Role |
| 23 | `form_manage_berita_acara` | Kelola Form Berita Acara | Form |
| 24 | `form_manage_rekap_analis` | Kelola Form Rekap Analis | Form |
| 25 | `form_manage_perhitungan_biaya` | Kelola Form Perhitungan Biaya | Form |
| 26 | `form_manage_kelola_harga_instrumen` | Kelola Form Harga Instrumen | Form |
| 27 | `discount_verifikasi` | Verifikasi Diskon | Diskon |
| 28 | `discount_riwayat_semua` | Riwayat Seluruh Diskon | Diskon |
| 29 | `discount_riwayat_personal` | Personal Riwayat Diskon | Diskon |

---

## 3. Role & Permission Assignment

Dibuat di `DatabaseSeeder::run()` setelah `PermissionsSeeder`.

### 3.1 Superadmin
```
is_system: true
permissions: SEMUA 28 permissions (sync all)
akun: superadmin@lab.com / password / is_verified: true
```

### 3.2 Admin
```
is_system: false
requires_verification: true
permissions: SEMUA 28 permissions (sync all)
```

### 3.3 Analyst
```
is_system: false
requires_verification: true
permissions: 10 permissions —
  • dashboard
  • berita_acara_riwayat_personal
  • berita_acara_riwayat_semua
  • hasil_pengujian_rekap
  • hasil_pengujian_rekap
  • hasil_pengujian_rekap
  • hasil_pengujian_rekap
  • hasil_pengujian_riwayat_personal
  • certificate_view
  • certificate_download
```

### 3.4 User
```
is_system: false
requires_verification: false
permissions: 10 permissions —
  • berita_acara_create
  • berita_acara_riwayat_personal
  • berita_acara_riwayat_personal
  • hasil_pengujian_riwayat_personal
  • hasil_pengujian_rekap
  • payment_riwayat_personal
  • payment_upload
  • payment_discount
  • certificate_view
  • certificate_download
```

---

## 4. `InstrumentPriceSeeder.php` — 53 Harga Instrumen

**Path:** `backend/database/seeders/InstrumentPriceSeeder.php`

Menggunakan `InstrumentPrice::create()` — **tidak idempotent**, akan duplikat jika dijalankan ulang.

### Kategori Instrumen:

| Kategori | Count | Contoh |
|---|---|---|
| **Gula & Karbohidrat** | 4 | Luff Shrool, Lane-Eynon, Sukrosa |
| **Proksimat** | 3 | Protein (Kjehldal), Vitamin C, Kadar Air, Kadar Garam |
| **Mikrobiologi** | 4 | Anti Bakteri (difusi cakram/sumuran), ALT, E Coli (MPN/Filtrasi) |
| **Serat** | 4 | Lignin, Selulosa, Hemiselulosa, Chesson combo |
| **Fitokimia** | 2 | Antioksidan (DPPH), Flavonoid |
| **Kromatografi** | 5 | GC, GC-MS, HPLC, TLC Scanner (20x20 & 10x10) |
| **Spektroskopi** | 5 | UV-VIS (kualitatif & kuantitatif), ATR FTIR, AAS, XRF |
| **Partikel & Fisik** | 4 | PSA Ukuran Partikel, Zeta Potensial, Bobot Molekul, Viskometer |
| **Termal & Mekanik** | 2 | Konduktivitas Termal, Impact Tester |
| **Hewan Coba** | 17 | Oral, Kolesterol, Darah, Organ, Kulit, Injeksi, Pakan, Kandang, dll |
| **Lain-lain** | 3 | Ekstrak Simplisia, Limbah, Daya Serap Iod |

### Daftar Lengkap:

| # | Nama Instrumen | Unit | Harga Reguler | Harga UNS | Note |
|---|---|---|---|---|---|
| 1 | Analisis Kadar Gula-Gula pereduksi metode Luff Shrool | Per hasil pengujian | 200.000 | - | - |
| 2 | Analisis Kadar Gula-Gula pereduksi metode Lane-Eynon | Per hasil pengujian | 150.000 | - | - |
| 3 | Analisis Sukrosa (Luff Shrool) | Per hasil pengujian | 200.000 | - | - |
| 4 | Analisis Sukrosa (Lane-Eynon) | Per hasil pengujian | 150.000 | - | - |
| 5 | Analisis Kadar Protein (Kjehldal) | Per hasil pengujian | 150.000 | - | - |
| 6 | Analisis Vitamin C | Per hasil pengujian | 100.000 | - | - |
| 7 | Analisis Kadar Air | Per hasil pengujian | 150.000 | 100.000 | - |
| 8 | Analisis Kadar Garam | Per hasil pengujian | 150.000 | 100.000 | - |
| 9 | Pengujian Anti Bakteri (Metode difusi cakram) | 4 hasil pengujian/cawan | 300.000 | 200.000 | - |
| 10 | Pengujian Anti Bakteri (Metode difusi sumuran) | 4 hasil pengujian/cawan | 300.000 | 200.000 | - |
| 11 | Pengujian ALT | Per hasil pengujian | 200.000 | 150.000 | - |
| 12 | Pengujian E Coli metode Most Probable Number (MPN) | Per hasil pengujian | 200.000 | 150.000 | - |
| 13 | Pengujian E Coli metode Filtrasi Membran | Per hasil pengujian | 300.000 | - | - |
| 14 | Pengujian Lignin (metode chesson) | Per hasil pengujian | 150.000 | - | - |
| 15 | Selulosa (metode chesson) | Per hasil pengujian | 110.000 | - | - |
| 16 | Hemiselulosa (metode chesson) | Per hasil pengujian | 110.000 | - | - |
| 17 | Lignin, Selulosa, dan Hemiselulosa (chesson) | Per hasil pengujian | 300.000 | - | - |
| 18 | Analisis antioksidan (Quercetin & DPPH) | Per hasil pengujian | 250.000 | - | - |
| 19 | Analisis Fitokimia metabolit sekunder (Flavonoid) | Per hasil pengujian | 120.000 | - | - |
| 20 | Analisis dengan GC | Per hasil pengujian | 100.000 | 60.000 | - |
| 21 | Analisis dengan GC-MS | Per hasil pengujian | 500.000 | 400.000 | - |
| 22 | Analisis dengan HPLC | Per hasil pengujian | 100.000 | 60.000 | - |
| 23 | Analisis UV-VIS Spektrometer (kualitatif, 200-900 nm) | Per hasil pengujian | 60.000 | 50.000 | - |
| 24 | Analisis UV-VIS Spektrometer (kuantitatif) | Per hasil pengujian | 25.000 | 5.000 | - |
| 25 | Analisis Mercury/Hg dengan DMA (cair, padat, gas) | Per hasil pengujian | 300.000 | 200.000 | - |
| 26 | Pengukuran Ukuran Partikel | Per hasil pengujian | 300.000 | 200.000 | PSA Nano |
| 27 | Pengukuran Zeta Potensial | Per hasil pengujian | 300.000 | 200.000 | PSA Nano |
| 28 | Pengukuran Bobot Molekul | Per hasil pengujian | 600.000 | 500.000 | PSA Nano |
| 29 | Analisis kualitatif ATR FTIR | Per hasil pengujian | 100.000 | 50.000 | - |
| 30 | Analisis dengan TLC Scanner (20x20) | Per plate | 300.000 | 150.000 | KLT |
| 31 | Analisis dengan TLC Scanner (10x10) | Per plate | 200.000 | 100.000 | KLT |
| 32 | Pengukuran Viskometer | Per hasil pengujian | 75.000 | 50.000 | - |
| 33 | Impact tester | Per hasil pengujian | 60.000 | 50.000 | - |
| 34 | AAS | Per hasil pengujian | 25.000 | 10.000 | - |
| 35 | Konduktivitas Termal | Per hasil pengujian | 120.000 | 100.000 | - |
| 36 | XRF | Per hasil pengujian | 300.000 | 200.000 | - |
| 37 | Oral/Hewan Coba | Per ekor | 1.000 | - | - |
| 38 | Cek Kolesterol/Cek Glukosa Hewan coba | Per ekor | 2.000 | - | - |
| 39 | Pengambilan Darah Hewan Coba | Per ekor | 4.000 | - | - |
| 40 | Penimbangan/pemberian kode Hewan Coba | Per ekor | 1.000 | - | - |
| 41 | Pengambilan Organ/Pembedahan Hewan Coba | Per ekor | 10.000 | - | - |
| 42 | Pengambilan Kulit Hewan Coba | Per ekor | 10.000 | - | - |
| 43 | Induksi/Injeksi/Anastesi | Per ekor | 2.000 | - | - |
| 44 | Pengecekan Suhu Hewan Coba | Per ekor | 2.000 | - | - |
| 45 | Pembuatan pakan ternak tinggi lemak | Per Kg | 100.000 | - | - |
| 46 | Pembuatan pakan ternak rendah lemak | Per Kg | 100.000 | - | - |
| 47 | Pembuatan model hewan coba Anemia | Per ekor | 20.000 | - | - |
| 48 | Pembuatan ekstrak Simplisia 0-100 gram | gram | 400.000 | 350.000 | - |
| 49 | Penanganan limbah Hewan Coba | Per ekor | 2.000 | - | - |
| 50 | Pengambilan hasil pengujian kotoran Hewan Coba | Per ekor | 2.000 | - | - |
| 51 | Pemisahan serum Hewan Coba menggunakan centrifuge | Per tube | 2.000 | - | - |
| 52 | Perawatan Kandang, Tempat pakan dan Minum Hewan coba | Per minggu | 20.000 | - | - |
| 53 | Daya serap Iod (Titrimetri) | Per hasil pengujian | 100.000 | - | Dalam UNS |

---

## 5. `FormTemplateSeeder.php` — 4 Template Form + Fields

**Path:** `backend/database/seeders/FormTemplateSeeder.php`

Menggunakan `FormTemplate::firstOrCreate(['type' => ...])` — idempotent.
Fields hanya dibuat jika `$template->fields()->count() === 0` — idempotent.

### 5.1 Template Berita Acara (`type: berita_acara`)

| Name | Formulir Berita Acara |
|---|---|
| Description | Formulir standar untuk pengajuan berita acara pengujian hasil pengujian |

| # | Label | Type | Required | Locked |
|---|---|---|---|---|
| 0 | Nama Pemohon | text | **Yes** | **Yes** (kolom "Pemohon" di tabel) |
| 1 | NIM | text | No | No |
| 2 | Prodi / Fakultas / Instansi | text | No | No |
| 3 | Dosen Pembimbing | text | No | No |
| 4 | Telp. /No. HP | phone | No | No |
| 5 | Email | email | No | No |
| 6 | Alamat | textarea | No | No |
| 7 | Judul (tema) Penelitian | textarea | **Yes** | **Yes** (kolom "Judul Penelitian" di tabel) |
| 8 | Akses Lab di UPT Lab Terpadu | select (Ya/Tidak) | No | No |
| 9 | Bahan contoh uji / material | textarea | No | No |
| 10 | Kemasan (packing) | text | No | No |
| 11 | Permintaan Analisa instrumentasi/Metode | textarea | No | No |
| 12 | Jumlah sampel / contoh uji | number | No | **Yes** (kolom "Jumlah" di tabel) |
| 13 | Kode/Nama Sampel | text | No | **Yes** (kolom "Nama Hasil Pengujian" di tabel) |
| 14 | Keterangan sampel | textarea | No | No |
| 15 | Keterangan Tambahan: | textarea | No | No |
| 16 | Pengukuran/pengulangan per sampel | text | No | No |
| 17 | Sisa sampel setelah pengujian | text | No | No |
| 18 | Upload Akses | file | No | No |
| 19 | Masa Berlaku Akses | text | No | No |
| 20 | Status pelanggan | select (Umum/UNS/Non-UNS) | No | No |

> Field yang `locked: true` adalah yang tampil sebagai kolom di tabel-tabel daftar (Berita Acara, Pembayaran, Hasil Pengujian): Nama Pemohon, Judul Penelitian, Jumlah Sampel, Kode/Nama Sampel. Sisanya customisable di Form Builder.

### 5.2 Template Rekap Analis (`type: rekap_analis`)

| Name | Formulir Rekap Analis |
|---|---|
| Description | Formulir standar untuk rekap data hasil analis |

| # | Label | Type | Required | Locked |
|---|---|---|---|---|
| 0 | Agenda | text | **Yes** (auto-fill dari nomor agenda berita acara) | No |
| 1 | Jumlah Pengujian (total Replikasi, jumlah cawan, jumlah pengukuran) | textarea | No | No |
| 2 | Nama File pengujian (contoh format nama file : FTIR_NamaCustomer_NoAgenda) | text | **Yes** | No |
| 3 | Keterangan Pengujian (Detail pengujian Replikasi, Jumlah Cawan, Jumlah Pengukuran) | textarea | **Yes** | No |

> Catatan: field "Nama" dan "Nama Pengujian" tidak diduplikasi di rekap analis karena sudah tersedia di berita acara ("Nama Pemohon" dan instrumen pada form berita acara). Form rekap analis menggabungkan data berita acara + field tambahan di atas.

### 5.3 Template Perhitungan Biaya (`type: perhitungan_biaya`)

| Name | Formulir Perhitungan Biaya |
|---|---|
| Description | Formulir untuk perhitungan biaya pengujian dan analisis |

| # | Label | Type | Required |
|---|---|---|---|
| 0 | Informasi Pengujian | heading | No |
| 1 | Nama Pengujian | text | **Yes** |
| 2 | Jenis Hasil Pengujian | text | No |
| 3 | Jumlah Hasil Pengujian | number | **Yes** |
| 4 | Biaya Pengujian | number | **Yes** |
| 5 | Diskon | number | No |
| 6 | Total Biaya | number | **Yes** |
| 7 | Keterangan | textarea | No |

### 5.4 Template Kelola Harga Instrumen (`type: kelola_harga_instrumen`)

| Name | Formulir Kelola Harga Instrumen |
|---|---|
| Description | Formulir untuk mengelola harga instrumen dan tarif pengujian |

| # | Label | Type | Required |
|---|---|---|---|
| 0 | Informasi Instrumen | heading | No |
| 1 | Nama Instrumen | text | **Yes** |
| 2 | Merk / Tipe | text | No |
| 3 | Lokasi | text | No |
| 4 | Harga Sewa per Jam | number | **Yes** |
| 5 | Harga Sewa per Hasil Pengujian | number | No |
| 6 | Keterangan | textarea | No |

---

## 6. UserFactory

**Path:** `backend/database/factories/UserFactory.php`

Factory standar Laravel untuk membuat user dummy (testing/development).

```
Default state:
  name:     fake()->name()
  email:    fake()->unique()->safeEmail()
  email_verified_at: now()
  password: bcrypt('password')
  remember_token: Str::random(10)

State: unverified()
  email_verified_at: null
```

> **Catatan:** Factory tidak digunakan oleh seeder utama (`DatabaseSeeder`). Hanya tersedia untuk development/testing manual via `php artisan tinker`.

---

## 7. Cara Menjalankan

```bash
# Fresh migrate + seed (hapus semua data)
php artisan migrate:fresh --seed

# Seed only (setelah migrate)
php artisan db:seed

# Seed specific class
php artisan db:seed --class=PermissionsSeeder
php artisan db:seed --class=InstrumentPriceSeeder
php artisan db:seed --class=FormTemplateSeeder
```

---

## 8. Idempotency

| Seeder | Idempotent? | Mekanisme |
|---|---|---|
| `PermissionsSeeder` | Ya | `Permission::firstOrCreate(['name' => ...])` |
| `DatabaseSeeder` (roles) | Ya | `Role::firstOrCreate(['name' => ...])` + `sync()` |
| `DatabaseSeeder` (superadmin) | Ya | `User::firstOrCreate(['email' => ...])` |
| `InstrumentPriceSeeder` | **Tidak** | `InstrumentPrice::create()` — akan duplikat |
| `FormTemplateSeeder` | Ya | `FormTemplate::firstOrCreate(['type' => ...])` + guard `count() === 0` untuk fields |

---

## 9. Data Akun Default

| Email | Password | Role | is_verified | Permission |
|---|---|---|---|---|
| `superadmin@lab.com` | `password` | superadmin | true | Semua (28) |

> Role `admin`, `analyst`, `user` sudah disiapkan struktur rolenya di database tapi **belum ada akun** — harus dibuat melalui UI Admin Roles atau manual insert.
