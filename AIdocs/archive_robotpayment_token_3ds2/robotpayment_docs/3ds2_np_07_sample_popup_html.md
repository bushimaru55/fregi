# ポップアップ方式（サンプル）: HTML

PDF記載のサンプルをそのまま転記しています。（必要に応じて自社実装へ置き換えてください）

```html
<head>
  <meta charset="utf-8" />
  <script type="text/javascript"
    src="https://credit.j-payment.co.jp/gateway/js/jquery.js"></script> <!--※1-->
  <script type="text/javascript"
    src="https://credit.j-payment.co.jp/gateway/js/CPToken.js"></script><!--※2-->
  <script type="text/javascript"
    src="https://credit.j-payment.co.jp/gateway/js/EMV3DSAdapter.js"></script><!--※3-->
</head>

<form id="mainform" method="POST" action="https://credit.j-payment.co.jp/gateway/gateway_token.aspx">
  <input type="hidden" value="000000" id="aid" name="aid">
  <input type="hidden" value="" id="rt" name="rt">

  <!-- 商品登録なしの場合：am, tx, sfが必要 -->
  <input type="hidden" value="1000" id="am" name="am">
  <input type="hidden" value="0" id="tx" name="tx">
  <input type="hidden" value="0" id="sf" name="sf">

  <!-- 商品登録ありの場合：iidが必要 -->
  <input type="hidden" value="ItemCode001" id="iid" name="iid">

  <input type="hidden" value="sample@sample.com" id="em" name="em">
  <input type="hidden" value="0300000000" id="pn" name="pn">

  <!-- トークン作成処理後にid:tkn要素に値がセットされます -->
  <input id="tkn" name="tkn" type="hidden" value="">

  <!-- トークンポップアップ表示用 -->
  <div id="CARD_INPUT_FORM"></div>

  <!-- 3Dセキュアポップアップ表示用 -->
  <div id="EMV3DS_INPUT_FORM"></div>

  <input type="button" value="購入する" onclick="doPurchase()" />
</form>
```

注記:
- ※1 加盟店側でjQuery読み込み済みの場合は不要
- ※2 トークン決済用
- ※3 3Dセキュア2.0用

（出典：PDF P.11）
