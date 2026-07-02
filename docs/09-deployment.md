# 09. Deployment & Development

## Prerequisites

| Tool | Version | Purpose |
|---|---|---|
| Node.js | 18+ | Frontend runtime |
| npm | 8+ | Package manager |
| PHP | 8.2+ | Backend runtime |
| Composer | 2+ | PHP package manager |
| XAMPP | latest | MySQL database |
| Git | latest | Version control |

## Quick Start

### 1. Clone & Install

```bash
git clone <repo-url>
cd LAB-TERPADU

# Install frontend dependencies
npm install

# Install backend dependencies
cd backend
composer install
```

### 2. Database Setup

```bash
# Start MySQL via XAMPP Control Panel

# Create database
mysql -u root -e "CREATE DATABASE db_lab_terpadu;"

# Setup backend
cd backend
cp .env.example .env
php artisan key:generate

# Edit .env — set:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=db_lab_terpadu
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations + seeders
php artisan migrate:fresh --seed
```

### 3. Run Development

```bash
# From project root
npm run dev          # Run both frontend + backend

# Or separately
npm run dev:frontend # Frontend only (port 5173)
npm run dev:backend  # Backend only (port 8000)
```

### 4. Default Accounts

| Email | Password | Role |
|---|---|---|
| `superadmin@lab.com` | `password` | superadmin |

## Environment Variables

### Backend `.env`

```env
APP_NAME=LAB-TERPADU
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_lab_terpadu
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=local
```

## Development Commands

### Frontend

```bash
npm run dev              # Start dev server
npm run build            # Production build
npm run preview          # Preview production build
npx vue-tsc --noEmit     # TypeScript check
```

### Backend

```bash
php artisan serve                   # Start dev server
php artisan migrate:fresh --seed    # Reset database
php artisan route:list              # List all routes
php artisan form:seed               # Re-seed form templates
```

## Production Build

```bash
# Frontend
npm run build    # Output: frontend/dist/

# Backend
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## API Server

```bash
cd backend
php artisan serve --port=8000
# API: http://localhost:8000/api
```

## File Storage

```
backend/storage/app/
├── private/              # Default disk
│   ├── akses/           # Lab access files
│   └── payment-proofs/  # Payment proof images
└── public/              # Public disk
    └── certificates/    # Certificate files
```

## Troubleshooting

### Database Connection Error

```bash
# Check MySQL is running (XAMPP Control Panel)
# Verify .env settings
# Test connection:
mysql -u root -p -h 127.0.0.1 db_lab_terpadu
```

### Migration Errors

```bash
# Reset database completely
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status
```

### Frontend Build Errors

```bash
# Clear node modules
rm -rf node_modules package-lock.json
npm install

# Check TypeScript
npx vue-tsc --noEmit
```

### CORS Issues

```php
// backend/config/cors.php
'allowed_origins' => ['http://localhost:5173'],
'supports_credentials' => true,
```

## Architecture Decisions

### Why Custom Token Auth?

- Disk penuh, tidak bisa install Sanctum/Passport
- Custom `api_token` column lebih ringan
- Cukup untuk 4 role system

### Why MySQL?

- XAMPP sudah terinstall
- Development environment yang umum
- MySQL Workbench untuk management

### Why No UI Library?

- Custom CSS lebih fleksibel
- Design system dengan CSS variables
- Font Poppins dari Google Fonts
- Tidak perlu learn library baru

### Why Form Builder?

- Admin bisa manage form tanpa coding
- Google Forms-style inline editing
- Drag & drop untuk reorder
- Per-option "isi manual" untuk flexibility

## Seeding

### Database Seeder

```php
// DatabaseSeeder.php
public function run(): void
{
    $this->call([
        SuperAdminSeeder::class,  // superadmin role + account
        PermissionsSeeder::class, // 22 permissions
    ]);
}
```

### Form Template Seeder

```bash
php artisan form:seed
```

Reads JSON files from `database/form_templates/`:
- `0001_form_berita_acara.json` — 22 fields
- `0002_form_rekap_analis.json` — (varies)

### Superadmin Seeder

```php
// SuperAdminSeeder.php
// Creates: superadmin role (is_system=true)
// Creates: superadmin@lab.com / password
```

## Backup

```bash
# Database
mysqldump -u root db_lab_terpadu > backup.sql

# Files
tar -czf storage-backup.tar.gz backend/storage/
```
