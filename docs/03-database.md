# 03. Database Schema

MySQL — `db_lab_terpadu`

## ER Diagram (Text)

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   users      │────<│  berita_acaras   │>────│     roles       │
│             │      │                  │      │                │
│ id          │      │ id               │      │ id             │
│ name        │      │ user_id (FK)     │      │ name           │
│ email       │      │ analyst_id (FK)  │      │ description    │
│ password    │      │ status           │      │ is_system      │
│ role        │      │ ...20+ fields    │      └───────┬────────┘
│ phone       │      │ created_at       │              │
│ api_token   │      └────────┬─────────┘              │
│ is_verified │               │                        │
│ created_at  │               │               ┌────────┴────────┐
│ updated_at  │        ┌──────┴──────┐        │ role_permission │
└─────────────┘        │             │        └────────┬────────┘
                       │             │                 │
               ┌───────┴───┐   ┌─────┴──────┐  ┌──────┴──────┐
               │  payments  │   │  samples   │  │ permissions │
               │           │   │            │  │             │
               │ id        │   │ id         │  │ id          │
               │ ba_id(FK) │   │ ba_id(FK)  │  │ name        │
               │ amount    │   │ name       │  │ display_name│
               │ status    │   │ status     │  │ group       │
               └───────────┘   └────────────┘  └─────────────┘
