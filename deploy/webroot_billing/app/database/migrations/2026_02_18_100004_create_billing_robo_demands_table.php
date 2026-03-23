<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 請求管理ロボ API 仕様に準拠: 請求情報番号・請求情報コードを保持する。
     * API 3 請求情報登録更新のレスポンスで返却される demand.number / demand.code を保存する。
     * 1契約に対して複数請求情報（例: 初回分・月額分）があり得るため、別テーブルで管理する。
     */
    public function up(): void
    {
        Schema::create('billing_robo_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->unsignedBigInteger('demand_number')->nullable()->comment('請求管理ロボ 請求情報番号');
            $table->string('demand_code', 20)->nullable()->comment('請求管理ロボ 請求情報コード');
            $table->string('demand_type', 20)->nullable()->comment('種別: initial / recurring 等');
            $table->timestamps();

            $table->index(['contract_id']);
            $table->index('demand_number');
            $table->index('demand_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_robo_demands');
    }
};
