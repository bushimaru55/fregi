# Billing-Robo 2モード実装 実施報告

## 1. マイグレーション・単体テストの実行結果

### 実行環境について

- **Docker Compose 利用時**: コンテナ内でマイグレーション・テストを実行可能（下記「コンテナでの実行結果」参照）。
- **PHP がホストにない場合**: コンテナを使うか、ローカルに PHP を用意してから以下を実行してください。

### 実行コマンド

```bash
# コンテナ経由（推奨）
docker compose exec app php artisan migrate --force
docker compose exec app ./vendor/bin/phpunit tests/Unit/Services/BillingRobo/ --testdox

# またはホストで（app ディレクトリで）
cd app
php artisan migrate --force
./vendor/bin/phpunit tests/Unit/Services/BillingRobo/ --testdox
```

### コンテナでの実行結果（動作確認時）

- **コンテナ状態**: `docker compose ps -a` で web / app / db / redis がすべて Up。
- **マイグレーション**: `2026_03_11_100001_add_billing_robo_mode_to_contracts_table` を実行済み（DONE）。
- **単体テスト**: BillingRobo 関連 11 件すべて成功（BillingScheduleService 7 件、BillingRoboBillingService 2 件、BillingRoboDemandService 2 件）。
- **Web**: `http://127.0.0.1:8080/` で HTTP 200 応答を確認済み。

### 期待される結果

- **マイグレーション**: `2026_03_11_100001_add_billing_robo_mode_to_contracts_table` が実行され、`contracts.billing_robo_mode` が追加される。
- **テスト**:
  - `BillingScheduleServiceTest`: 月末5営業日判定・getScheduleForDate/ForApplication の 7 件
  - `BillingRoboBillingServiceTest`: buildBillingBody のスケジュールあり/なし 2 件
  - `BillingRoboDemandServiceTest`: buildDemandArray のスケジュールあり/なし 2 件

### 失敗した場合の確認ポイント

- **BillingScheduleServiceTest**: Carbon のタイムゾーン・月末5営業日の集合（2026年3月は 25,26,27,30,31）を確認。
- **BillingRoboBillingServiceTest**: `buildBillingBody` の第2引数に配列を渡したとき `individual` に `issue_month` 等が含まれること。
- **BillingRoboDemandServiceTest**: モックの `ContractToBillingLinesMapper::map` が 1 件返すこと。`buildIndividualSpec` 用に `billing_code` と `billing_individual_number` が契約に設定されていること。

### 修正した内容（テスト・実装側）

- 単体テストは既存実装に合わせて作成済み。PHP 実行不可のため、こちらでは未実行。ローカルで失敗した場合はエラー内容に応じてテストまたは実装を修正してください。

### 最終的に通ったテスト一覧（コンテナ実行済み）

コンテナで実行し、11 件すべて成功しています。

```
BillingScheduleServiceTest
  ✔ Get last 5 business days of march 2026
  ✔ Is within last 5 business days returns true on 25th
  ✔ Is within last 5 business days returns true on 31st
  ✔ Is within last 5 business days returns false on 24th
  ✔ Get schedule for date within last 5 uses end of month
  ✔ Get schedule for date after last 5 uses next month first
  ✔ Get schedule for application uses contract desired start date

BillingRoboBillingServiceTest
  ✔ Build billing body without schedule has no issue month in individual
  ✔ Build billing body with schedule adds schedule to individual

BillingRoboDemandServiceTest
  ✔ Build demand array with schedule uses schedule values
  ✔ Build demand array without schedule uses default schedule
```

---

## 2. API3 標準運用モードの導線（最小変更案）

### 実装内容

- **契約作成元でモードを振り分ける**
  - **申込のみ保存**（`ContractController::store`）: `billing_robo_mode = api3_standard` を設定。請求先登録（API1）時にスケジュール付きで送る。
  - **決済ページから申込＋即時決済**（`RobotPaymentService::executePayment`）: 従来どおり `billing_robo_mode = api5_immediate` を設定。

### API3 モードの作成条件

| 条件 | 設定されるモード |
|------|------------------|
| 申込フォームで「申込を保存」のみ実行（契約＋明細のみ、決済しない） | `api3_standard` |
| 決済ページを経由してカード決済まで実行 | `api5_immediate` |

### 追加・修正したファイル

1. **app/Http/Controllers/ContractController.php**
   - `Contract::create` に `'billing_robo_mode' => Contract::BILLING_ROBO_MODE_API3_STANDARD` を追加。
   - 請求管理ロボ有効時、API1 呼び出しで `BillingScheduleService::getScheduleForApplication($contract)` を取得し、`upsertBillingFromContract($contract, $schedule)` に渡すように変更。
   - **API3 標準運用モード完成**: API1 成功後、`api3_standard` の契約に対して `BillingRoboDemandService::upsertDemandFromContract($contract, $schedule)` を実行。成功時はログ「請求管理ロボ API 3 請求情報登録完了（申込保存時）」、失敗時はログのみで store は成功のまま。
   - `use App\Services\BillingRobo\BillingRoboDemandService` を追加。

