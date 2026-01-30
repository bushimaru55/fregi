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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_plan_id')->constrained('contract_plans')->comment('契約プランID');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->comment('決済ID（決済完了後に設定）');
            
            // ステータス
            $table->enum('status', [
                'draft',            // 下書き（入力中）
                'pending_payment',  // 決済待ち
                'active',           // 有効
                'canceled',         // キャンセル
                'expired'           // 期限切れ
            ])->default('draft')->comment('契約ステータス');
            
            // 申込企業情報
            $table->string('company_name')->comment('会社名');
            $table->string('company_name_kana')->nullable()->comment('会社名（フリガナ）');
            $table->string('department')->nullable()->comment('部署名');
            $table->string('position')->nullable()->comment('役職');
            $table->string('contact_name')->comment('担当者名');
            $table->string('contact_name_kana')->nullable()->comment('担当者名（フリガナ）');
            $table->string('email')->comment('メールアドレス');
            $table->string('phone')->comment('電話番号');
            $table->string('postal_code')->nullable()->comment('郵便番号');
            $table->string('prefecture')->nullable()->comment('都道府県');
            $table->string('city')->nullable()->comment('市区町村');
            $table->string('address_line1')->nullable()->comment('番地');
            $table->string('address_line2')->nullable()->comment('建物名');
            
            // 契約内容
            $table->date('desired_start_date')->comment('利用開始希望日');
            $table->date('actual_start_date')->nullable()->comment('実際の利用開始日');
            $table->date('end_date')->nullable()->comment('利用終了日');
            
            // メモ・備考
            $table->text('notes')->nullable()->comment('備考');
            
            $table->timestamps();
            
            // インデックス
            $table->index(['status', 'created_at']);
            $table->index('email');
            $table->index('desired_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
