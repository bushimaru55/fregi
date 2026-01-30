<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * contracts.status を enum から string(50) に変更し、contract_statuses マスターの code を参照可能にする。
     * 既存データはそのまま（code がマスターに存在する前提）。
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contracts MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'applied' COMMENT '契約状態（contract_statuses.code を参照）'");
        } else {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('status', 50)->default('applied')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('draft', 'pending_payment', 'submitted', 'active', 'canceled', 'expired') NOT NULL DEFAULT 'draft' COMMENT '契約ステータス'");
        } else {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('status', 50)->default('applied')->change();
            });
        }
    }
};
