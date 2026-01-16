<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * site_settingsテーブルにプレーンテキスト用カラムを追加
     * - value: サニタイズ済みHTML（RichEditor出力）
     * - value_text: タグ除去テキスト（検索・一覧表示用）
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->longText('value_text')->nullable()->after('value')->comment('プレーンテキスト版（検索・一覧用）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('value_text');
        });
    }
};
