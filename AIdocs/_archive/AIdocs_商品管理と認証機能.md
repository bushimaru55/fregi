# 商品管理と認証機能の仕様書

## 概要

契約管理システムに商品追加機能と認証機能を実装し、管理画面へのアクセスを保護しました。

## 実装内容

### 1. データベース構造

#### products（商品マスタ）

| カラム名 | 型 | 制約 | 説明 |
|---------|-----|------|------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | 商品ID |
| name | VARCHAR(255) | NOT NULL | 商品名 |
| code | VARCHAR(255) | NOT NULL, UNIQUE | 商品コード |
| description | TEXT | NULL | 商品説明 |
| price | INT UNSIGNED | NOT NULL | 単価（税込） |
| type | ENUM | NOT NULL, DEFAULT 'plan' | 商品種別 |
| attributes | JSON | NULL | 商品属性 |
| is_active | BOOLEAN | DEFAULT TRUE | 有効フラグ |
| display_order | INT UNSIGNED | DEFAULT 0 | 表示順 |
| created_at | TIMESTAMP | NULL | 作成日時 |
| updated_at | TIMESTAMP | NULL | 更新日時 |

**商品種別（type）:**
- `plan`: プラン
- `option`: オプション
- `addon`: 追加商品

**インデックス:**
- `UNIQUE(code)`
- `INDEX(is_active, type, display_order)`

#### contract_items（契約明細）

| カラム名 | 型 | 制約 | 説明 |
|---------|-----|------|------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | 明細ID |
| contract_id | BIGINT UNSIGNED | FK(contracts.id), NOT NULL | 契約ID |
| product_id | BIGINT UNSIGNED | FK(products.id), NOT NULL | 商品ID |
| product_name | VARCHAR(255) | NOT NULL | 商品名（スナップショット） |
| product_code | VARCHAR(255) | NOT NULL | 商品コード（スナップショット） |
| quantity | INT UNSIGNED | NOT NULL, DEFAULT 1 | 数量 |
| unit_price | INT UNSIGNED | NOT NULL | 単価（スナップショット） |
| subtotal | INT UNSIGNED | NOT NULL | 小計 |
| product_attributes | JSON | NULL | 商品属性（スナップショット） |
| created_at | TIMESTAMP | NULL | 作成日時 |
| updated_at | TIMESTAMP | NULL | 更新日時 |

**インデックス:**
- `INDEX(contract_id)`
- `INDEX(product_id)`

### 2. 認証機能

#### Laravel Breeze導入
- **パッケージ**: laravel/breeze v1.29.1（PHP 8.1対応）
- **スタック**: Blade
- **機能**:
  - ログイン
  - ログアウト
  - パスワードリセット
  - プロフィール編集

#### 初期管理者アカウント
- **ユーザー名**: dsbrand
- **メールアドレス**: dsbrand@example.com
- **パスワード**: cs20051101

### 3. 商品管理機能

#### モデル
- **Product**: 商品マスタ
  - リレーション: `hasMany(ContractItem)`
  - スコープ: `active()`, `plans()`, `options()`
  - アクセサ: `formatted_price`, `type_label`

- **ContractItem**: 契約明細
  - リレーション: `belongsTo(Contract)`, `belongsTo(Product)`

#### コントローラー
- **Admin\ProductController**: RESTfulコントローラー
  - `index()`: 商品一覧
  - `create()`: 新規作成フォーム
  - `store()`: 新規作成処理
  - `edit()`: 編集フォーム
  - `update()`: 更新処理
  - `destroy()`: 削除処理

#### ビュー
- `admin/products/index.blade.php`: 商品一覧
- `admin/products/create.blade.php`: 新規作成フォーム
- `admin/products/edit.blade.php`: 編集フォーム
- `admin/products/_form.blade.php`: フォーム共通パーツ

### 4. ルーティング設計

#### 公開ページ（認証不要）
- `/billing/`: トップページ
- `/billing/contract/create`: 新規申込フォーム
- `/billing/contract/confirm`: 申込内容確認
- `/billing/contract/store`: 契約・決済作成
- `/billing/contract/complete`: 申込完了
- `/billing/return/success`: 決済成功
- `/billing/return/cancel`: 決済キャンセル
- `/billing/return/failure`: 決済失敗
- `/billing/login`: ログイン画面

