<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 契約プランマスター廃止に伴い、contract_plans の外部キー・カラムと
     * contract_plan_masters テーブルを削除する。
     */
    public function up(): void
    {
        Schema::table('contract_plans', function (Blueprint $table) {
            $table->dropForeign(['contract_plan_master_id']);
            $table->dropColumn('contract_plan_master_id');
        });

        Schema::dropIfExists('contract_plan_masters');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('contract_plan_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('マスター名');
            $table->text('description')->nullable()->comment('マスター説明');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->unsignedInteger('display_order')->default(0)->comment('表示順');
            $table->timestamps();
            $table->index(['is_active', 'display_order']);
        });

        Schema::table('contract_plans', function (Blueprint $table) {
            $table->foreignId('contract_plan_master_id')
                ->nullable()
                ->after('id')
                ->constrained('contract_plan_masters')
                ->onDelete('restrict')
                ->comment('契約プランマスターID');
        });
    }
};
