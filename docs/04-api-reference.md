# 04. API Reference

Base URL: `http://localhost:8000/api`

## Authentication

Semua endpoint (kecuali public) memerlukan header:
```
Authorization: Bearer {api_token}
```
Atau alternatif:
```
X-API-Token: {api_token}
```

---

## Public Endpoints

### Login

```
POST /api/auth/login
Content-Type: application/json

{
  "email": "string",
  "password": "string"
}

Response 200:
{
  "message": "Login successful.",
  "data": {
    "user": { "id", "name", "email", "role", "phone", "is_verified" },
    "token": "string"
  }
}

Response 422:
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The selected email is invalid."],
    "password": ["The password field is required."]
  }
}
```

### Register

```
POST /api/auth/register
Content-Type: application/json

{
  "name": "string (required)",
  "email": "string (required, email)",
  "password": "string (required, confirmed)",
  "phone": "string (nullable)",
  "role": "string (nullable, default: user)"
}

Response 201:
{
  "message": "Registration successful.",
  "data": {
    "user": { "id", "name", "email", "role", "is_verified" },
    "token": "string",
    "needs_verification": true
  }
}
```

### Get Roles (for registration dropdown)

```
GET /api/roles

Response 200:
{
  "data": [
    { "id": 2, "name": "admin", "description": "..." },
    { "id": 3, "name": "analyst", "description": "..." }
  ]
}
```

### Get Public Form Template

```
GET /api/form-templates/{id}

Response 200:
{
  "data": {
    "id": 2,
    "name": "Form Berita Acara",
    "type": "berita_acara",
    "description": "...",
    "fields": [
      {
        "id": 1,
        "label": "Nama Pemohon",
        "type": "text",
        "options": null,
        "is_required": true,
        "sort_order": 0
      }
    ]
  }
}
```

---

## User Endpoints

### Get Current User

```
GET /api/auth/me

Response 200:
{
  "data": { "id", "name", "email", "role", "phone", "is_verified" }
}
```

### Logout

```
POST /api/auth/logout

Response 200:
{
  "message": "Logged out."
}
```

---

## User — Berita Acara

### List User's BA

```
GET /api/user/berita-acara
GET /api/user/berita-acara?status=pending

Response 200:
{
  "data": [
    {
      "id", "status", "nama_pemohon", "nim",
      "created_at", "updated_at"
    }
  ]
}
```

### Get BA Detail

```
GET /api/user/berita-acara/{id}

Response 200:
{
  "data": {
    "id", "status", "nama_pemohon", "nim", ...,
    "payment": { "id", "amount", "status", ... },
    "samples": [{ "id", "name", "status", ... }],
    "certificate": { "id", "file_path", ... }
  }
}
```

### Create BA

```
POST /api/user/berita-acara
Content-Type: multipart/form-data

Fields:
  nama_pemohon: string
  nim: string
  prodi_fakultas_instansi: string
  dosen_pembimbing: string
  telp_hp: string
  email: string
  alamat: string
  judul_penelitian: string
  bahan_contoh_uji: string
  kemasan_packing: string
  permintaan_analisa: string
  jumlah_sampel: string
  kode_nama_sampel: string
  keterangan_sampel: string
  keterangan_tambahan: string
  akses_lab: file (optional)
  upload_akses: file (optional)

Response 201:
{
  "message": "Berita Acara created.",
  "data": { "id", "status", ... }
}
```

### Update BA

```
POST /api/user/berita-acara/{id}
_method=PUT
Content-Type: multipart/form-data

Same fields as create.

Response 200:
{
  "message": "Berita Acara updated.",
  "data": { "id", "status", ... }
}
```

---

## User — Payment

### List User's Payments

```
GET /api/user/payments

Response 200:
{
  "data": [
    {
      "id", "amount", "status", "discount_request",
      "berita_acara": { "id", "status", "nama_pemohon" }
    }
  ]
}
```

