# 02. Backend Documentation

Laravel 12 (PHP 8.2+) — API-only, no Blade views.

## Directory Structure

```
backend/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── FormTemplateSeedCommand.php   # artisan form:seed
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php           # Login, register, logout, me
│   │   │   ├── BeritaAcaraController.php    # User CRUD
│   │   │   ├── AdminBeritaAcaraController.php    # Admin BA management
│   │   │   ├── AdminPaymentController.php   # Admin payment confirm
│   │   │   ├── AdminSampleController.php    # Admin sample management
│   │   │   ├── AdminUserController.php      # Admin user management
│   │   │   ├── AnalystController.php        # Analyst operations
│   │   │   ├── CertificateController.php    # Certificate download
│   │   │   ├── FormTemplateController.php   # Form template CRUD + syncFields
│   │   │   ├── PaymentController.php        # User payment + discount
│   │   │   ├── PermissionController.php     # Permission list
│   │   │   ├── PublicFormController.php     # Public template access
│   │   │   └── RoleController.php           # Role CRUD + permissions
│   │   └── Middleware/
│   │       ├── ApiTokenMiddleware.php        # Auth + role check + superadmin bypass
│   │       └── EnsureUserVerified.php       # is_verified check
│   ├── Models/
│   │   ├── User.php
│   │   ├── BeritaAcara.php
│   │   ├── BeritaAcaraRevision.php
│   │   ├── Payment.php
│   │   ├── Sample.php
│   │   ├── Certificate.php
│   │   ├── Pengujian.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── FormTemplate.php
│   │   └── FormField.php
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── RouteServiceProvider.php
├── bootstrap/
│   └── app.php                            # Route config, middleware stack
├── config/
│   ├── app.php, auth.php, cors.php, database.php, etc.
├── database/
│   ├── migrations/                        # 12 migration files
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── SuperAdminSeeder.php           # superadmin role + account
│   │   └── PermissionsSeeder.php          # 22 permissions
│   └── form_templates/
│       ├── 0001_form_berita_acara.json
│       └── 0002_form_rekap_analis.json
├── routes/
│   └── api.php                            # All API routes
├── storage/app/                           # File uploads (akses, payment proof)
└── .env                                   # DB_HOST=127.0.0.1, DB_DATABASE=db_lab_terpadu
```

## Tech Stack

| Package | Version | Purpose |
|---|---|---|
| PHP | 8.2+ | Language |
| Laravel | 12.x | Framework |
| MySQL | - | Database (via XAMPP) |
| php artisan serve | - | Dev server (port 8000) |

> **Tidak ada Sanctum/Passport** — autentikasi pakai custom `api_token` column + `ApiTokenMiddleware`.

## Routing (`routes/api.php`)

### Middleware Stack

```php
// bootstrap/app.php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, // unused, just in stack
    'apirottle:10,1',
],
```

### Public Routes (no auth)

| Method | URI | Controller | Purpose |
|---|---|---|---|
| POST | `api/auth/login` | AuthController@login | Login, returns token |
| POST | `api/auth/register` | AuthController@register | Register new user |
| GET | `api/roles` | PublicController@roles | List non-system roles |
| GET | `api/form-templates/{id}` | PublicFormController@show | Public form data |

### User Routes (`auth:sanctum` + `verified`)

| Method | URI | Controller | Purpose |
|---|---|---|---|
| GET | `api/auth/me` | AuthController@me | Current user |
| POST | `api/auth/logout` | AuthController@logout | Logout |
| GET | `api/user/berita-acara` | BeritaAcaraController@index | User's BA list |
| GET | `api/user/berita-acara/{id}` | BeritaAcaraController@show | BA detail |
| POST | `api/user/berita-acara` | BeritaAcaraController@store | Create BA |
| POST | `api/user/berita-acara/{id}` | BeritaAcaraController@update | Update BA (_method=PUT) |
| GET | `api/user/payments` | PaymentController@index | User's payments |
| GET | `api/user/payments/{id}` | PaymentController@show | Payment detail |
| POST | `api/user/payments` | PaymentController@store | Create payment |
| POST | `api/user/payments/{id}/upload-proof` | PaymentController@uploadProof | Upload proof |
| POST | `api/user/payments/{id}/discount` | PaymentController@requestDiscount | Request discount |
| GET | `api/user/certificates/{id}/download` | CertificateController@download | Download cert |

### Admin Routes (role: admin|superadmin)

| Method | URI | Controller | Purpose |
|---|---|---|---|
| GET | `api/admin/berita-acara` | AdminBeritaAcaraController@index | All BA |
| GET | `api/admin/berita-acara/{id}` | AdminBeritaAcaraController@show | BA detail |
| PUT | `api/admin/berita-acara/{id}/status` | AdminBeritaAcaraController@updateStatus | Approve/reject/revision |
| PUT | `api/admin/berita-acara/{id}/assign-analyst` | AdminBeritaAcaraController@assignAnalyst | Assign analyst |
| GET | `api/admin/payments` | AdminPaymentController@index | All payments |
| PUT | `api/admin/payments/{id}/confirm` | AdminPaymentController@confirm | Confirm payment |
| GET | `api/admin/samples` | AdminSampleController@index | All samples |
| PUT | `api/admin/samples/{id}/mark-taken` | AdminSampleController@markTaken | Mark sample taken |
| GET | `api/admin/analysts` | AdminBeritaAcaraController@analysts | List analysts |
| GET | `api/admin/users` | AdminUserController@index | All users |
| PUT | `api/admin/users/{id}/verify` | AdminUserController@verify | Verify/revoke |

