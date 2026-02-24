# API 5: 即時決済（請求書合算）

**公式**: [即時決済 請求書合算 | billing-robo-apispec](https://apispec.billing-robo.jp/public/demand/bulk_register.html)

複数の**請求情報登録**、**合算請求書発行**、**クレジット決済処理**までを一括で行う。**決済手段がクレジットカードの場合のみ**利用可能。※請求元差出人複数オプションは未対応。

---

## エンドポイント

| 項目 | 内容 |
|------|------|
| Path | `/api/demand/bulk_register` |
| Method | POST |
| Content-Type | `application/json` |
| Encode | UTF-8 |

※ Path は **v1.0 なし**（他APIと異なる）。

---

## リクエスト（ルート）

| 名前 | 概要 | 桁数 | 種別 | 必須 |
|------|------|------|------|------|
| user_id | ユーザーID（管理画面ログインID） | 100 | メール形式 | 必須 |
| access_key | アクセスキー | 100 | 半角英数 | 必須 |
| bill | 請求書に属するパラメータの配列 | — | array | — |

---

## bill（請求書）配列の要素

| 名前 | 概要 | 桁数 | 種別 | 必須 |
|------|------|------|------|------|
| billing_code | 請求先コード | 20 | 半角英数+記号 | 必須 |
| billing_individual_number | 請求先部署番号 | 18 | 数値 | (必須)^1 |
| billing_individual_code | 請求先部署コード | 20 | 半角英数+記号 | (必須)^1 |
| billing_method | 請求方法 0:送付なし 1:自動メール 2:手動メール 3:自動郵送 4:手動郵送 5:自動メール+自動郵送 6:手動メール+手動郵送 7:自動マイページ配信 8:手動マイページ配信 | 1 | 数値 | 必須 |
| bill_template_code | 請求書テンプレートコード 10000:基本 10010:シンプル ※合計請求書は利用不可 | 18 | 数値 | 必須 |
| tax | 消費税率（画面で選択可能な税率のみ） | 2 | 数値 | 必須 |
| issue_date | 初回請求書発行日 ※本日以前で、初回送付日・初回決済期限以前のみ有効 | 10 | 日付形式 | 必須 |
| sending_date | 初回請求書送付日 ※本日以前で初回決済期限以前のみ有効 | 10 | 日付形式 | 必須 |
| deadline_date | 初回決済期限 ※本日以前のみ有効 | 10 | 日付形式 | 必須 |
| bs_owner_code | 請求元担当者コード | 20 | 半角英数+記号 | — |
| jb | 決済処理方法 仮実同時売上: `CAPTURE` 等 | 7 | 文字列 | 必須 |
| bill_detail | 請求書詳細に属するパラメータの配列（公式パラメータ名は bill.detail） | — | array | — |

^1 請求先部署番号と請求先部署コードは**いずれか一方**指定。決済手段がクレジット以外の場合は 231。

---

## bill_detail（請求書詳細）配列の要素

| 名前 | 概要 | 必須 |
|------|------|------|
| demand_type | 請求タイプ 0:単発 1:定期定額 2:定期従量 | 必須 |
| item_code | 商品コード | — |
| goods_code | 集計用商品コード | — |
| link_goods_code | 会計ソフト連携用商品コード | — |
| goods_name | 商品名 | 必須 |
| price | 単価（クレジット決済のため整数7桁まで） | demand_type=0,1 時 |
| quantity | 数量 | demand_type=0,1 時 |
| unit | 単位 | — |
| tax_category | 税区分 0:外税 1:内税 2:対象外 3:非課税 | 必須 |
| tax | 消費税率（未指定時は bill.tax を参照） | — |
| remark | 備考 | — |
| repetition_period_number | 繰返し周期 1～60 | demand_type=1,2 時 |
| repetition_period_unit | 繰返し周期単位 1:月 | demand_type=1,2 時 |
| start_date | サービス提供開始日 yyyy/mm/dd | 必須 |
| repeat_count | 繰返し回数 0:設定しない または 1～60 | demand_type=1,2 時 |
| period_format | 対象期間形式 0:○年○月分 1:○年○月○日分 2:○年○月～○年○月 3:○年○月○日～○年○月○日 99:非表示 | 必須 |
| period_value, period_unit, period_criterion | 対象期間・単位・基準（period_format に応じて） | 条件付き |
| sales_recorded_date | 売上計上日 | — |

---

## レスポンス

- Type: `application/json`, Encode: UTF-8
- ルート: `user` オブジェクト

### 正常時

- `user.user_id`, `user.demand` を返却。
- `demand` に請求先・部署・請求情報（code=請求情報番号）, type, goods_name, price, quantity, start_date, issue_*, sending_*, deadline_*, next_issue_date, jb 等が含まれる。

### エラー時

- `user.bill` 配列で返却。各要素に `error_code`, `error_message`, `ec`（決済エラーコード。error_code が 234 の時のみ）が含まれる。クレジット決済失敗時は 234 と ec（例: ER003）。

---

## リクエスト例（公式より）

```json
{
    "user_id": "sample@robotpayment.co.jp",
    "access_key": "xxxxxxxxxxxxxxxx",
    "bill": [
        {
            "billing_code": "billing",
            "billing_individual_number": 1,
            "billing_method": 0,
            "bill_template_code": 10010,
            "tax": 8,
            "issue_date": "2014/11/11",
            "sending_date": "2014/11/12",
            "deadline_date": "2014/11/13",
            "bs_owner_code": "bs_owner_code",
            "jb": "CAPTURE",
            "bill_detail": [
                {
                    "demand_type": 0,
                    "item_code": "item",
                    "goods_name": "商品",
                    "price": 1000,
                    "quantity": 1,
                    "unit": "円",
                    "tax_category": 0,
                    "start_date": "2014/11/11",
                    "period_format": 0,
                    "sales_recorded_date": "2014/11/11"
                }
            ]
        }
    ]
}
```

※ リクエストした請求書は**1件のみ**有効（242: 1件より多いとエラー）。

---

## エラー（個別エラーコード抜粋）

| エラーコード | 内容 |
|-------------|------|
| 201 | 請求先部署が存在しない |
| 202 | 請求先部署の登録ステータスが不正 |
| 203–229 | 請求タイプ・商品・単価・数量・税・請求方法・日付・テンプレート等が不正 |
| 230 | 請求情報登録に失敗 |
| 231 | 請求先部署の決済手段がクレジット以外（即時決済APIのみ） |
| 232 | jb が不正（即時決済APIのみ） |
| 233 | 請求書発行に失敗（即時決済APIのみ） |
| 234 | クレジット決済に失敗（即時決済APIのみ）※ ec に決済システムエラーコード |
| 235–247 | 請求元担当者・請求先コード・発行日・送付日・決済期限・件数・締め期間・消費税率・売上計上日等が不正 |

主な日付関連: 239（請求書発行日が不正）、240（請求書送付日が不正）、241（決済期限日が不正）、242（請求書発行件数が不正・1件より多い場合）、243–244（締め済み期間への発行日・売上計上日指定不可）、245（選択された消費税率は利用不可）、247（売上計上日が不正）。

共通エラー・決済システムエラーコードは公式ページを参照。

---

## 関連

- [02_scope_billing_robo.md](02_scope_billing_robo.md) … 本APIは範囲 5 に該当。Path は `/api/demand/bulk_register`（v1.0 なし）
- [03_api_01_billing_bulk_upsert.md](03_api_01_billing_bulk_upsert.md) … 請求先・部署は事前に登録済みであること
- [04_api_02_credit_card_token.md](04_api_02_credit_card_token.md) … クレジットカード決済手段の登録が前提
- [05_api_03_demand_bulk_upsert.md](05_api_03_demand_bulk_upsert.md) … 通常フローでは請求情報登録に利用。即時決済は本APIで請求情報〜決済まで一括
- [01_billing_robo_api_sequence.md](01_billing_robo_api_sequence.md) … 請求書発行（4）と即時決済（5）の使い分け
