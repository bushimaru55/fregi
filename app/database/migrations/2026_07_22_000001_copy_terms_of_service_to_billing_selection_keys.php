<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 既存の単一利用規約を決済タイプ別キーへコピーする（スキーマ変更なし）。
     * 既存キーは上書きしない（本番編集済み保護）。
     */
    public function up(): void
    {
        $legacy = SiteSetting::where('key', SiteSetting::TERMS_LEGACY_KEY)->first();
        if (!$legacy) {
            return;
        }

        $html = (string) ($legacy->value ?? '');
        $text = (string) ($legacy->value_text ?? SiteSetting::getTextValue(SiteSetting::TERMS_LEGACY_KEY, ''));

        foreach (SiteSetting::billingSelectionLabels() as $selection => $label) {
            $key = SiteSetting::termsKeyFor($selection);
            $exists = SiteSetting::where('key', $key)->exists();
            if ($exists) {
                continue;
            }

            SiteSetting::create([
                'key' => $key,
                'value' => $html,
                'value_text' => $text !== '' ? $text : null,
                'description' => "利用規約の本文（{$label}）",
            ]);
        }
    }

    /**
     * Reverse the migrations.
     * 決済タイプ別キーのみ削除する（旧 terms_of_service は残す）。
     */
    public function down(): void
    {
        $keys = [];
        foreach (array_keys(SiteSetting::billingSelectionLabels()) as $selection) {
            $keys[] = SiteSetting::termsKeyFor($selection);
        }

        SiteSetting::whereIn('key', $keys)->delete();
    }
};
