<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 決済下限額（ER017 対策）のため、登録済みの契約プラン・商品の価格を全て 100 円に統一する。
     */
    public function up(): void
    {
        DB::table('contract_plans')->update(['price' => 100]);
        DB::table('products')->update(['unit_price' => 100]);
    }

    /**
     * ロールバック時は元の価格を復元できないため、何もしない。
     */
    public function down(): void
    {
        // 一括で元の値に戻す情報を持っていないため、ロールバックは行わない。
    }
};
