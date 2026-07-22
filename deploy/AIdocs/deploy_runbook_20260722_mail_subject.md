# 本番デプロイ手順（2026-07-22 hotfix）— 申込受付メール件名の統一

**前提**: Plesk / **SSH・`php artisan` 不可** / コードは FTP のみ  
**デプロイ元**: `deploy/webroot_billing/`（ローカル開発本体は `app/`。本番投入は必ずこちら）  
**アップロード先ルート**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`  
**公開URL**: `https://dschatbot.ai/webroot/billing`

関連:
- 運用ルール: [deploy_rules.md](deploy_rules.md)
- ファイル一覧: [deploy_files_20260722_mail_subject.txt](../deploy_files_20260722_mail_subject.txt)

---

## 0. 今回の範囲

| 項目 | 内容 |
| --- | --- |
| 変更前 | 請求書払いのみ `【申込受付】新規申込のお知らせ（請求書払い）` |
| 変更後 | クレジット／請求書払いとも `【申込受付】新規申込のお知らせ - {会社名}`（従来の月額と同じ） |
| DB / `.env` / `build/` | **変更なし・上げない** |

---

## 1. デプロイ準備確認結果（実施済み）

| 確認 | 結果 |
| --- | --- |
| `app/app/Mail/ContractNotificationMail.php` | 件名分岐削除済み |
| `deploy/webroot_billing/app/app/Mail/ContractNotificationMail.php` | 同上へ同期済み（差分なし） |
| 相対パス | `app/app/Mail/...`（`app` が2段） |

---

## 2. 反映順序

```
退避 → FTP（1ファイル） → （必要なら）PHP再起動 → 動作確認
```

---

## 3. 事前退避（推奨）

```
.../app/app/Mail/ContractNotificationMail.php
  → ContractNotificationMail.php.bak_20260722_mail_subject
```

---

## 4. FTP アップロード（必須・1ファイル）

**アップロード元（ローカル・絶対パス）:**

`/Users/dfn4459wgl/Desktop/billing/deploy/webroot_billing/app/app/Mail/ContractNotificationMail.php`

**アップロード先（本番・絶対パス）:**

`/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/app/Mail/ContractNotificationMail.php`

| 注意 | 内容 |
| --- | --- |
| 起点 | 必ず `deploy/webroot_billing/` |
| 相対パス | `app/app/Mail/ContractNotificationMail.php`（`app` が2回） |
| 禁止 | `.env` / `storage/` / `build/` / `migrations/` / `vendor/` |

---

## 5. デプロイ後

通常は views 削除不要（PHPクラスのみ）。  
上書き後も旧件名のままなら、Plesk で PHP / PHP-FPM 再起動（OPcache）。

---

## 6. 動作確認

| # | 確認 | 期待 |
| --- | --- | --- |
| 1 | 請求書払いプランで申込完了 | 件名が `【申込受付】新規申込のお知らせ - {会社名}` |
| 2 | クレジット（月額）で申込完了 | 件名が従来どおり `【申込受付】新規申込のお知らせ - {会社名}` |

---

## 7. ロールバック

1. 退避ファイルに戻す  
2. 必要なら PHP 再起動  
3. 請求書払い申込で旧件名に戻ることを確認  

DBロールバックは不要。
