<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 請求管理ロボ API 仕様に準拠: 決済情報番号・決済情報コードを保持する。
     * API 1 の payment レスポンスの number / code、クレジットカード登録(API 2)で使用する。
     * 店舗オーダー番号(cod)は既存の merchant_order_no で保持。
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('billing_payment_method_number')->nullable()->after('merchant_order_no')->comment('請求管理ロボ 決済情報番号');
            $table->string('billing_payment_method_code', 20)->nullable()->after('billing_payment_method_number')->comment('請求管理ロボ 決済情報コード');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['billing_payment_method_number', 'billing_payment_method_code']);
        });
    }
};
