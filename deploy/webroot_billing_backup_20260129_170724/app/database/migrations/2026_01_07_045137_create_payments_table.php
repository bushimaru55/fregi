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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('会社ID');
            $table->unsignedBigInteger('contract_id')->nullable()->comment('契約ID（既存テーブル参照前提）');
            $table->string('order_no')->comment('自社採番オーダー番号');
            $table->string('fregi_receipt_no')->nullable()->comment('F-REGI発行番号');
            $table->string('fregi_slip_no')->nullable()->comment('F-REGI伝票番号');
            $table->unsignedInteger('amount')->comment('金額（税込）');
            $table->string('currency', 3)->default('JPY')->comment('通貨コード');
            $table->string('payment_method', 20)->default('card')->comment('支払方法');
            $table->enum('status', [
                'created',
                'redirect_issued',
                'waiting_notify',
                'paid',
                'failed',
                'canceled',
                'expired'
            ])->default('created')->comment('ステータス');
            $table->timestamp('requested_at')->nullable()->comment('請求日時');
            $table->timestamp('notified_at')->nullable()->comment('通知受領日時');
            $table->timestamp('completed_at')->nullable()->comment('完了日時');
            $table->text('failure_reason')->nullable()->comment('失敗理由');
            $table->json('raw_notify_payload')->nullable()->comment('通知ペイロード（JSON、アクセス制御必要）');
            $table->timestamps();
            
            // ユニーク制約
            $table->unique(['company_id', 'order_no']);
            $table->unique(['fregi_receipt_no', 'fregi_slip_no'], 'payments_fregi_unique');
            
            // インデックス
            $table->index(['company_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
