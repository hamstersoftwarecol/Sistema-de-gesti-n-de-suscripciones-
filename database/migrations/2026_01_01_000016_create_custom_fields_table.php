<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 30)->index(); // customer, subscription, product, supplier, invoice, seller
            $table->string('name', 60);
            $table->string('label');
            $table->string('type', 20)->default('text'); // text, textarea, number, date, email, url, select, checkbox
            $table->json('options')->nullable();
            $table->string('placeholder')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('show_in_table')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['entity', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
