# 環境変数チェックリスト（ドキュメント vs 現在の設定）

ドキュメントで定義されている ID・設定と、`deploy/webroot_billing/app/.env` の照合結果です。

---

## 1. ROBOT PAYMENT（決済ゲートウェイ）

| 変数名 | ドキュメント上の必須 | 説明 | 現在の設定（deploy .env） | 状態 |
|--------|----------------------|------|---------------------------|------|
| `ROBOTPAYMENT_ENABLED` | ○ | 決済を有効にするか | `false` | ⚠️ 決済を使う場合は `true` に変更 |
| `ROBOTPAYMENT_STORE_ID` | ○ | 店舗ID（6桁）。**決済システムCP**（credit.j-payment.co.jp）で確認 | **空** | ❌ **不足** — 決済有効時に必須 |
| `ROBOTPAYMENT_ACCESS_KEY` | △ | API接続用アクセスキー（デモ等で発行時のみ） | 空 | △ デモで案内されていれば設定 |
| `ROBOTPAYMENT_GATEWAY_URL` | △ | 決済送信先URL | 設定済み | ✅ |
| `ROBOTPAYMENT_COMPANY_ID` | △ | 自社DBの company_id（payments 用） | `1` | ✅ |
| `ROBOTPAYMENT_NOTIFY_INITIAL_URL` | ○※ | 初回決済結果通知URL（RP管理画面登録用の参照値） | 空 | ⚠️ 参照用。RP管理画面には別途登録必須 |
| `ROBOTPAYMENT_NOTIFY_RECURRING_URL` | ○※ | 自動課金結果通知URL（同上） | 空 | ⚠️ 同上 |

※ ドキュメント「接続に必要な情報」では必須だが、**.env は参照用**。**実際に決済結果を受け取るには、RP管理画面「決済結果通知設定」に同じURLを登録する必要あり**。

**不足しているID（決済を有効にする場合）**

- **`ROBOTPAYMENT_STORE_ID`**  
  - 決済システムコントロールパネル（https://credit.j-payment.co.jp/cp/SignIn.aspx）にログインし、「店舗ID」を確認。  
  - 請求管理ロボの「API接続編集」には店舗IDは表示されない。  
  - デモ用は ROBOT PAYMENT サポート（support@billing-robo.jp）等で案内された値を使用。

**ローカルで決済を検証する場合**: 上記に加え、**決済システムコントロールパネル**で「決済データ送信元IP」と「決済データ送信元URL」（例: `http://localhost:8080`）の登録が必要。一覧は [13_billing_robo_only_verification.md](13_billing_robo_only_verification.md) の「ROBOT PAYMENT 管理画面で設定する箇所」を参照。

---

## 2. 請求管理ロボ API（API 1〜5）

| 変数名 | ドキュメント上の必須 | 説明 | 現在の設定（deploy .env） | 状態 |
|--------|----------------------|------|---------------------------|------|
| `BILLING_ROBO_BASE_URL` | ○ | API のベースURL（デモ: demo.billing-robo.jp） | `https://demo.billing-robo.jp` | ✅ |
| `BILLING_ROBO_USER_ID` | ○ | 管理画面ログインID（メール形式） | 設定済み | ✅ |
| `BILLING_ROBO_ACCESS_KEY` | ○ | API 用アクセスキー | 設定済み | ✅ |

請求管理ロボ側の設定は **不足なし**。  
※ 送信元IP制限をかけている場合は、サーバの送信元IPを許可リストに登録する必要あり（請求管理ロボの「API接続編集」とは別に、**決済ゲートウェイ側の送信元IP**も登録が必要な場合あり）。

---

## 3. その他（メール・AWS 等）

| 変数名 | 説明 | 現在の設定 | 状態 |
|--------|------|------------|------|
| `MAIL_PASSWORD` | SMTP 認証用パスワード | 空 | ⚠️ メール送信する場合は設定が必要 |
| `AWS_*` | S3 等（未使用なら不要） | 空 | 使用時のみ設定 |

---

## 4. サマリー

| 用途 | 不足している主なID・設定 |
|------|---------------------------|
| **決済なし（申込保存・API 1 のみ）** | なし（請求管理ロボの3項目は設定済み） |
| **決済あり（gateway_token + 通知）** | **`ROBOTPAYMENT_STORE_ID`**（必須）。`ROBOTPAYMENT_ENABLED=true`、必要に応じて `ROBOTPAYMENT_ACCESS_KEY`。通知受信には RP 管理画面への通知URL登録も必須。 |

---

## 5. 参照ドキュメント

- ROBOT PAYMENT 接続: `AIdocs/archive_robotpayment_token_3ds2/payment_integration_robotpayment/接続に必要な情報.md`
- 請求管理ロボ デモ接続: `AIdocs/api_documents/09_demo_connection_billing_robo.md`
- フェーズ3 確認手順: `AIdocs/api_documents/11_phase3_verification_steps.md`
- 設定例: `app/.env.example`
