<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockLedger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PurchasesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            // Skip empty rows
            if (!$row['product_name'] || !$row['qty']) {
                continue;
            }

            // Ensure product exists
            $product = Product::firstOrCreate(
                ['name' => trim($row['product_name'])],
                ['quantity' => 0, 'buying_price' => $row['unit_price'] ?? 0]
            );

            $previousBalance = $product->quantity;

            // --- DATE FIX ---
            $dateValue = $row['date'] ?? null;

            if ($dateValue) {
                if (is_numeric($dateValue)) {
                    // Convert Excel numeric date (e.g. 45589)
                    $parsedDate = Carbon::instance(
                        ExcelDate::excelToDateTimeObject($dateValue)
                    );
                } else {
                    // Convert normal dates like 2/13/2025
                    $parsedDate = Carbon::parse($dateValue);
                }
            } else {
                $parsedDate = now();
            }

            // Calculate total manually (Excel column ignored)
            $calculatedTotal = floatval($row['qty']) * floatval($row['unit_price']);

            // Create purchase record
            $purchase = Purchase::create([
                'product_id'    => $product->id,
                'quantity'      => $row['qty'],
                'buying_price'  => $row['unit_price'],
                'total'         => $calculatedTotal,
                'purchase_date' => $parsedDate->format('Y-m-d'),
            ]);

            // Update product stock
            $product->quantity = $previousBalance + $row['qty'];
            $product->buying_price = $row['unit_price'];
            $product->save();

            // Stock ledger
            StockLedger::create([
                'product_id'   => $product->id,
                'type'         => 'purchase',
                'date'         => $purchase->purchase_date,
                'quantity'     => $row['qty'],
                'balance'      => $product->quantity,
                'rate'         => $row['unit_price'],
                'total'        => $calculatedTotal,
                'reference_id' => $purchase->id,
            ]);
        }
    }
}