### Get Payment Detail

```
GET /api/user/payments/{id}

Response 200:
{
  "data": {
    "id", "amount", "status", "discount_request",
    "discount_reason", "discount_approved", "payment_proof",
    "berita_acara": { ... }
  }
}
```

### Create Payment

```
POST /api/user/payments
Content-Type: application/json

{
  "berita_acara_id": "integer (required)",
  "amount": "numeric (required)"
}

Response 201:
{
  "message": "Payment created.",
  "data": { "id", "amount", "status", ... }
}
```

### Upload Payment Proof

```
POST /api/user/payments/{id}/upload-proof
Content-Type: multipart/form-data

Fields:
  payment_proof: file (required, image/*)

Response 200:
{
  "message": "Payment proof uploaded.",
  "data": { "id", "payment_proof", ... }
}
```

### Request Discount

```
POST /api/user/payments/{id}/discount
Content-Type: application/json

{
  "discount_request": "numeric (required)",
  "discount_reason": "string (required)"
}

Response 200:
{
  "message": "Discount request submitted.",
  "data": { "id", "discount_request", "discount_reason", ... }
}
```

---

## User — Certificate

### Download Certificate

```
GET /api/user/certificates/{id}/download

Response 200: Binary file (PDF)
Response 404: { "message": "Certificate not found." }
```

### Upload Sertifikat (superadmin/admin)

```
POST /api/admin/certificates/{beritaAcaraId}/upload
Content-Type: multipart/form-data

Fields:
  certificate: file (required, pdf/jpg/jpeg/png, maks 10MB)

Syarat: berita acara berstatus `completed` dan pembayaran `approved`.
Sertifikat lama (jika ada) otomatis diganti.

Response 200:
{
  "message": "Sertifikat berhasil diupload",
  "data": { "id", "berita_acara_id", "file_path", ... }
}
```

### Hapus Sertifikat

```
DELETE /api/admin/certificates/{beritaAcaraId}

Response 200:
{ "message": "Sertifikat berhasil dihapus" }
```

---

## Admin Endpoints

### List All BA

```
GET /api/admin/berita-acara
GET /api/admin/berita-acara?status=pending
GET /api/admin/berita-acara?search=john

Response 200:
{
  "data": [
    {
      "id", "status", "nama_pemohon", "nim",
      "user": { "id", "name", "email" },
      "analyst": { "id", "name" },
      "created_at"
    }
  ]
}
```

### Get BA Detail

```
GET /api/admin/berita-acara/{id}

Response 200:
{
  "data": {
    "id", "status", "nama_pemohon", ...,
    "user": { "id", "name", "email" },
    "analyst": { "id", "name" },
    "payment": { ... },
    "samples": [{ ... }],
    "certificate": { ... },
    "revisions": [{ "id", "revision_type", "notes", "revised_by" }]
  }
}
```

### Update BA Status

```
PUT /api/admin/berita-acara/{id}/status
Content-Type: application/json

{
  "status": "approved | rejected | revision",
  "notes": "string (optional)"
}

Response 200:
{
  "message": "Status updated.",
  "data": { "id", "status", ... }
}
```

### Assign Analyst

```
PUT /api/admin/berita-acara/{id}/assign-analyst
Content-Type: application/json

{
  "analyst_id": "integer (required)"
}

Response 200:
{
  "message": "Analyst assigned.",
  "data": { "id", "analyst_id", ... }
}
```

### List Analysts

```
GET /api/admin/analysts

Response 200:
{
  "data": [
    { "id", "name", "email", "phone" }
  ]
}
```

### List All Payments

```
GET /api/admin/payments

Response 200:
{
  "data": [
    {
      "id", "amount", "status", "discount_request",
      "berita_acara": { "id", "nama_pemohon" },
      "user": { "id", "name" }
    }
  ]
}
```

### Confirm Payment

