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
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade')->comment('契約ID');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->string('product_name')->comment('商品名（スナップショット）');
            $table->string('product_code')->comment('商品コード（スナップショット）');
            $table->unsignedInteger('quantity')->default(1)->comment('数量');
            $table->unsignedInteger('unit_price')->comment('単価（スナップショット）');
            $table->unsignedInteger('subtotal')->comment('小計');
            $table->json('product_attributes')->nullable()->comment('商品属性（スナップショット）');
            $table->timestamps();
            
            $table->index('contract_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
