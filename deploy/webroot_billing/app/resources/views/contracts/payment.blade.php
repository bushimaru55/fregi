@extends('layouts.public')

@section('title', 'クレジットカード登録')

@section('content')
<div class="max-w-4xl mx-auto px-4 md:px-0 py-8">
    <div class="mb-6 md:mb-8 text-center">
        <h1 class="text-2xl md:text-4xl font-bold text-gray-800 mb-2">クレジットカード登録</h1>
        <p class="text-sm md:text-base text-gray-600">お支払いに使用するクレジットカード情報をご登録ください。</p>
    </div>

    @if(isset($error) && $error)
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <p class="font-semibold">{{ $error }}</p>
            </div>
        </div>
    @endif

    {{-- 金額サマリー --}}
    <div class="payment-card p-4 md:p-6 mb-4 md:mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-3">ご利用金額</h2>
        <p class="text-2xl font-bold" style="color: var(--color-primary);">{{ number_format($amounts['ta'] ?? 0) }}円（税込）/月</p>
        <p class="text-sm text-gray-600 mt-2">この画面ではカード情報の登録のみを行います。即時の課金は発生しません。</p>
    </div>

    {{-- カード入力フォーム（RP トークン方式 + 3DS2.0） --}}
    <div class="payment-card p-4 md:p-6 mb-4 md:mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">クレジットカード情報</h2>
        <form id="rp-payment-form" method="POST" action="{{ route('contract.payment.execute') }}">
            @csrf
            <input type="hidden" name="tkn" id="tkn" value="">
            <input type="hidden" name="token_created_ms" id="token_created_ms" value="">
            <input type="hidden" name="er584_am" id="er584_am" value="">
            <input type="hidden" name="er584_tx" id="er584_tx" value="">
            <input type="hidden" name="er584_sf" id="er584_sf" value="">
            <input type="hidden" name="er584_use_zero" id="er584_use_zero" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="cn" class="block text-sm font-medium text-gray-700 mb-1">カード番号</label>
                    <input type="text" id="cn" name="cn" maxlength="19" placeholder="1234567812345678"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2" autocomplete="cc-number">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">有効期限</label>
                    <div class="flex gap-2">
                        <input type="text" id="ed_month" name="ed_month" maxlength="2" placeholder="MM"
                            class="w-16 border border-gray-300 rounded-lg px-3 py-2" autocomplete="cc-exp-month">
                        <span class="self-center">/</span>
                        <input type="text" id="ed_year" name="ed_year" maxlength="2" placeholder="YY"
                            class="w-16 border border-gray-300 rounded-lg px-3 py-2" autocomplete="cc-exp-year">
                    </div>
                </div>
                <div>
                    <label for="cvv" class="block text-sm font-medium text-gray-700 mb-1">セキュリティコード（CVV）</label>
                    <input type="text" id="cvv" name="cvv" maxlength="4" placeholder="123" inputmode="numeric" pattern="[0-9]*"
                        class="w-20 border border-gray-300 rounded-lg px-3 py-2" autocomplete="cc-csc">
                    <p class="text-xs text-gray-500 mt-0.5">カード裏面の3桁または4桁の数字</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="fn" class="block text-sm font-medium text-gray-700 mb-1">名義（姓）</label>
                    <input type="text" id="fn" name="fn" maxlength="50" class="w-full border border-gray-300 rounded-lg px-3 py-2" autocomplete="cc-given-name">
                </div>
                <div>
                    <label for="ln" class="block text-sm font-medium text-gray-700 mb-1">名義（名）</label>
                    <input type="text" id="ln" name="ln" maxlength="50" class="w-full border border-gray-300 rounded-lg px-3 py-2" autocomplete="cc-family-name">
                </div>
            </div>

            <div id="EMV3DS_INPUT_FORM"></div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <button type="button" id="btn-submit-payment" class="btn-cta px-6 py-3 font-bold rounded-lg shadow-sm">
                    <i class="fas fa-lock mr-2"></i>カードを登録する
                </button>
                <a href="{{ route('contract.confirm.get') }}" class="btn-primary px-6 py-3 font-bold rounded-lg shadow-sm text-center">
                    <i class="fas fa-arrow-left mr-2"></i>戻る
                </a>
            </div>
        </form>
        @if(config('app.debug'))
        <p class="mt-4 text-xs text-gray-400">検証用: 店舗ID={{ $store_id ?: '(未設定)' }} / 3DS am,tx,sf=0={{ $use_zero_amount_for_3ds ? 'はい' : 'いいえ' }}</p>
        @endif
    </div>
</div>

@push('styles')
<meta name="referrer" content="origin">
@endpush

