<?php

use App\Support\TableUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        TableUpgrade::create('product', 'products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('image_id')->nullable()->constrained('image')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brand')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('category')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true)->index();   // archived products are hidden but keep their history
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
