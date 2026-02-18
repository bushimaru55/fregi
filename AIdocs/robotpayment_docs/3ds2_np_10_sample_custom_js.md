# カスタマイズ方式（サンプル）: JavaScript

```javascript
/**
 * １．クレジットカードトークンの作成
 * トークン作成用の関数を準備します。
 */
function doPurchase() {
  CPToken.TokenCreate({
    aid: '000000', // 店舗ID
    cn: $("#cn").val(),
    ed: $("#ed_year").val() + $("#ed_month").val(),
    fn: $("#fn").val(),
    ln: $("#ln").val()
  }, execAuth); // 3Dセキュア2.0認証用関数をコールバックにセット
}

/**
 * ２．3Dセキュア2.0の認証を実行
 */
function execAuth(resultCode, errMsg) {
  if (resultCode != "Success") {
    window.alert(errMsg);
  } else {
    ThreeDSAdapter.authenticate({
      tkn: $("#tkn").val(),
      aid: '000000',

      // 商品登録なしの場合：am, tx, sfが必要
      am: 1000,
      tx: 0,
      sf: 0,

      // 商品登録ありの場合：iidが必要
      iid: 'ItemCode001',

      // 決済処理と同じ値を設定（不一致の場合はエラー）
      em: 'sample@sample.com',
      pn: '0300000000',
    }, execPurchase);
  }
}

/**
 * ３．決済処理の実行
 */
function execPurchase(resultCode, errMsg) {
  if (resultCode != "Success") {
    window.alert(errMsg);
  } else {
    // カード情報を消去
    $("#cn").val("");
    $("#ed_year").val("");
    $("#ed_month").val("");
    $("#fn").val("");
    $("#ln").val("");

    $("#mainform").submit();
  }
}
```

（出典：PDF P.14）
