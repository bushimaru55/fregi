<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fレジ決済廃止に伴い、契約状態マスターを「申し込み」「無料体験中」「製品版提供」「停止中」に統一する。
     * 既存の contract_statuses を差し替え、contracts.status を新コードにマッピングし、デフォルトを applied に変更。
     */
    public function up(): void
    {
        $statuses = [
            ['code' => 'applied', 'name' => '申し込み', 'display_order' => 10, 'is_active' => true],
            ['code' => 'trial', 'name' => '無料体験中', 'display_order' => 20, 'is_active' => true],
            ['code' => 'product', 'name' => '製品版提供', 'display_order' => 30, 'is_active' => true],
            ['code' => 'suspended', 'name' => '停止中', 'display_order' => 40, 'is_active' => true],
        ];

        DB::table('contract_statuses')->truncate();
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

        // 旧コード → 新コード（契約状態）
        DB::table('contracts')->update([
            'status' => DB::raw("CASE status
                WHEN 'draft' THEN 'applied'
                WHEN 'pending_payment' THEN 'applied'
                WHEN 'submitted' THEN 'applied'
                WHEN 'active' THEN 'product'
                WHEN 'canceled' THEN 'suspended'
                WHEN 'expired' THEN 'suspended'
                ELSE 'applied'
            END"),
        ]);

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contracts MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'applied' COMMENT '契約状態（contract_statuses.code を参照）'");
        }
    }

    public function down(): void
    {
        // ロールバック時は旧6ステータスに戻す（必要に応じて実装）
        // ここでは何もしない
    }
};
