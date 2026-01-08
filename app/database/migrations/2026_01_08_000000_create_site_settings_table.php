<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('設定キー');
            $table->text('value')->nullable()->comment('設定値');
            $table->text('description')->nullable()->comment('設定の説明');
            $table->timestamps();
            
            $table->index('key');
        });
        
        // 初期データ：利用規約を挿入
        DB::table('site_settings')->insert([
            'key' => 'terms_of_service',
            'value' => '利用規約の内容をここに入力してください。',
            'description' => '利用規約の本文',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
