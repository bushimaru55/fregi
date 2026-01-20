<?php

return [
    /*
    |--------------------------------------------------------------------------
    | F-REGI Secret Key
    |--------------------------------------------------------------------------
    |
    | F-REGI設定の接続パスワードを暗号化するために使用する秘密鍵です。
    | Base64エンコードされた32バイトのキーを設定してください。
    |
    | 生成方法:
    |   openssl rand -base64 32
    |
    | または:
    |   python3 - <<'PY'
    |   import os
    |   import base64
    |   print(base64.b64encode(os.urandom(32)).decode('utf-8'))
    |   PY
    |
    */

    'secret_key' => env('FREGI_SECRET_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | F-REGI Environment
    |--------------------------------------------------------------------------
    |
    | F-REGI APIへの接続先を指定します。
    | - test: テスト環境（https://ssl.f-regi.com/connecttest/）
    | - prod: 本番環境（https://ssl.f-regi.com/connect/）
    |
    | 注意: APP_ENVとは独立して設定できます。
    | 本番サーバでテスト接続を行う場合は、FREGI_ENV=test を設定してください。
    |
    */

    'environment' => env('FREGI_ENV', 'test'),

    /*
    |--------------------------------------------------------------------------
    | F-REGI Auth URL
    |--------------------------------------------------------------------------
    |
    | オーソリ処理（authm.cgi）のURLです。
    | FREGI_ENVに応じて自動的に設定されます。
    |
    */

    'auth_url' => env('FREGI_ENV', 'test') === 'test'
        ? 'https://ssl.f-regi.com/connecttest/authm.cgi'
        : 'https://ssl.f-regi.com/connect/authm.cgi',
];
