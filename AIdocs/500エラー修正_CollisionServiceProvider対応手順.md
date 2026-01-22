# 500エラー修正：CollisionServiceProvider対応手順

作成日: 2026-01-22

## 問題の概要

本番環境で以下のエラーが発生：
```
Class "NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider" not found
```

**原因**: `bootstrap/cache/packages.php` に開発依存パッケージ（Collision、Ignition等）が登録されており、本番環境（`composer install --no-dev`）ではこれらのパッケージがインストールされていないため、読み込み時にエラーが発生。

---

## 修正内容

### 1. `composer.json` の更新

`dont-discover` に開発依存パッケージを追加：

```json
"extra": {
    "laravel": {
        "dont-discover": [
            "nunomaduro/collision",
            "spatie/laravel-ignition"
        ]
    }
}
```

### 2. `bootstrap/cache/packages.php` の修正

以下の開発依存パッケージのエントリを削除：
- `nunomaduro/collision`
- `spatie/laravel-ignition`
- `laravel/breeze`
- `laravel/sail`

---

## 本番環境での対応手順（ターミナル不要）

### ステップ1: FTPクライアントで接続

FTPクライアント（FileZilla、Cyberduck等）を使用して本番サーバーに接続します。

### ステップ2: アップロード先に移動

**アップロード先**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/`

### ステップ3: ファイルをアップロード

以下の2ファイルを上書きアップロード：

1. **`composer.json`**
   - ローカル: `deploy/webroot_billing/app/composer.json`
   - 本番: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/composer.json`

2. **`bootstrap/cache/packages.php`**
   - ローカル: `deploy/webroot_billing/app/bootstrap/cache/packages.php`
   - 本番: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/bootstrap/cache/packages.php`

**注意**:
- `bootstrap/cache/` ディレクトリが存在しない場合は作成してください（パーミッション: 775）
- 既存のファイルを上書きしてください

### ステップ4: 動作確認

1. ブラウザで `https://dschatbot.ai/webroot/billing/` にアクセス
2. 500エラーが解消されているか確認
3. ページが正常に表示されるか確認

---

## トラブルシューティング

### 500エラーが続く場合

Pleskファイルマネージャーで以下を確認：

1. **ログファイルの確認**
   - `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/storage/logs/laravel.log`
   - `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/storage/logs/contract-payment-YYYY-MM-DD.log`

2. **パーミッションの確認**
   - `app/bootstrap/cache/` ディレクトリ: 775
   - `app/storage/` ディレクトリ: 775

3. **ファイルの存在確認**
   - `app/composer.json` が正しくアップロードされているか
   - `app/bootstrap/cache/packages.php` が正しくアップロードされているか

---

## 参考

- 修正日: 2026-01-22
- 関連ファイル: `deploy/webroot_billing/app/composer.json`, `deploy/webroot_billing/app/bootstrap/cache/packages.php`
