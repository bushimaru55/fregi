<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // 商品種別（plan: プラン, option: オプション, addon: 追加商品）
            $table->enum('type', ['plan', 'option', 'addon'])
                ->default('option')
                ->after('unit_price')
                ->comment('商品種別');
            
            // 商品説明
            $table->text('description')->nullable()->after('name')->comment('商品説明');
            
            // 有効フラグ
            $table->boolean('is_active')->default(true)->after('type')->comment('有効フラグ');
            
            // 表示順
            $table->unsignedInteger('display_order')->default(0)->after('is_active')->comment('表示順');
            
            // インデックス
            $table->index(['is_active', 'type', 'display_order'], 'products_is_active_type_display_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_is_active_type_display_order_index');
            $table->dropColumn(['type', 'description', 'is_active', 'display_order']);
        });
    }
};