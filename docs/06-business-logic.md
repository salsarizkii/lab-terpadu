# 06. Business Logic

## Status Flow — Berita Acara

### Normal Flow (Happy Path)

```
[1. Draft] ──submit──> [2. Pending] ──admin──> [Admin Verified]
                                                     │
                                                     └─penyelia──> [Penyelia Verified] ──analyst rekap──> [Rekap Ready]
                                                                                                              │
                                                                                                              └─manager teknis──> [Completed]
                                                                                                                                  │
                                                                                                                                  └─kembali ke user (bayar → upload bukti → admin konfirmasi → sertifikat)
```

Alur verifikasi bertingkat:
1. **User** submit → `pending`
2. **Admin** verifikasi (wajib nomor agenda) → `admin_verified`
3. **Penyelia** verifikasi → `penyelia_verified` (analis mulai bekerja)
4. **Analis** isi hasil pengujian + simpan rekap → `rekap_ready`
5. **Manager Teknis** verifikasi rekap → `completed` (kembali ke user untuk pembayaran)

### Status Definitions

| Status | Siapa yang set | Keterangan |
|---|---|---|
| `draft` | User (default) | User sedang mengisi form, belum submit |
| `pending` | User | User sudah submit, menunggu verifikasi admin |
| `revision` | Admin/Penyelia/Manager | Minta user revisi, alur kembali ke awal |
| `admin_verified` | Admin | Admin approve, menunggu verifikasi penyelia |
| `penyelia_verified` | Penyelia | Penyelia approve, siap dikerjakan analis |
| `rekap_ready` | Analyst (otomatis) | Rekap analis disimpan, menunggu verifikasi manager teknis |
| `completed` | Manager Teknis | Rekap disetujui, kembali ke user (pembayaran) |
| `rejected` | Admin/Manager | BA ditolak permanen |
| *(pending)* | Penyelia (reject) | Tolak dari penyelia → kembali ke antrian admin untuk direvisi |

### Transisi Status

```
draft → pending            (User submit)
pending → admin_verified   (Admin approve, wajib nomor_agenda)
pending → rejected         (Admin reject)
pending → revision         (Admin minta revisi)
admin_verified → penyelia_verified | revision | rejected   (Penyelia; rejected → dikembalikan ke pending/admin)
penyelia_verified → rekap_ready   (Analis simpan rekap → otomatis)
rekap_ready → completed | revision | rejected              (Manager Teknis)
revision → pending         (User re-submit setelah revisi)
```

> Revisi dari penyelia/manager mengembalikan BA ke `pending` — user perbaiki → alur diulang dari admin.
> **Tolak (rejected) dari penyelia**: BA dikembalikan ke antrian admin (`pending`) dengan alasan penolakan tersimpan di `rejection_reason` — admin memperbaiki lalu verifikasi ulang. `rejection_reason` dibersihkan otomatis saat admin memverifikasi ulang.
> **Tolak (rejected) dari admin/manager**: BA ditolak permanen.

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
- Pembayaran baru bisa dilakukan user setelah BA berstatus `completed` (setelah verifikasi manager teknis)
- Admin set `discount_approved` saat konfirmasi payment
- `discount_request` dan `discount_approved` bisa beda (admin bisa kurangi)
- Discount reason harus diisi user

## Analyst Assignment

```
1. Admin buka BA detail
2. Admin lihat dropdown analyst (list dari /admin/analysts)
3. Admin pilih analyst → PUT /admin/berita-acara/{id}/assign-analyst
4. Analyst bisa lihat assigned BA di /analyst/samples saat status penyelia_verified
5. Analyst proses sample, update test_result
6. Analyst simpan rekap → status otomatis rekap_ready
7. Manager teknis verifikasi rekap → completed
```

## Verifikasi Penyelia & Manager Teknis

- **Penyelia** melihat BA berstatus `admin_verified` (yang di-assign, kecuali punya `berita_acara_riwayat_semua`).
  Endpoint: `GET/PUT /api/penyelia/berita-acara[/{id}/status]` — transisi ke `penyelia_verified | revision | rejected`.
- **Manager Teknis** melihat BA berstatus `rekap_ready` (yang di-assign). Endpoint: `GET/PUT /api/manager-teknis/berita-acara[/{id}/status]` — transisi ke `completed | revision | rejected`.
- Controller: `VerificationController`, diakses lewat role/permission (`berita_acara_verify` untuk penyelia, `hasil_pengujian_rekap` untuk manager teknis).
- Semua aksi dicatat ke ActivityLog: `admin_verified`, `penyelia_verified`, `rekap_verified`, `completed`.

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
