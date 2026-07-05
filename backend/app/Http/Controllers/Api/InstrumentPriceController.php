<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstrumentPrice;
use Illuminate\Http\Request;

class InstrumentPriceController extends Controller
{
    public function index()
    {
        $prices = InstrumentPrice::orderBy('sort_order')->orderBy('id')->get();
        return response()->json($prices);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:100',
            'price_regular' => 'required|numeric|min:0',
            'price_uns' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $instrument = InstrumentPrice::create($validated);

        return response()->json([
            'message' => 'Instrumen berhasil ditambahkan',
            'data' => $instrument,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $instrument = InstrumentPrice::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'unit' => 'nullable|string|max:100',
            'price_regular' => 'sometimes|required|numeric|min:0',
            'price_uns' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $instrument->update($validated);

        return response()->json([
            'message' => 'Instrumen berhasil diperbarui',
            'data' => $instrument,
        ]);
    }

    public function destroy($id)
    {
        $instrument = InstrumentPrice::findOrFail($id);
        $instrument->delete();

        return response()->json([
            'message' => 'Instrumen berhasil dihapus',
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:instrument_prices,id',
            'items.*.price_regular' => 'required|numeric|min:0',
            'items.*.price_uns' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            InstrumentPrice::where('id', $item['id'])->update([
                'price_regular' => $item['price_regular'],
                'price_uns' => $item['price_uns'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Harga instrumen berhasil diperbarui',
        ]);
    }
}
