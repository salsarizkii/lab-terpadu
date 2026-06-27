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

        $validated = $request->validate([
            'fields' => 'required|array',
            'fields.*.label' => 'required|string|max:200',
            'fields.*.type' => 'required|in:text,textarea,number,select,checkbox,date,file',
            'fields.*.options' => 'nullable|array',
            'fields.*.is_required' => 'sometimes|boolean',
            'fields.*.placeholder' => 'nullable|string|max:200',
        ]);

        $template->fields()->delete();

        foreach ($validated['fields'] as $index => $field) {
            $template->fields()->create([
                'label' => $field['label'],
                'type' => $field['type'],
                'options' => $field['options'] ?? null,
                'is_required' => $field['is_required'] ?? false,
                'sort_order' => $index,
                'placeholder' => $field['placeholder'] ?? null,
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
}
