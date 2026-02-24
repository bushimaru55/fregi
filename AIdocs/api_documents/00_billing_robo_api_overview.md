# 請求管理ロボ API 仕様書（概要）

**出典**: [billing-robo-apispec | 請求管理ロボのAPI仕様書](https://apispec.billing-robo.jp/)  
本ドキュメントは現行の請求管理ロボ API 参照用です。旧連携（archive_robotpayment_token_3ds2）の仕様は利用できません。

---

## 目次

- 概要
- API一覧
- Webhook一覧
- 注釈（仕様変更方針・アクセス制限・種別・決済手段・消込手段・必須）
- 共通エラー
- 推奨SSL/TLSバージョン

---

## 概要

| 項目 | 内容 |
|------|------|
| **Base URL（本番）** | `https://billing-robo.jp`【推奨】 / `https://billing-robo.jp:10443` |
| **Base URL（デモ）** | `https://demo.billing-robo.jp`【推奨】 / `https://demo.billing-robo.jp:10443` |
| **Accepted content types** | `application/json` |
| **Encode** | `UTF-8` |
| **認証** | API実行時のユーザーIDとアクセスキーは請求管理ロボのヘルプサイト参照 |

---

## API一覧

いずれも **POST**。パスは Base URL 相対。

| API名 | Method | Path |
|-------|--------|------|
| 請求先登録更新 | POST | `/api/v1.0/billing/bulk_upsert` |
| 請求先停止削除 | POST | `/api/v1.0/billing/bulk_stop` |
| 口座振替依頼書発行 | POST | `/api/v1.0/billing/bulk_download_pdf` |
| 請求先部署参照 | POST | `/api/v1.0/billing_individual/search` |
| **クレジットカード登録（トークン方式 3Dセキュア利用）** | POST | `/api/v1.0/billing_payment_method/credit_card_token` |
| 決済情報参照 | POST | `/api/v1.0/billing_payment_method/search` |
| 請求情報登録更新 | POST | `/api/v1.0/demand/bulk_upsert` |
| 請求情報停止削除 | POST | `/api/v1.0/demand/bulk_stop` |
| 売上消込結果参照 | POST | `/api/v1.0/demand/search` |
| 請求情報参照 | POST | `/api/v1.0/demand/search2` |
| 請求書発行 | POST | `/api/v1.0/demand/bulk_issue_bill_select` |
| 即時決済 請求書合算 | POST | `/api/demand/bulk_register` |
| 請求書送付メール | POST | `/api/v1.0/bill/send_bill_by_email` |
| 請求書送付郵送 | POST | `/api/v1.0/bill/send_bill_by_mail` |
| 繰越予約 | POST | `/api/v1.0/bill/update_carryover` |
| 請求書参照 | POST | `/api/v1.0/bill/search` |
| 請求書更新 | POST | `/api/v1.0/bill/update` |
| 請求書無効 | POST | `/api/v1.0/bill/stop` |
| 請求書明細参照 | POST | `/api/v1.0/bill_detail/search` |
| 入金登録更新 | POST | `/api/v1.0/payment/bulk_upsert` |
| 入金無効削除 | POST | `/api/v1.0/payment/bulk_stop` |
| 入金参照 | POST | `/api/v1.0/payment/search` |
| 入金繰越予約 | POST | `/api/v1.0/payment/carryover_suspense` |
| 入金繰越予約取消 | POST | `/api/v1.0/payment/carryover_suspense_cancel` |
| 消込 | POST | `/api/v1.0/clearing/exec` |
| 消込結果参照 | POST | `/api/v1.0/clearing/search` |
| 消込取消 | POST | `/api/v1.0/clearing/bulk_cancel` |
| 消込結果明細参照 | POST | `/api/v1.0/clearing_detail/search` |
| 商品登録更新2 | POST | `/api/v1.0/goods/bulk_upsert2` |
| 商品停止削除 | POST | `/api/v1.0/goods/bulk_stop` |
| 商品参照 | POST | `/api/v1.0/goods/search` |
| カスタム項目登録更新 | POST | `/api/v1.0/custom_field/bulk_upsert` |
| カスタム項目削除 | POST | `/api/v1.0/custom_field/bulk_stop` |
| カスタム項目参照 | POST | `/api/v1.0/custom_field/search` |
| 請求元銀行口座登録更新 | POST | `/api/v1.0/bs_bank_transfer/bulk_upsert` |
| 請求元銀行口座停止削除 | POST | `/api/v1.0/bs_bank_transfer/bulk_stop` |
| 請求元銀行口座パターン登録更新 | POST | `/api/v1.0/bs_bank_transfer_pattern/bulk_upsert` |
| 請求元銀行口座パターン停止削除 | POST | `/api/v1.0/bs_bank_transfer_pattern/bulk_stop` |
| 請求元部署登録更新 | POST | `/api/v1.0/bs_department/bulk_upsert` |
| 請求元部署停止削除 | POST | `/api/v1.0/bs_department/bulk_stop` |
| 請求元担当者登録更新 | POST | `/api/v1.0/bs_owner/bulk_upsert` |
| 請求元担当者停止削除 | POST | `/api/v1.0/bs_owner/bulk_stop` |
| 与信申請登録 | POST | `/api/v1.0/request_marunage_credit/bulk_register` |
| 与信申請参照 | POST | `/api/v1.0/request_marunage_credit/search` |
| 与信停止申請 | POST | `/api/v1.0/marunage_credit/bulk_stop` |
| 与信参照 | POST | `/api/v1.0/marunage_credit/search` |
| 債権譲渡申請 | POST | `/api/v1.0/marunage_bill_credit/bulk_apply` |
| 債権譲渡申請参照 | POST | `/api/v1.0/marunage_bill_credit/search` |

- 各APIの詳細は公式サイトの個別ページを参照。廃止API一覧も公式にあり。

---

## Webhook一覧

- Webhook請求書発行イベント
- Webhook郵送通知
- Webhookクレジットカード登録状況通知

---

## 注釈

### APIの仕様公開・仕様変更の方針

- 機能拡張に応じてAPIのサポート終了と新バージョン提供があり得るが、**既存APIには後方互換性のある拡張**を随時実施。
- 後方互換の例: リクエストに任意パラメータ追加、レスポンスにフィールド追加、エラーコード追加、バリデーション緩和。
- サポート終了時は利用企業と相談の上、十分な移行期間を設ける。

### APIアクセス制限（2021/11/17〜）

#### リクエスト数の制限

- 同一IPで **5分あたり300回まで**。
- 超過時は **429 Too Many Requests**。上限を下回るまで制限。
- 制限の適用・解除に最大2分程度のラグあり。制限中のリクエストもカウント対象。

#### リクエストサイズの制限

- Request Body は **9MB未満**。
- 超過時は **413 Request Entity Too Large**。

### 種別（入力可能値の例）

| 種別 | 説明 |
|------|------|
| メール形式 | 正規表現で検証。例: robot_payment@example.com |
| アルファベット | A–Z, a–z |
| 半角英数 | A–Z, a–z, 0–9 |
| 半角英数+記号 | 上記+記号 |
| 口座名義 | 半角英数+半角カタカナ+指定記号 |
| 口座名義(RP口座振替) | 別途 [口座名義使用可能文字](https://keirinomikata.zendesk.com/hc/ja/articles/900000986386) 参照 |
| 銀行名等 | 半角英数(小文字除く)+半角カタカナ |
| 日付形式 | `YYYY-MM-DD` または `YYYY/MM/DD`（正規表現で検証） |

### 決済手段（数値）

| 数値 | 決済手段名 |
|------|------------|
| 0 | 銀行振込 |
| 1 | クレジットカード |
| 2 | バンクチェック |
| 3 | RP口座振替 |
| 4 | RL口座振替 |
| 5 | その他口座振替 |
| 6 | コンビニ払込票(A4) |
| 7 | コンビニ払込票(ハガキ) |
| 8 | その他コンビニ払込票 |
| 9 | バーチャル口座 |
| 10–14 | その他決済1–5 |
| 15 | まるなげ口座振替 |
| 16 | まるなげバンクチェック |
| 17 | ファクタリング口座振替 |
| 18 | ファクタリングバンクチェック |

### 消込手段（数値）

- 0–18: 上記決済手段と同様の区分。
- 98: 相殺
- 101: 貸倒
- 102: 確認済み
- 103: 手数料
- 104: 請求書明細相殺
- 106: 現金
- 107: 長期滞留債権
- 108: 破産更生等債権
- 109: 売上取消
- 110: 繰越
- 201: 入金確認済み

### 必須の表記

| 表記 | 説明 |
|------|------|
| 必須 | 全リクエストで必要 |
| (追加時) | 登録・更新両用APIで、追加時に必要 |
| (更新時) | 登録・更新両用APIで、更新時に必要 |
| ({条件})^n | 同一 n の必須条件のうちいずれか1つが必要 |

---

## 共通エラー

- 失敗時は原則 **application/json** でエラー情報を返却。多くの場合 HTTP **400 Bad Request**。
- **401 Unauthorized**: ログインID・アクセスキー・接続IPの不正。
- **503 Service Unavailable**: メンテナンス中。
- **429 Too Many Requests**: リクエスト数制限超過。
- **413 Request Entity Too Large**: リクエストサイズ超過。

接続エラー・不正URI・内部エラー・アクセス制限時などは、上記JSONが返せない場合あり。

### エラーコード

| コード | 内容 |
|--------|------|
| 1 | 内部エラー |
| 10 | 不明なURI |
| 11 | ログインIDが不正 |
| 12 | アクセスキーが不正 |
| 13 | 接続IPが不正 |
| 14 | 店舗IDが不正 |
| 16 | ログイン失敗 |
| 17 | 権限が不正 |
| 18 | 利用企業が不正 |
| 19 | メンテナンス中 |
| 20 | リクエスト数が不正 |
| 21 | 廃止されたAPI |

### レスポンス例

```json
{
    "error": {
        "code": 11,
        "message": "Invalid 'user_id'."
    }
}
```

### その他の特殊なエラーコード

- 51: 債権譲渡請求書編集不可
- 53–57: 債権譲渡申請ステータスに起因する編集不可等（詳細は公式参照）

決済システムエラーコードは公式「決済システムエラーコード一覧」を参照。

---

## 推奨SSL/TLSバージョン

**TLS 1.2** を推奨。

---

## 関連

- 公式API仕様トップ: https://apispec.billing-robo.jp/
- 当ディレクトリ: [README](README.md)
