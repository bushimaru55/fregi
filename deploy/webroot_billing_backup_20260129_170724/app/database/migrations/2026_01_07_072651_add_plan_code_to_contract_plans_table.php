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
        // まずカラムを追加（UNIQUE制約なし）
        Schema::table('contract_plans', function (Blueprint $table) {
            $table->string('plan_code', 50)->nullable()->after('id')->comment('プランコード（一意識別子）');
        });

        // 既存データにplan_codeを設定
        DB::statement("UPDATE contract_plans SET plan_code = CONCAT('PLAN-', LPAD(page_count, 3, '0')) WHERE plan_code IS NULL");

        // UNIQUE制約を追加
        Schema::table('contract_plans', function (Blueprint $table) {
            $table->string('plan_code', 50)->nullable(false)->change();
            $table->unique('plan_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_plans', function (Blueprint $table) {
            $table->dropUnique(['plan_code']);
            $table->dropColumn('plan_code');
        });
    }
};
