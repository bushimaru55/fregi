<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>送信テスト</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #00B4A1; color: #FFFFFF; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px; }
        .footer { text-align: center; padding: 16px; color: #718096; font-size: 12px; margin-top: 24px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>送信テスト</h1>
    </div>
    <div class="content">
        <p>このメールは、管理者画面の「送信テスト」から送信されたテストメールです。</p>
        <p>このアドレスに申込受付通知メールが届く設定になっています。</p>
        <p>送信日時: {{ now()->format('Y年n月j日 H:i:s') }}</p>
    </div>
    <div class="footer">
        <p>このメールは自動送信されています。</p>
    </div>
</body>
</html>
