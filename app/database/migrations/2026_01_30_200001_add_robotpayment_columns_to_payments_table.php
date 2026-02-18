<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ROBOT PAYMENT 用カラム追加（04_data_model_and_logging.md に準拠）
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider', 32)->nullable()->after('company_id')->comment('決済プロバイダ: robotpayment');
            $table->string('payment_kind', 32)->nullable()->after('provider')->comment('auto_initial / auto_recurring / normal');
            $table->string('merchant_order_no', 64)->nullable()->after('payment_kind')->comment('当社 cod（店舗オーダー番号）');
            $table->string('rp_gid', 64)->nullable()->after('merchant_order_no')->comment('RP 決済番号 gid');
            $table->string('rp_acid', 64)->nullable()->after('rp_gid')->comment('RP 自動課金番号 acid');
            $table->unsignedInteger('amount_initial')->nullable()->after('amount')->comment('初回請求合計（ta）');
            $table->unsignedInteger('amount_recurring')->nullable()->after('amount_initial')->comment('次月以降請求合計');
            $table->timestamp('paid_at')->nullable()->after('completed_at')->comment('決済成立日時');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'provider',
                'payment_kind',
                'merchant_order_no',
                'rp_gid',
                'rp_acid',
                'amount_initial',
                'amount_recurring',
                'paid_at',
            ]);
        });
    }
};
