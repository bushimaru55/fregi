<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_form_urls', function (Blueprint $table) {
            // 月額決済（自動課金）は CAPTURE のみ対応（RP仕様制限）
            $table->string('job_type', 10)->nullable()->default('CAPTURE')->after('is_active')
                ->comment('決済処理方法: CAPTURE=仮実同時売上（固定）');
        });
    }

    public function down(): void
    {
        Schema::table('contract_form_urls', function (Blueprint $table) {
            $table->dropColumn('job_type');
        });
    }
};
