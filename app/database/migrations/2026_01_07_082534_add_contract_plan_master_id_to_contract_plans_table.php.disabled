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
            $table->foreignId('contract_plan_master_id')
                ->nullable()
                ->after('id')
                ->constrained('contract_plan_masters')
                ->onDelete('restrict')
                ->comment('契約プランマスターID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_plans', function (Blueprint $table) {
            $table->dropForeign(['contract_plan_master_id']);
            $table->dropColumn('contract_plan_master_id');
        });
    }
};
