# F-REGI設定「編集のみ」運用への統一 - 変更ファイル一覧と修正要約

作成日: 2026-01-16

---

## 変更ファイル一覧（deploy反映用のパス）

```
app/app/Http/Controllers/Admin/FregiConfigController.php
app/app/Http/Requests/FregiConfigRequest.php
app/app/Services/FregiConfigService.php
app/resources/views/admin/fregi-configs/edit.blade.php
app/resources/views/admin/fregi-configs/show.blade.php
app/routes/web.php
app/resources/views/layouts/admin.blade.php
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
  - `create.blade.php`は削除対象（未削除の場合は削除推奨）
  - `index.blade.php`は削除対象または編集画面へのリダイレクトに変更
  - `edit.blade.php`で初回保存時のパスワード入力欄を有効化
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

---

## DB確認コマンド

### レコード数確認
```bash
docker compose exec -T db mysql -u root -proot_pass billing -e "SELECT COUNT(*) AS count FROM fregi_configs;"
```

### connect_password_encの長さ確認（enc_lenが見える行）
```bash
docker compose exec -T db mysql -u root -proot_pass billing -e "SELECT id, company_id, environment, shopid, LENGTH(connect_password_enc) AS enc_len, is_active, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at FROM fregi_configs ORDER BY id DESC LIMIT 5;"
```

**期待される出力例:**
```
+----+------------+------------+--------+---------+-----------+---------------------+
| id | company_id | environment| shopid | enc_len | is_active | created_at          |
+----+------------+------------+--------+---------+-----------+---------------------+
|  1 |          1 | test       | 23034  |     128 |         1 | 2026-01-16 14:30    |
+----+------------+------------+--------+---------+-----------+---------------------+
```

**確認ポイント:**
- `enc_len > 0`であること（暗号化されたパスワードが保存されている）
- `company_id = 1`であること（固定値が設定されている）
- `shopid`に値が入っていること

---

## 実装完了チェックリスト

- [x] Create操作ができないこと（ルート削除、メソッド削除）
- [x] 編集画面で単一レコードを取得または作成すること
- [x] `connect_password_enc`が必ず保存されること
- [x] `company_id`が固定値1で自動セットされること
- [x] `shopid`が正しく保存されること
- [x] 初回保存時に`connect_password`が必須であること
- [x] 2回目以降の保存時は既存の`connect_password_enc`が保持されること
