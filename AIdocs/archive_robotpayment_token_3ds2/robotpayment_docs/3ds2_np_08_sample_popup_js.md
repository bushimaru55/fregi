# ポップアップ方式（サンプル）: JavaScript

PDF記載のサンプルをそのまま転記しています。（必要に応じて自社実装へ置き換えてください）

```javascript
/**
 * １．クレジットカードトークンの作成
 * トークン作成用の関数を準備します。
 */
function doPurchase() {
  // トークン作成処理を呼び出します。
  CPToken.CardInfo({
    aid: '000000' // 店舗IDを設定します。
  }, execAuth); // 3Dセキュア2.0認証用関数をコールバックにセットします。
}

/**
 * ２．3Dセキュア2.0の認証を実行
 * 3Dセキュア2認証用関数の準備をします。
 * 引数はresultCode, errMsgの2つを受け取れるようします。
 */
function execAuth(resultCode, errMsg) {
  if (resultCode != "Success") {
    window.alert(errMsg);
  } else {
    // 3Dセキュア2.0認証処理を呼び出します。
    ThreeDSAdapter.authenticate({
      tkn: $("#tkn").val(),      // トークン作成後にtkn要素に値が入力されます。
      aid: '000000',             // 店舗IDを設定します。

      // 商品登録なしの場合：am, tx, sfが必要
      am: 1000,
      tx: 0,
      sf: 0,

      // 商品登録ありの場合：iidが必要
      iid: 'ItemCode001',

      // 決済処理と同じ値を設定します（不一致の場合はエラー）
      em: 'sample@sample.com',
      pn: '0300000000',
    }, execPurchase);
  }
}

/**
 * ３．決済処理の実行
 * 決済実行用の関数を準備します。
 * 引数はresultCode, errMsgの2つを受け取れるようにします。
 */
function execPurchase(resultCode, errMsg) {
  if (resultCode != "Success") {
    window.alert(errMsg);
  } else {
    $("#mainform").submit();
  }
}
```

（出典：PDF P.12）
