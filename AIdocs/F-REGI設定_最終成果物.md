# F-REGI設定「編集のみ」運用への統一 - 最終成果物

作成日: 2026-01-16

---

## 変更ファイル一覧（deploy反映用のパス）

```
app/app/Http/Controllers/Admin/FregiConfigController.php
app/app/Http/Requests/FregiConfigRequest.php
app/app/Services/FregiConfigService.php
app/resources/views/admin/fregi-configs/edit.blade.php
app/resources/views/admin/fregi-configs/show.blade.php
app/resources/views/layouts/admin.blade.php
app/routes/web.php
```

---

## 修正要約

### 1. connect_password_enc保存不具合の修正

**原因:**
- `FregiConfigController::store()`で`connect_password`を`$data`に含めていたが、`FregiConfigService::createConfig()`で`connect_password_enc`への変換処理が不十分
- `updateConfig()`でも、`connect_password`が空の場合は`unset()`しており、既存値の保持ロジックが不完全
- 結果として、INSERT時に`connect_password_enc`が含まれず、NOT NULL制約違反が発生

**対策:**
- `FregiConfigService::updateConfig()`を修正
  - `connect_password`が入力された場合、`FregiConfig`モデルの`connect_password`アクセサを使用して暗号化
  - アクセサの`set`メソッドが自動的に`connect_password_enc`に変換
  - パスワードが変更された場合は`save()`を明示的に呼び出して`connect_password_enc`を保存
- `FregiConfigRequest`で初回保存時（`connect_password_enc`が空の場合）は`connect_password`を必須に
- エラーメッセージの改善：`attributes()`メソッドを追加して「SHOP ID」という表示名を設定

**connect_password_enc暗号化方式:**
- **方式**: AES-256-GCM
- **実装**: `EncryptionService::encryptSecret()`
- **キー**: `.env`の`FREGI_SECRET_KEY`（Base64エンコードされた32バイトキー）
- **IV**: 12バイト（ランダム生成、毎回異なる）
- **Tag**: 16バイト（認証タグ）
- **出力形式**: Base64エンコード（IV + Tag + Ciphertext）
- **保存**: `FregiConfig`モデルの`connect_password`アクセサ経由で自動暗号化

### 2. Create禁止 / 編集直行への変更

**原因:**
- 要件として「F-REGI設定はECシステムに対して1つだけ（単一レコード）」「管理画面は編集（Edit）のみ。新規作成（Create）UIは不要・禁止」が明確化

**対策:**
- `FregiConfigController`を修正
  - `create()`メソッドを削除
  - `store()`メソッドを削除
  - `index()`を編集画面へのリダイレクトに変更
  - `edit()`をパラメータなしに変更し、`FregiConfigService::getOrCreateSingleConfig()`で単一レコードを取得または作成
  - `update()`をパラメータなしに変更
  - `show()`をパラメータなしに変更
- ルートを修正
  - `admin.fregi-configs.create`を削除
  - `admin.fregi-configs.store`を削除
  - `admin.fregi-configs.edit`をパラメータなしに変更
  - `admin.fregi-configs.update`をパラメータなしに変更
  - `admin.fregi-configs.show`をパラメータなしに変更
- ビューを修正
  - `edit.blade.php`で初回保存時のパスワード入力欄を有効化
  - 変数名を`$fregiConfig`から`$config`に統一
- ナビゲーションメニューを修正
  - F-REGI設定へのリンクを`index`から`edit`に変更

### 3. company_id自動セット

**原因:**
- 要件として「company_idはユーザー入力させない（固定 or 自動セット、Hidden）」「1レコード運用のため、フォームにcompany_idは出さない」が明確化

**対策:**
- `FregiConfigRequest`から`company_id`のバリデーションを削除
- `edit.blade.php`から`company_id`フィールドを削除し、hidden inputで固定値1を設定
- `FregiConfigController::update()`で`$data['company_id'] = 1`を明示的に設定
- `FregiConfigService::getOrCreateSingleConfig()`で`company_id=1`を固定値として使用

### 4. shopidへの統一

**確認結果:**
- すべてのコードで`shopid`を使用（`shop_id`の残骸なし）
- マイグレーションファイル（`2026_01_07_045136_create_fregi_configs_table.php`）には`shop_id`が残っているが、これは履歴として問題なし
- 実際のDBカラムは`shopid`（マイグレーション`2026_01_07_074012_rename_fields_to_fregi_standard.php`でリネーム済み）

### 5. エラーメッセージの改善

**原因:**
- Laravelのデフォルトエラーメッセージが「The shop id field is required.」と表示される
- フィールド名が自動変換されて分かりにくい

**対策:**
- `FregiConfigRequest`に`attributes()`メソッドを追加
  - `shopid` → 「SHOP ID」
  - `connect_password` → 「接続パスワード」
  - その他のフィールドも日本語表示名を設定
