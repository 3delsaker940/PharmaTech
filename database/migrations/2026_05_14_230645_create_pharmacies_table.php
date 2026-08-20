<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('city_id')->constrained();
            $table->text('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('license_number');
            $table->enum('status', ['active', 'suspended', 'archived', 'pending'])->default('pending');
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('pharmacy_id')
                ->references('id')
                ->on('pharmacies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_id']);
        });
        Schema::dropIfExists('pharmacies');
    }
};
