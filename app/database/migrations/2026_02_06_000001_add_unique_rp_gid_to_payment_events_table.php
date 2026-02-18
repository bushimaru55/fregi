<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ROBOT PAYMENT 通知の冪等性を担保するため、(event_type, rp_gid) の一意制約を追加。
     * 同一通知の重複INSERTを防止する（04_data_model_and_logging 4.2.2）。
     */
    public function up(): void
    {
        Schema::table('payment_events', function (Blueprint $table) {
            $table->unique(['event_type', 'rp_gid'], 'payment_events_event_type_rp_gid_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_events', function (Blueprint $table) {
            $table->dropUnique('payment_events_event_type_rp_gid_unique');
        });
    }
};
