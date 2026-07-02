# 07. Form Builder

Dynamic form template system — admin bisa buat dan edit form tanpa coding.

## Architecture

```
┌──────────────────────┐      ┌──────────────────────┐
│    FormTemplate       │─────<│     FormField         │
│                      │      │                      │
│ id                   │      │ id                   │
│ name                 │      │ form_template_id(FK) │
│ type                 │      │ label                │
│ description          │      │ type                 │
│ is_active            │      │ options (JSON)       │
│ is_default           │      │ is_required          │
│ created_by(FK)       │      │ sort_order           │
└──────────────────────┘      └──────────────────────┘
```

## Field Types (14)

### Input Fields (8)

| Type | Component | Output | Description |
|---|---|---|---|
| `text` | `<input type="text">` | string | Single line text |
| `textarea` | `<textarea>` | string | Multi-line text |
| `number` | `<input type="number">` | number | Numeric input |
| `email` | `<input type="email">` | string | Email validation |
| `phone` | `<input type="tel">` | string | Phone number |
| `date` | `<input type="date">` | date string | Date picker |
| `time` | `<input type="time">` | time string | Time picker |
| `file` | `<input type="file">` | file | File upload |

### Choice Fields (3)

| Type | Component | Options Format | Description |
|---|---|---|---|
| `select` | `<select>` | `[{label, is_other}]` | Dropdown selection |
| `radio` | `<input type="radio">` | `[{label, is_other}]` | Single choice |
| `checkbox` | `<input type="checkbox">` | `[{label, is_other}]` | Multiple choice |

### Layout Fields (3)

| Type | Component | Description |
|---|---|---|
| `heading` | `<h3>` | Section heading |
| `paragraph` | `<p>` | Descriptive text |
| `divider` | `<hr>` | Visual separator |

## Options Format

```typescript
interface FormFieldOption {
  label: string     // Display text
  is_other: boolean // If true, user can type custom answer
}
```

### Contoh

```json
[
  { "label": "Sangat Puas", "is_other": false },
  { "label": "Puas", "is_other": false },
  { "label": "Cukup", "is_other": false },
  { "label": "Kurang", "is_other": false },
  { "label": "", "is_other": true }  // "Isi manual" option
]
```

### `is_other` Behavior

- `is_other: false` → Regular option, user pilih dari list
- `is_other: true` → Special "Isi manual" option:
  - Select: user bisa typing custom value
  - Radio: text input muncul setelah radio dipilih
  - Checkbox: text input muncul setelah checkbox dicentang
- Label diabaikan saat `is_other: true` (bisa string kosong)

## Backend: SyncFields

```php
// FormTemplateController@syncFields
public function syncFields(Request $request, FormTemplate $template)
{
    $validated = $request->validate([
        'fields' => 'required|array',
        'fields.*.label' => 'required|string|max:255',
        'fields.*.type' => 'required|string|in:text,textarea,number,...',
        'fields.*.options' => 'nullable|array',
        'fields.*.options.*.label' => 'nullable|string|max:200',
        'fields.*.options.*.is_other' => 'required|boolean',
        'fields.*.is_required' => 'required|boolean',
    ]);

    // Custom error messages (Indonesian)
    $messages = [
        'fields.*.label.required' => 'Judul pertanyaan wajib diisi.',
        'fields.*.type.required' => 'Jenis pertanyaan wajib dipilih.',
        'fields.*.options.*.label.max' => 'Label opsi maksimal 200 karakter.',
    ];

    DB::beginTransaction();

    // 1. Delete all existing fields
    $template->fields()->delete();

    // 2. Insert new fields
    foreach ($validated['fields'] as $index => $fieldData) {
        // Filter out empty options
        $options = null;
        if (!empty($fieldData['options'])) {
            $options = array_values(array_filter(
                $fieldData['options'],
                fn($opt) => !empty($opt['label']) || !empty($opt['is_other'])
            ));
        }

        $template->fields()->create([
            'label' => $fieldData['label'],
            'type' => $fieldData['type'],
            'options' => $options,
            'is_required' => $fieldData['is_required'],
            'sort_order' => $index,
        ]);
    }

    DB::commit();

    return response()->json([
        'message' => 'Fields updated.',
        'data' => [
            'id' => $template->id,
            'name' => $template->name,
            'fields_count' => $template->fields()->count(),
        ],
    ]);
}
```

