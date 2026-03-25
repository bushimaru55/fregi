<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ROBOT PAYMENT (トークン方式 + 3DS2.0)
    |--------------------------------------------------------------------------
    | 確定仕様: AIdocs/payment_integration_robotpayment/
    | gateway_token.aspx は加盟店サーバ（固定IP）から送信すること（ER003対策）
    */

    'enabled' => env('ROBOTPAYMENT_ENABLED', false),

    'store_id' => env('ROBOTPAYMENT_STORE_ID', ''),

    'access_key' => env('ROBOTPAYMENT_ACCESS_KEY', ''),

    'gateway_url' => env(
        'ROBOTPAYMENT_GATEWAY_URL',
        'https://credit.j-payment.co.jp/gateway/gateway_token.aspx'
    ),

    'company_id' => env('ROBOTPAYMENT_COMPANY_ID', 1),

    // 月額決済（自動課金）は CAPTURE（仮実同時売上）のみ対応。AUTH は RP 仕様上不可。
    'job_type' => 'CAPTURE',

    'reply_type' => '0',

    'notify_initial_url' => env('ROBOTPAYMENT_NOTIFY_INITIAL_URL', ''),

    'notify_recurring_url' => env('ROBOTPAYMENT_NOTIFY_RECURRING_URL', ''),

];
