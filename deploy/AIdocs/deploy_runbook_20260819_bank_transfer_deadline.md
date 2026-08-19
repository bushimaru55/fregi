# 本番デプロイ手順（2026-08-19 hotfix）— 銀行振込の支払期限を翌月末に

**前提**: Plesk / **SSH・`php artisan` 不可** / コードは FTP のみ  
**デプロイ元**: `deploy/webroot_billing/`（ローカル開発本体は `app/`。本番投入は必ずこちら）  
**アップロード先ルート**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`  
**公開URL**: `https://dschatbot.ai/webroot/billing`

関連:
- 運用ルール: [deploy_rules.md](deploy_rules.md)
- ファイル一覧: [deploy_files_20260819_bank_transfer_deadline.txt](../deploy_files_20260819_bank_transfer_deadline.txt)

---

## 0. 今回の範囲

| 項目 | 内容 |
| --- | --- |
| 変更前（銀行振込） | 発行 7/31 のとき支払期限が **8/1**（課金開始月の1日） |
| 変更後（銀行振込） | 発行 7/31 のとき支払期限が **8/31**（課金開始月の末日） |
| 最終5営業日以内の申込 | 発行=翌月末、支払期限=**翌々月末**（相対値 0/99 のまま） |
| クレジット | **変更なし**（決済期限は引き続き当月1日） |
| DB / `.env` / `build/` | **変更なし・上げない** |

---

## 1. デプロイ準備確認結果（実施済み）

| 確認 | 結果 |
| --- | --- |
| `app/app/Services/BillingRobo/BillingScheduleService.php` | 銀行振込時のみ `deadline_day=99` |
| `deploy/webroot_billing/.../BillingScheduleService.php` | 同上へ同期済み（差分なし） |
| 相対パス | `app/app/Services/BillingRobo/...`（`app` が2段） |

---

## 2. 反映順序

```
退避 → FTP（1ファイル） → （必要なら）PHP再起動 → 動作確認
```

---

## 3. 事前退避（推奨）

```
.../app/app/Services/BillingRobo/BillingScheduleService.php
  → BillingScheduleService.php.bak_20260819_deadline
```

---

## 4. FTP アップロード（必須・1ファイル）

**アップロード元（ローカル・絶対パス）:**

`/Users/dfn4459wgl/Desktop/billing/deploy/webroot_billing/app/app/Services/BillingRobo/BillingScheduleService.php`

**アップロード先（本番・絶対パス）:**

`/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/app/Services/BillingRobo/BillingScheduleService.php`

| 注意 | 内容 |
| --- | --- |
| 起点 | 必ず `deploy/webroot_billing/` |
| 相対パス | `app/app/Services/BillingRobo/BillingScheduleService.php`（`app` が2回） |
| 禁止 | `.env` / `storage/` / `build/` / `migrations/` / `vendor/` |

---

## 5. デプロイ後

通常は views 削除不要（PHPクラスのみ）。  
上書き後も旧期限のままなら、Plesk で PHP / PHP-FPM 再起動（OPcache）。

**既存の請求情報は自動では直らない。** 期限 8/1 の発行済み請求書は、請求管理ロボ画面で当月末日へ修正する。

---

## 6. 動作確認

| # | 確認 | 期待 |
| --- | --- | --- |
| 1 | 請求書払いプランで新規申込（最終5営業日より前） | 発行=申込月末、決済期限=**翌月末** |
| 2 | 請求書払いプランで新規申込（最終5営業日以内） | 発行=翌月末、決済期限=**翌々月末** |
| 3 | クレジットプランで申込 | 決済期限が **当月1日** のまま（回帰なし） |

---

## 7. ロールバック

1. 退避ファイルに戻す  
2. 必要なら PHP 再起動  
3. 請求書払い新規申込で期限が再び当月1日になることを確認  

DBロールバックは不要。
