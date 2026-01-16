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
        Schema::create('fregi_config_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('fregi_configs')->onDelete('cascade')->comment('F-REGI設定ID');
            $table->unsignedInteger('version_no')->comment('バージョン番号');
            $table->json('snapshot_json')->comment('変更後のスナップショット（JSON）');
            $table->timestamp('changed_at')->comment('変更日時');
            $table->string('changed_by')->nullable()->comment('変更者');
            $table->text('change_reason')->nullable()->comment('変更理由');
            $table->timestamps();
            
            $table->index(['config_id', 'version_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fregi_config_versions');
    }
};
