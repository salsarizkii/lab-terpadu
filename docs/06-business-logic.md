# 06. Business Logic

## Status Flow — Berita Acara

### Normal Flow (Happy Path)

```
[1. Draft] ──submit──> [2. Pending] ──approve──> [3. Approved]
                                                      │
                                                      ├─assign analyst─> [Analyst Processing]
                                                      │
                                                      ├─payment approved─> [Payment Confirmed]
                                                      │
                                                      └─all done─> [4. Completed]
```

### Status Definitions

| Status | Siapa yang set | Keterangan |
|---|---|---|
| `draft` | User (default) | User sedang mengisi form, belum submit |
| `pending` | User | User sudah submit, menunggu review admin |
| `revision` | Admin | Admin minta user revisi some fields |
| `approved` | Admin | Admin approve BA, analyst bisa mulai kerja |
| `rejected` | Admin | Admin reject BA |

### Transisi Status

```
draft → pending         (User submit)
pending → approved      (Admin approve)
pending → rejected      (Admin reject)
pending → revision      (Admin minta revisi)
revision → pending      (User re-submit setelah revisi)
approved → (final)      (Selesai, analyst kerja)
```

### Validasi Transisi

```php
// AdminBeritaAcaraController@updateStatus
$validTransitions = [
    'pending'   => ['approved', 'rejected', 'revision'],
    'revision'  => ['pending'],
];

if (!isset($validTransitions[$currentStatus][$newStatus])) {
    return response()->json(['message' => 'Invalid status transition.'], 422);
}
```

## Status Flow — Payment

```
[User creates] → [pending] ──admin confirm──> [approved] | [rejected]
                         │
                         └─user upload proof─> [pending confirmation]
```

| Status | Keterangan |
|---|---|
| `pending` | Payment dibuat, menunggu konfirmasi admin |
| `approved` | Admin approve, user bisa lanjut |
| `rejected` | Admin reject |

## Status Flow — Sample

```
[Created] → [pending] ──analyst process──> [processing] ──done──> [ready] ──taken──> [taken]
```

| Status | Keterangan |
|---|---|
| `pending` | Hasil Pengujian baru, belum diproses |
| `processing` | Analyst sedang memproses |
| `ready` | Selesai diproses, siap diambil |
| `taken` | Sudah diambil oleh user/admin |

## Discount Flow

```
1. Admin toggle diskon_diizinkan = true di BA
2. User lihat form discount di payment page
3. User submit: discount_request + discount_reason
4. Admin lihat di payment confirmation page
5. Admin set discount_approved amount
6. Final amount = amount - discount_approved
```

### Rules

- User hanya bisa request discount jika `berita_acara.diskon_diizinkan = true`
- Admin set `discount_approved` saat konfirmasi payment
- `discount_request` dan `discount_approved` bisa beda (admin bisa kurangi)
- Discount reason harus diisi user

## Analyst Assignment

```
1. Admin buka BA detail
2. Admin lihat dropdown analyst (list dari /admin/analysts)
3. Admin pilih analyst → PUT /admin/berita-acara/{id}/assign-analyst
4. Analyst bisa lihat assigned BA di /analyst/berita-acara
5. Analyst proses sample, update test_result
6. Analyst mark sample ready
7. Admin mark sample taken
```

## Form Builder — Sync Logic

### Delete-Then-Reinsert

```php
// FormTemplateController@syncFields
\DB::beginTransaction();

// 1. Hapus semua fields lama
$template->fields()->delete();

// 2. Insert fields baru
foreach ($validated['fields'] as $index => $fieldData) {
    $template->fields()->create([
        'label' => $fieldData['label'],
        'type' => $fieldData['type'],
        'options' => $fieldData['options'] ?? null,
        'is_required' => $fieldData['is_required'],
        'sort_order' => $index,
    ]);
}

\DB::commit();
```

### Options Format

```json
[
  { "label": "Option 1", "is_other": false },
  { "label": "Option 2", "is_other": false },
  { "label": "", "is_other": true }
]
```

- `is_other: true` → user bisa isi manual (bukan pilihan dari list)
- Empty labels filtered out before save

## File Upload

### Akses Lab File

```php
// User upload file akses lab
$file = $request->file('akses_lab');
$path = $file->store('akses', 'public');
// Saved to: storage/app/private/akses/{hash}.{ext}
```

### Payment Proof

```php
$file = $request->file('payment_proof');
$path = $file->store('payment-proofs', 'public');
```

### Storage Config

```php
// .env
FILESYSTEM_DISK=local
// Files stored in: storage/app/private/
// Accessed via: Storage::disk('private')->url($path)
```

## Revenue Calculation

```php
// AdminDashboard - total revenue
$totalRevenue = Payment::where('status', 'approved')->sum('amount');

// With discounts
$netRevenue = Payment::where('status', 'approved')
    ->selectRaw('SUM(amount - COALESCE(discount_approved, 0)) as net')
    ->value('net');
```

## Revision System

```php
// When admin requests revision
BeritaAcaraRevision::create([
    'berita_acara_id' => $id,
    'revision_type' => 'status_change',
    'old_value' => $oldStatus,
    'new_value' => 'revision',
    'notes' => $request->notes,
    'revised_by' => auth()->id(),
]);
```

## Form Submission vs Form Builder

- **Form Builder** (`FormTemplateController@syncFields`) → Admin manage form structure
- **Form Submission** (`BeritaAcaraController@store`) → User submit form data (Berita Acara)
- Form fields define WHAT data to collect
- Berita Acara stores the ACTUAL submitted data
- Each BA field maps to a `berita_acaras` column
