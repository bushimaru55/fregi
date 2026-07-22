<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 請求管理ロボ API（API 1〜5）
    |--------------------------------------------------------------------------
    | 参照: AIdocs/api_documents/09_demo_connection_billing_robo.md
    */

    'base_url' => rtrim(env('BILLING_ROBO_BASE_URL', 'https://demo.billing-robo.jp'), '/'),

    'user_id' => env('BILLING_ROBO_USER_ID', ''),

    'access_key' => env('BILLING_ROBO_ACCESS_KEY', ''),

    // 請求書払い（銀行振込）の請求元銀行口座パターンコード。
    // 請求管理ロボ管理画面で登録済みのコードを設定する（API1 payment_method=0 時に送信）。
    'bank_transfer_pattern_code' => env('BILLING_ROBO_BANK_TRANSFER_PATTERN_CODE', ''),

];
