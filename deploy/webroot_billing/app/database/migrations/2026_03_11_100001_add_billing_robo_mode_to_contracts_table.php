<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Billing-Robo 連携の決済モード。
     * api3_standard: API1→2→3（請求先にスケジュールを寄せ、請求情報登録後にロボ側で請求書発行・決済）
     * api5_immediate: API1→2→5（即時決済）。既存の申込画面からのカード決済フロー用。
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('billing_robo_mode', 20)
                ->nullable()
                ->after('billing_individual_code')
                ->comment('Billing-Robo決済モード: api3_standard | api5_immediate');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('billing_robo_mode');
        });
    }
};
