<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * カード番号の末尾4桁を保存するカラムを追加
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('card_last4', 4)->nullable()->after('notes')->comment('カード番号下4桁（表示用・特定不可）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('card_last4');
        });
    }
};
