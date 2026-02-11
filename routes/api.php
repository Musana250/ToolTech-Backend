<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockLedgerController;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
// Authentication
Route::post('/login', [AuthController::class, 'login']);

// Users CRUD (custom style, since you already built it like this)
Route::get('/users', [UserController::class, 'index']);          // list all users
Route::get('/users/{id}', [UserController::class, 'show']);      // get single user
Route::post('/users', [UserController::class, 'store']);         // create user
Route::put('/users/{id}', [UserController::class, 'update']);    // update user
Route::delete('/users/{id}', [UserController::class, 'destroy']);// delete user
Route::get('/sales/total', [SaleController::class, 'totalSales']);
Route::get('/sales/weekly', [SaleController::class, 'weeklySales']);
Route::get('/products/total', [ProductController::class, 'totalCount']);

// Categories & Products CRUD (Laravel resource style)
Route::apiResource('categories', CategoryController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('purchases', PurchaseController::class);
Route::post('/purchases/upload-excel', [PurchaseController::class, 'uploadExcel']);

Route::apiResource('sales', SaleController::class);
Route::post('/sales/upload-excel', [SaleController::class, 'uploadExcel']);

Route::apiResource('stock-ledgers', StockLedgerController::class);
Route::get('/category-sales', function() {
    $data = DB::table('sales')
        ->join('products', 'sales.product_id', '=', 'products.id')
        ->join('categories', 'products.category_id', '=', 'categories.id')
        ->select('categories.name as name', DB::raw('SUM(sales.quantity) as value'))
        ->groupBy('categories.id', 'categories.name')
        ->get()
        ->map(function($item) {
            $item->value = (int) $item->value; // ensure integer
            return $item;
        });
    
    return response()->json($data);
});

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API works!']);
});