```
PUT /api/admin/payments/{id}/confirm
Content-Type: application/json

{
  "status": "approved | rejected",
  "discount_approved": "numeric (nullable)",
  "notes": "string (nullable)"
}

Response 200:
{
  "message": "Payment confirmed.",
  "data": { "id", "status", "discount_approved", ... }
}
```

### List All Samples

```
GET /api/admin/samples

Response 200:
{
  "data": [
    {
      "id", "name", "status", "test_result",
      "berita_acara": { "id", "nama_pemohon", "status" }
    }
  ]
}
```

### Mark Sample Taken

```
PUT /api/admin/samples/{id}/mark-taken

Response 200:
{
  "message": "Sample marked as taken.",
  "data": { "id", "status": "taken", ... }
}
```

---

## Admin — Export Data

### Export Database (Excel multi-sheet)

```
GET /api/admin/export/database

Permission: berita_acara_riwayat_semua + user_view (superadmin bypass)

Response 200: file .xls (XML Spreadsheet 2003) berisi sheet:
- Pesanan      — pemohon, tanggal masuk, judul, status, siapa & kapan
                 admin/penyelia/analis/manager teknis memproses, biaya,
                 pembayaran, sertifikat
- Pengguna     — seluruh user beserta role dan status verifikasi
- Pembayaran   — seluruh record pembayaran
- Sampel       — seluruh sampel
- Aktivitas    — seluruh activity log per pesanan
```

### Export Pesanan (CSV)

```
GET /api/admin/export/pesanan

Permission: berita_acara_riwayat_semua (superadmin bypass)

Response 200: file .csv (delimiter ';', BOM UTF-8) berisi kolom sama
dengan sheet "Pesanan" pada export database.
```

---

## Admin — User Management

### List Users

```
GET /api/admin/users

Response 200:
{
  "data": [
    { "id", "name", "email", "role", "is_verified", "created_at" }
  ]
}
```

### Verify/Revoke User

```
PUT /api/admin/users/{id}/verify
Content-Type: application/json

{
  "is_verified": true | false
}

Response 200:
{
  "message": "User verified." | "User verification revoked.",
  "data": { "id", "is_verified", ... }
}
```

---

## Admin — Role Management

### List Roles

```
GET /api/admin/roles

Response 200:
{
  "data": [
    {
      "id", "name", "description", "is_system", "users_count",
      "permissions": [{ "id", "name", "display_name", "group" }]
    }
  ]
}
```

### Get Role Detail

```
GET /api/admin/roles/{id}

Response 200:
{
  "data": {
    "id", "name", "description", "is_system",
    "users_count": 5,
    "permissions": [
      { "id", "name", "display_name", "group" }
    ]
  }
}
```

### Create Role

```
POST /api/admin/roles
Content-Type: application/json

{
  "name": "string (required)",
  "description": "string (nullable)",
  "permissions": ["permission_name_1", "permission_name_2"]
}

Response 201:
{
  "message": "Role created.",
  "data": { "id", "name", ... }
}
```

### Update Role

```
PUT /api/admin/roles/{id}
Content-Type: application/json

{
  "name": "string (required)",
  "description": "string (nullable)",
  "permissions": ["permission_name_1", "permission_name_2"]
}

Response 200:
{
  "message": "Role updated.",
  "data": { "id", "name", ... }
}
```

### Delete Role

```
DELETE /api/admin/roles/{id}

Response 200:
{
  "message": "Role deleted."
}

Response 400:
{
  "message": "Cannot delete system role."
}
```

### List Permissions

```
GET /api/admin/roles/permissions/list

Response 200:
{
  "data": [
    { "id", "name", "display_name", "group", "description" }
  ]
}
```

---

## Admin — Form Templates

### List Templates

```
GET /api/admin/form-templates
GET /api/admin/form-templates?type=berita_acara

Response 200:
{
  "data": [
    {
      "id", "name", "type", "description", "is_active", "is_default",
      "fields_count": 22,
      "creator": { "id", "name" },
      "created_at"
    }
  ]
}
```

