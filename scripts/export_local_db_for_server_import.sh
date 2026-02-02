#!/bin/bash
# ローカルDBをサーバー側（本番）にインポートするためのSQLファイルを出力する
# 出力先: AIdocs/ローカルDB_本番インポート用.sql
# 前提: docker-compose で db が起動していること

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

OUTPUT_FILE="$PROJECT_ROOT/AIdocs/ローカルDB_本番インポート用.sql"

# docker-compose の db サービス（コンテナ内で mysqldump 実行）
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root_pass}"
DB_NAME="${DB_NAME:-billing}"

echo "=========================================="
echo "ローカルDBエクスポート（本番インポート用）"
echo "=========================================="
echo "DB: $DB_NAME (docker-compose db コンテナ内)"
echo "出力: $OUTPUT_FILE"
echo ""

# コンテナ内で mysqldump を実行し、ホストの AIdocs に出力（app がマウントされているので app 経由で書く）
# プロジェクトルートの AIdocs に書きたい: deploy や app 以外のマウントはないため、
# 一時ファイルを app/storage に書き、コンテナ外で mv するか、docker compose exec の stdout をリダイレクトする
docker compose exec -T db mysqldump \
  -u "$DB_USER" \
  -p"$DB_PASS" \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --default-character-set=utf8mb4 \
  "$DB_NAME" > "$OUTPUT_FILE"

echo "完了: $OUTPUT_FILE"
echo "サイズ: $(wc -c < "$OUTPUT_FILE" | tr -d ' ') bytes"
echo ""
echo "本番でのインポート手順:"
echo "  1. Plesk で対象DBのバックアップを取得"
echo "  2. phpMyAdmin で対象DBを開く → SQLタブ"
echo "  3. 上記SQLファイルの内容を貼り付けて実行"
echo "  （ファイルが大きい場合は Plesk の「ダンプをインポート」でアップロード）"
