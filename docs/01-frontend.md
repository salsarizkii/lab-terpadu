# 01. Frontend Documentation

Vue 3 + TypeScript + Pinia + Vite

## Tech Stack

| Package | Version | Purpose |
|---|---|---|
| vue | ^3.5.32 | Core framework |
| vue-router | ^4.6.4 | SPA routing |
| pinia | ^3.0.4 | State management |
| axios | ^1.18.1 | HTTP client |
| vue-sonner | ^2.0.9 | Toast notifications |

> **Tidak ada UI library** — menggunakan custom CSS design system (`src/assets/design-system.css`) dengan CSS variables. Font: Poppins.

## Directory Structure

```
frontend/src/
├── main.ts                          # Entry point: createApp, install Pinia/Router
├── App.vue                          # Root: <Sonner /> + <router-view />
├── assets/
│   └── design-system.css            # Global CSS variables & reset
├── types/
│   └── index.ts                     # TypeScript interfaces (User, BeritaAcara, Payment, etc.)
├── router/
│   └── index.ts                     # Vue Router + auth guards
├── stores/
│   ├── authStore.ts                 # Auth state (user, token, login/logout)
│   └── beritaAcaraStore.ts          # Berita Acara CRUD
├── api/
│   ├── axios.ts                     # Axios instance + interceptors
│   ├── auth.ts                      # Login, register, logout, me
│   ├── admin.ts                     # Admin CRUD operations
│   ├── analyst.ts                   # Analyst operations
│   ├── beritaAcara.ts               # User berita acara CRUD
│   ├── certificate.ts               # Certificate download
│   ├── formTemplate.ts              # Form template CRUD
│   ├── payment.ts                   # Payment operations
│   └── role.ts                      # Role & permission CRUD
├── components/
│   ├── common/                      # Reusable UI components (14+)
│   │   ├── AppLayout.vue            # Shell: Navbar + Sidebar + content
│   │   ├── Navbar.vue               # Top bar
│   │   ├── Sidebar.vue              # Role-based navigation
│   │   ├── Button.vue               # primary/secondary/outline/ghost/danger/success
│   │   ├── Badge.vue                # Status badge
│   │   ├── Card.vue                 # Card container
│   │   ├── DataTable.vue            # Generic table with slots
│   │   ├── Modal.vue                # Dialog sm/md/lg
│   │   ├── FormInput.vue            # Text input
│   │   ├── FormSelect.vue           # Select dropdown
│   │   ├── FormTextarea.vue         # Textarea
│   │   ├── FileUpload.vue           # Drag file upload
│   │   ├── LoadingSpinner.vue       # Spinner
│   │   ├── FormBuilder.vue          # Drag-and-drop form editor (14 types)
│   │   ├── FormTemplateList.vue     # Template cards grid
│   │   ├── ActivityTimeline.vue      # Riwayat aktivitas (aktor: nama/email/role)
│   │   ├── MetodeInstrumenPicker.vue # Multi-select instruments
│   │   └── DownloadMenu.vue          # Dropdown unduh PDF/Word (BA, Rekap, Tagihan, Sertifikat)
│   ├── auth/
│   │   ├── LoginForm.vue
│   │   └── RegisterForm.vue
│   ├── admin/
│   │   ├── AnalystAssignment.vue
│   │   ├── PaymentConfirmation.vue
│   │   ├── SampleStatusList.vue
│   │   ├── BeritaAcaraVerification.vue
│   │   └── BeritaAcaraDetailView.vue
│   ├── analyst/
│   │   ├── SampleDataForm.vue
│   │   └── PengujianForm.vue
│   └── user/
│       ├── BeritaAcaraForm.vue
│       ├── BeritaAcaraCard.vue
│       ├── PaymentForm.vue
│       ├── FlowSteps.vue
│       ├── DiscountRequest.vue
│       └── CertificateDownload.vue
└── views/                           # Page components (14+)
    ├── LoginView.vue
    ├── RegisterView.vue
    ├── UserDashboard.vue
    ├── UserBeritaAcaraList.vue
    ├── UserBeritaAcaraCreate.vue
    ├── UserBeritaAcaraDetail.vue
    ├── UserPayment.vue
    ├── UserCertificate.vue
    ├── AdminDashboard.vue
    ├── AdminBeritaAcara.vue
    ├── AdminPayments.vue
    ├── AdminSamples.vue
    ├── AdminRoles.vue
    ├── AdminUsers.vue
    ├── FormTemplateEditor.vue
    ├── AnalystDashboard.vue
    └── AnalystAssigned.vue
```

