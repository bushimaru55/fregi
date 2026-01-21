<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 1契約 = 複数決済に対応するため、payment_idをnullableに変更
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // payment_idをnullableに変更（複数決済に対応）
            $table->foreignId('payment_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 注意: 既存データにNULLが含まれている場合はエラーになる可能性がある
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable(false)->change();
        });
    }
};