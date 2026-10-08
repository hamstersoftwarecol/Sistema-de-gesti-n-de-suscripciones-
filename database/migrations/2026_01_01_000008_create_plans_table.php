<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 14, 2);
            $table->string('billing_cycle', 20)->default('monthly'); // daily, weekly, monthly, quarterly, semiannual, yearly
            $table->unsignedSmallInteger('interval_count')->default(1);
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->decimal('setup_fee', 14, 2)->default(0);
            $table->text('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
