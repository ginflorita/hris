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
        Schema::table('users', function (Blueprint $table) {
            // is_system_account: seeded/bootstrap accounts (the initial
            // Superadmin) as opposed to ones created later through the UI.
            // is_protected: cannot be deleted, disabled, or demoted by
            // anyone, including other Superadmins — blueprint §30.
            $table->boolean('is_system_account')->default(false)->after('disabled_at');
            $table->boolean('is_protected')->default(false)->after('is_system_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_system_account', 'is_protected']);
        });
    }
};
