<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ROBOT PAYMENT 通知の原文保存・冪等用カラム追加
     */
    public function up(): void
    {
        Schema::table('payment_events', function (Blueprint $table) {
            $table->text('raw_query')->nullable()->after('event_type')->comment('通知クエリ文字列の原文');
            $table->string('rp_gid', 64)->nullable()->after('raw_query')->comment('RP 決済番号（冪等キー）');
            $table->string('rp_acid', 64)->nullable()->after('rp_gid')->comment('RP 自動課金番号');
            $table->index('rp_gid');
        });
    }

    public function down(): void
    {
        Schema::table('payment_events', function (Blueprint $table) {
            $table->dropIndex(['rp_gid']);
            $table->dropColumn(['raw_query', 'rp_gid', 'rp_acid']);
        });
    }
};
