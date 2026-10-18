<?php

use App\Support\TableUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        TableUpgrade::create('void', 'voids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->unique()->constrained('sale')->cascadeOnDelete();   // one void per sale
            $table->foreignId('voided_by')->constrained('user');
            $table->string('void_reason', 255);
            $table->timestamp('voided_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('void');
    }
};
