<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 請求管理ロボ API 3（請求情報登録）で送る tax_category / tax を商品マスタで保持する。
     * 0:外税 1:内税 2:対象外 3:非課税 / tax: 5, 8, 10 等
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedTinyInteger('tax_category')->default(1)->after('unit_price')
                ->comment('税区分 0:外税 1:内税 2:対象外 3:非課税（請求管理ロボAPI用）');
            $table->unsignedTinyInteger('tax')->default(10)->after('tax_category')
                ->comment('消費税率 5/8/10（tax_category=0,1時。請求管理ロボAPI用）');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['tax_category', 'tax']);
        });
    }
};
