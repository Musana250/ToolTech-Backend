<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'buying_price',
        'total',
        'purchase_date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    protected static function booted()
    {
        static::created(function ($purchase) {
            $productId = $purchase->product_id;
    
            // Always get the latest balance for this product
            $product = Product::find($productId);
            $previousBalance = $product->quantity ?? 0;
            // Add the purchased quantity
            $newBalance = $previousBalance + $purchase->quantity;
            $product->save();
    
            // ✅ Use the purchase_date (not created_at)
            \App\Models\StockLedger::create([
                'product_id' => $productId,
                'type' => 'purchase',
                'date' => $purchase->purchase_date, // Correct date from user input
                'quantity' => $purchase->quantity,
                'balance' => $newBalance,
                'rate' => $purchase->buying_price,
                'total' => $purchase->quantity * $purchase->buying_price,
                'reference_id' => $purchase->id,
            ]);
        });
    }

}
