<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * フルPAN（16桁）を暗号化して保存するカラムを追加。
     * 運用でフルPANが必要な場合、Laravel の encrypted キャストで復号して利用する。
     * セキュリティコード（scode）は PCI-DSS により保存しない。
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->text('card_pan_encrypted')->nullable()->after('card_name')->comment('フルPAN（暗号化。APP_KEYで復号）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('card_pan_encrypted');
        });
    }
};
