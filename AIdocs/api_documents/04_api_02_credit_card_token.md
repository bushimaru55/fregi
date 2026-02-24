# API 2: クレジットカード登録（トークン方式 3Dセキュア利用）

**公式**: [クレジットカード登録(トークン方式 3Dセキュア利用) | billing-robo-apispec](https://apispec.billing-robo.jp/public/billing_payment_method/credit_card_token.html)

請求先登録更新のレスポンスで返却された**店舗オーダー番号（cod）**を利用し、フロントで取得した**トークン**をサーバー経由で送信し、請求先の決済情報としてクレジットカードを登録する。カード番号はトークンに置き換えて通信するため情報漏洩リスクを軽減できる。

- **3Dセキュア**: 原則2025年3月末までに実装が必要。検証環境での検証は仕様上困難。トークン決済・3Dセキュア導入には請求管理ロボ側の設定が必要（カスタマーサポート要連絡）。
- **実装方式**: ポップアップ方式（弊社用意の決済画面をポップアップで表示）／カスタマイズ方式（加盟店サイト内でカード入力後、JSでトークン化）。
- **対応ブラウザ**: Edge / Chrome / Firefox / Safari 各最新版。

---

## エンドポイント

| 項目 | 内容 |
|------|------|
| Path | `/api/v1.0/billing_payment_method/credit_card_token` |
| Method | POST |
| Content-Type | `application/json` |
| Encode | UTF-8 |

---

## 前提フロー（トークン取得）

1. **請求先登録更新**（`POST /api/v1.0/billing/bulk_upsert`）を実行し、レスポンスの `billing[].payment[].cod`（店舗オーダー番号）を取得する。
2. フロントで決済システム提供の JavaScript を読み込み、**トークン取得**を行う。
   - **ポップアップ方式**: `CPToken.CardInfo({ aid: '店舗ID' }, execAuth)` → 3Dセキュア認証 → コールバックで `$("#tkn").val()` を取得。
   - **カスタマイズ方式**: ユーザーがカード番号・有効期限・名義を入力 → `CPToken.TokenCreate({ aid, cn, ed, fn, ln, md: '10' }, execAuth)` → 3Dセキュア認証 → コールバックで `$("#tkn").val()` を取得。
3. サイト内で入力したカード情報は**必ず消去**し、サーバーには**トークンのみ**を送る。

読み込み例（両方式共通）:

```html
<script src="https://credit.j-payment.co.jp/gateway/js/jquery.js"></script>
<script src="https://credit.j-payment.co.jp/gateway/js/CPToken.js"></script>
<script src="https://credit.j-payment.co.jp/gateway/js/EMV3DSAdapter.js"></script>
```

カスタマイズ方式のトークン生成パラメータ（CPToken.TokenCreate）:

| 名前 | 概要 | 桁数 | 種別 | 必須 |
|------|------|------|------|------|
| aid | 店舗ID（契約時に発行） | 6 | 数値 | 必須 |
| cn | カード番号 | 16 | 数値 | 必須 |
| ed | カード有効期限（YYMM） | 4 | 数値 | 必須 |
| fn | カード名義（姓） | 50 | 文字列 | 必須 |
| ln | カード名義（名） | 50 | 文字列 | 必須 |
| cvv | セキュリティコード | 3または4 | 数値 | オプション利用時 |
| md | 支払い方法（一括払いなら "10"） | 2 | 定型 | 必須 |

---

## リクエスト（サーバー→請求管理ロボ）

| 名前 | 概要 | 桁数 | 種別 | 必須 |
|------|------|------|------|------|
| user_id | ユーザーID（管理画面ログインID） | 100 | メール形式 | 必須 |
| access_key | アクセスキー | 100 | 半角英数 | 必須 |
| billing_code | 請求先コード | 20 | 半角英数+記号 | 必須 |
| billing_payment_method_number | 決済情報番号 | 18 | 数値 | (必須)^1 |
| billing_payment_method_code | 決済情報コード | 20 | 半角英数+記号 | (必須)^1 |
| token | トークン情報（フロントで取得した値） | — | 半角英数 | 必須 |
| email | メールアドレス ※請求先(部署)に登録されているものと同じ値が必須 | 100 | メール形式 | — |
| tel | 電話番号 ※請求先(部署)に登録されているものと同じ値が必須、ハイフン除去 | 15 | 数値 | — |

^1 決済情報番号と決済情報コードは**いずれか一方のみ**指定。両方指定は不可。

---

## レスポンス

- Type: `application/json`, Encode: UTF-8
- **正常時**: `error` は `null`（公式ページの正常レスポンス例は「エラー時」のため、正常時は error が無い or null と解釈）。
- **エラー時**: `error` オブジェクトあり。

| 名前 | 概要 | 型 |
|------|------|-----|
| error | エラー ※正常時は null | object |
| error.code | エラーコード | int / string |
| error.message | エラーメッセージ | string |

---

## リクエスト例（公式より）

```json
{
    "user_id": "sample@robotpayment.co.jp",
    "access_key": "xxxxxxxxxxxxxxxx",
    "billing_code": "billing_code",
    "billing_payment_method_number": 1,
    "token": "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
    "email": "sample@sample.com",
    "tel": "0300000000"
}
```

---

## エラー（個別）

| エラーコード | 内容 |
|-------------|------|
| 3401 | 請求先コードが不正 |
| 3402 | 決済情報番号が不正 |
| 3403 | 決済情報コードが不正 |
| 3404 | 請求先決済番号と請求先決済コードは同時に指定できない |
| 3405 | トークン情報が不正 |
| 3406 | メールアドレスが不正 |
| 3407 | 電話番号が不正 |

共通エラー・決済システムエラーは公式ページを参照。

---

## 関連

- [02_scope_billing_robo.md](02_scope_billing_robo.md) … 本APIは範囲 2 に該当
- [03_api_01_billing_bulk_upsert.md](03_api_01_billing_bulk_upsert.md) … 前段で実行。`cod`（店舗オーダー番号）はクレジットカード用決済情報のレスポンスで取得
- [01_billing_robo_api_sequence.md](01_billing_robo_api_sequence.md) … クレジットカード決済のシーケンス（請求先登録→トークン取得→本API→請求情報登録等）
