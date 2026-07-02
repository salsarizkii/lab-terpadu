# 05. Authentication & Authorization

## Auth Flow

```
┌──────────┐         ┌──────────────┐         ┌──────────────┐
│ Frontend │         │   Backend    │         │   Database   │
└────┬─────┘         └──────┬───────┘         └──────┬───────┘
     │  POST /auth/login    │                        │
     │ ──────────────────── >│   SELECT * FROM users │
     │                      │ ────────────────────── >│
     │                      │ <──────────────────────│
     │                      │   Verify password     │
     │                      │   Generate api_token  │
     │  { user, token }     │   Save api_token      │
     │ <────────────────────│ ────────────────────── >│
     │                      │                        │
     │  GET /api/user/...   │                        │
     │  Authorization: Bearer {token}                │
     │ ──────────────────── >│   SELECT * FROM users │
     │                      │   WHERE api_token = ?  │
     │                      │ ────────────────────── >│
     │                      │ <──────────────────────│
     │  { data }            │   Check role + bypass  │
     │ <────────────────────│                        │
```

## Token System

- **Type:** Custom `api_token` column di `users` table
- **Bukan:** Sanctum, Passport, JWT
- **Storage:** Frontend simpan di `localStorage` sebagai `token`
- **Header:** `Authorization: Bearer {token}` atau `X-API-Token: {token}`
- **Length:** 60 characters (Str::random(60))
- **Uniqueness:** Unique index di database

### Login Flow

```
1. User submit email + password
2. Backend: find user by email
3. Backend: Hash::check(password, user.password)
4. Backend: generate api_token = Str::random(60)
5. Backend: save api_token ke database
6. Backend: return { user, token }
7. Frontend: save ke localStorage
8. Frontend: save ke authStore state
```

### Logout Flow

```
1. User click logout
2. Frontend: POST /auth/logout (with token)
3. Backend: find user by api_token
4. Backend: set api_token = null
5. Backend: return { message: "Logged out." }
6. Frontend: clear localStorage
7. Frontend: clear authStore state
8. Frontend: redirect ke /login
```

### Session Persistence

```typescript
// authStore.ts
initFromStorage() {
  const token = localStorage.getItem('token')
  if (token) {
    this.token = token
    this.fetchMe()  // Validate token still valid
  }
}

// On 401 response from any API call:
clearAuth()
localStorage.removeItem('token')
router.push('/login')
```

## Role-Based Access Control

### Middleware: ApiTokenMiddleware

```
1. Extract token from Authorization header or X-API-Token
2. Find user WHERE api_token = token
3. If not found → 401 Unauthenticated
4. Attach user to request
5. If route has role requirement:
   a. If user.role = 'superadmin' → BYPASS (always allowed)
   b. Check if user.role is in allowed roles
   c. If not → 403 Forbidden
```

### Frontend Route Guard

```typescript
router.beforeEach((to, from, next) => {
  // 1. Auth required but not logged in → /login
  if (to.meta.auth && !isAuthenticated) return next('/login')

  // 2. Guest page but logged in → /dashboard
  if (to.meta.guest && isAuthenticated) return next('/dashboard')

  // 3. Role check
  if (to.meta.role) {
    const allowed = to.meta.role
    if (isSuperAdmin) {
      // Superadmin can access admin routes
      if (!allowed.includes('superadmin') && !allowed.includes('admin'))
        return next('/dashboard')
    } else if (!allowed.includes(user.role)) {
      return next('/dashboard')
    }
  }

  next()
})
```

### Role Access Matrix

| Page | user | analyst | admin | superadmin |
|---|---|---|---|---|
| /user/* | ✅ | ❌ | ❌ | ❌ |
| /analyst/* | ❌ | ✅ | ❌ | ✅ (bypass) |
| /admin/* | ❌ | ❌ | ✅ | ✅ (bypass) |
| /admin/roles | ❌ | ❌ | ❌ | ✅ |
| /admin/users | ❌ | ❌ | ❌ | ✅ |

## Superadmin Bypass

Superadmin memiliki special treatment:

1. **Middleware bypass** — Melewati semua role check di `ApiTokenMiddleware`
2. **Route access** — Bisa akses admin + superadmin routes
3. **System protection** — Role `superadmin` tidak bisa dihapus
4. **Permissions** — Tidak pakai permission system (akses semua)

## Password Handling

- **Hashing:** `Hash::make()` (bcrypt)
- **Verification:** `Hash::check()`
- **Rules:** Min 8 chars, confirmed (password + password_confirmation)

## User Verification

```php
// EnsureUserVerified middleware
// Applied to: user routes
// Returns 403 if user.is_verified = false
// Admin can verify/revoke via: PUT /admin/users/{id}/verify
```
