<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * F-REGI は過去機能のため fregi_configs / fregi_config_versions を削除する。
     * 請求管理ロボ API 仕様に DB を準拠させる一環。
     */
    public function up(): void
    {
        Schema::dropIfExists('fregi_config_versions');
        Schema::dropIfExists('fregi_configs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 復元は行わない（fregi は不要のため）
    }
};
