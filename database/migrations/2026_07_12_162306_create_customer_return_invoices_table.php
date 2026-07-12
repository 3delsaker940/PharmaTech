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
        Schema::create('customer_return_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')->constrained('pharmacies')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('original_sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_total', 12, 2)->default(0.00);
            $table->decimal('discount_total', 12, 2)->default(0.00);
            $table->decimal('refund_total', 12, 2);
            $table->enum('refund_method', ['cash', 'credit'])->default('cash');
            $table->enum('status', ['completed', 'cancelled', 'pending'])->default('completed');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['pharmacy_id', 'invoice_number']);

            $table->index(['pharmacy_id', 'status']);
            $table->index(['pharmacy_id', 'invoice_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_return_invoices');
    }
};
