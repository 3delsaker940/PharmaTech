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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('stock_batches')
                ->nullOnDelete();
            $table->enum('movement_type', [
                'purchase_in',
                'sale_out',
                'sale_return_in',
                'customer_return_in',
                'supplier_return_out',
                'adjustment_in',
                'adjustment_out',
                'expiry_out',
            ]);
            $table->integer('quantity_change');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['pharmacy_id', 'product_id']);
            $table->index(['pharmacy_id', 'movement_type']);
            $table->index(['pharmacy_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
