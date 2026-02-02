#!/bin/bash

# デプロイパッケージ生成スクリプト
# 本番環境用 webroot_billing パッケージを生成します

set -e  # エラーが発生したら終了

# カラー出力
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# プロジェクトルートの確認
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}デプロイパッケージ生成スクリプト${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# ディレクトリ定義
APP_DIR="$PROJECT_ROOT/app"
DEPLOY_DIR="$PROJECT_ROOT/deploy/webroot_billing"
DEPLOY_APP_DIR="$DEPLOY_DIR/app"

# 既存のデプロイパッケージのバックアップ
if [ -d "$DEPLOY_DIR" ]; then
    echo -e "${YELLOW}既存のデプロイパッケージをバックアップします...${NC}"
    BACKUP_DIR="${DEPLOY_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$(dirname "$BACKUP_DIR")"
    cp -r "$DEPLOY_DIR" "$BACKUP_DIR"
    echo -e "${GREEN}バックアップ完了: $BACKUP_DIR${NC}"
    echo ""
    
    # 既存のindex.phpとREADME.mdを保存
    if [ -f "$DEPLOY_DIR/index.php" ]; then
        INDEX_BACKUP="$BACKUP_DIR/index.php"
        echo -e "${GREEN}既存のindex.phpをバックアップ: $INDEX_BACKUP${NC}"
    fi
    if [ -f "$DEPLOY_DIR/README.md" ]; then
        README_BACKUP="$BACKUP_DIR/README.md"
        echo -e "${GREEN}既存のREADME.mdをバックアップ: $README_BACKUP${NC}"
    fi
    echo ""
fi

# デプロイディレクトリのクリーンアップ
echo -e "${YELLOW}既存のデプロイパッケージを削除します...${NC}"
rm -rf "$DEPLOY_DIR"
echo -e "${GREEN}削除完了${NC}"
echo ""

# デプロイディレクトリの作成
echo -e "${YELLOW}デプロイディレクトリを作成します...${NC}"
mkdir -p "$DEPLOY_DIR"
mkdir -p "$DEPLOY_APP_DIR"
echo -e "${GREEN}作成完了${NC}"
echo ""

# Viteビルドの実行（一時ディレクトリで実行し、ローカル app/public/build は変更しない）
echo -e "${YELLOW}Viteビルドを一時ディレクトリで実行します（ローカル app/public/build は変更しません）...${NC}"
BUILD_TMP=$(mktemp -d 2>/dev/null || mktemp -d -t 'billing_deploy')
if command -v rsync &> /dev/null; then
    rsync -a --exclude=node_modules --exclude=vendor --exclude=.git "$APP_DIR/" "$BUILD_TMP/app_src/"
else
    cp -r "$APP_DIR" "$BUILD_TMP/app_src"
    rm -rf "$BUILD_TMP/app_src/node_modules" "$BUILD_TMP/app_src/vendor" 2>/dev/null || true
fi
cd "$BUILD_TMP/app_src"
if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}node_modules をインストールしています...${NC}"
    npm install
fi
npm run build
if [ ! -f "$BUILD_TMP/app_src/public/build/manifest.json" ]; then
    echo -e "${RED}エラー: ビルド後に manifest.json が見つかりません${NC}"
    rm -rf "$BUILD_TMP"
    exit 1
fi
cp -r "$BUILD_TMP/app_src/public/build" "$DEPLOY_DIR/build"
echo -e "${GREEN}Viteビルド完了（成果物を deploy にコピー済み）${NC}"
cd "$PROJECT_ROOT"
rm -rf "$BUILD_TMP"
echo -e "${GREEN}manifest.json確認: $DEPLOY_DIR/build/manifest.json${NC}"
echo ""

# 公開ディレクトリのコピー
echo -e "${YELLOW}公開ディレクトリをコピーします...${NC}"

# .htaccessのコピーと修正
if [ -f "$APP_DIR/public/.htaccess" ]; then
    cp "$APP_DIR/public/.htaccess" "$DEPLOY_DIR/.htaccess"
    # RewriteBaseを追加（既に存在する場合はスキップ）
    if ! grep -q "RewriteBase /webroot/billing/" "$DEPLOY_DIR/.htaccess"; then
        sed -i.bak '/RewriteEngine On/a\
    # ベースパスを明示的に指定\
    RewriteBase /webroot/billing/
' "$DEPLOY_DIR/.htaccess"
        rm -f "$DEPLOY_DIR/.htaccess.bak"
        echo -e "${GREEN}.htaccessにRewriteBaseを追加${NC}"
    fi
fi

# build/ は上記の一時ディレクトリビルドで既に配置済み

# css/のコピー
if [ -d "$APP_DIR/public/css" ]; then
    cp -r "$APP_DIR/public/css" "$DEPLOY_DIR/css"
    echo -e "${GREEN}css/ をコピー完了${NC}"
fi

# js/のコピー
if [ -d "$APP_DIR/public/js" ]; then
    cp -r "$APP_DIR/public/js" "$DEPLOY_DIR/js"
    echo -e "${GREEN}js/ をコピー完了${NC}"
fi

# images/のコピー（ロゴ等）
if [ -d "$APP_DIR/public/images" ]; then
    cp -r "$APP_DIR/public/images" "$DEPLOY_DIR/images"
    echo -e "${GREEN}images/ をコピー完了${NC}"
