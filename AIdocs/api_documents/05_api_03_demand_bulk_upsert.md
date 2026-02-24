# API 3: 請求情報登録更新

**公式**: [請求情報登録更新 | billing-robo-apispec](https://apispec.billing-robo.jp/public/demand/bulk_upsert.html)

複数の請求情報を登録または更新する。停止中の請求情報を指定して送信すると有効な状態に復活する。

---

## エンドポイント

| 項目 | 内容 |
|------|------|
| Path | `/api/v1.0/demand/bulk_upsert` |
| Method | POST |
| Content-Type | `application/json` |
| Encode | UTF-8 |

---

## リクエスト（ルート）

| 名前 | 概要 | 桁数 | 種別 | 必須 |
|------|------|------|------|------|
| user_id | ユーザーID（管理画面ログインID） | 100 | メール形式 | 必須 |
| access_key | アクセスキー | 100 | 半角英数 | 必須 |
| demand | 請求情報に属するパラメータの配列 | — | array | — |

---

## demand（請求情報）配列の要素（抜粋）

### 請求先・部署・決済の指定

| 名前 | 概要 | 必須 |
|------|------|------|
| billing_code | 請求先コード ※登録後変更不可 | (追加時) |
| billing_individual_number | 請求先部署番号 ※登録後変更不可 | (追加時)^1 |
| billing_individual_code | 請求先部署コード ※登録後変更不可 | (追加時)^1 |
| payment_method_code | 決済情報コード（請求先部署に登録済みなら任意） | (追加時) |

^1 請求先部署番号と請求先部署コードは**いずれか一方のみ**指定。同時指定不可（1338）。

### 請求情報の識別（更新時）

| 名前 | 概要 | 必須 |
|------|------|------|
| number | 請求情報番号 ※登録後変更不可 | (更新時)^2 |
| code | 請求情報コード ※登録後変更不可 | (更新時)^2 |

^2 更新時は number と code の**いずれか一方**必須。同時指定不可（1342）。

### 商品・明細

| 名前 | 概要 | 必須 |
|------|------|------|
| item_code | 商品コード（商品マスタに登録済みのコード）※登録後変更不可 | — |
| type | 請求タイプ 0:単発 1:定期定額 2:定期従量 ※登録後変更不可 | (追加時) |
| goods_code | 集計用商品コード | — |
| link_goods_code | 会計ソフト連携用商品コード | — |
| goods_name | 商品名 | (追加時) |
| price | 単価（クレジットカード決済時は整数7桁まで） | (追加時で type=0,1 時) |
| quantity | 数量 | (追加時で type=0,1 時) |
| unit | 単位 | — |
| tax_category | 税区分 0:外税 1:内税 2:対象外 3:非課税 | (追加時) |
| tax | 消費税率 5/8/10 等（tax_category=0,1 時） | (追加時で税対象時) |
| remark | 備考 | — |

### 請求方法・スケジュール

| 名前 | 概要 | 必須 |
|------|------|------|
| billing_method | 請求方法 0:送付なし 1:自動メール 2:手動メール 3:自動郵送 4:手動郵送 5:自動メール+自動郵送 6:手動メール+手動郵送 7:自動マイページ配信 8:手動マイページ配信 | (追加時) |
| start_date | サービス提供開始日 yyyy/mm/dd ※登録後変更不可 | (追加時) |
| repetition_period_number | 繰返し周期 1～60（type=1,2 時） | (追加時で type=1,2 時) |
| repetition_period_unit | 繰返し周期単位 1:月 | (追加時で type=1,2 時) |
| repeat_count | 繰返し回数 0:設定しない または 1～60 | (追加時で type=1,2 時) |
| period_format | 対象期間形式 0:○年○月分 1:○年○月○日分 2:○年○月～○年○月 3:○年○月○日～○年○月○日 99:非表示 | (追加時) |
| period_value, period_unit, period_criterion | 対象期間・単位・基準（period_format に応じて） | 条件付き |
| issue_month, issue_day | 請求書発行日（-60～60, 1～30 or 99 末日） | (追加時) |
| sending_month, sending_day | 請求書送付日 | (追加時) |
| deadline_month, deadline_day | 決済期限（RP口座振替時は振替日 10 or 26） | (追加時) |
| sales_recorded_month, sales_recorded_day | 売上計上日 | — |
| slip_deadline_month, slip_deadline_day | 払込票有効期限（コンビニ払込票ハガキ時） | — |

### その他

| 名前 | 概要 |
|------|------|
| memo | メモ |
| bill_template_code | 請求書テンプレートコード（10000:基本 10010:シンプル 等） |
| bs_residence_code | 請求元差出人コード |
| bs_owner_code | 請求元担当者コード |
| attachment_planned_flg | 0:ファイル添付予定なし 1:ファイル添付予定あり |
| account_title_code | 勘定科目コード（省略時 4100） |
| bill_group_key | 請求書合算キー |
| outside_billing_number | 外部連携用請求書番号 |
| custom | 請求情報カスタム項目の配列（number または code, value） |

**更新時の注意**（公式より）  
- パラメータを送信しなかった場合、既存値が保持される。  
- 空文字で送信した場合は空で更新される。  
- 商品指定で登録した請求情報の商品は変更不可。

---

## custom（カスタム項目）配列の要素

| 名前 | 概要 | 必須 |
|------|------|------|
| number | カスタム項目番号 | (必須※1)^1 |
| code | カスタム項目コード | (必須※1)^1 |
| value | カスタム項目値 | — |

^1 カスタム項目必須フラグON時や登録更新時は number または code が必須。両方同時指定は不可（1362）。

---

## レスポンス

- Type: `application/json`, Encode: UTF-8
- ルート: `user_id`, `demand`（請求情報の配列）
- demand 各要素: `error_code`, `error_message`（正常時は null）, `billing_code`, `billing_individual_number` / `billing_individual_code`, `payment_method_code`, `number`（登録時は請求管理ロボ側で発番）, `code`, `item_code`, `type`, `goods_name`, `price`, `quantity`, `tax_category`, `tax`, `start_date`, `end_date`, `next_issue_date`, `custom` 等

---

## リクエスト例（公式より抜粋・2件同時登録）

```json
{
    "user_id": "sample@robotpayment.co.jp",
    "access_key": "xxxxxxxxxxxxxxxx",
    "demand": [
        {
            "billing_code": "billing1",
            "billing_individual_code": "abc1234",
            "item_code": "item1",
            "quantity": 5,
            "start_date": "2016/05/01",
            "custom": [{ "number": 15, "value": "カスタム項目値登録1" }]
        },
        {
            "billing_code": "billing2",
            "billing_individual_code": "abc1234",
            "payment_method_code": "payment1234",
            "type": 0,
            "goods_name": "商品2",
            "price": 2000,
            "quantity": 10,
            "tax_category": 0,
            "tax": 8,
            "billing_method": 1,
            "start_date": "2016/04/01",
            "period_format": 0,
            "issue_month": 0,
            "issue_day": 1,
            "sending_month": 0,
            "sending_day": 1,
            "deadline_month": 0,
            "deadline_day": 1,
            "bill_template_code": 10010,
            "custom": [{ "code": "custom16", "value": "カスタム項目値登録2" }]
        }
    ]
}
```

---

## エラー（個別エラーコード抜粋）

| コード | 内容 |
|--------|------|
| 1301 | 請求先コードが不正 |
| 1302–1304 | 請求先部署番号・コード・決済情報コードが不正 |
| 1305–1306 | 請求情報番号・コードが不正 |
| 1307–1317 | 商品・請求タイプ・単価・数量・税区分等が不正 |
| 1318–1336 | 請求方法・繰返し・日付・テンプレート・勘定科目等が不正 |
| 1338 | 請求先部署番号と請求先部署コードは同時に指定できません |
| 1339–1341 | 請求先部署が存在しない・決済情報が存在しない・登録ステータス不正 |
| 1342 | 請求情報番号と請求情報コードは同時に指定できません |
| 1343–1345 | 請求情報が存在しない・商品が存在しない・請求情報登録に失敗 |
| 1353 | 親部署のデフォルト利用可能決済手段と異なる決済手段は指定できません |
| 1356–1367 | 請求書合算キー・外部連携用請求書番号・カスタム項目・請求元差出人等が不正 |
| 1370–1371 | ファイル添付予定フラグが不正・利用不可 |
| 1372 | 請求まるなげロボに連携中。しばらく経ってから更新してください |
| 1373 | 請求情報に紐づく未発行の売上が存在するため編集できません |
| 1383 | 請求情報コードが重複しています |

その他 1301〜1383 は公式ページの「エラー」セクションを参照。

---

## 関連

- [02_scope_billing_robo.md](02_scope_billing_robo.md) … 本APIは範囲 3 に該当
- [03_api_01_billing_bulk_upsert.md](03_api_01_billing_bulk_upsert.md) … 請求先・部署は事前に API 1 で登録済みであること
- [04_api_02_credit_card_token.md](04_api_02_credit_card_token.md) … クレジットカード利用時は先に登録
- [01_billing_robo_api_sequence.md](01_billing_robo_api_sequence.md) … 請求情報登録の次は請求書発行 or 即時決済
