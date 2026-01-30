<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'submitted' (申込受付済み) to contracts.status enum.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('draft', 'pending_payment', 'submitted', 'active', 'canceled', 'expired') NOT NULL DEFAULT 'draft' COMMENT '契約ステータス'");
        } else {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('status', 50)->default('draft')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('draft', 'pending_payment', 'active', 'canceled', 'expired') NOT NULL DEFAULT 'draft' COMMENT '契約ステータス'");
        } else {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('status', 50)->default('draft')->change();
            });
        }
    }
};
