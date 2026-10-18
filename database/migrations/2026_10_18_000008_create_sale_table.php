<?php

use App\Support\TableUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        TableUpgrade::create('sale', 'sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained('user');
            $table->string('customer_name')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_contact', 50)->nullable();
            $table->string('payment_method', 20)->default('cash');   // cash, gcash, bank_transfer
            $table->string('payment_reference', 100)->nullable()->unique();
            $table->foreignId('payment_proof_id')->nullable()->constrained('image')->nullOnDelete();
            $table->string('payment_proof_hash', 64)->nullable()->index();   // stops the same payment photo being used twice
            $table->decimal('amount_tendered', 10, 2)->default(0);
            $table->decimal('change_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamp('sold_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale');
    }
};