## Frontend: FormBuilder.vue

### Features

1. **Drag & Drop** — Reorder fields via HTML5 drag API
2. **Inline Editing** — Click to edit label, options
3. **Add Field** — "Tambah" button in each card footer (defaults to text)
4. **Delete Field** — Delete button per card (except last one)
5. **Duplicate Field** — Copy button per card
6. **Required Toggle** — "Wajib diisi" checkbox per card
7. **Type Selector** — Dropdown with `<optgroup>` (Input / Pilihan)
8. **Option Editor** — For select/radio/checkbox with per-option "isi manual" toggle
9. **Section Type** — heading, paragraph, divider (no label/options editor)
10. **SVG Icons** — All icons are inline SVG (no emojis)

### Drop Zone Behavior

- Each card has a drop zone (min-height 8px, 48px when drag active)
- Drop zone appears above the card
- `dragCounter` ref tracks enter/leave for stable detection
- Drop position: `toIndex > fromIndex ? toIndex - 1 : toIndex`

### Card Structure

```
┌─────────────────────────────────────────────┐
│ [≡] Nama Pemohon (text)    [📋] [🗑️]      │ ← Header
├─────────────────────────────────────────────┤
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ Masukkan teks...                        │ │ ← Preview
│ └─────────────────────────────────────────┘ │
│                                             │
│ ☑ Wajib diisi                              │ ← Footer
│                                             │
│ [➕ Tambah]                                 │ ← Add button
└─────────────────────────────────────────────┘
```

### Type Dropdown Structure

```html
<select>
  <optgroup label="Input">
    <option value="text">[T] Text</option>
    <option value="textarea">[¶] Textarea</option>
    <option value="number">[#] Number</option>
    <option value="email">[@] Email</option>
    <option value="phone">[☎] Phone</option>
    <option value="date">[📅] Date</option>
    <option value="time">[⏰] Time</option>
    <option value="file">[📎] File</option>
  </optgroup>
  <optgroup label="Pilihan">
    <option value="select">[▾] Select</option>
    <option value="radio">[◉] Radio</option>
    <option value="checkbox">[☑] Checkbox</option>
  </optgroup>
  <optgroup label="Layout">
    <option value="heading">[H] Heading</option>
    <option value="paragraph">[P] Paragraph</option>
    <option value="divider">[—] Divider</option>
  </optgroup>
</select>
```

> **Note:** `<option>` elements cannot contain SVG — dropdown uses `[icon]` text prefix.

## Form Submission

### User Creates Berita Acara

```
1. User klik "Buat Berita Acara"
2. Frontend: GET /form-templates/{id} (public endpoint)
3. Backend: return template with fields
4. Frontend: render dynamic form based on fields
5. User fill form → POST /user/berita-acara (multipart)
6. Backend: validate + save + create samples + create payment
```

### Public Form Template

```php
// PublicFormController@show
// No auth required
// Returns template with fields, only if is_active = true
```

## Default Templates

| Template | Type | is_default | Field Count |
|---|---|---|---|
| Form Berita Acara | berita_acara | true | 22 |
| Form Rekap Analis | rekap_analis | true | (varies) |

### Default Template Rules

- `is_default = true` → cannot delete
- `is_default = true` → cannot toggle `is_active` off
- Created by seeder, not by users

## Custom Error Messages (Bahasa Indonesia)

```php
$messages = [
    'fields.*.label.required' => 'Judul pertanyaan wajib diisi.',
    'fields.*.type.required' => 'Jenis pertanyaan wajib dipilih.',
    'fields.*.options.*.label.max' => 'Label opsi maksimal 200 karakter.',
];
```

Frontend parses Laravel error object:

```typescript
// FormTemplateEditor.vue
const getFieldErrors = (fieldIndex: number) => {
  const errors: string[] = []
  if (error.value?.errors) {
    for (const [key, msgs] of Object.entries(error.value.errors)) {
      if (key.startsWith(`fields.${fieldIndex}.`)) {
        errors.push(...(msgs as string[]))
      }
    }
  }
  return errors
}
```
