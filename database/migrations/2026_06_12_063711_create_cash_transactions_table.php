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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_box_id')->constrained()->cascadeOnDelete();
            $table->enum('transaction_type', [
                'purchase_out',
                'sale_in',
                'customer_return_out',
                'supplier_return_in',
                'customer_debt_payment_in',
                'supplier_debt_payment_out',
                'manual_in',
                'manual_out',
            ]);
            $table->decimal('amount', 12, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->dateTime('transaction_time');
            $table->decimal('balance_after', 12, 2)->default(0.00);
            $table->timestamps();

            $table->index(['cash_box_id', 'transaction_type']);
            $table->index(['cash_box_id', 'transaction_time']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
