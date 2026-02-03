# ローカルDBを本番サーバーにインポートする手順

## 1. SQLファイルの作成（ローカル）

ローカルで Docker の DB を起動した状態で、プロジェクトルートで以下を実行する。

```bash
./scripts/export_local_db_for_server_import.sh
```

- **出力先**: `AIdocs/ローカルDB_本番インポート用.sql`
- スキーマ・データ・ルーチン・トリガー・イベントを含むフルダンプが出力される。

## 2. 本番でのインポート（Plesk）

1. **必ずバックアップを取得**
   - Plesk にログイン → データベース → 対象DB（例: `billing_prod`）→「エクスポート」で完全バックアップをダウンロード。

2. **既存データを上書きする場合**
   - phpMyAdmin で対象DBを開く。
   - 「SQL」タブを開く。
   - `AIdocs/ローカルDB_本番インポート用.sql` の内容を貼り付けて「実行」。
   - ファイルが大きい場合は、Plesk の「ダンプをインポート」でファイルをアップロードしてインポート。

3. **初回構築（空のDBに投入）する場合**
   - 上記と同様に、対象DBを選択してから SQL を実行またはダンプをインポート。

## 3. 既存DBの localhost URL を本番URLに更新する場合

既にインポート済みで、`contract_form_urls` に localhost のURLが残っている場合は、以下を実行する。

1. phpMyAdmin で対象DB（例: `billing_prod`）を開く。
2. 「SQL」タブを開く。
3. **`AIdocs/本番URLへ更新するSQL.sql`** の内容を貼り付けて「実行」する。

これで `contract_form_urls.url` 内の localhost が `https://dschatbot.ai/webroot/billing/` に置き換わる。

## 注意事項

- インポート後、本番の `APP_URL` 等に依存するデータ（例: `contract_form_urls.url` に含まれる localhost URL）は、上記「本番URLへ更新するSQL」または管理画面で本番用URLに修正すること。
- `*.sql` は `.gitignore` で除外されているため、`ローカルDB_本番インポート用.sql` はリポジトリに含まれない。再エクスポートする場合は上記スクリプトを再実行すること。
