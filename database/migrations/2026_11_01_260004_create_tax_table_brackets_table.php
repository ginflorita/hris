<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_table_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_table_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->decimal('min_income', 12, 2);
            $table->decimal('max_income', 12, 2)->nullable();
            $table->decimal('base_tax', 12, 2);
            $table->decimal('excess_rate_percent', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_table_brackets');
    }
};
