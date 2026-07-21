<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\DiscountHistory;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::whereHas('beritaAcara', function ($q) {
            $q->where('user_id', auth()->id());
        })->with('beritaAcara')->get();

        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'berita_acara_id' => 'required|exists:berita_acaras,id',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $beritaAcara = BeritaAcara::where('user_id', auth()->id())
            ->findOrFail($request->berita_acara_id);

        if ($beritaAcara->status !== 'approved') {
            return response()->json(['message' => 'Berita acara must be approved first'], 422);
        }

        if ($beritaAcara->payment) {
            return response()->json(['message' => 'Payment already exists for this berita acara'], 422);
        }

        $data = [
            'berita_acara_id' => $request->berita_acara_id,
            'amount' => $beritaAcara->biaya,
        ];

        if ($request->hasFile('payment_proof')) {
            $data['payment_proof'] = $request->file('payment_proof')->store('payments', 'public');
        }

        $payment = Payment::create($data);
        $payment->load('beritaAcara');

        return response()->json([
            'message' => 'Payment created successfully',
            'data' => $payment,
        ], 201);
    }

    public function show($id)
    {
        $payment = Payment::whereHas('beritaAcara', function ($q) {
            $q->where('user_id', auth()->id());
        })->with('beritaAcara.formula')->findOrFail($id);

        return response()->json($payment);
    }

    public function uploadProof(Request $request, $id)
    {
        $request->validate([
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:2048',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $payment = Payment::whereHas('beritaAcara', function ($q) {
            $q->where('user_id', auth()->id());
        })->findOrFail($id);

        $updateData = [
            'payment_proof' => $request->file('payment_proof')->store('payments', 'public'),
        ];

        if ($request->has('keterangan')) {
            $updateData['keterangan'] = $request->input('keterangan');
        }

        $payment->update($updateData);

        return response()->json([
            'message' => 'Payment proof uploaded',
            'data' => $payment,
        ]);
    }

    public function requestDiscount(Request $request, $id)
    {
        $request->validate([
            'discount_request' => 'required|numeric|min:0',
            'discount_reason' => 'nullable|string|max:1000',
        ]);

        $payment = Payment::whereHas('beritaAcara', function ($q) {
            $q->where('user_id', auth()->id());
        })->findOrFail($id);

        $payment->update([
            'discount_request' => $request->discount_request,
            'discount_reason' => $request->discount_reason,
            'discount_status' => 'pending',
        ]);

        DiscountHistory::create([
            'payment_id' => $payment->id,
            'discount_request' => $request->discount_request,
            'discount_reason' => $request->discount_reason,
            'status' => 'pending',
            'action' => 'request',
            'changed_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Discount request submitted',
            'data' => $payment,
        ]);
    }
}
