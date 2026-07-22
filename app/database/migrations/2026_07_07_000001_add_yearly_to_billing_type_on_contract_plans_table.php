<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'yearly' (年額課金) to contract_plans.billing_type enum.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contract_plans MODIFY COLUMN billing_type ENUM('one_time', 'monthly', 'yearly') NOT NULL DEFAULT 'one_time' COMMENT '決済タイプ（one_time: 一回限り, monthly: 月額課金, yearly: 年額課金）'");
        } else {
            Schema::table('contract_plans', function (Blueprint $table) {
                $table->string('billing_type', 20)->default('one_time')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     * yearly を含むプランが存在しないことを事前に確認すること。
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contract_plans MODIFY COLUMN billing_type ENUM('one_time', 'monthly') NOT NULL DEFAULT 'one_time' COMMENT '決済タイプ（one_time: 一回限り, monthly: 月額課金）'");
        } else {
            Schema::table('contract_plans', function (Blueprint $table) {
                $table->string('billing_type', 20)->default('one_time')->change();
            });
        }
    }
};
