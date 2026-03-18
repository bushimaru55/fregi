# 決済エラー特定のためのログ設計（5名協議メモ）

## 目的
決済エラーが**どこで**（どのステージで）発生したかを、エラーログから漏れなく・詳細に特定できるようにする。

---

## エンジニア協議の結論

### Engineer A（バックエンド）
- **相関IDの一貫付与**: 決済実行リクエスト受付時に `correlation_id` を1つ発行し、Controller → RobotPaymentService → BillingRoboExecutionService → 各API呼び出しまで**同じIDを引き回す**。ログには必ず `correlation_id` と **stage（ステージコード）** を含める。
- **ステージコード**: フロー上の「区切り」ごとに短いコード（例: `PAY_EXEC_ENTRY`, `PAY_API1_FAIL`）を定義し、grep で「どの段階で失敗したか」を一発で絞り込めるようにする。

### Engineer B（インフラ・運用）
- **出力先**: 既存の `contract_payment` ログチャンネル1本に集約。日付ローテーション・保持日数は現行どおり。
- **フォーマット**: 1行目に `[PAY][{stage}][{correlation_id}]` を付与し、同一リクエストのログを `correlation_id` で追えるようにする。構造化コンテキスト（JSON 相当）は Laravel の context 配列で記録し、ログファイルにそのまま出る形式を利用。

### Engineer C（フロントエンド）
- **クライアント起因のエラー**: CPToken 失敗（ER002 等）・3DS 失敗は、既存の「トークン作成失敗報告」エンドポイントでサーバーに送る。送信ペイロードに **stage**（例: `TOKEN_CREATE`, `3DS_AUTH`）と **resultCode / errMsg** を含め、サーバー側で `contract_payment` に同じフォーマットで記録する。
- サーバーからクライアントへ correlation_id を渡すのは今回は見送り（フォーム送信前はリクエストが無いため）。代わりに「トークン作成失敗」ログには `page_origin` と時刻で紐付ける。

### Engineer D（QA）
- **ステージ一覧と grep 例**をドキュメント化する。障害切り分け時に「このステージで止まった」とすぐ判断できるようにする。
- 本番では **error / warning レベル** と **stage コード** でフィルタし、アラートやダッシュボードと連携しやすくする。

### Engineer E（リード・セキュリティ）
- **機密情報は絶対にログに出さない**: トークン全文・カード番号・CVV は記録しない。既存と同様、`token_preview`（先4桁…後4桁）・`last4` 程度に留める。
- 新規ロガーは **決済フロー専用** とし、既存の `Log::channel('contract_payment')` の利用箇所は残しつつ、**エラー時およびフロー区切り**で必ず **PaymentStageLogger** 経由のステージ付きログを追加する。

---

## ステージコード一覧