#### 管理画面（認証必須）
- `/billing/admin/dashboard`: ダッシュボード
- `/billing/admin/contracts`: 契約一覧
- `/billing/admin/contracts/{id}`: 契約詳細
- `/billing/admin/products`: 商品一覧
- `/billing/admin/products/create`: 商品新規作成
- `/billing/admin/products/{id}/edit`: 商品編集
- 決済連携（ROBOT PAYMENT 等）の設定画面は、実装する決済方式に応じてルート・メニューを整備する

### 5. レイアウト構造

#### layouts/public.blade.php
- 公開ページ用レイアウト
- シンプルなナビゲーション（ホーム、新規申込、ログイン）

#### layouts/admin.blade.php
- 管理画面用レイアウト
- フルナビゲーション（ダッシュボード、契約管理、商品管理、決済連携設定、ログアウト）

#### layouts/app.blade.php（Breeze用）
- 認証関連ページ用
- Breezeのコンポーネント利用

### 6. ダッシュボード

#### 統計カード
- 契約数
- 有効契約数
- 商品数
- 決済完了数

#### 最近の契約
- 最新5件の契約表示
- 契約ステータスの色分け

#### クイックアクション
- 契約一覧へのリンク
- 商品管理へのリンク
- 商品新規作成へのリンク
- 決済連携設定へのリンク

### 7. 初期データ

#### 商品データ（8件）

**プラン（6件）:**
1. 学習ページ数 50 - 5,500円 (PLAN-050)
2. 学習ページ数 100 - 10,450円 (PLAN-100)
3. 学習ページ数 150 - 15,675円 (PLAN-150)
4. 学習ページ数 200 - 20,900円 (PLAN-200)
5. 学習ページ数 250 - 24,750円 (PLAN-250)
6. 学習ページ数 300 - 28,050円 (PLAN-300)

**オプション（2件）:**
1. プライオリティサポート - 5,000円 (OPT-SUPPORT)
2. データバックアップ - 3,000円 (OPT-BACKUP)

## セキュリティ

### 認証ミドルウェア
- 管理画面の全ルートに `auth` ミドルウェアを適用
- 未認証ユーザーは自動的にログインページへリダイレクト

### CSRF保護
- すべてのPOST/PUT/DELETEリクエストにCSRFトークン必須
- 決済通知エンドポイント（実装する決済方式のパス）のみ除外

### パスワードハッシュ
- Laravelの標準ハッシュ機能（bcrypt）を使用
- 管理者パスワード: `cs20051101` → ハッシュ化されてDB保存

## 商品追加の拡張性

### スナップショット機能
契約明細テーブル（contract_items）では、商品情報のスナップショットを保存します。
- 商品名、商品コード、単価を契約時点の値で保存
- 商品マスタが変更されても、過去の契約情報は影響を受けない

### 複数商品対応
将来的に1契約に複数商品を紐付けることが可能な設計:
1. 申込フォームでプラン + オプションを選択
2. 各商品ごとに `contract_items` レコードを作成
3. 合計金額を `payments.amount` に設定

### 商品属性（attributes）
JSON形式で任意の属性を保存可能:
```json
{
  "page_count": 100,
  "response_time": "24h",
  "frequency": "daily"
}
```

## 今後の拡張

### 機能追加候補
1. **複数商品選択**: 申込フォームでプラン + オプションを同時選択
2. **商品カテゴリ**: 商品を階層的に管理
3. **在庫管理**: 数量制限のある商品
4. **期間限定商品**: 有効期間の設定
5. **クーポン/割引**: 商品への割引適用
6. **バンドル商品**: 複数商品のセット販売
7. **定期契約**: 月額/年額プラン
8. **商品検索**: 商品名・コードでの検索
9. **商品並び替え**: ドラッグ&ドロップでdisplay_order変更
10. **商品履歴**: 価格変更履歴の管理

### 管理機能追加候補
1. **ロール管理**: 管理者権限の階層化
2. **監査ログ**: 管理者の操作履歴
3. **ダッシュボード拡張**: グラフ表示、期間指定
4. **レポート**: 売上集計、契約傾向分析
5. **通知機能**: 新規契約時のメール通知

## 変更履歴

| 日付 | 変更者 | 変更内容 |
|------|-------|---------|
| 2026-01-07 | AI | 初版作成 |
| 2026-01-07 | AI | 商品管理機能追加 |
| 2026-01-07 | AI | 認証機能追加（Laravel Breeze） |
| 2026-01-07 | AI | ダッシュボード作成 |

