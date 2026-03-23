<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 購入パターン判定用: contract_items に billing_type スナップショットを追加
     * 01_purchase_patterns.md に準拠（monthly / one_time）
     */
    public function up(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->string('billing_type', 20)->default('one_time')->after('subtotal')
                ->comment('決済タイプスナップショット: monthly / one_time');
        });
    }

    public function down(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropColumn('billing_type');
        });
    }
};
