<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>API 2 直接テスト（3DS なし）</title>
    <script type="text/javascript" src="https://credit.j-payment.co.jp/gateway/js/jquery.js"></script>
    <script type="text/javascript" src="https://credit.j-payment.co.jp/gateway/js/CPToken.js"></script>
    <style>body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:0 20px}label{display:block;margin:8px 0 2px;font-weight:bold}input[type=text]{width:240px;padding:4px}#log{background:#f0f0f0;padding:12px;margin-top:16px;white-space:pre-wrap;font-size:13px;min-height:40px}button{margin-top:12px;padding:8px 24px;font-size:16px}.result{padding:16px;margin-top:12px;border:2px solid;border-radius:8px}.result.ok{border-color:#2a2;background:#effe}.result.ng{border-color:#c22;background:#fee}h2{color:#333}hr{margin:24px 0}</style>
</head>
<body>
<h2>API 2 直接テスト（3DS なし・トークン方式）</h2>
<p><strong>目的:</strong> API 1 をスキップし、既存の billing_code で API 2 だけを呼ぶ。<br>
3DS を使わないトークンで ER584 が出るかどうかの切り分け。</p>
<p><strong>店舗ID: {{ config('robotpayment.store_id', '(未設定)') }}</strong></p>

@if(session('api2_result'))
@php $r = json_decode(session('api2_result'), true); @endphp
<div class="result {{ ($r['success'] ?? false) ? 'ok' : 'ng' }}">
    <strong>API 2 結果: {{ ($r['success'] ?? false) ? '成功' : '失敗' }}</strong><br>
    HTTP: {{ $r['http_status'] ?? '?' }}<br>
    @if(!empty($r['error']))
    エラー: {{ $r['error']['code'] ?? '' }} - {{ $r['error']['message'] ?? '' }}<br>
    @endif
    @if(!empty($r['body']))
    Body: <pre>{{ json_encode($r['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    @endif
</div>
@endif

<form id="mainform" method="POST" action="{{ route('contract.payment.api2-direct-test') }}">
    @csrf
    <input id="tkn" name="tkn" type="hidden" value="">

    <hr>
    <h3>1. 請求先情報（既存）</h3>
    <label>billing_code（API 1 で作成済みの請求先コード）</label>
    <input type="text" name="billing_code" id="billing_code" value="BC00000024">

    <label>payment_method_number</label>
    <input type="text" name="payment_method_number" id="payment_method_number" value="1">

    <hr>
    <h3>2. カード情報（トークン生成用）</h3>

    <label>カード番号</label>
    <input type="text" id="cn" value="4444333322221111">

    <label>有効期限 YY / MM</label>
    <input type="text" id="ed_year" value="30" size="4"> /
    <input type="text" id="ed_month" value="10" size="4">

    <label>カード名義（姓）</label>
    <input type="text" id="fn" value="TEST">

    <label>カード名義（名）</label>
    <input type="text" id="ln" value="CARD">

    <label>CVV</label>
    <input type="text" id="cvv" value="123" size="6">

    <label>支払方法 (md)</label>
    <input type="text" id="md" value="10" size="4">

    <hr>
    <button type="button" id="btn" onclick="doPurchase()">トークン取得 → API 2 直接送信（3DS なし）</button>
</form>

<div id="log">ログ出力...</div>

<script>
var storeId = @json(config('robotpayment.store_id', ''));

function log(msg) {
    document.getElementById('log').textContent += '\n' + new Date().toISOString().slice(11,23) + ' ' + msg;
    if (typeof console !== 'undefined') console.log('[api2-direct-test]', msg);
}

function doPurchase() {
    document.getElementById('btn').disabled = true;
    log('TokenCreate 呼び出し (aid=' + storeId + ', 3DS なし)');
    log('billing_code=' + document.getElementById('billing_code').value);
    log('payment_method_number=' + document.getElementById('payment_method_number').value);

    CPToken.TokenCreate({
        aid: storeId,
        cn: $('#cn').val(),
        ed: $('#ed_year').val() + $('#ed_month').val(),
        fn: $('#fn').val(),
        ln: $('#ln').val(),
        cvv: $('#cvv').val(),
        md: $('#md').val()
    }, execPurchase);
}

function execPurchase(resultCode, errMsg) {
    log('TokenCreate 結果: ' + resultCode + (errMsg ? ' / ' + errMsg : ''));
    if (resultCode !== 'Success') {
        alert(errMsg || 'トークン作成失敗');
        document.getElementById('btn').disabled = false;
        return;
    }
    var tkn = $('#tkn').val();
    log('トークン取得成功: 長さ=' + (tkn||'').length + ', 先頭8文字=' + (tkn||'').substring(0,8));

    // カード情報消去
    $('#cn').val('');
    $('#ed_year').val('');
    $('#ed_month').val('');
    $('#fn').val('');
    $('#ln').val('');
    $('#cvv').val('');

    log('API 2 直接テスト → フォーム送信');
    $('#mainform').submit();
}
</script>
</body>
</html>