```

## Table: `users`

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint PK | no | auto | Primary key |
| name | varchar(255) | no | - | Full name |
| email | varchar(255) | no | - | Unique email |
| password | varchar(255) | no | - | Hashed password |
| role | varchar(50) | no | 'user' | Role string (superadmin/admin/analyst/user) |
| phone | varchar(20) | yes | null | Phone number |
| api_token | varchar(60) | yes | null | Unique auth token (indexed) |
| is_verified | boolean | no | false | Email verified status |
| remember_token | varchar(100) | yes | null | Laravel remember token |
| created_at | timestamp | no | - | Created timestamp |
| updated_at | timestamp | no | - | Updated timestamp |

**Seed data:** 1 superadmin (superadmin@lab.com / password)

## Table: `roles`

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint PK | no | auto | Primary key |
| name | varchar(255) | no | - | Unique role name |
| description | varchar(255) | yes | null | Role description |
| is_system | boolean | no | false | System roles cannot be deleted |
| created_at | timestamp | no | - | - |
| updated_at | timestamp | no | - | - |

**Seed data:** superadmin (is_system=true), admin, analyst, user created via UI.

## Table: `permissions`

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint PK | no | auto | Primary key |
| name | varchar(255) | no | - | Unique permission name |
| display_name | varchar(255) | no | - | Human-readable name |
| group | varchar(100) | no | - | Permission group |
| description | varchar(255) | yes | null | Description |
| created_at | timestamp | no | - | - |
| updated_at | timestamp | no | - | - |

### Permission Groups (22 total)

| Group | Permissions |
|---|---|
| berita_acara | ba_create, ba_read, ba_update, ba_delete, ba_verify |
| pembayaran | payment_read, payment_confirm |
| sample | sample_read, sample_update |
| sertifikat | certificate_read, certificate_download |
| pengujian | pengujian_read, pengujian_create, pengujian_update |
| user | user_read, user_verify, user_manage |
| role | role_read, role_manage |
| form_template | form_read, form_manage |

## Table: `role_permission`

| Column | Type | Nullable | Description |
|---|---|---|---|
| role_id | bigint FK | no | References roles.id |
| permission_id | bigint FK | no | References permissions.id |

Composite primary key: (role_id, permission_id)

## Table: `berita_acaras`

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint PK | no | auto | Primary key |
| user_id | bigint FK | no | - | References users.id (applicant) |
| analyst_id | bigint FK | yes | null | References users.id (assigned analyst) |
| status | enum | no | 'draft' | draft/pending/revision/approved/rejected |
| nama_pemohon | varchar(255) | yes | null | Applicant name |
| nim | varchar(50) | yes | null | Student/staff ID |
| prodi_fakultas_instansi | varchar(255) | yes | null | Department/faculty/institution |
| dosen_pembimbing | varchar(255) | yes | null | Supervisor |
| telp_hp | varchar(20) | yes | null | Phone |
| email | varchar(255) | yes | null | Email |
| alamat | text | yes | null | Address |
| judul_penelitian | text | yes | null | Research title |
| akses_lab | text | yes | null | Lab access info (file path) |
| bahan_contoh_uji | text | yes | null | Test materials |
| kemasan_packing | varchar(255) | yes | null | Packaging type |
| permintaan_analisa | text | yes | null | Analysis request |
| jumlah_sampel | varchar(50) | yes | null | Sample count |
| kode_nama_sampel | varchar(255) | yes | null | Sample code/name |
| keterangan_sampel | text | yes | null | Sample notes |
| keterangan_tambahan | text | yes | null | Additional notes |
| pengukuran_per_sampel | varchar(255) | yes | null | Measurement per sample |
| sisa_sampel | varchar(255) | yes | null | Remaining sample |
| upload_akses | varchar(255) | yes | null | Upload access file path |
| masa_berlaku_akses | varchar(255) | yes | null | Access validity period |
| status_pelanggan | varchar(100) | yes | null | Customer status |
| status_berkas | varchar(100) | yes | null | Document status |
| biaya | decimal(15,2) | yes | null | Cost |
| diskon_diizinkan | boolean | no | false | Discount allowed by admin |
| created_at | timestamp | no | - | - |
| updated_at | timestamp | no | - | - |

## Table: `berita_acara_revisions`

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint PK | no | Primary key |
| berita_acara_id | bigint FK | no | References berita_acaras.id |
| revision_type | varchar(50) | no | Type of revision |
| old_value | text | yes | Previous value |
| new_value | text | yes | New value |
| notes | text | yes | Revision notes |
| revised_by | bigint FK | no | References users.id |
| created_at | timestamp | no | - |
| updated_at | timestamp | no | - |

## Table: `payments`

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint PK | no | auto | Primary key |
| berita_acara_id | bigint FK | no | - | References berita_acaras.id (unique) |
| amount | decimal(15,2) | no | - | Payment amount |
| discount_request | decimal(15,2) | yes | null | Discount amount requested |
| discount_reason | text | yes | null | Discount reason |
| discount_approved | decimal(15,2) | yes | null | Discount approved |
| status | enum | no | 'pending' | pending/approved/rejected |
| payment_proof | varchar(255) | yes | null | File path of proof |
| notes | text | yes | null | Notes |
| created_at | timestamp | no | - | - |
| updated_at | timestamp | no | - | - |

## Table: `samples`

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint PK | no | auto | Primary key |
| berita_acara_id | bigint FK | no | - | References berita_acaras.id |
| name | varchar(255) | no | - | Sample name |
| description | text | yes | null | Sample description |
| quantity | varchar(50) | yes | null | Quantity |
| unit | varchar(50) | yes | null | Unit (kg, liter, etc.) |
| test_result | text | yes | null | Test result text |
| status | enum | no | 'pending' | pending/processing/ready/taken |
| created_at | timestamp | no | - | - |
| updated_at | timestamp | no | - | - |

## Table: `certificates`

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint PK | no | Primary key |
| berita_acara_id | bigint FK (unique) | no | References berita_acaras.id |
| file_path | varchar(255) | yes | Certificate file path |
| certificate_number | varchar(100) | yes | Certificate number |
| issued_at | timestamp | yes | Issue date |
| downloaded_at | timestamp | yes | First download time |
| created_at | timestamp | no | - |
| updated_at | timestamp | no | - |

## Table: `pengujians`

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint PK | no | Primary key |
| berita_acara_id | bigint FK | no | References berita_acaras.id |
| data | json | yes | Testing data (flexible JSON) |
| created_at | timestamp | no | - |
| updated_at | timestamp | no | - |

## Table: `form_templates`

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint PK | no | auto | Primary key |
| name | varchar(255) | no | - | Template name |
| type | varchar(50) | no | - | berita_acara or rekap_analis |
| description | text | yes | null | Description |
| is_active | boolean | no | true | Active status |
| is_default | boolean | no | false | Default templates can't be deleted |
| created_by | bigint FK | no | - | References users.id |
| created_at | timestamp | no | - | - |
| updated_at | timestamp | no | - | - |

## Table: `form_fields`

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| id | bigint PK | no | auto | Primary key |
| form_template_id | bigint FK | no | - | References form_templates.id |
| label | varchar(255) | no | - | Field label |
| type | varchar(50) | no | - | Field type (14 types) |
| options | json | yes | null | Array of `{label, is_other}` |
| is_required | boolean | no | false | Required field |
| sort_order | int | no | 0 | Display order |
| allow_other | boolean | no | false | Allow other option (legacy) |
| created_at | timestamp | no | - | - |
| updated_at | timestamp | no | - | - |

### Field Types (14)

**Input:** text, textarea, number, email, phone, date, time, file
**Pilihan:** select, radio, checkbox
**Layout:** heading, paragraph, divider

### Options Format

```json
[
  { "label": "Option 1", "is_other": false },
  { "label": "Option 2", "is_other": false },
  { "label": "", "is_other": true }  // "Isi manual" option
]
```

## Table: `discount_reasons`

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint PK | no | Primary key |
| reason | varchar(255) | no | Reason text |
| created_at | timestamp | no | - |

## Indexes

| Table | Index | Column(s) | Purpose |
|---|---|---|---|
| users | UNIQUE | email | Email uniqueness |
| users | UNIQUE | api_token | Token uniqueness |
| role_permission | PRIMARY | role_id, permission_id | Composite PK |
| form_fields | INDEX | form_template_id | Fast template lookups |
| form_fields | INDEX | sort_order | Sort order |
| berita_acaras | INDEX | user_id | User's BA queries |
| berita_acaras | INDEX | analyst_id | Analyst's BA queries |
| berita_acaras | INDEX | status | Status filtering |
| payments | INDEX | berita_acara_id | BA payment lookup |
| samples | INDEX | berita_acara_id | BA samples lookup |
