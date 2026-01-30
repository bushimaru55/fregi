<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申込受付通知</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #a8e6cf 0%, #88d8c0 50%, #b8e6d3 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #4a5568;
            border-bottom: 2px solid #4ecdc4;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-label {
            font-weight: bold;
            width: 180px;
            color: #718096;
        }
        .info-value {
            flex: 1;
            color: #2d3748;
        }
        .card-info {
            background: #fff5f5;
            border: 2px solid #fc8181;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .warning {
            background: #fffaf0;
            border-left: 4px solid #f6ad55;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #718096;
            font-size: 12px;
            border-top: 1px solid #ddd;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>申込受付通知</h1>
        <p>新規申込を受け付けました</p>
    </div>

    <div class="content">
        {{-- 契約情報 --}}
        <div class="section">
            <div class="section-title">契約情報</div>
            <div class="info-row">
                <div class="info-label">契約ID</div>
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
                <div class="info-label">契約ステータス</div>
                <div class="info-value">{{ $contract->status_label }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">利用開始希望日</div>
                <div class="info-value">{{ $contract->desired_start_date->format('Y年m月d日') }}</div>
            </div>
            @if($contract->actual_start_date)
            <div class="info-row">
                <div class="info-label">実際の利用開始日</div>
                <div class="info-value">{{ $contract->actual_start_date->format('Y年m月d日') }}</div>
            </div>
            @endif
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
                <div class="info-label">オプション商品合計</div>
                <div class="info-value"><strong>{{ number_format($optionTotalAmount) }}円</strong></div>
            </div>
            @php
                $baseAmount = $contract->contractPlan->price ?? 0;
                $totalAmount = $baseAmount + $optionTotalAmount;
            @endphp
            <div class="info-row">
                <div class="info-label">合計金額</div>
                <div class="info-value"><strong style="color: #e53e3e; font-size: 18px;">{{ number_format($totalAmount) }}円</strong></div>
            </div>
        </div>
        @endif

        {{-- 申込企業情報 --}}
        <div class="section">
            <div class="section-title">申込企業情報</div>
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
                <div class="info-label">体験版からのインポートを希望する</div>
                <div class="info-value">{{ $contract->import_from_trial ? 'はい' : 'いいえ' }}</div>
            </div>
        </div>

        <div class="footer">
            <p>このメールは自動送信されています。</p>
            <p>© 2026 DSchatbot. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
