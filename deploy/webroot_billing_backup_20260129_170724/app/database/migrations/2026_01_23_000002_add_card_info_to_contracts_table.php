<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * カード番号の下4桁・有効期限・名義を契約に保存する。
     * フルPAN・セキュリティコード(scode)は一切保存しない（漏洩時にカード特定・決済流用を防ぐ）。
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('card_last4', 4)->nullable()->after('notes')->comment('カード番号下4桁（表示用・特定不可）');
            $table->string('card_expiry_month', 2)->nullable()->after('card_last4')->comment('有効期限月');
            $table->string('card_expiry_year', 4)->nullable()->after('card_expiry_month')->comment('有効期限年');
            $table->string('card_name', 45)->nullable()->after('card_expiry_year')->comment('カード名義');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['card_last4', 'card_expiry_month', 'card_expiry_year', 'card_name']);
        });
    }
};
