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
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade')->comment('決済ID');
            $table->string('event_type', 50)->comment('イベントタイプ: request, redirect, notify, return 等');
            $table->json('payload')->nullable()->comment('ペイロード（JSON、マスク必須）');
            $table->timestamp('created_at')->comment('イベント発生日時');
            
            $table->index(['payment_id', 'event_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
