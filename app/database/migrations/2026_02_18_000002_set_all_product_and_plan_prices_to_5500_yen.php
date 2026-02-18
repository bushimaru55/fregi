<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 契約プラン・商品の価格を全て 5500 円に統一する。
     */
    public function up(): void
    {
        DB::table('contract_plans')->update(['price' => 5500]);
        DB::table('products')->update(['unit_price' => 5500]);
    }

    /**
     * ロールバック時は元の価格を復元できないため、何もしない。
     */
    public function down(): void
    {
        //
    }
};