2. **AIdocs/api_documents/15_api3_standard_mode_completion.md**（新規）
   - 採用案（契約保存後に API3 まで実行する）と理由、API3 標準運用の追加確認一覧、修正対象ファイル一覧、API3 モード処理フローを記載。

---

## 3. 報告事項

### 採用した実装方式

- **契約保存後に API3 まで実行する**（store 内で API1 成功後、続けて API3 を呼ぶ）。
- 理由: 自社正本・請求先と請求情報を Billing-Robo に連携・ロボ側で請求運用、という方針に沿い、申込保存という 1 回の操作で「請求先＋請求情報」まで送るため。詳細は [15_api3_standard_mode_completion.md](api_documents/15_api3_standard_mode_completion.md) を参照。

### API3 モードでの処理フロー（完成後）

1. ユーザーが申込フォームで「申込を保存」を実行。
2. `ContractController::store` で契約作成（`billing_robo_mode = api3_standard`）、ContractItem 作成。
3. Billing-Robo 有効時: スケジュール取得 → **API1**（請求先＋individual にスケジュール）実行。Contract/Payment に billing_code 等を反映。
4. 続けて、同一契約が `api3_standard` なら **API3**（`BillingRoboDemandService::upsertDemandFromContract`）を実行。成功時は `billing_robo_demands` に保存。
5. API3 が 1340 等で失敗した場合はログに残すのみ。store は成功のままリダイレクト。後日 API2 実行後に API3 再実行する導線は別途検討。

### API5 モードの既存挙動への影響

- **影響なし。**
  - 決済ページからの申込は従来どおり `RobotPaymentService::executePayment` で契約作成時に `billing_robo_mode = api5_immediate` を設定している。
  - Billing-Robo 有効時は `BillingRoboExecutionService::executeForContract` が `contract->isBillingRoboApi5Immediate()` で分岐し、API5 のときは API1→2→5 のまま。

### API3 モードの作成条件（まとめ）

- **ContractController::store**（申込保存のみ）で作成された契約は `api3_standard`。
- 上記契約では、保存処理内で **API1（スケジュール付き）→ API3** まで実行する。API2 は申込のみフローでは実行しない（クレジットの場合は API2 未実行で API3 を送ると 1340 で失敗する可能性あり・未検証）。

### 未検証・未実行の確認事項

- **API2 未実行で API3 を送った場合の 1340 の有無**: 申込のみでは API2 を実行しないため、API3 が 1340 で失敗するかは未検証。失敗時はログのみで、後日 API3 再実行導線で対応想定。
- **翌月1日決済が成立する条件**: 請求管理ロボ側のスケジュール解釈は未検証。
- **マイグレーション・単体テスト**: コンテナで実行済み（成功）。再実行する場合は「1. マイグレーション・単体テストの実行結果」のコマンドを参照。

### 動作確認のためのコンテナ・URL

- **コンテナ**: `docker compose ps -a` で web / app / db / redis が Up であること。止まっている場合は `docker compose up -d`。
- **ベースパス**: Nginx は `/billing/` で Laravel に転送します。**URL は必ず `/billing/` を含めてください。**
- **申込フォーム（トップ）**: http://127.0.0.1:8080/billing/ または http://localhost:8080/billing/
- **管理画面**: http://127.0.0.1:8080/billing/admin/dashboard（要ログイン）。申し込み一覧は http://127.0.0.1:8080/billing/admin/contracts 。
- **マイグレーション再実行**: `docker compose exec app php artisan migrate --force`
- **BillingRobo 単体テスト**: `docker compose exec app ./vendor/bin/phpunit tests/Unit/Services/BillingRobo/ --testdox`

### 手動確認が必要な箇所

1. **マイグレーション**
   - コンテナで `php artisan migrate` を実行し、`contracts.billing_robo_mode` が存在することを確認（実行済みの場合はスキップされる）。

2. **単体テスト**
   - 上記 3 テストクラスをコンテナで実行し、すべて成功することを確認（実行済み）。

3. **申込のみ保存フロー（API3 モード）**
   - 申込フォームで「申込を保存」のみ行い、DB で当該契約の `billing_robo_mode` が `api3_standard` であること。
   - 同じタイミングで API1 が呼ばれ、続けて API3 が呼ばれること。ログに「請求管理ロボ API 1 請求先登録完了（申込保存時）」および「請求管理ロボ API 3 請求情報登録完了（申込保存時）」が出ること（API3 が 1340 で失敗する場合は「API 3 失敗」の warning ログを確認）。
   - `billing_robo_demands` に当該契約の請求情報が保存されていること（API3 成功時）。

4. **決済ページフロー（API5 モード）**
   - 決済ページからカード情報を入力して申込完了まで行い、契約の `billing_robo_mode` が `api5_immediate` のままであること。
   - API1→2→5 が従来どおり動作し、即時決済が完了すること。
