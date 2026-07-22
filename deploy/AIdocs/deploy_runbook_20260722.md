# 本番デプロイ手順（2026-07-22 改訂）— 決済タイプ別利用規約（既存影響最小化）

**前提**: Plesk / **SSH・`php artisan` 不可** / DBは phpMyAdmin で手動SQL / コードは FTP のみ  
**デプロイ元**: `deploy/webroot_billing/`  
**アップロード先**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`  
**公開URL**: `https://dschatbot.ai/webroot/billing`

---

## 本番環境の事前確認結果（2026-07-22 実施・公開面）

SSH/DB直結は不可のため、**公開HTTP**で現状を確認した。

| 確認項目 | 結果 | 判断 |
| --- | --- | --- |
| `https://dschatbot.ai/webroot/billing/` | HTTP 200 | 稼働中 |
| `/contract/create` | HTTP 200 | 稼働中 |
| `/login` | HTTP 200 | 稼働中 |
| `/build/manifest.json` | HTTP 200 | 静的成果物あり |
| `/admin/site-settings` | 302 → login | 認証必須（想定どおり） |
| 年額プラン表示（円/年） | あり（複数） | `yearly` は本番で利用中 |
| 「請求書払い（銀行振込）」バッジ | あり（例: 年額-請求書払いプラン） | `payment_collection_method` / `usesBankTransfer()` 相当が**本番稼働済み** |
| `data-terms-by-selection` / `terms-of-service-section` | **無し** | 決済タイプ別利用規約は**未デプロイ** |
| 利用規約表示 | 旧単一HTMLを常時表示 | 旧 `terms_of_service` が有効 |

### デプロイ安全性の結論

- **列追加SQL（`sql_20260722_safe_additive_columns.sql`）は、公開面の証拠上「既に適用済み」の可能性が高い。phpMyAdmin で SHOW 確認し、列があればスキップすること。**
- **今回の本番作業の本命は「利用規約キー INSERT-only」＋「A必須ファイルのFTP」のみ。**
- 既存クレジット／請求書払いの申込導線を壊すスキーマ変更は不要。
- 利用規約はキー未作成時も旧キーへフォールバックするため、コード投入直後も表示が空になりにくい。

> 注意: 公開面からは `contracts.payment_collection_method` の有無を直接証明できない。申込保存で列を書くため、**FTP前に phpMyAdmin で `contracts` 側も SHOW すること**（無ければ追加、あればスキップ）。

関連:
- 請求書払い／年額（参照のみ）: [deploy_runbook_20260715.md](deploy_runbook_20260715.md)
- 運用ルール: [deploy_rules.md](deploy_rules.md)
- ファイル一覧: [deploy_files_20260722.txt](../deploy_files_20260722.txt)
- データSQL（安全版）: [sql_20260722_copy_terms_of_service_keys.sql](sql_20260722_copy_terms_of_service_keys.sql)
- 列追加SQL（必要な場合のみ・安全版）: [sql_20260722_safe_additive_columns.sql](sql_20260722_safe_additive_columns.sql)

---

## 0. 既存影響の方針（必読）

| 対象 | 方針 | 既存への影響 |
| --- | --- | --- |
| 旧利用規約 `terms_of_service` | **削除・変更しない** | なし |
| 決済タイプ別キー | **未作成のキーだけ INSERT**（既存キーは上書きしない） | なし（新規行のみ） |
| テーブル構造（利用規約） | **変更しない** | なし |
| `payment_collection_method` 列 | 未追加のときのみ **DEFAULT `'card'` で追加** | 既存行はすべて `card`＝従来クレジット挙動のまま |
| `billing_type` に `yearly` | 未追加のときのみ ENUM 拡張 | 既存値は不変 |
| クレジット申込フロー | 変更しない（列 default / コード分岐で維持） | 回帰なしを確認する |
| DROP / TRUNCATE / DELETE（業務データ） | **行わない** | — |
| `.env` / `build/` / storage | **アップロードしない** | — |

アプリは決済タイプ別キーが無い場合、旧 `terms_of_service` に **フォールバック**する。  
そのためデータSQLは「推奨」だが、未実行でも旧利用規約表示は維持される。

---

## 1. 今回の変更概要

| 区分 | 内容 |
| --- | --- |
| 管理画面 | 利用規約を決済タイプ5区分のタブで編集 |
| 申込フォーム | 選択中ベース製品の決済タイプで利用規約を差し替え |
| 保存後UX | 保存したタブを選択したまま戻る |

**Vite / `build/`**: 変更なし（再ビルド不要）

---

## 2. 反映順序（必ず厳守）

```
バックアップ → 事前確認SQL → （必要な場合のみ）安全な列追加
→ （推奨）利用規約キーの INSERT-only
→ FTP（コード） → ビューキャッシュ削除 → 動作確認
```

**禁止**: コードだけ先に上げて、列が無い状態で本番申込を通すこと。  
（現行成果物の `ContractController` / `Contract` は `payment_collection_method` を書き込むため）

---

## 3. 事前バックアップ（必須）

Plesk で対象DBをエクスポートする。  
可能ならアップロード対象ファイルも別名退避（例: `ContractController.php.bak_20260722`）。

---

## 4. DB（phpMyAdmin・migrate しない）

### 4.1 事前確認（コピペ用）

```sql
-- A. 回収方法列の有無
SHOW COLUMNS FROM contract_plans LIKE 'payment_collection_method';
SHOW COLUMNS FROM contracts LIKE 'payment_collection_method';

-- B. billing_type に yearly があるか
SHOW COLUMNS FROM contract_plans LIKE 'billing_type';

-- C. 利用規約キーの現状
SELECT `key`, CHAR_LENGTH(`value`) AS value_len
FROM site_settings
WHERE `key` LIKE 'terms_of_service%'
ORDER BY `key`;
```

