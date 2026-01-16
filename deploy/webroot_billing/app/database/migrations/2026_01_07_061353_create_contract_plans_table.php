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
        Schema::create('contract_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('プラン名');
            $table->unsignedInteger('page_count')->comment('学習ページ数');
            $table->unsignedInteger('price')->comment('料金（税込）');
            $table->text('description')->nullable()->comment('プラン説明');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->unsignedInteger('display_order')->default(0)->comment('表示順');
            $table->timestamps();
            
            $table->unique('page_count');
            $table->index(['is_active', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_plans');
    }
};
