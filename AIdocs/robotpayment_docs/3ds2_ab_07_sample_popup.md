# 07 ポップアップ方式 実装方法（サンプル）（P.15〜P.16）

> 出典：『クレジットカード トークン方式 3Dセキュア2.0(自動課金決済) 接続仕様書』  
> 目的：PDF内容を「漏れなく」実装で参照しやすいMarkdownに整理（ページ番号付き）  
> 注意：本資料は「自動課金決済」と「トークン作成/3DS2認証」の接続仕様を扱います。

> サンプルは「トークン作成 → 3DS認証 → 決済送信」の流れを示します。  
> 自動課金初回決済の必須項目（`actp`,`acam` 等）は `3ds2_ab_02_initial_no_item.md` / `3ds2_ab_03_initial_with_item.md` を参照しフォームに追加してください。

## JS読込（P.15）
- jQuery：`https://credit.j-payment.co.jp/gateway/js/jquery.js`
- トークン：`https://credit.j-payment.co.jp/gateway/js/CPToken.js`
- 3DS：`https://credit.j-payment.co.jp/gateway/js/EMV3DSAdapter.js`

## 画面埋め込み要素（P.15）
- トークン入力：`<div id="CARD_INPUT_FORM"></div>`
- 3DS表示：`<div id="EMV3DS_INPUT_FORM"></div>`
- トークン格納：`<input id="tkn" name="tkn" type="hidden" value="">`

## 主要呼び出し（P.16）
- `CPToken.CardInfo({ aid }, execAuth)`
- `ThreeDSAdapter.authenticate({ tkn, aid, am/tx/sf or iid, em, pn }, execPurchase)`
- 成功時にフォーム送信（`gateway_token.aspx`）