### 4.2 列が無い場合のみ — 安全な追加（既存挙動を変えない）

**本番公開面の確認では、年額・請求書払いプランが既に表示されているため、本節はスキップになる見込みが高い。**  
必ず phpMyAdmin の SHOW 結果で判断する（推測だけで ALTER しない）。

| SHOW 結果 | 対応 |
| --- | --- |
| `payment_collection_method` が両テーブルに存在する | **スキップ**（再実行禁止） |
| `billing_type` の Type に `yearly` が含まれる | **スキップ** |
| いずれかが無い | 下記（詳細は `sql_20260722_safe_additive_columns.sql`）を不足分だけ実行 |

要点（実行する場合）:
- `ADD COLUMN ... NOT NULL DEFAULT 'card'` → 既存行はクレジット扱いのまま
- `ENUM` に `yearly` を足すだけ → 既存 `one_time` / `monthly` は不変
- **DROP しない / 既存データを書き換えない**

既に列がある場合は **この節をすべてスキップ**（再実行するとエラーになる）。

### 4.3 利用規約キー — INSERT only（推奨・上書きなし）

`sql_20260722_copy_terms_of_service_keys.sql` を実行する。

- 旧 `terms_of_service` は触らない
- 5キーのうち **まだ無いものだけ** 旧本文をコピーして作成
- 既に存在するキーは **更新しない**（本番で編集済みなら保護）

### 4.4 事後確認

```sql
SHOW COLUMNS FROM contract_plans LIKE 'payment_collection_method';
SHOW COLUMNS FROM contracts LIKE 'payment_collection_method';

SELECT `key`, CHAR_LENGTH(`value`) AS value_len
FROM site_settings
WHERE `key` LIKE 'terms_of_service%'
ORDER BY `key`;
```

期待（利用規約）: 旧1 + 新規最大5（合計最大6行）。既存業務テーブルの行数は変わらない。

---

## 5. FTP アップロード

一覧: [deploy_files_20260722.txt](../deploy_files_20260722.txt)

### 5.1 必須（利用規約機能）— 本番はこちらが本命

公開確認の結果、請求書払い／年額は本番稼働済みのため、**今回は A のみ上げれば足りる**（B は差分が無い／既反映なら不要）。

```
app/app/Models/SiteSetting.php
app/app/Livewire/Admin/TermsOfServiceEditor.php
app/app/Http/Controllers/Admin/SiteSettingController.php
app/app/Http/Controllers/ContractController.php
app/app/Http/Requests/ContractRequest.php
app/resources/views/livewire/admin/terms-of-service-editor.blade.php
app/resources/views/admin/site-settings/index.blade.php
app/resources/views/admin/site-settings/edit.blade.php
app/resources/views/contracts/create.blade.php
```

> `ContractController` は回収方法も参照する。§4.1 で `contracts` / `contract_plans` の列存在を確認してから上げること。  
> `ContractPlan` / `Contract` モデルは本番で既に請求書払い表示できているため、**差分が無いなら上げなくてよい**（上げても default 挙動は同じ）。差分がある場合のみ同梱する。

### 5.2 任意（管理画面で請求書払い／年額・BillingRobo を追加改修する場合のみ）

公開面では請求書払いが既に動いている。**利用規約だけなら B は不要。**

```
（deploy_files_20260722.txt の B セクション）
```

### 5.3 アップロード禁止

- `app/.env`
- `app/storage/**`（特に `framework/views`・logs）
- `build/`
- `app/database/migrations/**`
- `vendor/` の丸ごと差し替え（今回不要）

---

## 6. デプロイ後（SSH 不要）

FTP で削除（存在するもののみ）:

```
app/storage/framework/views/*.php
```

`config/billing_robo.php` を上げた場合のみ:

```
app/bootstrap/cache/config.php
```

---

## 7. 動作確認（既存回帰を含む）

| # | 操作 | 期待 |
| --- | --- | --- |
| 1 | 既存クレジットプランで申込→確認→決済 | **従来どおり**決済ページへ進む |
| 2 | サイト管理 > 利用規約 | 5タブ表示。旧内容が各タブに入っている（またはフォールバック） |
| 3 | あるタブだけ編集して保存 | そのタブが選ばれたまま戻る。他タブは変わらない |
| 4 | 申込フォームで製品切替 | 下部の利用規約が切り替わる |
| 5 | 管理画面ログイン・申込一覧 | 表示できる（既存機能の健全性） |

---

## 8. ロールバック（影響を広げない順）

1. **コード**: 退避した旧ファイルに戻す → `views/*.php` 削除  
2. **利用規約キー**: 原則残してよい（旧キーは生きている）。消す場合のみ:

```sql
DELETE FROM site_settings
WHERE `key` IN (
  'terms_of_service_one_time',
  'terms_of_service_monthly',
  'terms_of_service_yearly',
  'terms_of_service_monthly_invoice',
  'terms_of_service_yearly_invoice'
);
-- 旧 terms_of_service は削除しない
```

3. **追加列**: 原則残す（`DEFAULT card` のため既存クレジットに影響しない）。  
   どうしても戻す場合は **コードを旧版に戻したあと**、かつ振込プラン未作成を確認してから DROP（詳細は 7/15 runbook §7）。

---

## 9. やらないこと（チェックリスト）

- [ ] `DROP TABLE` / 業務データの `DELETE` / `TRUNCATE` をしていない
- [ ] 旧 `terms_of_service` を消していない・上書きしていない
- [ ] 既存の決済タイプ別キーを `UPDATE` していない（INSERT only）
- [ ] `.env` を上書きしていない
- [ ] 列が無い状態で `ContractController.php` を上げていない
- [ ] `build/` を不要に差し替えていない
