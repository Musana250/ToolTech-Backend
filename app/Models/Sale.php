<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'selling_price',
        'total',
        'sales_date',
    ];
    protected $casts = [
        'total' => 'float',
        'sales_date' => 'datetime', // ensure sales_date exists in DB
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    protected static function booted()
    {
        static::created(function ($sale) {
            $productId = $sale->product_id;
    
            // Get the latest balance for this product
            $product = Product::find($productId);
            $previousBalance = $product->quantity ?? 0;
            // Subtract sale quantity
            $newBalance = $previousBalance - $sale->quantity;
            $product->save();
    
            \App\Models\StockLedger::create([
                'product_id' => $productId,
                'type' => 'sale',
                'date' => $sale->sales_date, // ✅ Correct date from user input
                'quantity' => -$sale->quantity,
                'balance' => $newBalance,
                'rate' => $sale->selling_price,
                'total' => $sale->quantity * $sale->selling_price,
                'reference_id' => $sale->id,
            ]);
        });
    }

    
}

