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
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('usage_url_domain')->after('address_line2')->comment('ご利用URL・ドメイン');
            $table->boolean('import_from_trial')->default(false)->after('usage_url_domain')->comment('体験版からのインポートを希望する');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['usage_url_domain', 'import_from_trial']);
        });
    }
};
