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

## Permission List (22)

### Group: berita_acara (5)

| Name | Display Name | Description |
|---|---|---|
| `ba_create` | Buat Berita Acara | Create new BA |
| `ba_read` | Lihat Berita Acara | View BA list |
| `ba_update` | Edit Berita Acara | Edit BA |
| `ba_delete` | Hapus Berita Acara | Delete BA |
| `ba_verify` | Verifikasi Berita Acara | Verify/reject BA (admin) |

### Group: pembayaran (2)

| Name | Display Name | Description |
|---|---|---|
| `payment_read` | Lihat Pembayaran | View payment list |
| `payment_confirm` | Konfirmasi Pembayaran | Confirm/reject payment |

### Group: sample (2)

| Name | Display Name | Description |
|---|---|---|
| `sample_read` | Lihat Sampel | View samples |
| `sample_update` | Update Sampel | Update sample status/result |

### Group: sertifikat (2)

| Name | Display Name | Description |
|---|---|---|
| `certificate_read` | Lihat Sertifikat | View certificates |
| `certificate_download` | Download Sertifikat | Download certificate |

### Group: pengujian (3)

| Name | Display Name | Description |
|---|---|---|
| `pengujian_read` | Lihat Pengujian | View test data |
| `pengujian_create` | Buat Pengujian | Create test entry |
| `pengujian_update` | Edit Pengujian | Update test data |

### Group: user (3)

| Name | Display Name | Description |
|---|---|---|
| `user_read` | Lihat User | View user list |
| `user_verify` | Verifikasi User | Verify/revoke user |
| `user_manage` | Kelola User | Full user management |

### Group: role (2)

| Name | Display Name | Description |
|---|---|---|
| `role_read` | Lihat Role | View roles |
| `role_manage` | Kelola Role | Create/edit/delete roles |

### Group: form_template (2)

| Name | Display Name | Description |
|---|---|---|
| `form_read` | Lihat Form Template | View form templates |
| `form_manage` | Kelola Form Template | Create/edit form templates |

## Role Management

### Default Roles

| Role | is_system | Users | Permissions |
|---|---|---|---|
| `superadmin` | true | 1 (seeded) | All (bypass) |
| `admin` | false | 0 (created via UI) | Managed by superadmin |
| `analyst` | false | 0 (created via UI) | Managed by superadmin |
| `user` | false | 0 (created via UI) | Managed by superadmin |

### Role CRUD Rules

- Only superadmin can manage roles (`/admin/roles`)
- System roles (`is_system = true`) cannot be deleted
- System roles cannot have `is_system` toggled off
- Role name is unique
- Permissions are synced via `role_permission` pivot

### Create Role Flow

```
1. Superadmin klik "Tambah Role"
2. Fill: name, description
3. Check permission checkboxes (grouped by category)
4. POST /admin/roles { name, description, permissions: [...] }
5. Backend: Role::create() + $role->permissions()->sync($permissionIds)
6. Return role with permissions
```

## Superadmin Bypass

```php
// ApiTokenMiddleware
if ($user->role === 'superadmin') {
    return $next($request);  // Skip all role checks
}
```

Superadmin:
- Can access ALL routes (admin, analyst, user)
- Can manage roles and permissions
- Cannot be deleted or have role changed
- Does NOT use permission system (has implicit full access)

## Permission Seeding

```php
// PermissionsSeeder.php
// 22 permissions across 8 groups
// Created in migrate:fresh --seed
```

## Frontend Role Guard

```typescript
// router/index.ts
meta: {
  auth: true,
  role: ['admin', 'superadmin']  // Only these roles can access
}

// In beforeEach guard:
if (isSuperAdmin) {
  // Superadmin bypass: can access admin routes
}
```

## Permission UI (AdminRoles.vue)

### Features

1. **Role List** — Table with name, description, is_system, users_count
2. **Role Detail** — Click to expand/edit
3. **Permission Checkboxes** — Grouped by category (8 groups)
4. **Inline Editing** — Edit role name/description
5. **Delete** — Disabled for system roles
6. **Users Count** — Shows how many users have this role
