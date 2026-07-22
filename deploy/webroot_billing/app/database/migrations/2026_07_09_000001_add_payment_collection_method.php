<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 回収方法（card: クレジット / bank_transfer: 請求書払い・銀行振込）を追加する。
     * 既存データは default 'card' となり、既存のクレジット決済フローに影響しない。
     */
    public function up(): void
    {
        if (!Schema::hasColumn('contract_plans', 'payment_collection_method')) {
            Schema::table('contract_plans', function (Blueprint $table) {
                $table->string('payment_collection_method', 20)
                    ->default('card')
                    ->after('billing_type')
                    ->comment('回収方法（card: クレジット, bank_transfer: 請求書払い・銀行振込）');
            });
        }

        if (!Schema::hasColumn('contracts', 'payment_collection_method')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('payment_collection_method', 20)
                    ->default('card')
                    ->after('billing_robo_mode')
                    ->comment('申込時点の回収方法（card / bank_transfer）');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('contracts', 'payment_collection_method')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('payment_collection_method');
            });
        }
        if (Schema::hasColumn('contract_plans', 'payment_collection_method')) {
            Schema::table('contract_plans', function (Blueprint $table) {
                $table->dropColumn('payment_collection_method');
            });
        }
    }
};
