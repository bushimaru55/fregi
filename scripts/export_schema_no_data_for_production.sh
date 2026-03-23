#!/bin/bash
# 本番（SSH 不可・migrate 不可）向け: テーブル定義のみの SQL を出力する
# 出力先: deploy/billing_schema_no_data.sql
# 手順: deploy/AIdocs/deploy_rules.md §3 参照。Plesk / phpMyAdmin でインポート。
# 前提: docker compose で db が起動していること

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

OUTPUT_DIR="$PROJECT_ROOT/deploy"
OUTPUT_FILE="$OUTPUT_DIR/billing_schema_no_data.sql"
mkdir -p "$OUTPUT_DIR"

DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root_pass}"
DB_NAME="${DB_NAME:-billing}"

echo "=========================================="
echo "スキーマのみエクスポート（本番インポート用）"
echo "=========================================="
echo "DB: $DB_NAME (docker-compose db)"
echo "出力: $OUTPUT_FILE"
echo ""

docker compose exec -T db mysqldump \
  -u "$DB_USER" \
  -p"$DB_PASS" \
  --no-data \
  --routines \
  --triggers \
  --single-transaction \
  --default-character-set=utf8mb4 \
  "$DB_NAME" > "$OUTPUT_FILE"

echo "完了: $OUTPUT_FILE"
echo "サイズ: $(wc -c < "$OUTPUT_FILE" | tr -d ' ') bytes"
echo ""
echo "本番でのインポート（SSH 不可）:"
echo "  1. Plesk > データベース > 対象 DB > ダンプをインポート"
echo "  2. または phpMyAdmin で SQL ファイルを実行"
echo "  初回のみ「データベースを再作成」にチェック可（deploy_rules.md §3.2）"
echo ""
