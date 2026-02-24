# 10 トークン作成（カスタマイズ方式）仕様（P.20）

> 出典：『クレジットカード トークン方式 3Dセキュア2.0(自動課金決済) 接続仕様書』  
> 目的：PDF内容を「漏れなく」実装で参照しやすいMarkdownに整理（ページ番号付き）  
> 注意：本資料は「自動課金決済」と「トークン作成/3DS2認証」の接続仕様を扱います。

- 接続先：`https://credit.j-payment.co.jp/gateway/js/CPToken.js`
- 関数：`CPToken.TokenCreate(tokenRequest, callback)`
- 必須：`aid, cn, ed(YYMM), fn, ln`
- 任意：`cvv`（要契約）, `md`（分割/リボは要契約）, `pt`（md=61時必須）
- 文字制約：記号は `.` と `-`、`fn+ln` 合計44文字以内（P.20）
- レスポンス：なし（処理完了後、`id=tkn` にセット）
- 注意：`rpemvtds` クラスを使用しない（P.20）
