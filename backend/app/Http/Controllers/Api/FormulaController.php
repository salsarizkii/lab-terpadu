<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formula;
use Illuminate\Http\Request;

class FormulaController extends Controller
{
    public function index()
    {
        $formulas = Formula::with('creator:id,name')
            ->where('created_by', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json($formulas);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
            'expression' => 'required|string',
            'unit' => 'nullable|string|max:100',
            'variables' => 'required|array',
        ]);

        $validated['created_by'] = auth()->id();

        $formula = Formula::create($validated);

        return response()->json([
            'message' => 'Rumus berhasil disimpan',
            'data' => $formula,
        ], 201);
    }

    public function show($id)
    {
        $formula = Formula::with('creator:id,name')->findOrFail($id);
        return response()->json($formula);
    }

    public function update(Request $request, $id)
    {
        $formula = Formula::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:500',
            'expression' => 'sometimes|string',
            'unit' => 'nullable|string|max:100',
            'variables' => 'sometimes|array',
        ]);

        $formula->update($validated);

        return response()->json([
            'message' => 'Rumus berhasil diperbarui',
            'data' => $formula,
        ]);
    }

    public function destroy($id)
    {
        $formula = Formula::findOrFail($id);
        $formula->delete();

        return response()->json(['message' => 'Rumus berhasil dihapus']);
    }
}
