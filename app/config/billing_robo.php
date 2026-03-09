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

];
