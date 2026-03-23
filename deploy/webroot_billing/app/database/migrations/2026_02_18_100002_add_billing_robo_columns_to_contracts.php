<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 請求管理ロボ API 仕様に準拠: 契約に請求先・請求先部署の識別子を保持する。
     * API 1 請求先登録更新のレスポンスで返却される billing.code / individual.number or code を保存する。
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('billing_code', 20)->nullable()->after('customer_id')->comment('請求管理ロボ 請求先コード');
            $table->unsignedBigInteger('billing_individual_number')->nullable()->after('billing_code')->comment('請求管理ロボ 請求先部署番号');
            $table->string('billing_individual_code', 20)->nullable()->after('billing_individual_number')->comment('請求管理ロボ 請求先部署コード');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['billing_code', 'billing_individual_number', 'billing_individual_code']);
        });
    }
};
