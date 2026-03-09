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

## ROBOT PAYMENT なしでできること・できないこと

| 機能 | 請求管理ロボのみで実現可能か | 備考 |
|------|------------------------------|------|
| 請求先登録（API 1） | ✅ 可能 | 申込保存時に API 1 のみ実行。決済手段は「クレジットカード（あとで登録）」の枠だけ作成される。 |
| 請求情報登録（API 3）・請求書発行（API 4） | ✅ 可能 | 決済手段を銀行振込等にすれば、ROBOT PAYMENT 不要で請求書まで発行できる。 |
| 即時決済（API 5） | △ 決済手段による | クレジットの即時決済は、**あらかじめカードが登録済み**である必要がある。 |
| クレジットカード登録（API 2）・初回カード決済 | ❌ 不可 | トークン取得に **決済システム（ROBOT PAYMENT）の JavaScript（CPToken.js）** が必須。請求管理ロボ API 2 は「トークンを受け取って登録する」だけなので、トークン取得元がなければカード登録も初回決済もできない。 |

**まとめ**: 請求先登録・請求情報・請求書発行まででよく、クレジット決済を使わない（銀行振込・後払い等）なら **請求管理ロボ API のみで機能実現可能**。**クレジットカードの申込時決済やカード登録**を行う場合は、トークン発行のため **ROBOT PAYMENT（credit.j-payment.co.jp）との連携が必須**。

---

## ローカル環境での ROBOT PAYMENT 連携（可能です）

**ローカル環境（Docker / localhost）からでも ROBOT PAYMENT 連携は可能**です。以下の 2 点を **決済システムコントロールパネル**（credit.j-payment.co.jp）で設定すれば、トークン取得〜ゲートウェイ送信までローカルで検証できます。

| 設定項目 | 設定内容 | 備考 |
|----------|----------|------|
| **決済データ送信元IP** | ローカル環境の**送信元IP**（例: Docker の場合は `docker compose exec app curl -s -4 ifconfig.me` で確認した値） | 未設定だと ER003（送信元IPの認証に失敗しました）になる。 |
| **決済データ送信元URL**（リファラー） | `http://localhost:8080` または `http://127.0.0.1:8080`（実際にブラウザで開くオリジンに合わせる） | 「PC用決済フォーム設定」内。未設定だと「店舗設定からリファラーURLを設定してください」になる。**localhost の登録は公式ドキュメントで案内されている**。 |

- 設定場所: **決済システムコントロールパネル**（https://credit.j-payment.co.jp/cp/SignIn.aspx）→「設定」→「決済システム」→「決済ゲートウェイ＆CTI決済設定」（送信元IP）／「PC用決済フォーム設定」（送信元URL）。
- デモ契約で credit.j-payment.co.jp にログインできない場合は、ROBOT PAYMENT サポート（support@billing-robo.jp）に「送信元IPの登録」と「ローカル開発用の決済データ送信元URL（例: http://localhost:8080）の登録」を依頼する。
- **決済結果通知**だけは、localhost はインターネットから叩けないため **ngrok 等で公開URLを用意**するか、通知の検証はステージングで行う。

**詳細手順**: `AIdocs/archive_robotpayment_token_3ds2/payment_integration_robotpayment/デモアカウント_ローカル開発テスト.md`（2.4 決済データ送信元URL、2.2 通知URL 等）を参照。

---

## ROBOT PAYMENT（テスト環境）管理画面で設定する箇所

以下は **決済システムコントロールパネル**（https://credit.j-payment.co.jp/cp/SignIn.aspx）の「設定」→「決済システム」で行う設定です。請求管理ロボ（billing-robo.jp）の画面とは別です。

| # | 設定項目 | 画面での場所 | 設定内容 | 必須 |
|---|----------|--------------|----------|------|
| 1 | **決済データ送信元IP** | 決済ゲートウェイ＆CTI決済設定 | アプリが稼働するサーバ（またはローカル Docker）の**送信元IPv4**。未設定だと ER003。 | ✅ 決済実行する場合 |
| 2 | **決済データ送信元URL** | PC用決済フォーム設定 | 決済ページを表示する**ブラウザのオリジン**。**メインの「決済データ送信元URL」が優先チェックされる**（「複数決済データ送信元URL」だけでは不十分）。ローカル検証時はメインURLを `http://localhost:8080` に変更すること。本番では `https://billing-robo.jp/` に戻す。 | ✅ カード入力〜トークン取得する場合 |
| 3 | **店舗ID** | 画面上部などに表示 | 6桁の店舗ID。`.env` の `ROBOTPAYMENT_STORE_ID` に同じ値を設定する。 | ✅ |
| 4 | 決済結果通知URL（初回・自動課金） | 請求管理ロボ側の「決済結果通知設定」で登録 | 本システムの `/api/robotpayment/notify-initial` 等の**絶対URL**。ローカルは ngrok で公開URLを用意するか、通知はステージングで検証。 | 通知を受ける場合 |

**ローカルで決済まで検証するとき**: 上記 1・2・3 を設定すれば、トークン取得〜ゲートウェイ送信まで可能。4 は省略可（通知だけ別環境で検証）。

**リファラーエラーが出たとき**: トークン作成失敗時にログに `page_origin` が記録される。`php artisan robotpayment:show-test-log --lines=30` で確認し、表示されたオリジンを「**決済データ送信元URL（メイン）**」に設定する。ROBOT PAYMENT の仕様上、メインURLが優先チェックされ、「複数決済データ送信元URL」だけでは通らない。本番デプロイ時はメインURLを `https://billing-robo.jp/` に戻すこと。

---

## 参照

- 詳細な確認手順（パターンA/B）: [11_phase3_verification_steps.md](11_phase3_verification_steps.md)
- 環境変数チェックリスト: [12_env_checklist.md](12_env_checklist.md)
- 決済手段別シーケンス: [01_billing_robo_api_sequence.md](01_billing_robo_api_sequence.md)