| ステージコード | 説明 | 主な発生箇所 |
|----------------|------|-----------------------------|
| `PAY_EXEC_ENTRY` | 決済実行コントローラ受付 | RobotPaymentController::execute |
| `PAY_NO_SESSION` | 申込セッションなし | Controller |
| `PAY_NO_TOKEN` | トークン未送信 | Controller |
| `PAY_EXEC_SUCCESS` | 決済実行成功 | Controller |
| `PAY_EXEC_FAIL` | 決済実行失敗（サービス戻り） | Controller |
| `PAY_EXEC_EXCEPTION` | 決済実行中に例外 | Controller |
| `PAY_SVC_ENTRY` | 決済サービス開始 | RobotPaymentService |
| `PAY_SVC_CONTRACT_CREATED` | 契約・Payment 作成済み | RobotPaymentService |
| `PAY_SVC_BILLING_ROBO` | 請求ロボ連携分岐 | RobotPaymentService |
| `PAY_SVC_GATEWAY_SEND` | RP gateway 送信 | RobotPaymentService |
| `PAY_SVC_GATEWAY_OK` | RP gateway 成功 | RobotPaymentService |
| `PAY_SVC_GATEWAY_FAIL` | RP gateway 失敗 | RobotPaymentService |
| `PAY_API1_START` | 請求管理ロボ API1 開始 | BillingRoboBillingService |
| `PAY_API1_OK` | API1 成功 | BillingRoboBillingService |
| `PAY_API1_FAIL` | API1 失敗 | BillingRoboBillingService |
| `PAY_API2_START` | 請求管理ロボ API2 開始 | BillingRoboCreditCardService |
| `PAY_API2_OK` | API2 成功 | BillingRoboCreditCardService |
| `PAY_API2_FAIL` | API2 失敗 | BillingRoboCreditCardService |
| `PAY_API5_START` | 請求管理ロボ API5 開始 | BillingRoboBulkRegisterService |
| `PAY_API5_OK` | API5 成功 | BillingRoboBulkRegisterService |
| `PAY_API5_FAIL` | API5 失敗 | BillingRoboBulkRegisterService |
| `PAY_API3_START` | 請求管理ロボ API3 開始 | BillingRoboDemandService |
| `PAY_API3_OK` | API3 成功 | BillingRoboDemandService |
| `PAY_API3_FAIL` | API3 失敗 | BillingRoboDemandService |
| `PAY_CLIENT_TOKEN_FAIL` | クライアント: トークン作成失敗 | logTokenCreateFailed |
| `PAY_CLIENT_3DS_FAIL` | クライアント: 3DS 認証失敗 | logTokenCreateFailed（拡張） |

---

## ログフォーマット

- **メッセージ**: `[PAY][{stage}][{correlation_id}] {短い説明}`  
  - correlation_id が無い場合（クライアント報告など）は `[PAY][{stage}][-]` とする。
- **context**: Laravel の Log の context 配列。  
  - 例: `contract_id`, `payment_id`, `correlation_id`, `stage`, `error`, `http_status`, `response_preview` など。  
  - トークン・カード番号は含めない。

---

## 運用・切り分け

- **エラー発生ステージの特定**:  
  `grep 'PAY_.*_FAIL\|PAY_EXEC_EXCEPTION\|PAY_CLIENT_' storage/logs/contract-payment-*.log`
- **特定 correlation_id の追跡**:  
  `grep 'pay_xxxx' storage/logs/contract-payment-*.log`
- **直近の決済エラーのみ**:  
  `grep '\[PAY\].*FAIL\|EXCEPTION\|NO_SESSION\|NO_TOKEN' storage/logs/contract-payment-$(date +%Y-%m-%d).log`

---

## 実装方針

1. **PaymentStageLogger** クラスを新設し、`stage` と `correlation_id` を付与したログを `contract_payment` チャンネルに書く。
2. Controller / RobotPaymentService / BillingRoboExecutionService / BillingRoboBillingService / BillingRoboCreditCardService / BillingRoboBulkRegisterService / BillingRoboDemandService の各所で、**エラー時および重要な区切り**で `PaymentStageLogger::log(...)` を呼ぶ。
3. クライアント報告エンドポイントで、ペイロードに `stage` と `result_code` を受け取り、`PAY_CLIENT_*` でログに記録する。
4. 既存の `Log::channel('contract_payment')` は残し、**追加**でステージ付きログを入れる（既存メッセージの置き換えは最小限）。

以上、5名協議の結果として本設計で実装する。

---

## 実装サマリ

- **PaymentStageLogger**: `App\Logging\PaymentStageLogger` — ステージ定数と `log` / `info` / `warning` / `error` を提供。出力先は `contract_payment` チャンネル。
- **組み込み箇所**: `RobotPaymentController::execute`、`RobotPaymentService::executePayment`、`BillingRoboExecutionService::executeForContract`。クライアント報告は `logTokenCreateFailed` で `stage` / `result_code` を受け取り `PAY_CLIENT_TOKEN_FAIL` / `PAY_CLIENT_3DS_FAIL` を記録。
- **ログ確認**: `storage/logs/contract-payment-YYYY-MM-DD.log`。直近の決済フロー確認には `php artisan contract-payment:show-test-log`（既存）の利用を推奨。
