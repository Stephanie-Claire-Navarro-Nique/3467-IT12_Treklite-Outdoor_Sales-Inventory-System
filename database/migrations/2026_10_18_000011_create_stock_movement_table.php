<?php

use App\Support\TableUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        TableUpgrade::create('stock_movement', 'stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variant')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('user');
            $table->string('type', 20);                               // receive, count, sale, void
            $table->integer('quantity_before')->nullable();
            $table->integer('quantity_change');
            $table->integer('quantity_after');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement');
    }
};
