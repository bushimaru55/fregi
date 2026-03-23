<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申込受付完了</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #333333; background-color: #f0f4f8;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f0f4f8;">
<tr><td style="padding: 24px 16px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 640px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
{{-- ヘッダー --}}
<tr>
<td style="background-color: #00B4A1; color: #ffffff; padding: 24px 20px; text-align: center; border-radius: 8px 8px 0 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="text-align: center;">
<div style="font-size: 20px; font-weight: bold; margin: 0 0 6px 0;">申込受付完了のお知らせ</div>
<div style="font-size: 13px; opacity: 0.95;">DSchatbotサービスへのお申し込みありがとうございます</div>
</td></tr></table>
</td>
</tr>

{{-- 本文エリア --}}
<tr><td style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
@if($headerText)
<div style="margin-bottom: 20px; word-wrap: break-word; color: #2d3748; line-height: 1.7;">{!! nl2br(e($headerText)) !!}</div>
@endif

{{-- お申し込み内容 --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px;">
<tr><td style="font-size: 15px; font-weight: bold; color: #2d3748; padding-bottom: 10px; border-bottom: 2px solid #4ecdc4;">お申し込み内容</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">受付番号：{{ $contract->id }}</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">代表製品：{{ optional($contract->representative_plan)->name ?? '—' }}</td></tr>
@if($contract->contractItems->isNotEmpty())
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">合計金額（税込）：{{ number_format($contract->contractItems->sum('subtotal')) }}円</td></tr>
@endif
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">利用開始希望日：{{ $contract->desired_start_date ? $contract->desired_start_date->format('Y年m月d日') : '未指定' }}</td></tr>
</table>

@if($optionItems && $optionItems->isNotEmpty())
{{-- オプション商品 --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px;">
<tr><td style="font-size: 15px; font-weight: bold; color: #2d3748; padding-bottom: 10px; border-bottom: 2px solid #4ecdc4;">オプション商品</td></tr>
@foreach($optionItems as $item)
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">{{ $item->product_name ?? ($item->product->name ?? '') }}：{{ number_format($item->subtotal ?? $item->unit_price ?? 0) }}円</td></tr>
@endforeach
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748; font-weight: bold;">オプション小計：{{ number_format($optionTotalAmount) }}円</td></tr>
@php $totalAmount = $contract->contractItems->sum('subtotal'); @endphp
<tr><td style="padding: 10px 0; border-bottom: 1px solid #edf2f7; color: #c53030; font-size: 16px; font-weight: bold;">合計金額：{{ number_format($totalAmount) }}円（税込）</td></tr>
</table>
@endif

{{-- お客様情報 --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px;">
<tr><td style="font-size: 15px; font-weight: bold; color: #2d3748; padding-bottom: 10px; border-bottom: 2px solid #4ecdc4;">お客様情報</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">会社名：{{ $contract->company_name }}</td></tr>
@if($contract->company_name_kana)
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">会社名（フリガナ）：{{ $contract->company_name_kana }}</td></tr>
@endif
@if($contract->department)
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">部署名：{{ $contract->department }}</td></tr>
@endif
@if($contract->position)
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">役職：{{ $contract->position }}</td></tr>
@endif
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">担当者名：{{ $contract->contact_name }}</td></tr>
@if($contract->contact_name_kana)
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">担当者名（フリガナ）：{{ $contract->contact_name_kana }}</td></tr>
@endif
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">メールアドレス：{{ $contract->email }}</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">電話番号：{{ $contract->phone }}</td></tr>
@if($contract->full_address)
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">住所：{{ $contract->full_address }}</td></tr>
@endif
</table>

{{-- ご利用情報 --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px;">
<tr><td style="font-size: 15px; font-weight: bold; color: #2d3748; padding-bottom: 10px; border-bottom: 2px solid #4ecdc4;">ご利用情報</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">ご利用URL・ドメイン：{{ $contract->usage_url_domain }}</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">体験版からのインポート：{{ $contract->import_from_trial ? '希望する' : '希望しない' }}</td></tr>
</table>

@if($footerText)
<div style="margin-top: 20px; word-wrap: break-word; color: #2d3748; line-height: 1.7;">{!! nl2br(e($footerText)) !!}</div>
@endif
</td></tr>

{{-- フッター --}}
<tr><td style="text-align: center; padding: 20px 24px; color: #718096; font-size: 12px; border-top: 1px solid #e2e8f0;">
このメールはシステムより自動送信されています。<br>© 2026 DSchatbot. All rights reserved.
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
