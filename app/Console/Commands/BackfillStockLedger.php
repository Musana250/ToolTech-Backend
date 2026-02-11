<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockLedger;

class BackfillStockLedger extends Command
{
    protected $signature = 'ledger:backfill';
    protected $description = 'Backfill old purchases and sales into the stock_ledgers table using their transaction dates';

    public function handle()
{
    $this->info('Starting full recalculation of stock ledger...');

    // Clear ledger to start fresh (optional)
    if ($this->confirm('Do you want to clear the existing stock ledger first?', true)) {
        StockLedger::truncate();
        $this->info('Stock ledger table cleared!');
    }

    // Get all unique products
    $products = Purchase::select('product_id')
        ->distinct()
        ->union(Sale::select('product_id')->distinct())
        ->pluck('product_id');

    foreach ($products as $productId) {
        $this->info("Processing product ID: {$productId}");

        // Collect all purchase + sale records
        $entries = collect();

        // Purchases
        $entries = $entries->merge(
            Purchase::where('product_id', $productId)->get()->map(function ($purchase) {
                return [
                    'type' => 'purchase',
                    'quantity' => $purchase->quantity,
                    'rate' => $purchase->buying_price,
                    'total' => $purchase->quantity * $purchase->buying_price,
                    'reference_id' => $purchase->id,
                    'date' => $purchase->purchase_date,
                ];
            })
        );

        // Sales
        $entries = $entries->merge(
            Sale::where('product_id', $productId)->get()->map(function ($sale) {
                return [
                    'type' => 'sale',
                    'quantity' => -$sale->quantity,
                    'rate' => $sale->selling_price,
                    'total' => $sale->quantity * $sale->selling_price,
                    'reference_id' => $sale->id,
                    'date' => $sale->sales_date,
                ];
            })
        );

        // Sort entries by date
        $entries = $entries->sortBy('date');

        // Calculate running balance
        $balance = 0;
        foreach ($entries as $entry) {
            $balance += $entry['quantity'];

            StockLedger::create([
                'product_id' => $productId,
                'type' => $entry['type'],
                'date' => $entry['date'],
                'quantity' => $entry['quantity'],
                'balance' => $balance,
                'rate' => $entry['rate'],
                'total' => $entry['total'],
                'reference_id' => $entry['reference_id'],
            ]);
        }
    }

    $this->info("✅ Stock Ledger backfill complete!");
}

}
