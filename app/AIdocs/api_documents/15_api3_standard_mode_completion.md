# API3 標準運用モード 完成のための整理

## 1. 採用案と理由

### 採用: 契約保存後に API3 まで実行する

**理由**

- **自社システムを正本とする**: 契約・明細は自社で作成し、その内容を Billing-Robo に送るだけなので、store 内で API1 → API3 まで実行するのが自然。
- **請求先と請求情報を Billing-Robo に連携する**: 申込保存時に請求先（API1）と請求情報（API3）の両方を送ることで、ロボ側に「誰に・何を請求するか」が揃う。
- **Billing-Robo 側で請求運用を行う**: 請求情報が登録されていれば、ロボ側のスケジュール（API1 の individual に載せた発行日・送付日・決済期限）に従って請求書発行・決済運用が可能になる。

**非採用: 契約保存後は未送信で保持し別サービス/ジョブから API3 を実行する**

- 導線が 2 本になり、運用説明・障害切り分けが重くなる。申込保存という 1 回の操作で「請求先＋請求情報」まで送る方が、方針に沿う。

**注意（未検証）**

- 申込のみフローでは **API2（クレジットカード登録）を実行していない**。Billing-Robo のクレジットシーケンスは API1 → API2 → API3 の順。請求先部署に「デフォルト決済手段」が存在しないと API3 で 1340 になる可能性がある（[14_current_issues.md](14_current_issues.md)）。API2 未実行の状態で API3 を送った場合、1340 等で失敗するかは **未検証**。失敗した場合はログに残し、後日 API2 実行後に API3 を再実行する導線（ジョブや管理機能）で対応する想定。

---

## 2. API3 標準運用モードに必要な追加確認

| 確認項目 | 内容 | 状態 |
|----------|------|------|
| API1 で持たせたスケジュールだけで足りるか | API1 の individual に issue_month, issue_day, sending_*, deadline_* を載せている。API3 の demand にも同じスケジュールを渡しており、請求先側のスケジュールと整合している。請求管理ロボ側が「請求先部署のスケジュール」を請求情報登録時に参照するかは公式仕様要確認。 | 実装済み。仕様上の優先順位は未検証。 |
| API3 に渡すべき項目で不足がないか | buildDemandArray で billing_code, individual, type, goods_name, price, quantity, tax_category, tax, billing_method, start_date, period_format, issue_month/day, sending_*, deadline_*, repetition_*（定期時）を送っている。API3 必須項目は満たしている。 | 不足なし。 |
| API2 が API3 標準運用でも必要かどうか | クレジット決済の場合、公式シーケンスは API1 → API2 → API3。請求先部署にデフォルト決済手段（API2 で登録したカード）が紐付いていないと API3 で 1340 になる可能性あり。申込のみで API2 を実行しない場合、API3 は失敗する可能性あり。 | **API2 はクレジットの場合は必要。申込のみで API3 だけ実行すると 1340 で失敗する可能性は未検証。** |
| 翌月1日決済が成立する条件に不足がないか | BillingScheduleService で「月末5営業日以降」のとき issue_month=1, issue_day=1（翌月1日）を返している。請求管理ロボ側がそのスケジュールで請求書発行・決済期限日を解釈するか、および API2 済みであることが前提かは未検証。 | 未検証。 |

---

## 3. 修正対象ファイル一覧（API3 標準運用モード完成）

### 必須で修正するファイル

| ファイル | 変更内容 |
|----------|----------|
| app/Http/Controllers/ContractController.php | store 内で API1 成功後、api3_standard の契約に対して BillingRoboDemandService::upsertDemandFromContract($contract, $schedule) を実行する。失敗時はログのみで store は成功させる。 |

### あった方がよいが必須ではないファイル

| ファイル | 変更内容 |
|----------|----------|
| 管理画面またはジョブ | API2 未実行で API3 が失敗した契約について、後から「API3 再実行」する導線。 |
| app/Console/Commands/ | API3 未登録の api3_standard 契約に API3 を実行する Artisan コマンド（手動再実行用）。 |

### 追加マイグレーション

- 不要（既存の billing_robo_mode, billing_robo_demands で足りる）。

### 追加テスト

| ファイル | 内容 |
|----------|------|
| tests/Feature/ または tests/Unit/ | 申込保存（store）時に api3_standard 契約で API1 のあと API3 が呼ばれること。API3 失敗時も store が成功すること（モックで API3 を失敗させた場合）。 |

---

## 4. API3 モードでの処理フロー（完成後）

1. ユーザーが申込フォームで「申込を保存」を実行。
2. ContractController::store で契約作成（billing_robo_mode = api3_standard）、ContractItem 作成。
3. Billing-Robo 有効時: BillingScheduleService でスケジュール取得 → API1（請求先＋individual にスケジュール）実行。Contract/Payment に billing_code 等を反映。
4. 続けて、同一契約が api3_standard なら BillingRoboDemandService::upsertDemandFromContract($contract, $schedule) を実行。成功時は billing_robo_demands に保存。
5. API3 が 1340 等で失敗した場合はログに残すのみ。store は成功のままリダイレクト。
6. 後日、API2（カード登録）を別フローで実行したうえで、必要なら API3 を再実行する導線を運用で対応。
