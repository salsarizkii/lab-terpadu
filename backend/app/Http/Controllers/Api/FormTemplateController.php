<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use Illuminate\Http\Request;

class FormTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = FormTemplate::withCount('fields')->with('creator:id,name');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->get());
    }

    public function show($id)
    {
        $template = FormTemplate::with('fields')->with('creator:id,name')->findOrFail($id);

        return response()->json($template);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'type' => 'required|string|in:berita_acara,rekap_analis',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_active'] = true;

        $template = FormTemplate::create($validated);

        return response()->json([
            'message' => 'Template form berhasil dibuat',
            'data' => $template,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $template = FormTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Template form berhasil diperbarui',
            'data' => $template,
        ]);
    }

    public function destroy($id)
    {
        $template = FormTemplate::findOrFail($id);

        if ($template->is_default) {
            return response()->json(['message' => 'Template default tidak dapat dihapus'], 403);
        }

        $template->delete();

        return response()->json(['message' => 'Template form berhasil dihapus']);
    }

    public function syncFields(Request $request, $id)
    {
        $template = FormTemplate::findOrFail($id);

        $messages = [
            'fields.*.label.required' => 'Judul pertanyaan wajib diisi.',
            'fields.*.label.max' => 'Judul pertanyaan maksimal 200 karakter.',
            'fields.*.type.required' => 'Jenis pertanyaan wajib dipilih.',
            'fields.*.type.in' => 'Jenis pertanyaan tidak valid.',
            'fields.*.options.*.label.max' => 'Label opsi maksimal 200 karakter.',
        ];

        $validated = $request->validate([
            'fields' => 'required|array',
            'fields.*.label' => 'required|string|max:200',
            'fields.*.type' => 'required|in:text,textarea,number,select,checkbox,radio,date,time,email,phone,file,heading,paragraph,divider',
            'fields.*.options' => 'nullable|array',
            'fields.*.options.*.label' => 'nullable|string|max:200',
            'fields.*.options.*.is_other' => 'sometimes|boolean',
            'fields.*.is_required' => 'sometimes|boolean',
        ], $messages);

        $template->fields()->delete();

        foreach ($validated['fields'] as $index => $field) {
            $options = null;
            if (isset($field['options'])) {
                $options = array_filter(
                    array_map(fn($opt) => [
                        'label' => $opt['label'] ?? '',
                        'is_other' => $opt['is_other'] ?? false,
                    ], $field['options']),
                    fn($opt) => $opt['label'] !== '' || $opt['is_other']
                );
                $options = array_values($options);
                if (empty($options)) $options = null;
            }

            $template->fields()->create([
                'label' => $field['label'],
                'type' => $field['type'],
                'options' => $options,
                'is_required' => $field['is_required'] ?? false,
                'sort_order' => $index,
            ]);
        }

        $template->load('fields');

        return response()->json([
            'message' => 'Fields berhasil disimpan',
            'data' => $template,
        ]);
    }

    public function publicShow($id)
    {
        $template = FormTemplate::with('fields')
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json($template);
    }

    public function publicActiveByType($type)
    {
        $template = FormTemplate::with('fields')
            ->where('type', $type)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return response()->json(['message' => 'Template tidak ditemukan'], 404);
        }

        return response()->json($template);
    }
}
