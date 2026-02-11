<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use App\Imports\PurchasesImport;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseController extends Controller
{
    // Get all purchases
    public function index()
    {
        return response()->json(Purchase::with('product')->get());
    }

    // Store a new purchase
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'buying_price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
        ]);

        // Create purchase record
        $purchase = Purchase::create($data);

        // Update product stock
        $product = $purchase->product;
        $previousBalance = $product->quantity ?? 0;

        $product->quantity = $previousBalance + $data['quantity'];
        $product->buying_price = $data['buying_price'];
        $product->save();

        // Create stock ledger entry
        StockLedger::create([
            'product_id'   => $data['product_id'],
            'type'         => 'purchase',
            'date'         => $data['purchase_date'],
            'quantity'     => $data['quantity'],
            'balance'      => $product->quantity,
            'rate'         => $data['buying_price'],
            'total'        => $data['quantity'] * $data['buying_price'],
            'reference_id' => $purchase->id,
        ]);

        return response()->json($purchase, 201);
    }

    // Show single purchase
    public function show($id)
    {
        $purchase = Purchase::with('product')->findOrFail($id);
        return response()->json($purchase);
    }

    // Update purchase
    public function update(Request $request, $id)
    {
        $purchase = Purchase::findOrFail($id);

        $data = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'quantity' => 'sometimes|integer|min:1',
            'buying_price' => 'sometimes|numeric|min:0',
            'total' => 'sometimes|numeric|min:0',
            'purchase_date' => 'sometimes|date',
        ]);

        $purchase->update($data);

        return response()->json($purchase);
    }

    // Delete purchase
    public function destroy($id)
    {
        $purchase = Purchase::findOrFail($id);
        $purchase->delete();

        return response()->json(['message' => 'Purchase deleted successfully']);
    }


    // -------------------------------
    // ✅ Excel Upload Function
    // -------------------------------
    public function uploadExcel(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }
    
        $file = $request->file('file');
    
        Excel::import(new PurchasesImport, $file);
    
        return response()->json(['message' => 'File imported successfully']);
    }
}  