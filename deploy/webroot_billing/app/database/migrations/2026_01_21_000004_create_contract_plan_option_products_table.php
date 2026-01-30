<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * オプション商品とベース商品（契約プラン）の多対多の関係を管理する中間テーブル
     */
    public function up(): void
    {
        Schema::create('contract_plan_option_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_plan_id')
                ->constrained('contract_plans')
                ->onDelete('cascade')
                ->comment('ベース商品（契約プラン）ID');
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade')
                ->comment('オプション商品ID');
            $table->timestamps();
            
            // 同じ組み合わせの重複を防ぐ
            $table->unique(['contract_plan_id', 'product_id'], 'contract_plan_option_product_unique');
            
            // インデックス
            $table->index('contract_plan_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_plan_option_products');
    }
};
