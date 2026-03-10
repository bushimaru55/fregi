# ER584 原因特定のための詳細ログ設計（5名協議結果）

## 協議の前提

- **目的**: 次の1回の検証実行で、ER584 の原因をログから一意に特定できるようにする。
- **出力**: 1回の決済試行につき **1本の `[ER584_DEBUG]` ログ** に必要な情報を集約する。
- **秘密情報**: トークン本体・カード番号・アクセスキーは一切出力しない。

---

## 5名の観点と要求ログ項目

### E1（フロント／トークンライフサイクル）

- **懸念**: トークン二重利用・経過時間・フロントで渡している aid/金額が本当に正しいか。
- **要求項目**:
  - `correlation_id`: 1リクエストを一意に識別
  - `token_created_ms`: フロントでトークン取得した時刻（epoch ms）
  - `token_age_ms`: サーバ受信時点での経過ミリ秒（15分超過の有無）
  - `token_hash_prefix`: トークン SHA-256 の先頭12文字（同一トークン再利用の有無）
  - `duplicate_detected`: 同一トークンで2回目送信か
  - `frontend`: `aid`, `am`, `tx`, `sf`, `use_zero_amount`, `em_len`, `pn_len`（3DS／API2と整合するか）

### E2（API2／請求管理ロボ連携）

- **懸念**: 請求管理ロボに送っている内容と、返ってきたエラーが仕様と合っているか。
- **要求項目**:
  - `api2_request`: `billing_code`, `payment_method_number` or `payment_method_code`, `email`（マスク）, `tel_len`
  - `api2_response`: `http_status`, `error_code`, `error_message`, `body` 要約
  - `api2_url`: 実際に叩いた URL（環境違いの有無）

### E3（トークン形式／仕様）

- **懸念**: トークンが「決済システムから発行された [tkn]」として正しい形式か。
- **要求項目**:
  - `token_len`
  - `token_looks_base64`: トークンが Base64 として妥当な文字のみか
  - `token_trimmed`: 前後の空白が付与されていないか（送信前の trim 有無）

### E4（環境／設定）

- **懸念**: 店舗ID・ベースURL・送信元が本番／デモと一致しているか。
- **要求項目**:
  - `env`: `store_id`, `billing_robo_base_url`（ホストまで、パスなし）
  - `request_ip`: 送信元IP（請求管理ロボ／ROBOT PAYMENT の許可IPと照合用）
  - `user_agent`: ブラウザ種別（必要時の再現用）

### E5（相関／タイミング）

- **懸念**: どの契約・どのタイミングで失敗したかを一意に追えるか。
- **要求項目**:
  - `contract_id`, `billing_code`
  - `received_at_ms`: サーバが決済実行を受付けた時刻（epoch ms）
  - 上記の `token_created_ms` / `token_age_ms` と合わせて時系列を復元可能にする

---

## 合意した 1 本化スキーマ

決済実行で **API2 が失敗したときだけ**、`contract_payment` チャネルに次の1件を出力する。

```json
{
  "message": "[ER584_DEBUG]",
  "context": {
    "correlation_id": "pay_xxxx",
    "contract_id": 24,
    "token": {
      "length": 88,
      "age_ms": 1234,
      "hash_prefix_12": "abc123def456",
      "duplicate_detected": false,
      "looks_base64": true,
      "trimmed": true
    },
    "frontend": {
      "aid": "133732",
      "am": 0,
      "tx": 0,
      "sf": 0,
      "use_zero_amount": true,
      "em_len": 28,
      "pn_len": 10
    },
    "api2_request": {
      "billing_code": "BC00000024",
      "payment_method_number": 1,
      "payment_method_code": null,
      "email_preview": "kou***",
      "tel_len": 10
    },
    "api2_response": {
      "http_status": 200,
      "error_code": "ER584",
      "error_message": "Credit card payment error."
    },
    "env": {
      "store_id": "133732",
      "billing_robo_base_url": "https://demo.billing-robo.jp"
    },
    "request": {
      "ip": "192.168.65.1",
      "user_agent": "Mozilla/5.0..."
    },
    "received_at_ms": 1234567890123
  }
}
```

- **検索**: ログで `[ER584_DEBUG]` を grep すれば、その1行（または直近数行）で原因切り分けに必要な情報が揃う。
- **秘密**: トークン本体・メール全文・アクセスキーは含めない。

---

## 実装方針

1. **Controller**: `correlation_id` 生成、`token_created_ms` / `token_age_ms` / `token_hash_prefix` / `duplicate_detected` 算出、`request_ip` / `user_agent` を `debug_context` に詰め、`executePayment` に渡す。
2. **RobotPaymentService**: `executePayment` の第3引数で `debug_context` を受け取り、`BillingRoboCreditCardService::registerToken` に渡す。
3. **BillingRoboCreditCardService**: API2 失敗時に `debug_context` と API2 リクエスト／レスポンス・契約情報をマージし、上記スキーマで **1 回だけ** `[ER584_DEBUG]` を出力する。
4. **フロント**: 既存の `token_created_ms` 送信を維持。必要なら `aid`/`am`/`tx`/`sf`/`use_zero_amount`/`em_len`/`pn_len` を hidden で送り、サーバ側で `frontend` に含める（またはサーバで計算可能なものはサーバのみで記録）。

---

## 参照

- ER584/ER585 公式説明（同一トークン二重利用・15分超過・店舗ID不一致・トークン形式）
- 04_api_02_credit_card_token.md
- 13_billing_robo_only_verification.md
