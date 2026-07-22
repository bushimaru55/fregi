# 本番デプロイ手順（2026-07-22 hotfix）— 申込画面の利用規約番号（1. 2. 3.）表示

**前提**: Plesk / **SSH・`php artisan` 不可** / コードは FTP のみ  
**デプロイ元**: `deploy/webroot_billing/`（ローカル開発本体は `app/`。本番投入は必ずこちら）  
**アップロード先ルート**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`  
**公開URL**: `https://dschatbot.ai/webroot/billing`

関連:
- 運用ルール: [deploy_rules.md](deploy_rules.md)
- ファイル一覧: [deploy_files_20260722_terms_liststyle.txt](../deploy_files_20260722_terms_liststyle.txt)
- 本日付の利用規約機能本体: [deploy_runbook_20260722.md](deploy_runbook_20260722.md)

---

## 0. 今回の範囲

| 項目 | 内容 |
| --- | --- |
| 症状 | 管理画面では `1. 2. 3.` が見えるが、申込フォームでは番号が消える |
| 原因 | 本文HTMLは `<ol><li>` のまま。申込画面 CSS（`.quill-content`）に `list-style-type` が無く、Tailwind Preflight の `list-style: none` で番号が消える |
| 修正 | `.quill-content ul/ol/li` に管理画面と同様の list-style を追加 |
| DB | **変更なし** |
| `.env` / `build/` / migrations | **変更なし・上げない** |

---

## 1. デプロイ準備確認結果（実施済み）

| 確認 | 結果 |
| --- | --- |
| ローカル本体 `app/.../create.blade.php` に修正あり | OK（`list-style-type: decimal` 等） |
| 成果物 `deploy/webroot_billing/app/.../create.blade.php` に同修正あり | OK |
| `app` ↔ `deploy` 差分 | **IDENTICAL**（sha256 一致） |
| 本番 `/contract/create` | HTTP **200**（稼働中） |
| 本番HTMLに修正CSS | **なし**（＝本 hotfix 未デプロイ） |
| DB SQL | 不要 |
| Vite / `build/` | 不要 |

sha256（準備時点）:

```
dc43ae98079b0766e50d1bb8caf91a82756cbc5ab57eb167c4d460100d0c0588
  app/resources/views/contracts/create.blade.php
  deploy/webroot_billing/app/resources/views/contracts/create.blade.php
```

---

## 2. 反映順序

```
退避 → FTP（1ファイル） → viewsキャッシュ削除 → 動作確認
```

---

## 3. 事前退避（推奨）

本番の次を別名コピーする:

```
.../app/resources/views/contracts/create.blade.php
  → create.blade.php.bak_20260722_liststyle
```

---

## 4. FTP アップロード（必須・1ファイル）

**アップロード元（ローカル・絶対パス）:**

`/Users/dfn4459wgl/Desktop/billing/deploy/webroot_billing/app/resources/views/contracts/create.blade.php`

**アップロード先（本番・絶対パス）:**

`/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/resources/views/contracts/create.blade.php`

| 注意 | 内容 |
| --- | --- |
| 起点 | 必ず `deploy/webroot_billing/`（`app/` 直下を本番へ上げない運用） |
| 相対パス | `app/resources/views/contracts/create.blade.php`（`app/app/...` ではない） |
| 上書き | 既存ファイルを上書き |
| 禁止 | `.env` / `storage/` / `build/` / `migrations/` / `vendor/` |

---

## 5. デプロイ後（SSH 不要）

FTP で次を削除（フォルダ自体は残す）:

```
/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/storage/framework/views/*.php
```

（Blade の `@push('styles')` が再コンパイルされるため）

---

## 6. 動作確認

| # | 確認 | 期待 |
| --- | --- | --- |
| 1 | `https://dschatbot.ai/webroot/billing/contract/create` | HTTP 200 |
| 2 | ベース製品を選択し「4. 利用規約」を見る | **1. 2. 3.** の番号が表示される |
| 3 | ページソースに `list-style-type: decimal` | あること |
| 4 | 管理画面の利用規約プレビュー | 従来どおり番号表示（今回未変更） |

---

## 7. ロールバック

1. 本番の `create.blade.php` を `create.blade.php.bak_20260722_liststyle` に戻す  
2. `app/storage/framework/views/*.php` を再削除  
3. `/contract/create` が 200 であること、番号表示の有無を確認  

DBロールバックは不要（スキーマ変更なし）。
