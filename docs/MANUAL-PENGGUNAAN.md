# Manual Penggunaan Aplikasi UPT Lab Terpadu (LIMS)

> **Laboratory Information Management System** — Sistem Manajemen Informasi Laboratorium untuk Pengelolaan Pengujian Hasil Pengujian Penelitian

---

## Daftar Isi

1. [Tentang Aplikasi](#1-tentang-aplikasi)
2. [Arsitektur & Tech Stack](#2-arsitektur--tech-stack)
3. [Alur Bisnis Utama](#3-alur-bisnis-utama)
4. [Autentikasi (Login & Register)](#4-autentikasi-login--register)
5. [Panduan Superadmin](#5-panduan-superadmin)
6. [Panduan Admin](#6-panduan-admin)
7. [Panduan Analyst (Analis)](#7-panduan-analyst-analis)
8. [Panduan User (Pengguna)](#8-panduan-user-pengguna)
9. [Manajemen Form Dinamis](#9-manajemen-form-dinamis)
10. [Manajemen Harga Instrumen](#10-manajemen-harga-instrumen)
11. [Sistem Role & Permission](#11-sistem-role--permission)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. Tentang Aplikasi

### Apa itu UPT Lab Terpadu?

**UPT Lab Terpadu** adalah aplikasi web berbasis **Laboratory Information Management System (LIMS)** yang dirancang untuk mengelola seluruh alur pengujian hasil pengujian penelitian di laboratorium terpadu. Aplikasi ini memudahkan peneliti/mahasiswa, analis, dan admin laboratorium dalam mengelola pengajuan pengujian, pembayaran, pemrosesan hasil pengujian, hingga penerbitan sertifikat hasil pengujian.

### Siapa yang Menggunakan?

| Role | Deskripsi |
|---|---|
| **Superadmin** | Pengelola utama sistem. Mengelola role, permission, user, dan memiliki akses penuh ke seluruh fitur. |
| **Admin** | Pengelola operasional lab. Memverifikasi pengajuan, mengkonfirmasi pembayaran, mengelola form dan harga instrumen. |
| **Analyst** | Teknisi/peneliti yang melakukan pengujian hasil pengujian. Memproses hasil pengujian, menginput hasil pengujian, dan membuat rekap analis. |
| **User** | Peneliti/mahasiswa yang mengajukan pengujian hasil pengujian. Membuat berita acara, mengunggah bukti pembayaran, dan mengunduh sertifikat. |

### Fitur Utama

- **Autentikasi** — Login, registrasi, profil, ubah password
- **Berita Acara (BA)** — Pengajuan pengujian hasil pengujian dengan form dinamis 20+ field
- **Pembayaran** — Upload bukti pembayaran, diskon, konfirmasi admin
- **Manajemen Hasil Pengujian** — Pelacakan status hasil pengujian, hasil pengujian
- **Sertifikat** — Unduh sertifikat hasil pengujian
- **Form Builder** — Pembuat form dinamis dengan 14 tipe field (drag-and-drop)
- **Manajemen Harga Instrumen** — Pengelolaan harga instrumen laboratorium
- **RBAC** — Role-Based Access Control dengan 22 permission

---

## 2. Arsitektur & Tech Stack

| Layer | Teknologi | Port |
|---|---|---|
| **Frontend** | Vue 3 + TypeScript + Pinia + Vite | 5173 (default) |
| **Backend** | Laravel 12 (PHP 8.2+) — API Only | 8000 |
| **Database** | MySQL (via XAMPP) | 3306 |

### Menjalankan Aplikasi

```bash
# Install dependencies
npm install

# Jalankan frontend & backend sekaligus
npm run dev

# Atau jalankan terpisah
npm run dev:frontend    # Hanya frontend (port 5173)
npm run dev:backend     # Hanya backend (port 8000)
```

### Default Akun Superadmin

| Field | Value |
|---|---|
| Email | `superadmin@lab.com` |
| Password | `password` |

---

## 3. Alur Bisnis Utama

Aplikasi ini mengikuti alur bisnis 4 langkah:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   1. PENGAJUAN  │───▶│  2. PEMBAYARAN  │───▶│  3. PENGAMBILAN │───▶│ 4. SERTIFIKAT  │
│   (Berita Acara)│    │  (Upload Bukti) │    │ (Proses Hasil Pengujian) │    │  (Download)     │
│                 │    │                 │    │                 │    │                 │
│   User/Role:    │    │   User/Role:    │    │   User/Role:    │    │   User/Role:    │
│   User          │    │   User + Admin  │    │   Analyst+Admin │    │   User          │
└─────────────────┘    └─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Detail Alur:

1. **Pengajuan (Berita Acara)**
   - User mengisi form pengajuan dengan data diri, detail penelitian, informasi hasil pengujian, dan kebutuhan akses lab
   - Admin memverifikasi, meminta revisi, atau menolak pengajuan
   - Admin menugaskan analis ke pengajuan

2. **Pembayaran**
   - Setelah disetujui, user mengunggah bukti pembayaran
   - User dapat meminta diskon
   - Admin mengkonfirmasi atau menolak pembayaran

3. **Pengambilan (Proses Hasil Pengujian)**
   - Analis memproses hasil pengujian yang ditugaskan
   - Analis menginput data hasil pengujian dan hasil pengujian
   - Analis membuat rekap analis (rekapan biaya)
   - Admin menandai hasil pengujian sebagai "diambil"

4. **Sertifikat**
   - User mengunduh sertifikat hasil pengujian
   - Waktu unduh tercatat di sistem

---

## 4. Autentikasi (Login & Register)

### Login

1. Buka halaman login di `/login`
2. Masukkan **email** dan **password**
3. Klik tombol **"Masuk"**
4. Anda akan diarahkan ke dashboard sesuai role Anda

**Catatan Penting:**
- Jika akun belum diverifikasi oleh superadmin, login akan gagal dengan pesan "belum diverifikasi oleh superadmin"
- Sistem menggunakan token satu sesi — setiap login baru akan menggantikan token sebelumnya

### Register (Daftar Akun Baru)

1. Buka halaman registrasi di `/register`
2. Isi form registrasi:
   - **Nama Lengkap**
   - **Email**
   - **Password**
   - **Nomor Telepon**
   - **Role** — Pilih `user` atau `analyst` (superadmin tidak bisa didaftarkan lewat sini)
3. Klik tombol **"Daftar"**
4. Akun akan dibuat dengan status **belum diverifikasi** — menunggu approval dari superadmin

### Logout

1. Klik icon profil di navbar (kanan atas)
2. Pilih **"Logout"**
3. Token akan dihapus dari database dan localStorage

### Ubah Profil

1. Klik icon profil di navbar
2. Pilih **"Profil"**
3. Ubah data yang diperlukan
4. Klik **"Simpan"**

### Ubah Password

1. Klik icon profil di navbar
2. Pilih **"Ubah Password"**
3. Masukkan password lama dan password baru
4. Klik **"Simpan"**

---

## 5. Panduan Superadmin

> **Superadmin memiliki akses penuh ke SELURUH fitur aplikasi.** Semua permission check dan role check di-bypass untuk superadmin.

### 5.1 Dashboard Superadmin

**URL:** `/admin/dashboard`

Dashboard menampilkan ringkasan statistik:
- Total pengajuan (berita acara)
- Total pembayaran
- Total hasil pengujian
- Total pengguna
- Status pengajuan terkini

### 5.2 Manajemen Role & Permission

**URL:** `/admin/roles`

Superadmin adalah satu-satunya role yang bisa mengelola role dan permission.

#### Melihat Daftar Role

1. Buka menu **"Kelola Role"** di sidebar
2. Daftar role akan ditampilkan dalam tabel
3. Setiap role menampilkan: nama, jumlah user, jumlah permission

#### Membuat Role Baru

1. Klik tombol **"+ Tambah Role"**
2. Isi **Nama Role** (contoh: "petugas_lab")
3. Isi **Deskripsi** (opsional)
4. Pilih permission yang diinginkan dari daftar yang tersedia
   - Permission dikelompokkan per grup: Umum, Berita Acara, Pembayaran, Hasil Pengujian, Sertifikat, Pengguna, Role, Form
   - Centang permission yang diinginkan
5. Klik **"Simpan"**

#### Mengedit Role

1. Klik tombol **"Edit"** pada role yang ingin diubah
2. Ubah nama, deskripsi, atau permission
3. Klik **"Simpan"**

#### Menghapus Role

1. Klik tombol **"Hapus"** pada role yang ingin dihapus
2. Konfirmasi penghapusan
3. **Peringatan:** Role yang sedang digunakan oleh user tidak bisa dihapus

#### Daftar 22 Permission yang Tersedia

| Grup | Permission | Fungsi |
|---|---|---|
| **Umum** | `dashboard` | Akses dashboard |
| **Berita Acara** | `berita_acara_riwayat_personal` | Melihat daftar berita acara |
| | `berita_acara_create` | Membuat berita acara baru |
| | `berita_acara_verify` | Memverifikasi berita acara |
| | `berita_acara_assign` | Menugaskan analis |
| | `berita_acara_riwayat_personal` | Melihat riwayat BA sendiri |
| | `berita_acara_riwayat_semua` | Melihat riwayat BA semua orang |
| **Pembayaran** | `payment_riwayat_personal` | Melihat daftar pembayaran |
| | `payment_upload` | Upload bukti pembayaran |
| | `payment_discount` | Meminta diskon |
| | `payment_confirm` | Mengkonfirmasi pembayaran |
| **Hasil Pengujian** | `hasil_pengujian_rekap` | Melihat daftar hasil pengujian |
| | `hasil_pengujian_rekap` | Input data hasil pengujian |
| | `hasil_pengujian_rekap` | Membuat rekap analis |
| | `hasil_pengujian_rekap` | Update status hasil pengujian |
| | `hasil_pengujian_riwayat_personal` | Riwayat hasil pengujian sendiri |
| | `hasil_pengujian_riwayat_semua` | Riwayat hasil pengujian semua orang |
| **Sertifikat** | `certificate_view` | Melihat sertifikat |
| | `certificate_download` | Download sertifikat |
| **Pengguna** | `user_view` | Melihat daftar pengguna |
| | `user_verify` | Memverifikasi pengguna |
| **Role** | `(superadmin only)` | Mengelola role |
| **Form** | `form_manage_berita_acara` | Kelola form berita acara |
| | `form_manage_rekap_analis` | Kelola form rekap analis |
| | `form_manage_perhitungan_biaya` | Kelola form perhitungan biaya |
| | `form_manage_kelola_harga_instrumen` | Kelola form harga instrumen |

### 5.3 Manajemen Pengguna

**URL:** `/admin/users`

#### Melihat Daftar Pengguna

1. Buka menu **"Kelola Pengguna"** di sidebar
2. Daftar pengguna ditampilkan dengan informasi: nama, email, role, status verifikasi

#### Memverifikasi Pengguna

1. Cari pengguna yang belum diverifikasi (status "Belum Diverifikasi")
2. Klik tombol **"Verifikasi"** pada pengguna tersebut
3. Pengguna akan dapat login setelah diverifikasi

#### Menonaktifkan Pengguna

1. Klik tombol **"Nonaktifkan"** pada pengguna yang ingin dinonaktifkan
2. Pengguna tidak akan bisa login sampai diaktifkan kembali

### 5.4 Mengelola Berita Acara

**URL:** `/admin/berita-acara`

Superadmin bisa melihat **semua** berita acara dari seluruh pengguna.

#### Memverifikasi Berita Acara

1. Klik detail berita acara yang perlu diverifikasi
2. Tinjau data yang diinput oleh user
3. Pilih salah satu aksi:
   - **"Setujui"** — Berita acara disetujui, lanjut ke tahap pembayaran
   - **"Minta Revisi"** — Kembalikan ke user untuk perbaikan (sertakan catatan revisi)
   - **"Tolak"** — Tolak pengajuan (sertakan alasan)

#### Menugaskan Analis

1. Buka detail berita acara yang sudah disetujui
2. Pilih analis yang akan ditugaskan dari dropdown
3. Bisa menugaskan **lebih dari satu analis** untuk satu berita acara
4. Klik **"Simpan"**

#### Mengkonfirmasi Pembayaran

1. Buka menu **"Pembayaran"** di sidebar
2. Lihat daftar pembayaran yang menunggu konfirmasi
3. Klik detail pembayaran
4. Tinjau bukti pembayaran yang diunggah
5. Pilih:
   - **"Konfirmasi"** — Pembayaran diterima
   - **"Tolak"** — Pembayaran ditolak (sertakan alasan)

### 5.5 Mengelola Form Dinamis

**URL:** `/admin/forms`

Lihat [Bagian 9: Manajemen Form Dinamis](#9-manajemen-form-dinamis) untuk panduan lengkap.

### 5.5a Verifikasi Berita Acara (Nomor Agenda)

**URL:** `/admin/berita-acara/{id}` → kartu "Aksi"

Sebelum menyetujui pengajuan, admin wajib mengisi **Nomor Agenda** (tersimpan otomatis saat selesai mengetik). Saat mengetik, sistem otomatis memeriksa ketersediaan nomor (icon spinner muncul selama pengecekan) — status **"Tersedia"** (hijau) atau **"Sudah dipakai"** (merah). Tombol **Verifikasi** tetap nonaktif selama pengecekan berlangsung dan baru bisa diklik setelah nomor agenda terisi dan tersedia. Aksi **Perlu Perbaikan (revisi)** dan **Ditolak** tetap bisa dilakukan tanpa nomor agenda.

Popup verifikasi menampilkan ringkasan: **Nomor Agenda**, **Judul**, **Pemohon** (nama + email pengaju), dan **Instrumen** sebelum admin menekan tombol **Setujui**.

### 5.6 Mengelola Harga Instrumen

**URL:** `/admin/form-instruments`

Lihat [Bagian 10: Manajemen Harga Instrumen](#10-manajemen-harga-instrumen) untuk panduan lengkap.

### 5.7 Melihat Semua Riwayat

Superadmin dengan permission `berita_acara_riwayat_semua` dan `hasil_pengujian_riwayat_semua` bisa melihat riwayat lengkap dari semua pengguna.

### 5.8 Mengelola Template Dokumen PDF

**URL:** `/admin/forms` → kartu **"Template Dokumen PDF"**

Superadmin bisa menyesuaikan tampilan 4 jenis dokumen PDF (Berita Acara, Rekap Analisis, Tagihan, Sertifikat) **tanpa perlu coding**:

1. **Unduh Template Word** — klik "Unduh Template Word" untuk mengambil file .docx template saat ini.
2. **Edit di Microsoft Word** — tulis `<<nama_variabel>>` di posisi yang diinginkan. Contoh: untuk menampilkan jawaban pertanyaan "Nama Pemohon", ketik `<<Nama Pemohon>>` (nama pertanyaan boleh ditulis apa adanya; spasi & huruf besar otomatis dicocokkan). Saat PDF dibuat, tag diganti dengan jawaban user.
3. **Upload kembali** — unggah file yang sudah diedit di kolom "Upload Template Word". File Word ini menjadi sumber utama dokumen.
4. **Pratinjau** — klik tab "Preview". Jika template punya file Word, pratinjau adalah **tampilan file Word itu sendiri** (persis seperti di Word, nilai placeholder diisi data contoh) — dirender di browser, tidak butuh instalasi tambahan. Jika belum ada file Word, pratinjau memakai rendering HTML.
5. **Simpan Template** — simpan perubahan. Tombol "Kembalikan ke Template Awal" mengembalikan template bawaan.

**Unduhan dokumen (berlaku untuk Berita Acara, Rekap Analis, Tagihan, Sertifikat):**
- Tombol **"Word …"** → file .docx = template Word + data terisi. Selalu tersedia (tanpa dependensi server).
- Tombol **"PDF …"** → dihasilkan dari file Word yang sama: server merender .docx terisi lewat **Chrome headless + docx-preview** (aset di `public/pdf-render/`) — mesin render yang sama dengan pratinjau, jadi PDF identik dengan pratinjau. Bila Chrome/Edge tidak tersedia, fallback ke PhpWord→PDF (murni PHP); bila semua gagal, PDF otomatis memakai rendering HTML lama.

**Daftar Variabel yang Didukung** menampilkan 4 kelompok (klik variabel untuk menyalin):
- **Pertanyaan Form Berita Acara** — semua pertanyaan di form BA aktif (misal `<<nama_pemohon>>`, `<<nim>>`)
- **Pertanyaan Form Rekap Analis** — semua pertanyaan form rekap (misal `<<nama_analis>>`, `<<parameter_uji>>`)
- **Pembayaran & Diskon** — `<<no_tagihan>>`, `<<subtotal>>`, `<<diskon>>`, `<<total_bayar>>`, `<<rincian_biaya_tabel>>`, dll.
- **Data Dokumen** — `<<tanggal_cetak>>`, `<<kop_nama_lab>>`, `<<kepala_upt>>`, `<<tabel_sampel>>`, dll.

**Catatan Tambahan** menampilkan teks di bagian bawah dokumen.

---

## 6. Panduan Admin

> **Admin mengelola operasional laboratorium.** Memverifikasi pengajuan, mengkonfirmasi pembayaran, mengelola form dan harga instrumen.

### 6.1 Dashboard Admin

**URL:** `/admin/dashboard`

Dashboard menampilkan:
- Statistik pengajuan terkini
- Jumlah pembayaran pending
- Jumlah hasil pengujian yang perlu diproses
- Ringkasan aktivitas lab

### 6.2 Mengelola Berita Acara

**URL:** `/admin/berita-acara`

#### Melihat Daftar Berita Acara

1. Buka menu **"Berita Acara"** di sidebar
2. Daftar berita acara ditampilkan dengan status: Draft, Menunggu Verifikasi, Disetujui, Ditolak, Revisi
3. Gunakan filter untuk mencari berita acara tertentu

#### Memverifikasi Berita Acara

1. Klik **detail** berita acara yang berstatus "Menunggu Verifikasi"
2. Tinjau semua data yang diinput:
   - Data diri pemohon
   - Detail penelitian
   - Informasi hasil pengujian
   - Kebutuhan akses laboratorium
   - Form dinamis tambahan (jika ada)
3. Pilih aksi:
   - **✅ Setujui** — Berita acara lanjut ke tahap pembayaran
   - **📝 Minta Revisi** — Kembalikan ke user dengan catatan perbaikan
   - **❌ Tolak** — Tolak pengajuan dengan alasan

#### Menugaskan Analis

1. Buka detail berita acara yang sudah disetujui
2. Scroll ke bagian **"Penugasan Analis"**
3. Pilih analis dari dropdown yang tersedia
4. Bisa menugaskan **beberapa analis** sekaligus
5. Klik **"Simpan Penugasan"**
6. Setelah ter-assign, tombol berubah menjadi ringkasan **"Analis Ditugaskan"** beserta nama analis dan ikon **edit** (✏️) untuk mengubah penugasan

#### Menugaskan Penyelia

1. Buka detail berita acara di menu Berita Acara (admin)
2. Di kartu **"Aksi"**, klik tombol **"Assign Penyelia"** (di bawah Assign Analis)
3. Centang satu atau lebih penyelia (daftar hanya menampilkan user dengan role **Penyelia**)
4. Klik **"Assign"** — tercatat di Riwayat Aktivitas sebagai **"Penyelia Ditugaskan"**
5. Setelah ter-assign, tombol berubah menjadi ringkasan **"Penyelia Ditugaskan"** beserta nama penyelia dan ikon **edit** (✏️) untuk mengubah penugasan

#### Menugaskan Manager Teknis

1. Buka detail berita acara di menu Berita Acara (admin)
2. Di kartu **"Aksi"**, klik tombol **"Assign Manager Teknis"** (di bawah Assign Penyelia)
3. Centang satu atau lebih manager teknis (daftar hanya menampilkan user dengan role **Manager Teknis**)
4. Klik **"Assign"** — tercatat di Riwayat Aktivitas sebagai **"Manager Teknis Ditugaskan"**
5. Setelah ter-assign, tombol berubah menjadi ringkasan **"Manager Teknis Ditugaskan"** beserta nama manager teknis dan ikon **edit** (✏️) untuk mengubah penugasan

#### Melihat Riwayat Revisi

1. Buka detail berita acara
2. Riwayat revisi tampil sebagai bagian dari **"Riwayat Aktivitas"** (sudah digabung, tidak ada card terpisah)
3. Pada item **"Revisi Diminta"**, di bawah waktu (kolom kanan) klik **"📄 Detail Perbaikan"** untuk melihat catatan revisi dan diff data form sebelum/sesudah
4. Setiap revisi mencatat: catatan revisi dan data form sebelumnya

### 6.3 Mengelola Pembayaran

**URL:** `/admin/payments`

#### Melihat Daftar Pembayaran

1. Buka menu **"Pembayaran"** di sidebar
2. Daftar pembayaran ditampilkan dengan status: Menunggu, Dikonfirmasi, Ditolak
3. Lihat detail: jumlah, diskon yang diminta, bukti pembayaran

#### Mengkonfirmasi Pembayaran

1. Klik **detail** pembayaran yang menunggu konfirmasi
2. Tinjau bukti pembayaran (file gambar/PDF)
3. Periksa apakah ada permintaan diskon
4. Pilih aksi:
   - **✅ Konfirmasi** — Pembayaran diterima
   - **❌ Tolak** — Pembayaran ditolak dengan alasan

#### Menangani Diskon

1. Jika ada permintaan diskon, nominal asli dan nominal diskon ditampilkan
2. Tinjau alasan diskon dari user
3. Setujui atau tolak diskon saat mengkonfirmasi pembayaran

### 6.4 Mengelola Hasil Pengujian

**URL:** `/admin/samples`

#### Melihat Status Hasil Pengujian

1. Buka menu **"Hasil Pengujian"** di sidebar
2. Daftar hasil pengujian ditampilkan berdasarkan berita acara
3. Status hasil pengujian: Pending, Processing, Ready, Taken

#### Menandai Hasil Pengujian Sebagai "Diambil"

1. Klik detail hasil pengujian yang berstatus "Ready"
2. Klik tombol **"Tandai Diambil"**
3. Status hasil pengujian berubah menjadi "Taken"

### 6.5 Mengelola Form Template

**URL:** `/admin/forms`

Admin bisa membuat dan mengelola form template untuk berbagai keperluan.

#### Membuat Form Baru

1. Klik **"+ Tambah Form"**
2. Pilih **Tipe Form**:
   - `berita_acara` — Form pengajuan pengujian
   - `rekap_analis` — Form rekap analis
   - `perhitungan_biaya` — Form perhitungan biaya
   - `kelola_harga_instrumen` — Form pengelolaan harga instrumen
3. Beri **Nama Form** dan **Deskripsi**
4. Klik **"Simpan"**

#### Menambah Field ke Form

1. Buka form yang ingin diedit
2. Di panel kiri, pilih tipe field yang ingin ditambahkan (14 tipe tersedia):
   - **Text** — Input teks singkat
   - **Textarea** — Input teks panjang
   - **Number** — Input angka
   - **Select** — Dropdown pilihan
   - **Checkbox** — Centang (bisa banyak pilihan)
   - **Radio** — Pilihan tunggal (opsi)
   - **Date** — Pilih tanggal
   - **Time** — Pilih waktu
   - **Email** — Input email
   - **Phone** — Input nomor telepon
   - **File** — Upload file
   - **Heading** — Judul/seksi
   - **Paragraph** — Teks penjelasan
   - **Divider** — Garis pemisah
3. Drag field ke area form di panel kanan
4. Konfigurasi field:
   - **Label** — Nama field
   - **Placeholder** — Teks bantuan
   - **Required** — Wajib diisi atau tidak
   - **Options** — Pilihan untuk select/checkbox/radio
5. **Urutkan field** dengan drag-and-drop
6. Klik **"Simpan"**

#### Field yang Dikunci (Locked)

- Beberapa field bersifat "locked" dan tidak bisa dihapus oleh admin
- Field locked ditandai dengan ikon gembok
- Field ini merupakan field sistem yang sudah terintegrasi dengan backend

### 6.6 Mengelola Pengguna

**URL:** `/admin/users`

#### Memverifikasi Pengguna

1. Buka menu **"Kelola Pengguna"**
2. Cari pengguna dengan status **"Belum Diverifikasi"**
3. Klik tombol **"Verifikasi"**
4. Pengguna akan bisa login setelah diverifikasi

#### Menonaktifkan Pengguna

1. Klik tombol **"Nonaktifkan"** pada pengguna
2. Pengguna tidak akan bisa login

### 6.7 Mengelola Perhitungan Biaya

**URL:** `/admin/form-pricing`

Admin bisa mengelola form perhitungan biaya pengujian.

### 6.8 Mengelola Harga Instrumen

**URL:** `/admin/form-instruments`

Admin bisa mengelola daftar harga instrumen laboratorium.

Lihat [Bagian 10](#10-manajemen-harga-instrumen) untuk panduan lengkap.

---

## 7. Panduan Analyst (Analis)

> **Analyst memproses hasil pengujian dan menginput hasil pengujian.** Hanya bisa melihat berita acara yang ditugaskan kepadanya.

### 7.1 Dashboard Analyst

**URL:** `/analyst/dashboard`

Dashboard menampilkan:
- Berita acara yang ditugaskan
- Status pemrosesan hasil pengujian
- Statistik pengujian

### 7.2 Melihat Berita Acara yang Ditugaskan

**URL:** `/analyst/samples`

1. Buka menu **"Hasil Pengujian"** di sidebar
2. Daftar berita acara yang ditugaskan ke Anda ditampilkan
3. Klik detail untuk melihat informasi lengkap:
   - Data pemohon
   - Detail penelitian
   - Daftar hasil pengujian
   - Status setiap hasil pengujian

### 7.3 Menginput Data Hasil Pengujian

1. Buka detail berita acara yang ditugaskan
2. Pada bagian **"Data Hasil Pengujian"**, klik **"Input/Edit Hasil Pengujian"**
3. Isi data hasil pengujian:
   - **Nama Hasil Pengujian** — Identitas hasil pengujian
   - **Deskripsi** — Keterangan hasil pengujian
   - **Jumlah** — Kuantitas hasil pengujian
   - **Satuan** — Unit pengukuran
4. Klik **"Simpan"**

### 7.4 Menginput Hasil Pengujian

1. Buka detail hasil pengujian yang sudah diproses
2. Pada bagian **"Hasil Pengujian"**, masukkan hasil uji
3. Upload file hasil pengujian (jika diperlukan)
4. Klik **"Simpan Hasil"**

### 7.5 Menandai Hasil Pengujian Selesai

1. Buka detail hasil pengujian
2. Ubah status menjadi **"Ready"** (Siap Diambil)
3. Hasil Pengujian akan muncul di panel admin untuk ditandai "Diambil"

### 7.6 Membuat Rekap Analis

**URL:** `/analyst/samples/:id/rekap`

Rekap analis adalah dokumen rekapan yang berisi ringkasan pengujian dan perhitungan biaya.

1. Buka detail berita acara yang ditugaskan
2. Klik tombol **"Buat Rekap Analis"**
3. Isi form rekap analis:
   - Data pengujian
   - Rincian biaya
   - Keterangan tambahan
4. Form ini menggunakan template form type `rekap_analis` yang sudah dikonfigurasi admin
5. Klik **"Simpan Rekap"**
6. Rekap akan memperbarui data berita acara dan menandai hasil pengujian sebagai "taken"

### 7.7 Meminta Diskon

1. Buka detail berita acara
2. Klik tombol **"Minta Diskon"**
3. Masukkan nominal diskon dan alasan
4. Permintaan diskon akan dikirim ke admin untuk persetujuan

### 7.8 Menyimpan Pengujian (Testing Records)

**URL:** `/analyst/samples/:id`

1. Buka detail berita acara
2. Klik tombol **"Input Pengujian"**
3. Isi form pengujian:
   - **Nama Pengujian** — Jenis pengujian yang dilakukan
   - **Agenda** — Deskripsi pengujian
   - **Jumlah Pengujian** — Berapa kali pengujian dilakukan
4. Klik **"Simpan"**

---

## 8. Panduan User (Pengguna)

> **User adalah peneliti/mahasiswa yang mengajukan pengujian hasil pengujian.** Membuat berita acara, upload bukti pembayaran, dan unduh sertifikat.

### 8.1 Dashboard User

**URL:** `/user/dashboard`

Dashboard menampilkan:
- Ringkasan pengajuan Anda
- Status berita acara terkini
- Statistik pribadi

### 8.2 Membuat Berita Acara (Pengajuan Pengujian)

**URL:** `/user/berita-acara/create`

#### Langkah-langkah:

1. Buka menu **"Berita Acara"** di sidebar
2. Klik tombol **"+ Buat Baru"**
3. Isi form pengajuan yang terdiri dari beberapa seksi:

**Seksi 1: Data Diri Pemohon**
- Nama lengkap
- NIM/NIDN (untuk mahasiswa/dosen)
- Institusi/Universitas
- Jurusan/Program Studi
- Email
- Nomor Telepon
- Alamat

**Seksi 2: Detail Penelitian**
- Judul penelitian
- Deskripsi penelitian
- Tujuan pengujian
- Metode pengujian

**Seksi 3: Informasi Hasil Pengujian**
- Nama hasil pengujian
- Jenis hasil pengujian
- Jumlah hasil pengujian
- Kondisi hasil pengujian
- Penanganan khusus (jika ada)

**Seksi 4: Kebutuhan Akses Lab**
- Waktu akses yang dibutuhkan
- Peralatan khusus yang diperlukan
- Instrumen spesifik

**Seksi 5: Form Dinamis Tambahan**
- Form tambahan yang dikonfigurasi oleh admin
- Field bisa berupa teks, angka, upload file, dll.

4. **Simpan sebagai Draft** atau **Kirim Langsung**
   - **Draft** — Bisa diedit kapan saja
   - **Kirim** — Langsung dikirim ke admin untuk verifikasi

5. Setelah dikirim, status berubah menjadi **"Menunggu Verifikasi"**

### 8.3 Mengelola Berita Acara

**URL:** `/user/berita-acara`

#### Melihat Daftar Berita Acara

1. Buka menu **"Berita Acara"** di sidebar
2. Daftar berita acara Anda ditampilkan
3. Status yang mungkin:
   - **Draft** — Belum dikirim
   - **Menunggu Verifikasi** — Menunggu admin verifikasi
   - **Revisi** — Perlu perbaikan
   - **Disetujui** — Sudah disetujui, lanjut ke pembayaran
   - **Ditolak** — Pengajuan ditolak

#### Mengedit Berita Acara (Draft)

1. Klik tombol **"Edit"** pada berita acara berstatus Draft
2. Ubah data yang diperlukan
3. Klik **"Simpan"** atau **"Kirim"**

#### Melihat Detail & Riwayat Revisi

1. Klik **detail** berita acara
2. Lihat status terkini
3. Riwayat revisi tampil dalam **"Riwayat Aktivitas"**: di bawah waktu (kolom kanan) klik **"📄 Detail Perbaikan"** pada item "Revisi Diminta" untuk melihat catatan dan diff data sebelum/sesudah
4. Jika status **"Perlu Revisi"**, tombol **"Perbaiki"** muncul di bawah waktu pada item revisi terbaru (tombol "Detail Perbaikan" disembunyikan selama belum diperbaiki) — klik untuk mengubah dan mengirim ulang

### 8.4 Mengunggah Bukti Pembayaran

**URL:** `/user/payments`

#### Langkah-langkah:

1. Pastikan berita acara Anda sudah **disetujui**
2. Buka menu **"Pembayaran"** di sidebar
3. Klik tombol **"+ Buat Pembayaran"**
4. Pilih berita acara yang akan dibayar
5. Isi informasi pembayaran:
   - **Jumlah Pembayaran**
   - **Metode Pembayaran** (Transfer Bank, dll.)
   - **Catatan** (opsional)
6. **Upload Bukti Pembayaran** — Drag & drop atau klik untuk upload file (gambar/PDF)
7. Klik **"Kirim"**
8. Status pembayaran berubah menjadi **"Menunggu Konfirmasi"**

### 8.5 Meminta Diskon Pembayaran

1. Buka detail pembayaran
2. Klik tombol **"Minta Diskon"**
3. Isi:
   - **Nominal Diskon** — Jumlah yang ingin dikurangi
   - **Alasan Diskon** — Penjelasan mengapa meminta diskon
4. Klik **"Kirim"**
5. Permintaan akan dilihat oleh admin saat mengkonfirmasi pembayaran

### 8.6 Mengunduh Sertifikat

**URL:** `/user/certificates`

#### Langkah-langkah:

1. Pastikan pembayaran sudah **dikonfirmasi** oleh admin
2. Buka menu **"Sertifikat"** di sidebar
3. Daftar sertifikat yang tersedia ditampilkan
4. Klik tombol **"Download"** pada sertifikat yang ingin diunduh
5. File sertifikat akan diunduh
6. Waktu unduh tercatat di sistem

### 8.7 Melihat Riwayat Pengajuan

1. Buka menu **"Berita Acara"** di sidebar
2. Semua pengajuan Anda tercatat di sini
3. Klik detail untuk melihat riwayat lengkap: dari draft hingga sertifikat

---

## 9. Manajemen Form Dinamis

**URL:** `/admin/forms`

### Apa itu Form Builder?

Form Builder memungkinkan admin membuat form kustom untuk berbagai keperluan tanpa perlu coding. Form yang dibuat akan digunakan oleh user/analyst saat mengisi data.

### Tipe Form yang Tersedia

| Tipe | Digunakan Untuk |
|---|---|
| `berita_acara` | Form pengajuan pengujian hasil pengujian |
| `rekap_analis` | Form rekap analis |
| `perhitungan_biaya` | Form perhitungan biaya |
| `kelola_harga_instrumen` | Form pengelolaan harga instrumen |

### 14 Tipe Field yang Tersedia

| Tipe | Deskripsi | Konfigurasi |
|---|---|---|
| **Text** | Input teks singkat | Label, placeholder, required |
| **Textarea** | Input teks panjang | Label, placeholder, required |
| **Number** | Input angka | Label, placeholder, min, max, required |
| **Select** | Dropdown pilihan | Label, options (daftar pilihan), required |
| **Checkbox** | Centang (multi pilihan) | Label, options, required |
| **Radio** | Pilihan tunggal | Label, options, required |
| **Date** | Pilih tanggal | Label, required |
| **Time** | Pilih waktu | Label, required |
| **Email** | Input email | Label, placeholder, required |
| **Phone** | Input nomor telepon | Label, placeholder, required |
| **File** | Upload file | Label, accepted types, required |
| **Heading** | Judul/seksi | Teks heading |
| **Paragraph** | Teks penjelasan | Teks panjang |
| **Divider** | Garis pemisah | Tidak ada konfigurasi |

### Cara Membuat/Edit Form

1. Buka menu **"Kelola Form"** di sidebar
2. Klik **"+ Tambah Form"** untuk form baru, atau **"Edit"** pada form yang sudah ada
3. Di panel kiri (**Toolbox**), pilih tipe field
4. **Drag** field dari toolbox ke area form di panel kanan
5. **Klik field** untuk mengedit propertinya:
   - Label — Nama field yang ditampilkan
   - Placeholder — Teks bantuan di dalam input
   - Required — Centang jika wajib diisi
   - Options — Daftar pilihan (untuk select/checkbox/radio)
6. **Urutkan field** dengan drag-and-drop
7. **Hapus field** dengan klik ikon hapus (kecuali field locked)
8. Klik **"Simpan"** untuk menyimpan form

### Field Locked (Terkunci)

- Beberapa field bersifat "locked" oleh sistem
- Field locked ditandai dengan ikon gembok
- **Tidak bisa dihapus** oleh admin
- Field ini terintegrasi langsung dengan backend

---

## 10. Manajemen Harga Instrumen

**URL:** `/admin/form-instruments`

### Apa itu Manajemen Harga Instrumen?

Fitur ini memungkinkan admin mengelola daftar harga sewa/penggunaan instrumen laboratorium. Harga ditampilkan ke user saat membuat pengajuan.

### Melihat Daftar Harga Instrumen

1. Buka menu **"Harga Instrumen"** di sidebar
2. Daftar instrumen ditampilkan dalam tabel:
   - Nama Instrumen
   - Satuan
   - Harga Regular
   - Harga UNS
   - Catatan
   - Urutan

### Menambah Harga Instrumen Baru

1. Klik tombol **"+ Tambah"**
2. Isi form:
   - **Nama Instrumen** — Nama instrumen laboratorium
   - **Satuan** — Unit pengukuran (contoh: "per jam", "per hasil pengujian")
   - **Harga Regular** — Harga untuk pengguna umum
   - **Harga UNS** — Harga khusus mahasiswa/pegawai UNS
   - **Catatan** — Keterangan tambahan (opsional)
   - **Urutan** — Posisi tampilan dalam daftar
3. Klik **"Simpan"**

### Mengedit Harga Instrumen

1. Klik tombol **"Edit"** pada instrumen yang ingin diubah
2. Ubah data yang diperlukan
3. Klik **"Simpan"**

### Menghapus Harga Instrumen

1. Klik tombol **"Hapus"** pada instrumen yang ingin dihapus
2. Konfirmasi penghapusan

### Bulk Update Harga

1. Pilih beberapa instrumen dengan checkbox
2. Klik tombol **"Update Harga"**
3. Masukkan persentase atau nominal kenaikan/penurunan
4. Semua harga yang dipilih akan diperbarui sekaligus

---

## 11. Sistem Role & Permission

### Arsitektur Role

Sistem ini menggunakan **hybrid role approach**:

1. **String Role** — `users.role` menyimpan string role (superadmin, admin, analyst, user) untuk pengecekan cepat
2. **RBAC Table** — Tabel `roles` dan `permissions` dengan pivot `role_permission` untuk pengelolaan permission yang fleksibel

### Bagaimana Permission Bekerja

1. Saat login, permission user dimuat dari database via relasi `roleModel.permissions`
2. Permission disimpan di state frontend (`authStore`)
3. Setiap menu/route mengecek permission sebelum menampilkan/mengakses
4. **Superadmin bypass** — Semua permission check di-skip untuk superadmin

### Flow Pengecekan Permission

```
User Login
    │
    ▼
Load user.permissions dari database
    │
    ▼
Simpan di authStore.permissions (localStorage)
    │
    ▼
Saat navigasi:
    │
    ├── hasPermission('permission_name')?
    │       │
    │       ├── Superadmin → selalu TRUE
    │       ├── User ada permission → TRUE
    │       └── User tidak ada permission → FALSE
    │
    └── Tampilkan/sembunyikan menu & akses route
```

### Permission per Role (Default)

| Role | Permission Default |
|---|---|
| **Superadmin** | Semua 22 permission (bypass) |
| **Admin** | `dashboard`, `berita_acara_riwayat_personal`, `berita_acara_verify`, `berita_acara_assign`, `berita_acara_riwayat_semua`, `payment_riwayat_personal`, `payment_confirm`, `hasil_pengujian_rekap`, `hasil_pengujian_rekap`, `hasil_pengujian_riwayat_semua`, `user_view`, `user_verify`, `form_manage_*` |
| **Analyst** | `dashboard`, `berita_acara_riwayat_personal`, `berita_acara_riwayat_personal`, `hasil_pengujian_rekap`, `hasil_pengujian_rekap`, `hasil_pengujian_rekap`, `hasil_pengujian_rekap`, `hasil_pengujian_riwayat_personal`, `payment_discount` |
| **User** | `dashboard`, `berita_acara_riwayat_personal`, `berita_acara_create`, `berita_acara_riwayat_personal`, `payment_upload`, `payment_discount`, `certificate_view`, `certificate_download` |

**Catatan:** Permission dapat dikustomisasi oleh superadmin melalui UI Manajemen Role.

---

## 12. Troubleshooting

### Login Gagal

| Masalah | Solusi |
|---|---|
| "Email atau password salah" | Pastikan email dan password benar. Perhatikan huruf besar/kecil. |
| "Belum diverifikasi oleh superadmin" | Hubungi admin/superadmin untuk memverifikasi akun Anda. |
| Tidak bisa login setelah register | Akun perlu diverifikasi oleh superadmin terlebih dahulu. |

### Form Tidak Muncul

| Masalah | Solusi |
|---|---|
| Form dinamis kosong | Hubungi admin untuk membuat form template untuk tipe yang diperlukan. |
| Field tidak bisa diedit | Field mungkin bersifat "locked" oleh sistem. |

### Pembayaran Tidak Muncul

| Masalah | Solusi |
|---|---|
| Tidak bisa buat pembayaran | Pastikan berita acara sudah disetujui admin. |
| Bukti pembayaran tidak ter-upload | Pastikan format file sesuai (gambar/PDF) dan ukuran tidak terlalu besar. |

### Sertifikat Tidak Bisa Diunduh

| Masalah | Solusi |
|---|---|
| Tombol download tidak aktif | Pastikan pembayaran sudah dikonfirmasi admin. |
| File corrupt | Hubungi admin untuk regenerate sertifikat. |

### Akses Ditolak (403)

| Masalah | Solusi |
|---|---|
| "Akses ditolak" | Anda tidak memiliki permission untuk fitur ini. Hubungi superadmin untuk menambah permission ke role Anda. |

### Token Expired / Session Hilang

| Masalah | Solusi |
|---|---|
| Logout otomatis | Sistem menggunakan satu sesi — login di tempat lain akan menggantikan sesi sebelumnya. |
| Error 401 | Token sudah tidak valid. Silakan login kembali. |

---

## Appendix: Glossary

| Istilah | Deskripsi |
|---|---|
| **Berita Acara (BA)** | Dokumen pengajuan pengujian hasil pengujian dari user ke laboratorium |
| **Rekap Analis** | Dokumen rekapan hasil pengujian dan perhitungan biaya dari analis |
| **Form Dinamis** | Form yang bisa dikonfigurasi oleh admin tanpa coding |
| **Locked Field** | Field form yang tidak bisa dihapus karena terintegrasi dengan sistem |
| **Permission** | Izin akses ke fitur tertentu (contoh: `berita_acara_create`) |
| **Role** | Peran pengguna dalam sistem (superadmin, admin, analyst, user) |
| **RBAC** | Role-Based Access Control — sistem pengelolaan akses berdasarkan peran |
| **Api Token** | Token autentikasi unik yang dihasilkan saat login |
| **Draft** | Status berita acara yang belum dikirim |
| **Bulk Update** | Update massal beberapa data sekaligus |