## Routing

### Route Map

| Path | Component | Auth | Role / Permission |
|---|---|---|---|
| `/login` | LoginView | guest | - |
| `/register` | RegisterView | guest | - |
| `/dashboard` | redirect | yes | - |
| `/user/dashboard` | UserDashboard | yes | `dashboard` |
| `/user/berita-acara` | UserBeritaAcaraList | yes | `berita_acara_create` / `berita_acara_riwayat_personal` |
| `/user/berita-acara/create` | UserBeritaAcaraCreate | yes | `berita_acara_create` |
| `/user/berita-acara/:id` | UserBeritaAcaraDetail | yes | `berita_acara_create` / `berita_acara_riwayat_personal` |
| `/user/berita-acara/:id/edit` | UserBeritaAcaraEdit | yes | `berita_acara_create` |
| `/user/payments` | UserPayment | yes | `payment_upload` / `payment_discount,payment_riwayat_personal` |
| `/user/payments/:id` | UserPaymentDetail | yes | `payment_upload` / `payment_discount,payment_riwayat_personal` |
| `/user/samples` | UserSamples | yes | `hasil_pengujian_riwayat_personal` |
| `/user/certificates` | UserCertificate | yes | `certificate_view` / `certificate_download` |
| `/admin/dashboard` | AdminDashboard | yes | `dashboard` |
| `/admin/berita-acara` | AdminBeritaAcara | yes | `berita_acara_riwayat_semua` / `berita_acara_riwayat_personal,berita_acara_verify,berita_acara_assign,berita_acara_create` |
| `/admin/payments` | AdminPayments | yes | `payment_riwayat_semua` |
| `/admin/samples` | AdminSamples | yes | `hasil_pengujian_rekap` / `hasil_pengujian_riwayat_personal,hasil_pengujian_riwayat_semua` |
| `/admin/roles` | AdminRoles | yes | superadmin only (`meta.superadminOnly`) |
| `/admin/users` | AdminUsers | yes | `user_view` |
| `/admin/forms` | FormTemplateEditor | yes | form manage permissions |
| `/admin/discounts` | AdminDiscounts | yes | `discount_verifikasi` / `discount_riwayat_*` |
| `/analyst/dashboard` | AnalystDashboard | yes | `dashboard` |
| `/analyst/assigned` | AnalystAssigned | yes | `hasil_pengujian_rekap` / `berita_acara_riwayat_semua` |
| `/penyelia/berita-acara` | PenyeliaBeritaAcara | yes | `berita_acara_verify` |
| `/penyelia/berita-acara/:id` | PenyeliaBeritaAcaraDetail | yes | `berita_acara_verify` |
| `/manager-teknis/berita-acara` | ManagerBeritaAcara | yes | `hasil_pengujian_rekap` |
| `/manager-teknis/berita-acara/:id` | ManagerBeritaAcaraDetail | yes | `hasil_pengujian_rekap` |
| `/:pathMatch(.*)*` | → redirect `/dashboard` | - | - |

### Guard Logic (`router/index.ts`)

```
beforeEach:
  1. Route auth required + not authenticated → /login
  2. Route guest + authenticated → /dashboard
  3. meta.superadminOnly + bukan superadmin → /dashboard
  4. meta.permission: wajib punya permission (superadmin bypass)
     meta.permissionOr: minimal salah satu permission (koma)
  5. /dashboard redirects by role:
     - superadmin/admin → /admin/dashboard
     - analyst → /analyst/dashboard
     - user → /user/dashboard
```

## State Management (Pinia)

### authStore (`stores/authStore.ts`)

| State/Computed | Type | Purpose |
|---|---|---|
| `user` | `User \| null` | Current user |
| `token` | `string \| null` | JWT token |
| `isAuthenticated` | computed | `!!token` |
| `isSuperAdmin` | computed | `role === 'superadmin'` |
| `isAdmin` | computed | `role === 'admin' \|\| 'superadmin'` |
| `isAnalyst` | computed | `role === 'analyst'` |
| `isUser` | computed | `role === 'user'` |

**Actions:** `login()`, `register()`, `logout()`, `fetchMe()`, `initFromStorage()`, `setAuth()`, `clearAuth()`

Session dipersist ke `localStorage`.

### beritaAcaraStore (`stores/beritaAcaraStore.ts`)

