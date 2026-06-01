# 本番デプロイ手順（2026-06-01）

**前提**: Plesk / **SSH・`php artisan` 不可** / FTP のみ  
**デプロイ元**: `deploy/webroot_billing/`  
**アップロード先**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`

---

## 1. 今回の変更概要

| 区分 | 内容 |
| --- | --- |
| 請求先部署・宛名1 | API 1 の `address1` を **「会社名＋御中」** に変更（従来: 担当者名＋任意で部署名） |
| DB | **変更不要**（`site_settings` の更新不要） |
| フロント | **変更不要**（Vite 再ビルド不要） |

**影響**: 本デプロイ後に **新規申込**（または請求管理ロボ API 1 が再実行される更新）から反映。既存の請求先部署は自動では変わらない。

---

## 2. FTP アップロード一覧（差分のみ）

### 2.1 必須（今回）

```
app/app/Services/BillingRobo/BillingRoboBillingService.php
```

ローカル: `deploy/webroot_billing/app/app/Services/BillingRobo/BillingRoboBillingService.php`  
本番: `.../webroot/billing/app/app/Services/BillingRobo/BillingRoboBillingService.php`

### 2.2 アップロードしないもの

- `app/.env`（上書きしない）
- `app/storage/logs/`・セッション
- `build/`（今回変更なし）

### 2.3 前回デプロイ未反映の場合

前回手順（`deploy/AIdocs/deploy_runbook_20260525.md`）の 2.1・2.2 をあわせて反映してください。

---

## 3. デプロイ後作業（SSH 不要）

### 3.1 Blade / OPcache 反映

FTP で次を削除（存在するもののみ）:

```
app/storage/framework/views/*.php
```

任意（設定が古い場合）:

```
app/bootstrap/cache/config.php
```

`artisan view:clear` の代替。

---

## 4. 動作確認

| # | 操作 | 期待 |
| --- | --- | --- |
| 1 | テスト申込 → 決済完了（請求管理ロボ API 1 成功） | 請求先部署の **宛名1** = `申込会社名` + `御中`（例: `株式会社サンプル御中`） |
| 2 | 請求先名・部署名 | 請求先名 = 会社名、部署名 = 部署 or 会社名（従来どおり） |
| 3 | 申込フォーム | 担当者名はフォーム・メールに表示されるが、宛名1には入らない |

**確認画面**: 請求管理ロボ → 請求先 → 請求先部署 → 宛名1

---

## 5. 既存請求先の宛名1を直す場合

- 請求管理ロボ管理画面で手修正、または
- 当システムから API 1 更新が走る操作（再登録・管理画面からの再連携がある場合）

通常の FTP のみでは既存レコードは更新されません。

---

## 6. ロールバック

1. FTP でアップロード前の `BillingRoboBillingService.php` に戻す  
2. `app/storage/framework/views/*.php` を削除  

旧ロジック（宛名1 = 担当者名 + 任意 `(部署名)`）に戻ります。