### Admin Role/Permission Routes (superadmin only)

| Method | URI | Controller | Purpose |
|---|---|---|---|
| GET | `api/admin/roles` | RoleController@index | All roles |
| GET | `api/admin/roles/{id}` | RoleController@show | Role detail |
| POST | `api/admin/roles` | RoleController@store | Create role |
| PUT | `api/admin/roles/{id}` | RoleController@update | Update role |
| DELETE | `api/admin/roles/{id}` | RoleController@destroy | Delete role |
| GET | `api/admin/roles/permissions/list` | PermissionController@index | List permissions |

### Form Template Routes

| Method | URI | Controller | Purpose |
|---|---|---|---|
| GET | `api/admin/form-templates` | FormTemplateController@index | List templates (admin) |
| GET | `api/admin/form-templates/{id}` | FormTemplateController@show | Template detail |
| PUT | `api/admin/form-templates/{id}` | FormTemplateController@update | Update metadata |
| PUT | `api/admin/form-templates/{id}/fields` | FormTemplateController@syncFields | Full field sync |

### Analyst Routes (role: analyst)

| Method | URI | Controller | Purpose |
|---|---|---|---|
| GET | `api/analyst/berita-acara` | AnalystController@index | Assigned BA |
| GET | `api/analyst/berita-acara/{id}` | AnalystController@show | BA detail |
| POST | `api/analyst/berita-acara/{id}/pengujian` | AnalystController@storePengujian | Store pengujian |
| PUT | `api/analyst/berita-acara/{id}/samples/{sampleId}` | AnalystController@updateSample | Update sample |
| PUT | `api/analyst/berita-acara/{id}/samples/{sampleId}/ready` | AnalystController@markSampleReady | Mark ready |

## Models & Relationships

### User
- `hasOne(BeritaAcara)` — as applicant
- `belongsTo(Role)` via `role` string column
- `$fillable`: name, email, password, role, phone, api_token, is_verified
- `$hidden`: password, api_token

### BeritaAcara
- `belongsTo(User, 'user_id')` — applicant
- `belongsTo(User, 'analyst_id')` — assigned analyst
- `hasOne(Payment)`
- `hasMany(Sample)`
- `hasOne(Certificate)`
- `hasMany(BeritaAcaraRevision)`
- `hasMany(Pengujian)`
- `$fillable`: all 20+ form fields + status, analyst_id, user_id, diskon_diizinkan

### BeritaAcaraRevision
- `belongsTo(BeritaAcara)`
- `$fillable`: berita_acara_id, revision_type, old_value, new_value, notes, revised_by

### Payment
- `belongsTo(BeritaAcara)`
- `$fillable`: berita_acara_id, amount, discount_request, discount_reason, discount_approved, status, payment_proof

### Sample
- `belongsTo(BeritaAcara)`
- `$fillable`: berita_acara_id, name, description, quantity, unit, test_result, status

### Certificate
- `belongsTo(BeritaAcara)`
- `$fillable`: berita_acara_id, file_path, downloaded_at

### Pengujian
- `belongsTo(BeritaAcara)`
- `$fillable`: berita_acara_id, data (JSON)

### FormTemplate
- `hasMany(FormField, 'form_template_id')` — sorted by sort_order
- `belongsTo(User, 'created_by')` — creator
- `$fillable`: name, type, description, is_active, is_default, created_by
- `casts`: is_active → boolean, is_default → boolean

### FormField
- `belongsTo(FormTemplate)`
- `$fillable`: form_template_id, label, type, options (JSON), is_required, sort_order, allow_other
- `casts`: options → array, is_required → boolean, allow_other → boolean

### Role
- `belongsToMany(Permission, 'role_permission')`
- `hasMany(User)`
- `$fillable`: name, description, is_system
- `casts`: is_system → boolean

### Permission
- `belongsToMany(Role, 'role_permission')`
- `$fillable`: name, display_name, group, description

## Middleware

### ApiTokenMiddleware

```php
// Handles: api.token check + role validation + superadmin bypass
// Token extracted from: Authorization: Bearer {api_token} OR X-API-Token header
// Uses: WHERE api_token = ? (not hash, not Sanctum)
// Superadmin bypass: passes all role checks
```

### EnsureUserVerified

```php
// Returns 403 if user.is_verified = false
// Applied to user routes (not admin, not analyst, not public)
```

## Error Response Format

```json
// Validation error (422)
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}

// Auth error (401)
{ "message": "Unauthenticated." }

// Forbidden (403)
{ "message": "Forbidden." }

// Not found (404)
{ "message": "Not found." }

// Success
{
  "message": "Operation successful.",
  "data": { ... }
}
```

## Database Config (`.env`)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_lab_terpadu
DB_USERNAME=root
DB_PASSWORD=
```
