<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['purchase', 'sale']); // type of transaction
            $table->integer('quantity'); // +ve for purchase, -ve for sale
            $table->decimal('rate', 10, 2)->nullable(); // unit price if needed
            $table->decimal('total', 12, 2)->nullable(); // total value = qty * rate
            $table->foreignId('reference_id')->nullable(); // links to sales or purchase ID
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