| State | Type | Purpose |
|---|---|---|
| `list` | `BeritaAcara[]` | All items |
| `current` | `BeritaAcara \| null` | Current viewed item |
| `loading` | `boolean` | Loading state |

**Actions:** `fetchAll(params?)`, `fetchById(id)`, `create(formData)`, `update(id, formData)`

## API Layer (`api/`)

Semua module pakai shared Axios instance (`api/axios.ts`):
- Base URL: `http://localhost:8000/api`
- Request interceptor: attach `Bearer ${token}` dari localStorage
- Response interceptor: 401 → clear auth → redirect `/login`

### Endpoints Summary

| Module | Method | Endpoint | Purpose |
|---|---|---|---|
| auth.ts | `login(email, password)` | `POST /auth/login` | Returns `{user, token}` |
| auth.ts | `register(data)` | `POST /auth/register` | Returns `{message, needs_verification}` |
| auth.ts | `logout()` | `POST /auth/logout` | Server-side logout |
| auth.ts | `me()` | `GET /auth/me` | Current user data |
| admin.ts | `getBeritaAcara(params?)` | `GET /admin/berita-acara` | List all BA |
| admin.ts | `getBeritaAcaraDetail(id)` | `GET /admin/berita-acara/:id` | BA detail |
| admin.ts | `updateStatus(id, payload)` | `PUT /admin/berita-acara/:id/status` | Approve/reject/revision |
| admin.ts | `assignAnalyst(id, analystId)` | `PUT /admin/berita-acara/:id/assign-analyst` | Assign analyst |
| admin.ts | `assignPenyelia(id, penyeliaIds)` | `PUT /admin/berita-acara/:id/assign-penyelia` | Assign penyelia |
| admin.ts | `assignManagerTeknis(id, managerTeknisIds)` | `PUT /admin/berita-acara/:id/assign-manager-teknis` | Assign manager teknis |
| admin.ts | `getPenyelias()` | `GET /admin/penyelia` | List penyelia users |
| admin.ts | `getManagerTeknis()` | `GET /admin/manager-teknis` | List manager teknis users |
| admin.ts | `checkNomorAgenda(nomor, ignoreId)` | `GET /admin/berita-acara/check-nomor-agenda` | Cek ketersediaan nomor agenda |
| admin.ts | `getPayments(params?)` | `GET /admin/payments` | List payments |
| admin.ts | `confirmPayment(id, payload)` | `PUT /admin/payments/:id/confirm` | Confirm payment |
| admin.ts | `getSamples(params?)` | `GET /admin/samples` | List samples |
| admin.ts | `markSampleTaken(id)` | `PUT /admin/samples/:id/mark-taken` | Mark sample taken |
| admin.ts | `getAnalysts()` | `GET /admin/analysts` | List analyst users |
| admin.ts | `getUsers(params?)` | `GET /admin/users` | List users |
| admin.ts | `verifyUser(id, isVerified)` | `PUT /admin/users/:id/verify` | Verify/revoke |
| admin.ts | `exportDatabase()` | `GET /admin/export/database` | Export database (Excel .xls multi-sheet) |
| admin.ts | `exportPesanan()` | `GET /admin/export/pesanan` | Export daftar pesanan (CSV) |
| analyst.ts | `getBeritaAcara(params?)` | `GET /analyst/berita-acara` | List assigned BA |
| analyst.ts | `getBeritaAcaraDetail(id)` | `GET /analyst/berita-acara/:id` | BA detail |
| analyst.ts | `updateSample(baId, sampleId, payload)` | `PUT .../samples/:sampleId` | Update sample result |
| analyst.ts | `markSampleReady(baId, sampleId)` | `PUT .../samples/:sampleId/ready` | Mark sample ready |
| analyst.ts | `storePengujian(baId, payload)` | `POST .../pengujian` | Store testing data |
| verification.ts | `getPenyeliaBeritaAcara()` | `GET /penyelia/berita-acara` | List BA utk penyelia |
| verification.ts | `getPenyeliaBeritaAcaraDetail(id)` | `GET /penyelia/berita-acara/:id` | Detail |
| verification.ts | `penyeliaVerify(id, payload)` | `PUT /penyelia/berita-acara/:id/status` | Verifikasi penyelia |
| verification.ts | `getManagerBeritaAcara()` | `GET /manager-teknis/berita-acara` | List BA utk manager |
| verification.ts | `getManagerBeritaAcaraDetail(id)` | `GET /manager-teknis/berita-acara/:id` | Detail |
| verification.ts | `managerVerify(id, payload)` | `PUT /manager-teknis/berita-acara/:id/status` | Verifikasi manager teknis |
| beritaAcara.ts | `getList(params?)` | `GET /user/berita-acara` | User's BA list |
| beritaAcara.ts | `getById(id)` | `GET /user/berita-acara/:id` | BA detail |
| beritaAcara.ts | `create(formData)` | `POST /user/berita-acara` | Create BA (multipart) |
| beritaAcara.ts | `update(id, formData)` | `POST /user/berita-acara/:id` | Update BA (_method=PUT) |
| payment.ts | `create(formData)` | `POST /user/payments` | Create payment |
| payment.ts | `requestDiscount(id, payload)` | `POST /user/payments/:id/discount` | Request discount |
| payment.ts | `getList(params?)` | `GET /user/payments` | User's payments |
| payment.ts | `getById(id)` | `GET /user/payments/:id` | Payment detail |
| payment.ts | `uploadProof(id, formData)` | `POST /user/payments/:id/upload-proof` | Upload proof |
| certificate.ts | `download(id)` | `GET /user/certificates/:id/download` | Download certificate |
| pdfTemplate.ts | `downloadFilledPdf(type, id)` | `GET /pdf-generate/:type/:id` | Download PDF data nyata (blob) |
| pdfTemplate.ts | `downloadFilledDocx(type, id)` | `GET /docx-generate/:type/:id` | Download .docx data nyata (blob) |
| formTemplate.ts | `getTemplates(type?)` | `GET /admin/form-templates` | List templates |
| formTemplate.ts | `getTemplate(id)` | `GET /admin/form-templates/:id` | Template detail |
| formTemplate.ts | `updateTemplate(id, payload)` | `PUT /admin/form-templates/:id` | Update template |
| formTemplate.ts | `syncFields(id, fields)` | `PUT /admin/form-templates/:id/fields` | Sync form fields |
| formTemplate.ts | `getPublicTemplate(id)` | `GET /form-templates/:id` | Public template |
| role.ts | `getPublicRoles()` | `GET /roles` | Roles for registration |
| role.ts | `getRoles()` | `GET /admin/roles` | All roles with counts |
| role.ts | `getRole(id)` | `GET /admin/roles/:id` | Role detail |
| role.ts | `createRole(payload)` | `POST /admin/roles` | Create role |
| role.ts | `updateRole(id, payload)` | `PUT /admin/roles/:id` | Update role |
| role.ts | `deleteRole(id)` | `DELETE /admin/roles/:id` | Delete role |
| role.ts | `getPermissions()` | `GET /admin/roles/permissions/list` | List permissions |