fi

# favicon.icoのコピー
if [ -f "$APP_DIR/public/favicon.ico" ]; then
    cp "$APP_DIR/public/favicon.ico" "$DEPLOY_DIR/favicon.ico"
    echo -e "${GREEN}favicon.ico をコピー完了${NC}"
fi

# robots.txtのコピー
if [ -f "$APP_DIR/public/robots.txt" ]; then
    cp "$APP_DIR/public/robots.txt" "$DEPLOY_DIR/robots.txt"
    echo -e "${GREEN}robots.txt をコピー完了${NC}"
fi

echo ""

# index.phpの配置
echo -e "${YELLOW}index.phpを配置します...${NC}"
if [ -f "$INDEX_BACKUP" ]; then
    cp "$INDEX_BACKUP" "$DEPLOY_DIR/index.php"
    echo -e "${GREEN}既存のindex.phpを復元${NC}"
else
    # 新規作成
    cat > "$DEPLOY_DIR/index.php" << 'EOF'
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/app/storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/app/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/app/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
EOF
    echo -e "${GREEN}index.phpを新規作成${NC}"
fi
echo ""

# Laravel本体のコピー
echo -e "${YELLOW}Laravel本体をコピーします...${NC}"

# コピーするディレクトリ/ファイル
COPY_ITEMS=(
    "app"
    "bootstrap"
    "config"
    "database"
    "resources"
    "routes"
    "storage"
    "artisan"
    "composer.json"
    "composer.lock"
)

for item in "${COPY_ITEMS[@]}"; do
    if [ -e "$APP_DIR/$item" ]; then
        if [ -d "$APP_DIR/$item" ]; then
            cp -r "$APP_DIR/$item" "$DEPLOY_APP_DIR/"
        else
            cp "$APP_DIR/$item" "$DEPLOY_APP_DIR/"
        fi
        echo -e "${GREEN}$item をコピー完了${NC}"
    fi
done

echo ""

# app/.htaccessの作成
echo -e "${YELLOW}app/.htaccessを作成します...${NC}"
cat > "$DEPLOY_APP_DIR/.htaccess" << 'EOF'
Require all denied
EOF
echo -e "${GREEN}app/.htaccess作成完了${NC}"
echo ""

# composer installの実行
echo -e "${YELLOW}composer installを実行します...${NC}"
cd "$DEPLOY_APP_DIR"
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction
    echo -e "${GREEN}composer install完了${NC}"
else
    echo -e "${RED}警告: composerコマンドが見つかりません${NC}"
    echo -e "${YELLOW}手動で composer install --no-dev を実行してください${NC}"
fi
echo ""

cd "$PROJECT_ROOT"

# パーミッションの設定
echo -e "${YELLOW}パーミッションを設定します...${NC}"
chmod -R 775 "$DEPLOY_APP_DIR/storage" 2>/dev/null || echo -e "${YELLOW}警告: storage/のパーミッション設定に失敗（手動で設定してください）${NC}"
chmod -R 775 "$DEPLOY_APP_DIR/bootstrap/cache" 2>/dev/null || echo -e "${YELLOW}警告: bootstrap/cache/のパーミッション設定に失敗（手動で設定してください）${NC}"
echo -e "${GREEN}パーミッション設定完了（手動確認推奨）${NC}"
echo ""

# README.mdの復元/更新
echo -e "${YELLOW}README.mdを更新します...${NC}"
if [ -f "$README_BACKUP" ]; then
    cp "$README_BACKUP" "$DEPLOY_DIR/README.md"
    echo -e "${GREEN}既存のREADME.mdを復元${NC}"
else
    # 新規作成（簡易版）
    cat > "$DEPLOY_DIR/README.md" << 'EOF'
# 本番環境デプロイパッケージ

## 概要

このパッケージは、本番環境の `/httpdocs/webroot/billing/` ディレクトリにデプロイするためのファイル一式です。

**デプロイ先URL**: `https://dschatbot.ai/webroot/billing/`

## FTPアップロード手順

1. FTPクライアントで本番サーバーに接続
2. `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/` にアップロード
3. パーミッション設定: `chmod -R 775 app/storage app/bootstrap/cache`
4. `.env` ファイルを設定（`app/.env`）

## 動作確認URL

- ✅ `https://dschatbot.ai/webroot/billing/` - トップページ
- ✅ `https://dschatbot.ai/webroot/billing/build/manifest.json` - ビルド成果物
- ❌ `https://dschatbot.ai/webroot/billing/app/.env` - 403エラー（正常）

詳細は `AIdocs/本番環境セットアップガイド.md` を参照してください。
EOF
    echo -e "${GREEN}README.mdを新規作成${NC}"
fi
echo ""

# 完了メッセージ
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}デプロイパッケージ生成完了${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "生成先: ${GREEN}$DEPLOY_DIR${NC}"
echo ""
echo -e "${YELLOW}次のステップ:${NC}"
echo "1. app/.env を配置: AIdocs/本番環境.envテンプレート.txt をコピーし deploy/webroot_billing/app/.env に保存して本番の値を設定"
echo "2. FTPで deploy/webroot_billing/ の中身を /httpdocs/webroot/billing/ にアップロード"
echo "3. パーミッションを設定してください（app/storage, app/bootstrap/cache を 775）"
echo "4. 動作確認を行ってください"
echo ""
