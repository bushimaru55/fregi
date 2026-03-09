# 請求管理ロボ API のみの検証手順

ROBOT PAYMENT（決済ゲートウェイ）は使わず、**請求管理ロボ側の API 連携だけ**を確認する手順です。

---

## 構築済みの請求管理ロボ連携

| API | 役割 | 呼び出しタイミング |
|-----|------|---------------------|
| **API 1** 請求先登録更新 | 契約情報を請求管理ロボに登録し、billing_code / cod 等を取得 | 申込保存時（`ContractController::store`）、または決済実行時（`RobotPaymentService::executePayment`） |
| **API 2** クレジットカード登録 | 決済成功後にカードトークンを請求管理ロボに登録 | 決済ゲートウェイ成功後のみ（ROBOT PAYMENT 利用時） |

- **API 3〜5**（請求書・消込等）は別フェーズで実装する想定です。

---

## 前提: .env の設定

`app/.env` に次が設定されていること。

- `BILLING_ROBO_BASE_URL=https://demo.billing-robo.jp`
- `BILLING_ROBO_USER_ID=`（管理画面ログインID・メール形式）
- `BILLING_ROBO_ACCESS_KEY=`（API用アクセスキー）

決済は使わないため、次で問題ありません。

- `ROBOTPAYMENT_ENABLED=false`

---

## 検証手順（API 1 のみ）

1. **疎通確認**
   ```bash
   cd /path/to/billing
   docker compose run --rm app php artisan billing-robo:ping
   ```
   - 「疎通成功（HTTP 200）」であること。

2. **申込フォームから送信**
   - ブラウザで `http://localhost:8080/billing/contract/create` を開く。
   - 必須項目を入力 → 確認画面へ → **申込内容を送信**。
   - `ROBOTPAYMENT_ENABLED=false` のため、決済ページには行かず **完了ページ** に遷移する。

3. **API 1 の結果をログで確認**
   ```bash
   docker compose run --rm app php artisan robotpayment:show-test-log --lines=80
   ```
   - **成功**: `請求管理ロボ API 1 請求先登録完了（申込保存時）` と `billing_code` が出ていること。
   - **失敗**: `請求管理ロボ API 1 失敗（申込保存時）` または `請求管理ロボ API 1 例外（申込保存時）` の `error` / `message` を確認。

4. **DB で請求先の反映を確認**
   ```bash
   docker compose exec db mysql -u billing -pbilling_pass billing -e "
   SELECT id, billing_code, billing_individual_number, billing_individual_code FROM contracts ORDER BY id DESC LIMIT 3;
   "
   ```
   - 直近の契約で `billing_code` に値（例: BC00000009）が入っていれば API 1 成功。

---

## 請求管理ロボ管理画面での確認

- 管理画面（例: https://demo.billing-robo.jp）にログインし、**請求先一覧**で、上記 `billing_code` または契約の会社名・部署で検索し、登録内容が一致していることを確認する。

---

## 参照

- 詳細な確認手順（パターンA/B）: [11_phase3_verification_steps.md](11_phase3_verification_steps.md)
- 環境変数チェックリスト: [12_env_checklist.md](12_env_checklist.md)
