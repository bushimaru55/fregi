# FTP作業チェックリスト（本番デプロイ）

FTPのみ完了させるための最小手順です。

---

## 作業前（ローカルで1回だけ）

### □ 1. 本番用 .env を配置

- [ ] `AIdocs/本番環境.envテンプレート.txt` をコピー
- [ ] **`deploy/webroot_billing/app/.env`** として保存
- [ ] 本番の値を設定（`APP_KEY`, `DB_*`, `APP_URL`, `SESSION_PATH`, `MAIL_*` 等）

※ スクリプトは `.env` をコピーしないため、必ず手動で上記パスに置く。

### □ 2. アップロード元の確認

- [ ] アップロードするのは **`deploy/webroot_billing/` の中身**（フォルダごとではなく、中身一式）
- [ ] `deploy/webroot_billing/app/.env` が存在することを確認

---

## FTP作業

### □ 3. FTP接続

- [ ] FTPクライアント（FileZilla / Cyberduck 等）で本番サーバに接続（FTPS推奨）

### □ 4. アップロード先

- [ ] リモート側のパス: **`/httpdocs/webroot/billing/`**
  - （Plesk によっては `httpdocs/webroot/billing/` のように表示）

### □ 5. アップロードするもの

- [ ] **ローカル**: `deploy/webroot_billing/` の**中身すべて**を選択
  - `index.php`, `.htaccess`, `build/`, `css/`, `js/`, `favicon.ico`, `robots.txt`, `app/`, `README.md` 等
- [ ] **リモート**: `/httpdocs/webroot/billing/` にそのままアップロード（上書き）
- [ ] ディレクトリ構造を保ったままアップロードする

### □ 6. パーミッション（アップロード後）

FTPクライアントまたはPleskファイルマネージャで、可能な範囲で設定：

- [ ] `app/storage/` → **775**
- [ ] `app/bootstrap/cache/` → **775**
- [ ] `app/storage/logs/` → **775**

---

## FTP作業後の確認（ブラウザ）

- [ ] `https://dschatbot.ai/webroot/billing/` にアクセス → ページが表示される
- [ ] `https://dschatbot.ai/webroot/billing/build/manifest.json` → 200 で JSON が返る
- [ ] `https://dschatbot.ai/webroot/billing/login` → 管理ログイン画面が表示される

---

## 注意

- **アップロードするのは「webroot_billing フォルダの中身」**であり、「webroot_billing フォルダ自体」ではない。
- リモートに既にファイルがある場合は上書きでよい（必要に応じて事前バックアップはPlesk側で取得）。
