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
        Schema::table('fregi_configs', function (Blueprint $table) {
            // shop_id → shopid (F-REGI標準: SHOPID)
            $table->renameColumn('shop_id', 'shopid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fregi_configs', function (Blueprint $table) {
            $table->renameColumn('shopid', 'shop_id');
        });
    }
};
