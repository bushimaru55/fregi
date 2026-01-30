<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * カード情報の保存を廃止し、関連カラムを削除する。
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'card_pan_encrypted',
                'card_last4',
                'card_expiry_month',
                'card_expiry_year',
                'card_name',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('card_last4', 4)->nullable()->after('notes');
            $table->string('card_expiry_month', 2)->nullable()->after('card_last4');
            $table->string('card_expiry_year', 4)->nullable()->after('card_expiry_month');
            $table->string('card_name', 45)->nullable()->after('card_expiry_year');
            $table->text('card_pan_encrypted')->nullable()->after('card_name');
        });
    }
};
