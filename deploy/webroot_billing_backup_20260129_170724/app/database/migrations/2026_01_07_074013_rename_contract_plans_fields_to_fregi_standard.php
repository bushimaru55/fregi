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
            // plan_code → item (F-REGI標準: ITEM)
            $table->renameColumn('plan_code', 'item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_plans', function (Blueprint $table) {
            $table->renameColumn('item', 'plan_code');
        });
    }
};
