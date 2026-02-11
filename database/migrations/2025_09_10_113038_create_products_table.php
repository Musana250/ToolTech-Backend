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
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->integer('quantity')->default(0);

        // Buying and selling prices
        $table->decimal('buying_price', 10, 2)->default(0.00);
        $table->decimal('selling_price', 10, 2)->default(0.00);

        // Foreign key to categories
        $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');

        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
