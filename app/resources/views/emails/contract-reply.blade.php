<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申込受付完了</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.8;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #00B4A1 0%, #008B7E 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .custom-text {
            margin-bottom: 25px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .section {
            background: #f9f9f9;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #e5e5e5;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #00B4A1;
            border-bottom: 2px solid #00B4A1;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            width: 180px;
            color: #666;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .total-row {
            background: #fff5f5;
            padding: 12px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .total-amount {
            color: #e53e3e;
            font-size: 20px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            padding: 20px 30px;
            background: #f9f9f9;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #e5e5e5;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>申込受付完了のお知らせ</h1>
            <p>DSchatbotサービスへのお申し込みありがとうございます</p>
        </div>

        <div class="content">
            {{-- 上部文章（管理画面で設定） --}}
            @if($headerText)
            <div class="custom-text">{{ $headerText }}</div>
            @endif

            {{-- 契約情報 --}}
            <div class="section">
                <div class="section-title">お申し込み内容</div>
                <div class="info-row">
                    <div class="info-label">受付番号</div>
                    <div class="info-value">{{ $contract->id }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">契約プラン</div>
                    <div class="info-value">{{ $contract->contractPlan->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">料金</div>
                    <div class="info-value">{{ number_format($contract->contractPlan->price) }}円（税込）</div>
                </div>
                <div class="info-row">
                    <div class="info-label">利用開始希望日</div>
                    <div class="info-value">{{ $contract->desired_start_date ? $contract->desired_start_date->format('Y年m月d日') : '未指定' }}</div>
                </div>
            </div>

            {{-- オプション商品 --}}
            @if($optionItems && $optionItems->isNotEmpty())
            <div class="section">
                <div class="section-title">オプション商品</div>
                @foreach($optionItems as $item)
                <div class="info-row">
                    <div class="info-label">{{ $item->product_name ?? ($item->product->name ?? '') }}</div>
                    <div class="info-value">{{ number_format($item->subtotal ?? $item->unit_price ?? 0) }}円</div>
                </div>
                @endforeach
                <div class="info-row">
                    <div class="info-label">オプション小計</div>
                    <div class="info-value"><strong>{{ number_format($optionTotalAmount) }}円</strong></div>
                </div>
                @php
                    $baseAmount = $contract->contractPlan->price ?? 0;
                    $totalAmount = $baseAmount + $optionTotalAmount;
                @endphp
                <div class="total-row">
                    <div class="info-row" style="border: none;">
                        <div class="info-label">合計金額</div>
                        <div class="info-value total-amount">{{ number_format($totalAmount) }}円</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- 申込企業情報 --}}
            <div class="section">
                <div class="section-title">お客様情報</div>
                <div class="info-row">
                    <div class="info-label">会社名</div>
                    <div class="info-value">{{ $contract->company_name }}</div>
                </div>
                @if($contract->company_name_kana)
                <div class="info-row">
                    <div class="info-label">会社名（フリガナ）</div>
                    <div class="info-value">{{ $contract->company_name_kana }}</div>
                </div>
                @endif
                @if($contract->department)
                <div class="info-row">
                    <div class="info-label">部署名</div>
                    <div class="info-value">{{ $contract->department }}</div>
                </div>
                @endif
                @if($contract->position)
                <div class="info-row">
                    <div class="info-label">役職</div>
                    <div class="info-value">{{ $contract->position }}</div>
                </div>
                @endif
                <div class="info-row">
                    <div class="info-label">担当者名</div>
                    <div class="info-value">{{ $contract->contact_name }}</div>
                </div>
                @if($contract->contact_name_kana)
                <div class="info-row">
                    <div class="info-label">担当者名（フリガナ）</div>
                    <div class="info-value">{{ $contract->contact_name_kana }}</div>
                </div>
                @endif
                <div class="info-row">
                    <div class="info-label">メールアドレス</div>
                    <div class="info-value">{{ $contract->email }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">電話番号</div>
                    <div class="info-value">{{ $contract->phone }}</div>
                </div>
                @if($contract->full_address)
                <div class="info-row">
                    <div class="info-label">住所</div>
                    <div class="info-value">{{ $contract->full_address }}</div>
                </div>
                @endif
            </div>

            {{-- ご利用情報 --}}
            <div class="section">
                <div class="section-title">ご利用情報</div>
                <div class="info-row">
                    <div class="info-label">ご利用URL・ドメイン</div>
                    <div class="info-value">{{ $contract->usage_url_domain }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">体験版からのインポート</div>
                    <div class="info-value">{{ $contract->import_from_trial ? '希望する' : '希望しない' }}</div>
                </div>
            </div>

            {{-- 下部文章（管理画面で設定） --}}
            @if($footerText)
            <div class="custom-text" style="margin-top: 25px;">{{ $footerText }}</div>
            @endif
        </div>

        <div class="footer">
            <p>このメールはシステムより自動送信されています。</p>
            <p>© 2026 DSchatbot. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
