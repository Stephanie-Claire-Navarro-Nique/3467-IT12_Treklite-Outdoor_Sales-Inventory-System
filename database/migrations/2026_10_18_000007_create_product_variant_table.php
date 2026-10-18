<?php

use App\Support\TableUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        TableUpgrade::create('product_variant', 'product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('size_id')->nullable()->constrained('size')->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('color')->nullOnDelete();
            $table->foreignId('product_id')->constrained('product')->cascadeOnDelete();
            $table->decimal('price', 10, 2)->nullable();            // null = use the product price
            $table->integer('initial_stock')->default(0);
            $table->integer('quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant');
    }
};
