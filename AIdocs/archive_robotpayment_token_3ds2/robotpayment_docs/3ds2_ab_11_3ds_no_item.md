# 11 3Dセキュア2.0認証（商品登録なし）仕様（P.21）

> 出典：『クレジットカード トークン方式 3Dセキュア2.0(自動課金決済) 接続仕様書』  
> 目的：PDF内容を「漏れなく」実装で参照しやすいMarkdownに整理（ページ番号付き）  
> 注意：本資料は「自動課金決済」と「トークン作成/3DS2認証」の接続仕様を扱います。

- 接続先：`https://credit.j-payment.co.jp/gateway/js/EMV3DSAdapter.js`
- 関数：`ThreeDSAdapter.authenticate(authenticateRequest, callback)`
- 必須：`aid, tkn, am, tx, sf`
- `em` と `pn` は **どちらか必須**（P.21）
- 重要：authenticate の `Success` は「API呼び出し成功」を意味し、3DS成功は後続の決済結果で判断（P.21）
- 注意：`rpemvtds` クラスを使用しない（P.21）
