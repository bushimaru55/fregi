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
            $table->enum('billing_type', ['one_time', 'monthly'])
                ->default('one_time')
                ->after('price')
                ->comment('決済タイプ（one_time: 一回限り, monthly: 月額課金）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_plans', function (Blueprint $table) {
            $table->dropColumn('billing_type');
        });
    }
};
