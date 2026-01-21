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
        Schema::table('contract_items', function (Blueprint $table) {
            // 既存の外部キー制約を削除
            $table->dropForeign(['product_id']);
            
            // product_idをnullableに変更（ベース商品はcontract_plan_idを使用）
            $table->foreignId('product_id')->nullable()->change();
            
            // 外部キー制約を再追加
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict');
            
            // contract_plan_idを追加（ベース商品用）
            $table->foreignId('contract_plan_id')
                ->nullable()
                ->after('contract_id')
                ->constrained('contract_plans')
                ->onDelete('cascade')
                ->comment('契約プランID（ベース商品の場合のみ）');
            
            // インデックス追加
            $table->index('contract_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropIndex(['contract_plan_id']);
            $table->dropForeign(['contract_plan_id']);
            $table->dropColumn('contract_plan_id');
            
            // 既存の外部キー制約を削除
            $table->dropForeign(['product_id']);
            
            // product_idをNOT NULLに戻す（既存データがある場合は注意）
            $table->foreignId('product_id')->nullable(false)->change();
            
            // 外部キー制約を再追加
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict');
        });
    }
};