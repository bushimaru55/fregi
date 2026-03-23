<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 申し込み＝選択された商品（複数可能）の設計に合わせ、
     * contracts.contract_plan_id を nullable にする（代表プラン用・未設定可）。
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['contract_plan_id']);
            $table->unsignedBigInteger('contract_plan_id')->nullable()->change();
            $table->foreign('contract_plan_id')->references('id')->on('contract_plans')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['contract_plan_id']);
            $table->unsignedBigInteger('contract_plan_id')->nullable(false)->change();
            $table->foreign('contract_plan_id')->references('id')->on('contract_plans')->onDelete('restrict');
        });
    }
};
