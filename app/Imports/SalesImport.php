<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockLedger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SalesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            // Skip empty rows
            if (!$row['product_name'] || !$row['qty']) {
                continue;
            }

            // ❌ Only existing products
            $product = Product::where('name', trim($row['product_name']))->first();
            if (!$product) {
                // Could log missing product here
                continue;
            }

            $previousBalance = $product->quantity;

            // --- Parse Date ---
            $dateValue = $row['date'] ?? null;
            if ($dateValue) {
                if (is_numeric($dateValue)) {
                    $parsedDate = Carbon::instance(
                        ExcelDate::excelToDateTimeObject($dateValue)
                    );
                } else {
                    $parsedDate = Carbon::parse($dateValue);
                }
            } else {
                $parsedDate = now();
            }

            // Calculate total
            $calculatedTotal = floatval($row['qty']) * floatval($row['selling_price']);

            // Create Sale record
            $sale = Sale::create([
                'product_id'    => $product->id,
                'quantity'      => $row['qty'],
                'selling_price' => $row['selling_price'],
                'total'         => $calculatedTotal,
                'sales_date'     => $parsedDate->format('Y-m-d'),
            ]);

            // Update product: subtract quantity & update selling_price
            $product->quantity = $previousBalance - $row['qty'];
            $product->selling_price = $row['selling_price'];
            $product->save();

            // Add ledger entry
            StockLedger::create([
                'product_id'   => $product->id,
                'type'         => 'sale',
                'date'         => $sale->sales_date,
                'quantity'     => -$row['qty'], // negative for sale
                'balance'      => $product->quantity,
                'rate'         => $row['selling_price'],
                'total'        => $calculatedTotal,
                'reference_id' => $sale->id,
            ]);
        }
    }
}
