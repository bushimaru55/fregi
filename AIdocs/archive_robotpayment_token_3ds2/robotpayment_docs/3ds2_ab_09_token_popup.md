# 09 トークン作成（ポップアップ方式）仕様（P.19）

> 出典：『クレジットカード トークン方式 3Dセキュア2.0(自動課金決済) 接続仕様書』  
> 目的：PDF内容を「漏れなく」実装で参照しやすいMarkdownに整理（ページ番号付き）  
> 注意：本資料は「自動課金決済」と「トークン作成/3DS2認証」の接続仕様を扱います。

- 接続先：`https://credit.j-payment.co.jp/gateway/js/CPToken.js`
- 関数：`CPToken.CardInfo(tokenRequest, callback)`
- tokenRequest：`aid`（店舗ID, 必須）
- レスポンス：なし（処理完了後、HTMLの `id=tkn` にトークンがセット）
- 注意：`rpemvtds` クラスを使用しない（P.19）
