<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 契約ステータスマスター（管理画面で追加・変更可能。申込レコードは code で参照するためマスター変更の影響を受けない）
     */
    public function up(): void
    {
        Schema::create('contract_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('ステータスコード（契約レコードの status に保存）');
            $table->string('name', 100)->comment('表示名');
            $table->unsignedInteger('display_order')->default(0)->comment('表示順');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_statuses');
    }
};
