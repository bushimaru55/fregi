<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 申し込み一覧の初期ステータスを全て「申し込み」(applied) に統一する。
     * 以降は管理画面から手作業でステータスを更新する。
     */
    public function up(): void
    {
        DB::table('contracts')->update(['status' => 'applied']);
    }

    public function down(): void
    {
        // ロールバック時は何もしない
    }
};
