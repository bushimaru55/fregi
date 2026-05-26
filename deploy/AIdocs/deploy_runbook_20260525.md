# 本番デプロイ手順（2026-05-25）

**前提**: Plesk / **SSH・`php artisan` 不可** / FTP のみ  
**デプロイ元**: `deploy/webroot_billing/`  
**アップロード先**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`

---

## 1. 今回の変更概要

| 区分 | 内容 |
| --- | --- |
| 請求サイクル・売上計上日 | 月末5営業日ルール、`start_date` を課金開始月1日に統一（`549b8a1`, `7bdda99`） |
| 表示文言 | 「ご利用URL・ドメイン」→「ウェブサイトのURL」 |
| フロント | Vite 再ビルド（`build/manifest.json`, `build/assets/app-YYxrbjWZ.css`） |

※ 住所必須・決済「戻る」・カード名義プレースホルダは本番反映済みの想定。未反映なら下記「申込・決済まわり」もアップロード。

---

## 2. FTP アップロード一覧（差分）

### 2.1 必須（今回）

```
build/manifest.json
build/assets/app-YYxrbjWZ.css
build/assets/app-OANExTJh.js

app/resources/views/contracts/create.blade.php
app/resources/views/contracts/confirm.blade.php
app/app/Http/Requests/ContractRequest.php
app/resources/views/admin/contracts/show.blade.php
app/resources/views/admin/contracts/edit.blade.php
app/resources/views/emails/contract-notification.blade.php
app/resources/views/emails/contract-reply.blade.php

app/app/Services/BillingRobo/BillingScheduleService.php
app/app/Services/BillingRobo/BillingRoboDemandService.php
app/app/Services/BillingRobo/BillingRoboBulkRegisterService.php
app/app/Services/RobotPayment/PurchasePatternService.php
app/app/Http/Controllers/Admin/SiteSettingController.php
app/resources/views/admin/site-settings/edit-billing-cycle.blade.php
app/config/app.php
app/database/migrations/2026_05_22_000001_fix_inverted_billing_cycle_schedule_setting.php
app/database/migrations/2026_05_22_000002_normalize_billing_cycle_schedule_to_negative_offset.php
```

ローカル上のパスはすべて `deploy/webroot_billing/` 配下。  
例: `deploy/webroot_billing/build/manifest.json` → 本番 `.../webroot/billing/build/manifest.json`

### 2.2 本番に未反映の場合のみ（申込・決済まわり）

```
app/resources/views/contracts/payment.blade.php
app/app/Http/Controllers/ContractController.php
```

### 2.3 アップロードしないもの

- `app/storage/framework/views/`（コンパイル済みキャッシュ。本番で削除して再生成）
- `app/.env`（本番専用。上書きしない）
- `app/storage/logs/` などログ・セッション

### 2.4 本番から削除してよい旧 CSS（任意）

以前の manifest が `app-CaseE0Pu.css` 等の場合、FTP で `build/assets/` 内の**参照されていない旧ファイル**を削除してよい（必須ではない）。

---

## 3. デプロイ後作業（SSH 不要）

### 3.1 Blade キャッシュ削除

FTP で次を削除（存在するもののみ）:

```
app/storage/framework/views/*.php
```

`artisan view:clear` の代替。

### 3.2 請求サイクル設定（phpMyAdmin）

`migrate` は実行しない。次の SQL で現状確認:

```sql
SELECT `key`, value_text, updated_at
FROM site_settings
WHERE `key` = 'billing_cycle_schedule';
```

**旧スキーマ**（反転前）の例:

```json
{"within":{"issue_month":0,"issue_day":99,...},"after":{"issue_month":1,...}}
```

**中間スキーマ**（549b8a1 時点）の例:

```json
{"within":{"issue_month":1,...},"after":{"issue_month":0,...}}
```

上記いずれかと**完全一致**する場合のみ、次で更新:

```sql
UPDATE site_settings
SET
  value_text = '{"within":{"issue_month":-1,"issue_day":99,"sending_month":-1,"sending_day":99,"deadline_month":0,"deadline_day":1},"after":{"issue_month":-1,"issue_day":99,"sending_month":-1,"sending_day":99,"deadline_month":0,"deadline_day":1}}',
  updated_at = NOW()
WHERE `key` = 'billing_cycle_schedule'
  AND (
    value_text = '{"within":{"issue_month":0,"issue_day":99,"sending_month":0,"sending_day":99,"deadline_month":1,"deadline_day":1},"after":{"issue_month":1,"issue_day":99,"sending_month":1,"sending_day":99,"deadline_month":2,"deadline_day":1}}'
    OR value_text = '{"within":{"issue_month":1,"issue_day":99,"sending_month":1,"sending_day":99,"deadline_month":2,"deadline_day":1},"after":{"issue_month":0,"issue_day":99,"sending_month":0,"sending_day":99,"deadline_month":1,"deadline_day":1}}'
  );
```

手動編集済みの値は上書きされない（WHERE 条件により 0 件更新）。

未設定の場合は INSERT 不要（コード側の既定値が使われる）。

### 3.3 設定キャッシュ（任意）

反映が怪しい場合、FTP で削除:

```
app/bootstrap/cache/config.php
```

---

## 4. 動作確認

| # | URL / 操作 | 期待 |
| --- | --- | --- |
| 1 | https://dschatbot.ai/webroot/billing/build/manifest.json | 200、`app-YYxrbjWZ.css` を参照 |
| 2 | https://dschatbot.ai/webroot/billing/ | 「ウェブサイトのURL」表示 |
| 3 | テスト申込（月末5営業日以前） | 請求管理ロボ: 発行=申込月末、決済=翌月1日、売上計上=翌月1日 |
| 4 | 管理画面 請求サイクル設定 | 発行=前月/末日、決済=当月/1日 |

---

## 5. ロールバック

- **ファイル**: FTP でアップロード前のバックアップに戻す
- **DB**: `site_settings` の `value_text` を手順 3.2 実行前の値に UPDATE
