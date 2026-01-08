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
        Schema::create('fregi_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('会社ID（既存テーブル参照前提）');
            $table->string('environment', 10)->comment('環境: test/prod');
            $table->string('shop_id')->comment('F-REGI SHOPID');
            $table->text('connect_password_enc')->comment('接続パスワード（暗号化済みBase64）');
            $table->string('notify_url')->comment('通知URL');
            $table->string('return_url_success')->comment('成功時戻りURL');
            $table->string('return_url_cancel')->comment('キャンセル時戻りURL');
            $table->boolean('is_active')->default(false)->comment('アクティブフラグ（同一company/environmentで1件のみtrue）');
            $table->string('updated_by')->nullable()->comment('最終更新者');
            $table->timestamps();
            
            // インデックス: company_id, environment, is_active の組み合わせで検索
            $table->index(['company_id', 'environment', 'is_active']);
            
            // 注意: (company_id, environment, is_active=true) の一意性はアプリ側で保証
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fregi_configs');
    }
};