## Riwayat Aktivitas (`components/common/ActivityTimeline.vue`)

Komponen reusable untuk menampilkan siapa yang melakukan aksi pada sebuah berita acara (nama, email, role) lengkap dengan waktu & deskripsi. Data dari relasi `activity_logs.user` yang di-eager-load endpoint detail.

```vue
<ActivityTimeline :logs="beritaAcara.activity_logs || []" />
```

Untuk log `revision_requested`, badge otomatis menampilkan **"Revisi Diminta"** jika belum ada log `resubmitted` setelahnya, atau **"Revisi telah diperbaiki"** (tipe success) jika sudah ada `resubmitted` (revisi selesai). Log `revision_requested` yang memiliki `revision_id` dapat menampilkan tombol **📄 Detail Perbaikan** di bawah waktu (kolom kanan, right-aligned) yang me-`emit('detail', log)` ke parent. Aktifkan dengan prop `show-detail` (hanya halaman detail BA yang menyediakan handler). Parent me-resolve revision dari `log.revision_id` lalu membuka modal diff.

Prop `show-proses` menampilkan tombol **Perbaiki** di bawah waktu pada item revisi terbaru (saat BA berstatus `revision` dan user belum memperbaiki) yang me-`emit('proses', log)` ke parent (dipakai UserBeritaAcaraDetail → `handlePerbaiki` untuk masuk ke halaman edit). Prop `revision-active` menandakan BA sedang berstatus `revision`; saat aktif, tombol **Detail Perbaikan** disembunyikan pada item revisi terbaru yang belum diperbaiki (berlaku di sisi admin maupun user — user cukup langsung memperbaiki), sedangkan revisi lama tetap menampilkan Detail. Tombol aksi selalu tampil di kolom kanan tepat di bawah timestamp.