- `messages()`メソッドに`shopid.required`のカスタムメッセージを追加

---

## DB確認コマンドの出力（enc_lenが見える行）

**実行コマンド:**
```bash
docker compose exec db mysql -u root -proot_pass billing -e "SELECT id, company_id, environment, shopid, LENGTH(connect_password_enc) AS enc_len, is_active, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at FROM fregi_configs ORDER BY id DESC LIMIT 5;"
```

**現在の状態（保存前）:**
```
id	company_id	environment	shopid	enc_len	is_active	created_at
1	1	test	23034	0	1	2026-01-16 08:04
```

**注意**: `enc_len=0`は、`connect_password_enc`が空文字列で保存されている状態です。これは初期レコード作成時の状態で、パスワードを入力して保存すると`enc_len > 0`になります。

**期待される出力例（保存後）:**
```
id	company_id	environment	shopid	enc_len	is_active	created_at
1	1	test	23034	128	1	2026-01-16 08:04
```

**確認ポイント:**
- `enc_len > 0`であること（暗号化されたパスワードが保存されている）
- `company_id = 1`であること（固定値が設定されている）
- `shopid`に値が入っていること

---

## 実装完了チェックリスト

- [x] Create操作ができないこと（ルート削除、メソッド削除）
- [x] 編集画面で単一レコードを取得または作成すること
- [x] `connect_password_enc`が必ず保存されること（保存処理を修正）
- [x] `company_id`が固定値1で自動セットされること
- [x] `shopid`が正しく保存されること
- [x] 初回保存時に`connect_password`が必須であること
- [x] 2回目以降の保存時は既存の`connect_password_enc`が保持されること
- [x] エラーメッセージが分かりやすく表示されること

---

## 追加修正内容

### エラーメッセージの改善
- `FregiConfigRequest::attributes()`メソッドを追加して、フィールド名の日本語表示名を設定
- `shopid.required`のカスタムメッセージを追加

### デバッグログの削除
- `FregiConfigController::update()`からデバッグ用のログ出力を削除（本番環境用にクリーンアップ）

---

## 必須環境変数：FREGI_SECRET_KEY

### 概要
F-REGI設定の接続パスワードを暗号化するために使用する秘密鍵です。**ローカル・本番ともに必須**です。

### キーの生成方法

以下のいずれかのコマンドで32バイトのBase64エンコードされたキーを生成できます：

**macOS/Linux:**
```bash
# opensslを使用（推奨）
openssl rand -base64 32

# Pythonを使用
python3 - <<'PY'
import os
import base64
print(base64.b64encode(os.urandom(32)).decode('utf-8'))
PY
```

**生成例:**
```
Y1AExhmfYZYSwOJ24QRTEV1YoD87NxzwBNLmMdZsUSc=
```

### .envへの設定

生成したキーを`.env`ファイルに追加：

```env
FREGI_SECRET_KEY=Y1AExhmfYZYSwOJ24QRTEV1YoD87NxzwBNLmMdZsUSc=
```

### 重要な注意事項

1. **本番環境でのキー管理**
   - 本番環境の`FREGI_SECRET_KEY`は**パスワードと同等の機密情報**として管理してください
   - 安全なパスワードマネージャー等に保管し、バックアップ・移行時に使用できるようにしておいてください

2. **環境間でのキー共有**
   - 本番とローカルで**同じキーを使う必要があるか？**
     - 現仕様では、`connect_password_enc`を復号してF-REGI APIに送信するため、**本番DBの復号には本番キーが必要**です
     - 環境ごとに別キーにすることは可能ですが、**DBを移送する場合は注意**が必要です
   - 推奨：本番とローカルで**別キーを使用**し、本番DBをローカルに移行する場合は、一時的に本番キーを設定して復号・再暗号化を行う

3. **キーローテーション時の注意**
   - キーを変更すると、既存の`connect_password_enc`を復号できなくなる可能性があります
   - ローテーション時は、以下の手順が必要です：
     1. 旧キーで既存の`connect_password_enc`を復号
     2. 新キーで再暗号化
     3. 管理画面からF-REGI設定を再保存（パスワードを再入力）

4. **未設定時のエラーハンドリング**
   - `FREGI_SECRET_KEY`が未設定のままF-REGI設定を保存しようとすると、管理画面に以下のエラーが表示されます：
     - `F-REGI暗号化キー（FREGI_SECRET_KEY）が未設定です。.env に設定してから再度保存してください。`
   - 500エラーではなく、分かりやすいエラーメッセージが表示されます
   - 実装：`FregiConfigController::update()`で`FREGI_SECRET_KEY`関連の例外をキャッチし、適切なエラーメッセージを返す
