# 本番 DB スキーマ（SSH / migrate 不可時）

## ファイル

| ファイル | 内容 |
|----------|------|
| [billing_schema_no_data.sql](billing_schema_no_data.sql) | **テーブル定義のみ**（`mysqldump --no-data`）。初回本番 DB 構築用。 |

## 再生成（開発者向け）

```bash
./scripts/export_schema_no_data_for_production.sh
```

前提: `docker compose` で `db` が起動済み。ローカル DB は `migrate` 済みであること。

## 本番への適用

[deploy/AIdocs/deploy_rules.md](../deploy/AIdocs/deploy_rules.md) §3 に従い、Plesk の「ダンプをインポート」または phpMyAdmin で実行する。**本番では `php artisan migrate` は使わない。**

初期データ（管理者・マスター）は別途 [deploy_rules.md](../deploy/AIdocs/deploy_rules.md) §4 のとおり SQL または管理画面から投入する。
