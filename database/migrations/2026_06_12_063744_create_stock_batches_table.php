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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('batch_number');
            $table->date('expiry_date')->nullable();
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->integer('quantity_on_hand')->default(0);
            $table->timestamp('received_at');
            $table->enum('status', ['active', 'expired', 'depleted', 'inactive'])
                ->default('active');
            $table->timestamps();

            $table->index(['pharmacy_id', 'product_id']);
            $table->index(['pharmacy_id', 'status']);
            $table->index(['pharmacy_id', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