### Get Template Detail

```
GET /api/admin/form-templates/{id}

Response 200:
{
  "data": {
    "id", "name", "type", "description", "is_active", "is_default",
    "created_by", "created_at",
    "fields": [
      {
        "id", "label", "type", "options",
        "is_required", "sort_order"
      }
    ]
  }
}
```

### Update Template Metadata

```
PUT /api/admin/form-templates/{id}
Content-Type: application/json

{
  "name": "string (required)",
  "description": "string (nullable)",
  "is_active": "boolean (required)"
}

Response 200:
{
  "message": "Template updated.",
  "data": { "id", "name", "is_active", ... }
}
```

### Sync Form Fields

```
PUT /api/admin/form-templates/{id}/fields
Content-Type: application/json

{
  "fields": [
    {
      "label": "string (required)",
      "type": "string (required)",
      "options": [
        { "label": "string", "is_other": false }
      ],
      "is_required": "boolean (required)"
    }
  ]
}

Response 200:
{
  "message": "Fields updated.",
  "data": {
    "id", "name", "fields_count": 22
  }
}
```

---

## Analyst Endpoints

### List Assigned BA

```
GET /api/analyst/berita-acara
GET /api/analyst/berita-acara?status=approved

Response 200:
{
  "data": [
    {
      "id", "status", "nama_pemohon", "nim",
      "user": { "id", "name" },
      "samples": [{ "id", "name", "status" }],
      "created_at"
    }
  ]
}
```

### Get BA Detail

```
GET /api/analyst/berita-acara/{id}

Response 200:
{
  "data": {
    "id", "status", "nama_pemohon", ...,
    "user": { "id", "name", "email" },
    "samples": [{ "id", "name", "status", "test_result" }],
    "pengujians": [{ "id", "data" }]
  }
}
```

### Store Pengujian

```
POST /api/analyst/berita-acara/{id}/pengujian
Content-Type: application/json

{
  "data": { ... }  // Flexible JSON object
}

Response 201:
{
  "message": "Pengujian stored.",
  "data": { "id", "berita_acara_id", "data" }
}
```

### Update Sample

```
PUT /api/analyst/berita-acara/{id}/samples/{sampleId}
Content-Type: application/json

{
  "name": "string (nullable)",
  "description": "string (nullable)",
  "quantity": "string (nullable)",
  "unit": "string (nullable)",
  "test_result": "string (nullable)",
  "status": "pending | processing | ready"
}

Response 200:
{
  "message": "Sample updated.",
  "data": { "id", "name", "status", "test_result", ... }
}
```

### Mark Sample Ready

```
PUT /api/analyst/berita-acara/{id}/samples/{sampleId}/ready

Response 200:
{
  "message": "Sample marked as ready.",
  "data": { "id", "status": "ready", ... }
}
```

### Upload Berkas Ulang

```
POST /api/analyst/berita-acara/{id}/upload-berkas
Content-Type: multipart/form-data

Fields:
  berkas_ulang: file (required, pdf/jpg/jpeg/png/doc/docx, maks 10MB)

Response 200:
{
  "message": "File berhasil diupload",
  "data": { "berkas_ulang": "berkas_ulang/xxx.pdf" }
}

Catatan: file tersimpan di kolom `berita_acaras.berkas_ulang`,
terpisah dari `upload_akses` milik berita acara.
```

### Hapus Berkas Ulang

```
DELETE /api/analyst/berita-acara/{id}/upload-berkas

Response 200:
{
  "message": "File berhasil dihapus"
}
```

---

## Error Responses

### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden

```json
{
  "message": "Forbidden."
}
```

### 404 Not Found

```json
{
  "message": "Not found."
}
```

### 422 Validation Error

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

### 500 Server Error

```json
{
  "message": "Internal server error."
}
```
