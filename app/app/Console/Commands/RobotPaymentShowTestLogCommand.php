<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * 決済フロー（contract_payment）の直近ログを出力する。
 * テスト結果を共有する際に実行し、出力を貼り付けて問題修正の参考にできるようにする。
 */
class RobotPaymentShowTestLogCommand extends Command
{
    protected $signature = 'robotpayment:show-test-log
                            {--lines=150 : 出力する行数（直近から）}
                            {--path= : ログファイルのパス（未指定時は contract-payment の直近 daily）}';

    protected $description = '決済フロー用ログ（contract_payment）の直近を出力。テスト結果共有用。';

    public function handle(): int
    {
        $path = $this->option('path');
        $lines = (int) $this->option('lines');
        $logDir = storage_path('logs');

        if ($path === '' || $path === null) {
            $files = File::glob($logDir . '/contract-payment*.log');
            if ($files === false || $files === []) {
                $this->warn('contract_payment のログファイルが見つかりません。');
                $this->line('パス: ' . $logDir);
                return Command::SUCCESS;
            }
            rsort($files);
            $path = $files[0];
        }

        if (!is_readable($path)) {
            $this->error('読み込めません: ' . $path);
            return Command::FAILURE;
        }

        $content = File::get($path);
        $allLines = explode("\n", $content);
        $total = count($allLines);
        $slice = array_slice($allLines, max(0, $total - $lines));
        $output = implode("\n", $slice);

        $this->line('--- contract_payment ログ（直近 ' . count($slice) . ' 行 / ファイル: ' . basename($path) . '） ---');
        $this->newLine();
        $this->line($output);
        $this->newLine();
        $this->line('--- ここまで。問題報告時は上記を貼り付けてください。 ---');

        return Command::SUCCESS;
    }
}
