<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * usage_url_domain を nullable に変更（管理画面で空欄更新時にエラーにならないようにする）
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('usage_url_domain')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('usage_url_domain')->nullable(false)->change();
        });
    }
};
