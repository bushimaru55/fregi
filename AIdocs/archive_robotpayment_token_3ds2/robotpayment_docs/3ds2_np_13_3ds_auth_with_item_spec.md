# 3Dセキュア2.0認証（商品登録あり）仕様

## 配布JS
- 接続先: https://credit.j-payment.co.jp/gateway/js/EMV3DSAdapter.js
- ファイル: EMV3DSAdapter.js

## 関数
- `ThreeDSAdapter.authenticate(authenticateRequest, callback)`

### authenticate 引数仕様
|引数名|説明|備考|
|---|---|---|
|authenticateRequest|3DS認証に必要なパラメータ|JSON形式|
|callback|実行後にコールバックされる関数|※決済処理を行う関数を指定|

### authenticateRequest 仕様（商品登録あり）
|項目|フィールド|詳細|必須|形式/制約|
|---|---|---|---|---|
|店舗ID|aid|契約時に発行される店舗のID|〇|半角数字（6）|
|トークン情報|tkn|トークン|〇|半角英数記号|
|商品コード|iid|商品コード（※1）|〇|半角英数（50）|
|メールアドレス|em|メールアドレス（※1,※2）| |半角英数（254）|
|電話番号|pn|電話番号（※1,※2）| |半角数字（15）|

#### 注記
- ※1 決済処理と同じ値を指定
- ※2 メールアドレス、電話番号のどちらかが必須

### callback 仕様（重要）
- 結果コード
  - 成功時：`Success`
  - 失敗時：エラーコード
- `Success` は **API呼び出し成功** を意味し、**3DS認証成功を意味しません**。
- 3DS認証の成否は、後続の **決済処理のレスポンス** で出力されます。
- メッセージ：結果コードの詳細

## レスポンス
- なし

## その他
- 実装の際に `rpemvtds` クラスを使用しないでください。

（出典：PDF P.17）