```vue
<ActivityTimeline :logs="item.activity_logs || []" show-detail :revision-active="isRevision" :show-proses="isRevision && !hasClickedPerbaiki" @detail="openRevisionFromLog" @proses="handlePerbaiki" />
```

**Map aksi → label/tipe badge:** `created`, `resubmitted`, `verified`, `revision_requested`, `rejected`, `nomor_agenda`, `analyst_assigned`, `penyelia_assigned`, `manager_teknis_assigned`, `payment_confirmed`, `payment_rejected`, `payment_uploaded`, `sample_taken`, `sample_ready`, `sample_updated`, `pengujian_saved`, `rekap_saved`, `discount_*`, `certificate_downloaded`. Aksi tak dikenal ditampilkan apa adanya. Empty state: "Belum ada aktivitas tercatat". Saat BA berstatus `revision`, revisi terbaru yang belum diperbaiki menampilkan tombol "Perbaiki" tanpa tombol Detail; label "Revisi Diminta" berubah jadi "Revisi telah diperbaiki" pada revisi yang sudah ada aksi `resubmitted` berikutnya.

**Dipasang di:** AdminBeritaAcaraDetail (`show-detail`), AdminPayments/PaymentConfirmation, AdminDiscounts (popup), AnalystAssigned, AnalystRekapAnalis, UserBeritaAcaraDetail (`show-detail`), UserPaymentDetail.

## TypeScript Types (`types/index.ts`)```typescript
interface User {
  id: number; name: string; email: string; role: string;
  phone?: string; is_verified: boolean; created_at: string;
}

interface BeritaAcara {
  id: number; user_id: number; analyst_id?: number;
  status: 'draft' | 'pending' | 'revision' | 'admin_verified' | 'penyelia_verified' | 'rekap_ready' | 'completed' | 'rejected';
  nomor_agenda?: string;
  nama_pemohon?: string; nim?: string; prodi_fakultas_instansi?: string;
  dosen_pembimbing?: string; telp_hp?: string; email?: string; alamat?: string;
  judul_penelitian?: string; akses_lab?: string; bahan_contoh_uji?: string;
  kemasan_packing?: string; permintaan_analisa?: string; jumlah_sampel?: string;
  kode_nama_sampel?: string; keterangan_sampel?: string; keterangan_tambahan?: string;
  pengukuran_per_sampel?: string; sisa_sampel?: string; upload_akses?: string;
  masa_berlaku_akses?: string; status_pelanggan?: string; status_berkas?: string;
  biaya?: string; diskon_diizinkan?: boolean; formula_id?: number | null; custom_items?: any[]; keterangan?: string;
  user?: User; analyst?: User; analysts?: User[]; penyelias?: User[]; manager_teknis?: User[];
  revisions?: BeritaAcaraRevision[]; payment?: Payment; samples?: Sample[]; certificate?: Certificate; pengujians?: any[];
  activity_logs?: ActivityLog[];
  created_at: string; updated_at: string;
}

interface Payment {
  id: number; berita_acara_id: number; amount: string;
  discount_request?: string; discount_reason?: string; discount_approved?: string;
  status: 'pending' | 'approved' | 'rejected'; payment_proof?: string;
}

interface Sample {
  id: number; berita_acara_id: number; name: string;
  description?: string; quantity?: string; unit?: string;
  test_result?: string; status: 'pending' | 'processing' | 'ready' | 'taken';
}

interface Certificate {
  id: number; berita_acara_id: number; file_path?: string; downloaded_at?: string;
}

interface ActivityLog {
  id: number; berita_acara_id: number; revision_id?: number | null;
  action: string;
  description?: string; user_id?: number; user?: User; created_at: string;
}

interface FormFieldOption {
  label: string; is_other: boolean;
}

interface FormField {
  id?: number; label: string;
  type: 'text' | 'textarea' | 'number' | 'select' | 'checkbox' | 'radio' | 'date' | 'time' |
        'email' | 'phone' | 'file' | 'heading' | 'paragraph' | 'divider';
  options?: FormFieldOption[]; is_required: boolean; sort_order?: number;
}

interface FormTemplate {
  id: number; name: string; type: 'berita_acara' | 'rekap_analis';
  description?: string; is_active: boolean; is_default: boolean;
  created_by: number; fields_count?: number; fields?: FormField[];
  creator?: { id: number; name: string };
}

interface Role {
  id: number; name: string; description: string | null;
  is_system: boolean; users_count?: number; permissions?: Permission[];
}

interface Permission {
  id: number; name: string; display_name: string; group: string; description?: string;
}
```
