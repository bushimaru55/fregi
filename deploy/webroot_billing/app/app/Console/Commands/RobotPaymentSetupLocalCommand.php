<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RobotPaymentSetupLocalCommand extends Command
{
    protected $signature = 'robotpayment:setup-local';

    protected $description = 'ローカルで ROBOT PAYMENT 動作確認を行うためのマイグレーション・テストデータ投入';

    public function handle(): int
    {
        $this->info('ROBOT PAYMENT ローカル動作確認の準備を開始します。');

        $this->info('マイグレーションを実行しています...');
        $exitCode = Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());
        if ($exitCode !== 0) {
            $this->error('マイグレーションに失敗しました。');
            return Command::FAILURE;
        }

        $planCount = DB::table('contract_plans')->where('is_active', true)->count();
        if ($planCount === 0) {
            $this->info('テスト用製品を作成しています...');
            Artisan::call('test:create-plans');
            $this->line(Artisan::output());
        } else {
            $this->info("製品は既に {$planCount} 件あります。");
        }

        $statusCount = DB::table('contract_statuses')->count();
        if ($statusCount === 0) {
            $this->warn('contract_statuses が空です。マイグレーションに contract_statuses の seed が含まれているか確認してください。');
        } else {
            $this->info("契約ステータス: {$statusCount} 件");
        }

        $this->newLine();
        $this->info('--- 次のステップ ---');
        $this->line('1. .env を設定してください:');
        $this->line('   ROBOTPAYMENT_ENABLED=true');
        $this->line('   ROBOTPAYMENT_STORE_ID=133732');
        $this->line('2. 設定を反映: php artisan config:clear');
        $this->line('3. 起動: php artisan serve');
        $this->line('4. ブラウザで http://127.0.0.1:8000 を開き、申込フォーム → 確認 → 決済ページ の流れを確認');
        $this->newLine();

        return Command::SUCCESS;
    }
}
