# API 1: 請求先登録更新

**公式**: [請求先登録更新 | billing-robo-apispec](https://apispec.billing-robo.jp/public/billing/bulk_upsert.html)

複数の請求先・請求先部署・請求先決済手段・請求先補助科目を登録または更新する。停止中の請求先・請求先部署・決済情報を指定して送信すると有効に復元する。

---

## エンドポイント

| 項目 | 内容 |
|------|------|
| Path | `/api/v1.0/billing/bulk_upsert` |
| Method | POST |
| Content-Type | `application/json` |
| Encode | UTF-8 |

---

## リクエスト（ルート）

| 名前 | 概要 | 桁数 | 種別 | 必須 |
|------|------|------|------|------|
| user_id | ユーザーID（管理画面ログインID） | 100 | メール形式 | 必須 |
| access_key | アクセスキー | 100 | 半角英数 | 必須 |
| billing | 請求先に属するパラメータの配列 | — | array | — |

---

## billing（請求先）配列の要素

| 名前 | 概要 | 桁数 | 種別 | 必須 |
|------|------|------|------|------|
| code | 請求先コード ※登録後は変更不可、両端スペース除去 | 20 | 半角英数+記号 | 必須 |
| name | 請求先名 ※両端スペース除去 | 100 | 文字列 | (追加時) |
| individual | 請求先部署に属するパラメータの配列 | — | array | — |
| payment | 請求先決済手段に属するパラメータの配列 ※省略可 | — | array | — |
| sub_account_title | 請求先補助科目（拡張予定・現在利用不可） | — | array | — |

---

## individual（請求先部署）配列の要素（抜粋）

| 名前 | 概要 | 必須 |
|------|------|------|
| number | 請求先部署番号 ※登録後変更不可 | (更新時)^1 |
| code | 請求先部署コード ※登録後変更不可 | (更新時)^1 |
| name | 請求先部署名 | (追加時) |
| address1 | 宛名1 | (追加時) |
| zip_code | 郵便番号（ハイフン除去時7桁数字） | (追加時) |
| pref | 都道府県名 or 「その他」 | (追加時) |
| city_address | 市区町村番地 | (追加時) |
| email | メールアドレス | (追加時) |
| tel | 電話番号 ※payment_method=1,2,6,7,8時など | (追加時で条件時) |
| payment_method_code | 決済情報コード（デフォルト決済手段の紐付けに利用） | — |
| billing_method, issue_month, issue_day, sending_* , deadline_* 等 | 請求書発行日・送付日・決済期限など（請求情報登録で利用する値） | — |

^1 number と code は (更新時) いずれか必須。両方指定は不可。

---

## payment（請求先決済手段）配列の要素（抜粋）

| 名前 | 概要 | 必須 |
|------|------|------|
| number | 決済情報番号 ※登録後変更不可 | (更新時)^3 |
| code | 決済情報コード ※登録後変更不可 | (更新時)^3 |
| name | 決済情報名（空文字不可） | (追加時) |
| payment_method | 決済手段（0:銀行振込, 1:クレジットカード, 2:バンクチェック, 3:RP口座振替, 9:バーチャル口座 等） | (追加時) |
| bank_transfer_pattern_code | 請求元銀行口座パターンコード ※payment_method=0時など | — |
| credit_card_regist_kind | 1:あとで登録 / 2:登録用メール送付 ※payment_method=1時 | — |
| credit_card_email | クレジットカード登録メール送信先 | — |

^3 number と code は (更新時) いずれか必須。両方同時指定不可。

---

## レスポンス

- Type: `application/json`, Encode: UTF-8
- ルート: `user_id`, `billing`（請求先の配列。各要素に error_code, error_message, code, name, individual[], payment[], sub_account_title[]）
- individual 各要素: number, code, name, address1, zip_code, pref, city_address, email, payment_method_code 等
- payment 各要素: number, code, name, payment_method, register_status（0:未処理, 1:登録待ち, 2:メール送信済み, 3:申請中, 4:送信エラー, 5:登録完了, 6:登録失敗, 7:与信停止中）, cod（店舗オーダー番号・payment_method=1以外はNULL）等

---

## リクエスト例（公式より抜粋）

```json
{
    "user_id": "sample@robotpayment.co.jp",
    "access_key": "xxxxxxxxxxxxxxxx",
    "billing": [
        {
            "code": "billing_code",
            "name": "請求先名",
            "individual": [
                {
                    "code": "abc-12345",
                    "name": "請求先部署名",
                    "address1": "宛名1",
                    "zip_code": "1651111",
                    "pref": "東京都",
                    "city_address": "渋谷区",
                    "email": "email@robotpayment.co.jp"
                }
            ],
            "payment": [
                {
                    "name": "銀行振込123",
                    "bank_transfer_pattern_code": "bank-123",
                    "payment_method": 0
                }
            ]
        }
    ]
}
```

---

## エラー（個別エラーコード抜粋）

| コード | 内容 |
|--------|------|
| 1101 | リクエストパラメータに請求先が存在しない |
| 1102 | 請求先コードが不正 |
| 1103 | 請求先名が不正（新規時: 空白 or 101桁以上） |
| 1104–1106 | 請求先部署番号・コード・名が不正 |
| 1108–1124 | 宛名・郵便番号・都道府県・市区町村・メール等が不正 |
| 1140–1152 | 決済情報・決済手段・口座関連が不正 |
| 1155–1159 | 請求先/部署/決済手段/補助科目 登録失敗 |
| 1167 | 請求先部署番号と請求先部署コードは同時に指定できません |
| 1173 | 請求先決済番号と請求先決済コードは同時に指定できません |
| 1178 | クレジットカード情報登録メール送信に失敗 |
| 1183 | クレジットカード登録メール送信先が不正 |

その他 1101〜1193 の個別エラーは公式ページの「エラー」セクションを参照。

---

## 関連

- [02_scope_billing_robo.md](02_scope_billing_robo.md) … 本APIは範囲 1 に該当
- [01_billing_robo_api_sequence.md](01_billing_robo_api_sequence.md) … シーケンス上は「請求先登録更新」の次に決済手段に応じた処理（クレジットカードの場合はトークン取得→クレジットカード登録API）
- [00_billing_robo_api_overview.md](00_billing_robo_api_overview.md) … Base URL・共通エラー
