<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 契約状態ステータスマスターの初期データ（Fレジ決済廃止に伴い契約状態を管理）
     * 申し込み / 無料体験中 / 製品版提供 / 停止中
     */
    public function up(): void
    {
        $statuses = [
            ['code' => 'applied', 'name' => '申し込み', 'display_order' => 10, 'is_active' => true],
            ['code' => 'trial', 'name' => '無料体験中', 'display_order' => 20, 'is_active' => true],
            ['code' => 'product', 'name' => '製品版提供', 'display_order' => 30, 'is_active' => true],
            ['code' => 'suspended', 'name' => '停止中', 'display_order' => 40, 'is_active' => true],
        ];
        $now = now();
        foreach ($statuses as $s) {
            DB::table('contract_statuses')->insert([
                'code' => $s['code'],
                'name' => $s['name'],
                'display_order' => $s['display_order'],
                'is_active' => $s['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('contract_statuses')->truncate();
    }
};
