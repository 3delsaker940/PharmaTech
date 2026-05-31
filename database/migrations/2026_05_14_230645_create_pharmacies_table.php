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
            $table->foreignId('user_id')->constrained('users','id')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('city_id')->constrained();
            $table->string('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('license_number')->nullable();
            $table->enum('status', ['active', 'suspended', 'archived', 'pending'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
    }
};
