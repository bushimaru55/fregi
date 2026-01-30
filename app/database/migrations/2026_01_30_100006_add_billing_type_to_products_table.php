<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * オプション製品にも1回限り・月額課金の設定を可能にする（管理画面での設定・保存用）。
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('billing_type', 20)
                ->default('one_time')
                ->after('type')
                ->comment('決済タイプ（one_time: 一回限り, monthly: 月額課金）。オプション製品で利用。');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('billing_type');
        });
    }
};
