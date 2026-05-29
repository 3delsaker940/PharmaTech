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
        Schema::create('product_medical_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('indications')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('overdose')->nullable();
            $table->text('pregnancy_safety')->nullable();
            $table->text('lactation_safety')->nullable();
            $table->text('warnings')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('drug_interactions')->nullable();
            $table->text('dose_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_medical_info');
    }
};
