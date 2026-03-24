<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_form_urls', function (Blueprint $table) {
            // 決済処理方法: CAPTURE（仮実同時売上）/ AUTH（仮売上のみ）
            // null はデフォルト（.env の ROBOTPAYMENT_JOB_TYPE に従う）
            $table->string('job_type', 10)->nullable()->default(null)->after('is_active')
                ->comment('決済処理方法: CAPTURE=仮実同時売上 / AUTH=仮売上のみ / null=サイト設定に従う');
        });
    }

    public function down(): void
    {
        Schema::table('contract_form_urls', function (Blueprint $table) {
            $table->dropColumn('job_type');
        });
    }
};