@push('scripts')
<script src="https://credit.j-payment.co.jp/gateway/js/jquery.js"></script>
<script src="https://credit.j-payment.co.jp/gateway/js/CPToken.js"></script>
<script src="https://credit.j-payment.co.jp/gateway/js/EMV3DSAdapter.js"></script>
<script>
(function() {
    var storeId = @json($store_id ?? '');
    var useZeroAmountFor3ds = @json($use_zero_amount_for_3ds ?? false);
    if (typeof console !== 'undefined' && console.log) {
        console.log('決済ページ検証: 店舗ID(検証用)=', storeId, '3DS am,tx,sf=0=', useZeroAmountFor3ds);
    }
    var am = useZeroAmountFor3ds ? 0 : {{ (int)($amounts['am'] ?? 0) }};
    var tx = useZeroAmountFor3ds ? 0 : {{ (int)($amounts['tx'] ?? 0) }};
    var sf = useZeroAmountFor3ds ? 0 : {{ (int)($amounts['sf'] ?? 0) }};
    var em = @json($customer_email ?? '');
    var pn = @json($customer_phone ?? '');

    document.getElementById('btn-submit-payment').addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        // #region agent log
        fetch('http://127.0.0.1:7244/ingest/ccd86c1d-58cb-4227-a2c1-85434b7ca10d',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'745ed8'},body:JSON.stringify({sessionId:'745ed8',hypothesisId:'H1',location:'payment.blade.php',message:'click_start',data:{},timestamp:Date.now()})}).catch(function(){});
        // #endregion
        var cn = document.getElementById('cn').value.replace(/\s/g, '');
        var edYear = document.getElementById('ed_year').value.trim();
        var edMonth = document.getElementById('ed_month').value.trim();
        if (edMonth.length === 1) edMonth = '0' + edMonth;
        var ed = edYear + edMonth;
        var cvv = (document.getElementById('cvv') && document.getElementById('cvv').value) ? document.getElementById('cvv').value.replace(/\D/g, '') : '';
        var fn = document.getElementById('fn').value.trim();
        var ln = document.getElementById('ln').value.trim();

        if (!cn || !ed || !cvv || !fn || !ln) {
            alert('カード番号・有効期限・セキュリティコード（CVV）・名義をすべて入力してください。');
            btn.disabled = false;
            return;
        }
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>登録中...';

        if (cvv.length < 3 || cvv.length > 4) {
            alert('セキュリティコードは3桁または4桁で入力してください。');
            btn.disabled = false;
            return;
        }

        var cardLength = cn.length;
        var last4 = cardLength >= 4 ? cn.slice(-4) : '';
        if (cardLength >= 12 && cardLength <= 19 && last4.length === 4) {
            var form = document.getElementById('rp-payment-form');
            var csrf = form && form.querySelector('input[name="_token"]') ? form.querySelector('input[name="_token"]').value : '';
            fetch('{{ route('contract.payment.card-hint') }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body: new URLSearchParams({ _token: csrf, card_length: cardLength, last4: last4, expiry_mm: edMonth, expiry_yy: edYear })
            }).catch(function() {});
        }

        if (typeof CPToken === 'undefined') {
            alert('決済システムの読み込みに失敗しました。しばらくしてから再度お試しください。');
            btn.disabled = false;
            return;
        }

        CPToken.TokenCreate({
            aid: storeId,
            cn: cn,
            ed: ed,
            cvv: cvv,
            fn: fn,
            ln: ln,
            md: '10'
        }, function(resultCode, errMsg) {
            // #region agent log
            var tknEl = document.getElementById('tkn');
            var tknLen = (tknEl && tknEl.value) ? tknEl.value.length : 0;
            fetch('http://127.0.0.1:7244/ingest/ccd86c1d-58cb-4227-a2c1-85434b7ca10d',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'745ed8'},body:JSON.stringify({sessionId:'745ed8',hypothesisId:'H1_H2_H3',location:'payment.blade.php token_callback',message:'token_callback',data:{resultCode:resultCode,errMsg:(errMsg||'').slice(0,80),tknLen:tknLen},timestamp:Date.now()})}).catch(function(){});
            // #endregion
            if (resultCode !== 'Success') {
                var msg = errMsg || 'トークン作成に失敗しました。';
                var form = document.getElementById('rp-payment-form');
                var csrf = form && form.querySelector('input[name="_token"]') ? form.querySelector('input[name="_token"]').value : '';
                fetch('{{ route('contract.payment.token-create-failed') }}', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    body: new URLSearchParams({ _token: csrf, err_msg: msg, page_origin: window.location.origin || (window.location.protocol + '//' + window.location.host), stage: 'token_create', result_code: String(resultCode || '') })
                }).catch(function() {});
                alert(msg);
                btn.disabled = false;
                return;
            }
            var tkn = document.getElementById('tkn').value;
            if (!tkn) {
                alert('トークンが取得できませんでした。');
                btn.disabled = false;
                return;
            }
            // #region agent log
            var tokenCreatedMs = Date.now();
            document.getElementById('token_created_ms').value = String(tokenCreatedMs);
            fetch('http://127.0.0.1:7244/ingest/ccd86c1d-58cb-4227-a2c1-85434b7ca10d',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'745ed8'},body:JSON.stringify({sessionId:'745ed8',hypothesisId:'H3',location:'payment.blade.php token ready',message:'token_ready',data:{token_created_ms:tokenCreatedMs,tkn_len:(tkn||'').length},timestamp:Date.now()})}).catch(function(){});
            // #endregion
            if (typeof ThreeDSAdapter === 'undefined') {
                document.getElementById('er584_am').value = String(am);
                document.getElementById('er584_tx').value = String(tx);
                document.getElementById('er584_sf').value = String(sf);
                document.getElementById('er584_use_zero').value = useZeroAmountFor3ds ? '1' : '0';
                document.getElementById('cn').value = '';
                document.getElementById('ed_year').value = '';
                document.getElementById('ed_month').value = '';
                if (document.getElementById('cvv')) document.getElementById('cvv').value = '';
                document.getElementById('fn').value = '';
                document.getElementById('ln').value = '';
                // #region agent log
                fetch('http://127.0.0.1:7244/ingest/ccd86c1d-58cb-4227-a2c1-85434b7ca10d',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'745ed8'},body:JSON.stringify({sessionId:'745ed8',hypothesisId:'H5',location:'payment.blade.php',message:'form_submit_no3ds',data:{},timestamp:Date.now()})}).catch(function(){});
                // #endregion
                document.getElementById('rp-payment-form').submit();
                return;
            }
            // 仕様書 P.16/17: authenticate の Success は「API呼び出し成功」であり、3DS認証の最終成否ではない。最終成否は決済レスポンスで判断する。
            ThreeDSAdapter.authenticate({
                tkn: tkn,
                aid: storeId,
                am: am,
                tx: tx,
                sf: sf,
                em: em,
                pn: pn
            }, function(resultCode, errMsg) {
                // #region agent log
                fetch('http://127.0.0.1:7244/ingest/ccd86c1d-58cb-4227-a2c1-85434b7ca10d',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'745ed8'},body:JSON.stringify({sessionId:'745ed8',hypothesisId:'3DS',location:'payment.blade.php 3DS callback',message:'3ds_callback',data:{resultCode:resultCode,errMsg:(errMsg||'').slice(0,80)},timestamp:Date.now()})}).catch(function(){});
                // #endregion
                if (resultCode !== 'Success') {
                    var form = document.getElementById('rp-payment-form');
                    var csrf = form && form.querySelector('input[name="_token"]') ? form.querySelector('input[name="_token"]').value : '';
                    var msg3ds = errMsg || '3Dセキュア認証に失敗しました。';
                    fetch('{{ route('contract.payment.token-create-failed') }}', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                        body: new URLSearchParams({ _token: csrf, err_msg: msg3ds, page_origin: window.location.origin || (window.location.protocol + '//' + window.location.host), stage: '3ds_auth', result_code: String(resultCode || '') })
                    }).catch(function() {});
                    alert(msg3ds);
                    btn.disabled = false;
                    return;
                }
                // #region agent log
                fetch('http://127.0.0.1:7244/ingest/ccd86c1d-58cb-4227-a2c1-85434b7ca10d',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'745ed8'},body:JSON.stringify({sessionId:'745ed8',hypothesisId:'H5',location:'payment.blade.php 3DS success',message:'form_submit_3ds',data:{},timestamp:Date.now()})}).catch(function(){});
                // #endregion
                document.getElementById('er584_am').value = String(am);
                document.getElementById('er584_tx').value = String(tx);
                document.getElementById('er584_sf').value = String(sf);
                document.getElementById('er584_use_zero').value = useZeroAmountFor3ds ? '1' : '0';
                document.getElementById('cn').value = '';
                document.getElementById('ed_year').value = '';
                document.getElementById('ed_month').value = '';
                if (document.getElementById('cvv')) document.getElementById('cvv').value = '';
                document.getElementById('fn').value = '';
                document.getElementById('ln').value = '';
                document.getElementById('rp-payment-form').submit();
            });
        });
    });
})();
</script>
@endpush
@endsection
