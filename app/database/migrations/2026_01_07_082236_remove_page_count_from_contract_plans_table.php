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
        Schema::table('contract_plans', function (Blueprint $table) {
            // UNIQUE制約を削除してからカラムを削除
            $table->dropUnique('contract_plans_page_count_unique');
            $table->dropColumn('page_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_plans', function (Blueprint $table) {
            $table->unsignedInteger('page_count')->comment('学習ページ数')->after('name');
            $table->unique('page_count', 'contract_plans_page_count_unique');
        });
    }
};
