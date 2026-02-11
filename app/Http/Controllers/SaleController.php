<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\imports\SalesImport;
use Maatwebsite\Excel\Facades\Excel;
class SaleController extends Controller
{
    // Get all sales
    public function index()
    {
        return response()->json(Sale::with('product')->get());
    }

    // Get single sale
    public function show($id)
    {
        $sale = Sale::with('product')->find($id);

        if (!$sale) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        return response()->json($sale);
    }

    // Create a new sale
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'selling_price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'sales_date' => 'required|date',
        ]);

        // Create sale record
        $sale = Sale::create($data);

        // Update product quantity and price
        $product = $sale->product;
        $product->quantity -= $data['quantity'];
        $product->selling_price = $data['selling_price'];
        $product->save();

        return response()->json($sale, 201);
    }

    // Update a sale
    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);

        $data = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'quantity' => 'sometimes|integer|min:1',
            'selling_price' => 'sometimes|numeric|min:0',
            'total' => 'sometimes|numeric|min:0',
            'sales_date' => 'sometimes|date',
        ]);

        $sale->update($data);

        return response()->json($sale);
    }

    // Delete a sale
    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);
        $sale->delete();

        return response()->json(['message' => 'Sale deleted successfully']);
    }

    // Get total sales
    public function totalSales()
    {
        $total = Sale::sum('total');

        return response()->json([
            'total_sales' => (float) $total,
        ]);
    }

    // Get weekly sales
    public function weeklySales()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $dateColumn = 'sales_date'; // adjust if needed

        $total = Sale::whereBetween($dateColumn, [$startOfWeek, $endOfWeek])
                     ->sum('total');

        return response()->json([
            'week_start' => $startOfWeek->toDateString(),
            'week_end'   => $endOfWeek->toDateString(),
            'weekly_sales' => (float) $total,
        ]);
    }
    public function uploadExcel(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }
    
        $file = $request->file('file');
    
        Excel::import(new SalesImport, $file);
    
        return response()->json(['message' => 'File imported successfully']);
    }
}
