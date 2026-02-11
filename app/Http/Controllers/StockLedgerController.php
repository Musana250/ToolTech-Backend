<?php

namespace App\Http\Controllers;

use App\Models\StockLedger;
use App\Models\Product;
use Illuminate\Http\Request;

class StockLedgerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // You can include product details using with()
        $ledgers = StockLedger::with('product')->latest()->get();
        return response()->json($ledgers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:purchase,sale',
            'quantity' => 'required|integer',
            'rate' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'reference_id' => 'nullable|integer',
        ]);

        $ledger = StockLedger::create($validated);

        return response()->json([
            'message' => 'Stock ledger entry created successfully.',
            'data' => $ledger
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $ledger = StockLedger::with('product')->findOrFail($id);
        return response()->json($ledger);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $ledger = StockLedger::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'type' => 'sometimes|in:purchase,sale',
            'quantity' => 'sometimes|integer',
            'rate' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'reference_id' => 'nullable|integer',
        ]);

        $ledger->update($validated);

        return response()->json([
            'message' => 'Stock ledger entry updated successfully.',
            'data' => $ledger
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $ledger = StockLedger::findOrFail($id);
        $ledger->delete();

        return response()->json(['message' => 'Stock ledger entry deleted successfully.']);
    }
}
