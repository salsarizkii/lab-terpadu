<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\Formula;
use App\Services\CostCalculationService;
use App\Services\SystemActivityLogService;
use Illuminate\Http\Request;

class FormulaController extends Controller
{
    public function index()
    {
        $formulas = Formula::with('creator:id,name')
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

        SystemActivityLogService::log(
            'formula_created',
            "Membuat rumus: {$formula->name}",
            Formula::class,
            $formula->id,
            null,
            ['name' => $formula->name, 'expression' => $formula->expression]
        );

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

        $oldData = ['name' => $formula->name, 'expression' => $formula->expression];

        $formula->update($validated);

        SystemActivityLogService::log(
            'formula_updated',
            "Memperbarui rumus: {$formula->name}",
            Formula::class,
            $formula->id,
            $oldData,
            ['name' => $formula->name, 'expression' => $formula->expression]
        );

        return response()->json([
            'message' => 'Rumus berhasil diperbarui',
            'data' => $formula,
        ]);
    }

    public function destroy($id)
    {
        $formula = Formula::findOrFail($id);
        $formulaName = $formula->name;
        $formula->delete();

        SystemActivityLogService::log(
            'formula_deleted',
            "Menghapus rumus: {$formulaName}",
            Formula::class,
            $formula->id,
            ['name' => $formulaName],
            null
        );

        return response()->json(['message' => 'Rumus berhasil dihapus']);
    }

    /**
     * Calculate cost for a berita_acara using this formula.
     * POST /admin/formulas/{id}/calculate
     */
    public function calculate(Request $request, $id)
    {
        $formula = Formula::findOrFail($id);

        $validated = $request->validate([
            'berita_acara_id' => 'required|exists:berita_acaras,id',
        ]);

        $beritaAcara = BeritaAcara::findOrFail($validated['berita_acara_id']);
        $calcService = new CostCalculationService();

        try {
            $result = $calcService->calculate($beritaAcara, $formula);
            return response()->json([
                'biaya' => $result['biaya'],
                'resolved_values' => $result['resolved_values'],
                'expression' => $formula->expression,
                'unit' => $formula->unit,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghitung biaya: ' . $e->getMessage(),
            ], 422);
        }
    }
}
