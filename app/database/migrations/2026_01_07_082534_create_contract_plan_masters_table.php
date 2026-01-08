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
        Schema::create('contract_plan_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('マスター名');
            $table->text('description')->nullable()->comment('マスター説明');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->unsignedInteger('display_order')->default(0)->comment('表示順');
            $table->timestamps();
            
            $table->index(['is_active', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_plan_masters');
    }
};
