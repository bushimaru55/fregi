<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申込受付通知</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #333333; background-color: #f0f4f8;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f0f4f8;">
<tr><td style="padding: 24px 16px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 640px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
{{-- ヘッダー --}}
<tr>
<td style="background-color: #00B4A1; color: #ffffff; padding: 24px 20px; text-align: center; border-radius: 8px 8px 0 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="text-align: center;">
<div style="font-size: 20px; font-weight: bold; margin: 0 0 6px 0;">申込受付通知</div>
<div style="font-size: 13px; opacity: 0.95;">新規申込を受け付けました</div>
</td></tr></table>
</td>
</tr>

{{-- 契約情報 --}}
<tr><td style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td style="font-size: 15px; font-weight: bold; color: #2d3748; padding-bottom: 10px; border-bottom: 2px solid #4ecdc4;">契約情報</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">契約ID：{{ $contract->id }}</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">代表製品：{{ optional($contract->representative_plan)->name ?? '—' }}</td></tr>
@if($contract->contractItems->isNotEmpty())
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">選択された商品・合計：{{ number_format($contract->contractItems->sum('subtotal')) }}円（税込）</td></tr>
@endif
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">契約ステータス：{{ $contract->status_label }}</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">利用開始希望日：{{ $contract->desired_start_date->format('Y年m月d日') }}</td></tr>
@if($contract->actual_start_date)
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">実際の利用開始日：{{ $contract->actual_start_date->format('Y年m月d日') }}</td></tr>
@endif
</table>
</td></tr>

@if($optionItems && $optionItems->isNotEmpty())
{{-- オプション・合計 --}}
<tr><td style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td style="font-size: 15px; font-weight: bold; color: #2d3748; padding-bottom: 10px; border-bottom: 2px solid #4ecdc4;">オプション商品・合計金額</td></tr>
@foreach($optionItems as $item)
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">{{ $item->product_name ?? ($item->product->name ?? '') }}：{{ number_format($item->subtotal ?? $item->unit_price ?? 0) }}円</td></tr>
@endforeach
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748; font-weight: bold;">オプション商品合計：{{ number_format($optionTotalAmount) }}円</td></tr>
@php $totalAmount = $contract->contractItems->sum('subtotal'); @endphp
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #c53030; font-size: 16px; font-weight: bold;">合計金額：{{ number_format($totalAmount) }}円（税込）</td></tr>
</table>
</td></tr>
@endif

{{-- 申込企業情報 --}}
<tr><td style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td style="font-size: 15px; font-weight: bold; color: #2d3748; padding-bottom: 10px; border-bottom: 2px solid #4ecdc4;">申込企業情報</td></tr>
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
</td></tr>

{{-- ご利用情報 --}}
<tr><td style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td style="font-size: 15px; font-weight: bold; color: #2d3748; padding-bottom: 10px; border-bottom: 2px solid #4ecdc4;">ご利用情報</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">ご利用URL・ドメイン：{{ $contract->usage_url_domain }}</td></tr>
<tr><td style="padding: 6px 0; border-bottom: 1px solid #edf2f7; color: #2d3748;">体験版からのインポート希望：{{ $contract->import_from_trial ? 'はい' : 'いいえ' }}</td></tr>
</table>
</td></tr>

{{-- フッター --}}
<tr><td style="text-align: center; padding: 20px 24px; color: #718096; font-size: 12px; border-top: 1px solid #e2e8f0;">
このメールは自動送信されています。<br>© 2026 DSchatbot. All rights reserved.
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
