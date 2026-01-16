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
        Schema::create('contract_form_urls', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->nullable()->unique()->comment('閲覧用トークン（申込フォームURLの場合はNULL）');
            $table->text('url')->comment('生成されたURL');
            $table->json('plan_ids')->comment('選択された契約プランIDの配列');
            $table->string('name')->nullable()->comment('URL名（管理用メモ）');
            $table->timestamp('expires_at')->comment('有効期限（申込フォームURLの場合は長期間有効）');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
            
            $table->index(['is_active', 'expires_at']);
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_form_urls');
    }
};
