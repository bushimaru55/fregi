# デプロイパッケージ（`webroot_billing`）のビルドメモ

## `vendor/`（本番用・--no-dev）

`scripts/build_deploy_webroot_billing.sh` はホストに `composer` が無い場合、**vendor をコピーしない**ことがある。

本番向けは **開発用 Docker の PHP 8.1** で次を実行し、`deploy/webroot_billing/app/vendor` に **--no-dev** の結果をコピーする:

```bash
docker compose exec app bash -lc "cd /var/www && composer install --no-dev --optimize-autoloader --no-interaction"
rm -rf deploy/webroot_billing/app/vendor
cp -a app/vendor deploy/webroot_billing/app/vendor
docker compose exec app bash -lc "cd /var/www && composer install --optimize-autoloader --no-interaction"
```

最後の行で **ローカル `app/vendor` を開発依存付きに戻す**。

## 成果物の場所

- ディレクトリ: `deploy/webroot_billing/`
- DB スキーマのみ: [billing_schema_no_data.sql](billing_schema_no_data.sql)（[README_SCHEMA.md](README_SCHEMA.md)）
