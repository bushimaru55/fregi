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
        Schema::table('contract_form_urls', function (Blueprint $table) {
            // tokenカラムをnullableに変更
            $table->string('token', 64)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_form_urls', function (Blueprint $table) {
            // 元に戻す（nullableを解除）
            $table->string('token', 64)->nullable(false)->change();
        });
    }
};
