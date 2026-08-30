<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employments', function (Blueprint $table) {
            $table->foreignId('salary_grade_id')->nullable()->after('position_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salary_grade_id');
        });
    }
};
