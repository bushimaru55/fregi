<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

/**
 * 通常決済（買い切り単品）用 gateway_token.aspx への疎通テスト。
 * 有効なトークンは使わず、通信・IP認証・パラメータ形式が通るかだけを確認する。
 * 期待: RP から何らかのレスポンス（トークンエラー等）が返ること。ER003 なら送信元IP未登録。
 */
class RobotPaymentGatewayPingCommand extends Command
{
    protected $signature = 'robotpayment:gateway-ping
                            {--cod= : 店舗オーダー番号（未指定時は test-connectivity-<timestamp>）}';

    protected $description = '通常決済（買い切り単品）: gateway_token.aspx へ疎通テスト POST を送り、応答を表示する';

    public function handle(): int
    {
        $storeId = Config::get('robotpayment.store_id');
        $gatewayUrl = Config::get('robotpayment.gateway_url');

        if ($storeId === '' || $gatewayUrl === '') {
            $this->error('ROBOTPAYMENT_STORE_ID または GATEWAY_URL が未設定です。.env を確認し php artisan config:clear を実行してください。');
            return Command::FAILURE;
        }

        $cod = $this->option('cod') ?: 'test-connectivity-' . now()->format('YmdHis');
        $params = [
            'aid' => $storeId,
            'jb' => Config::get('robotpayment.job_type', 'CAPTURE'),
            'rt' => Config::get('robotpayment.reply_type', '0'),
            'cod' => $cod,
            'tkn' => 'CONNECTIVITY_TEST',
            'em' => 'connectivity-test@example.com',
            'pn' => '0312345678',
            'am' => 1,
            'tx' => 0,
            'sf' => 0,
        ];
        if (Config::get('robotpayment.access_key') !== '') {
            $params['access_key'] = Config::get('robotpayment.access_key');
        }

        $this->info('通常決済（買い切り単品）疎通テスト');
        $this->line('接続先: ' . $gatewayUrl);
        $this->line('送信パラメータ: aid, jb, rt, cod, tkn, em, pn, am=1, tx=0, sf=0');
        $this->newLine();

        $response = Http::asForm()->timeout(15)->post($gatewayUrl, $params);
        $status = $response->status();
        $body = $response->body();

        $this->line('HTTP Status: ' . $status);
        $this->line('Response Body: ' . ($body !== '' ? $body : '(空)'));

        if (str_contains($body, 'ER003')) {
            $this->newLine();
            $this->warn('ER003 = 送信元IPエラー。決済システムCPで「決済データ送信元IP」にこのサーバの送信元IPを登録してください。');
        } elseif ($status === 0 || $response->failed()) {
            $this->newLine();
            $this->warn('接続失敗またはタイムアウト。ネットワーク・URLを確認してください。');
        } else {
            $this->newLine();
            $this->info('RP からレスポンスが返りました。通信・IP認証は通過している可能性が高いです。');
            $this->line('（トークン無効などのエラーは想定内。本番ではブラウザで取得した tkn を送信します。）');
        }

        return Command::SUCCESS;
    }
}
