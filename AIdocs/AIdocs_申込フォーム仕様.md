# 申込フォーム仕様書

## 概要

F-REGI決済システムと連携した契約申込フォームの仕様を定義します。

## データベース設計

### 1. contract_plans（契約プラン）

| カラム名 | 型 | 制約 | 説明 |
|---------|-----|------|------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | プランID |
| name | VARCHAR(255) | NOT NULL | プラン名 |
| page_count | INT UNSIGNED | NOT NULL, UNIQUE | 学習ページ数 |
| price | INT UNSIGNED | NOT NULL | 料金（税込） |
| description | TEXT | NULL | プラン説明 |
| is_active | BOOLEAN | DEFAULT TRUE | 有効フラグ |
| display_order | INT UNSIGNED | DEFAULT 0 | 表示順 |
| created_at | TIMESTAMP | NULL | 作成日時 |
| updated_at | TIMESTAMP | NULL | 更新日時 |

**インデックス:**
- `UNIQUE(page_count)`
- `INDEX(is_active, display_order)`

**初期データ:**
```php
[
    ['name' => '学習ページ数 50', 'page_count' => 50, 'price' => 5500],
    ['name' => '学習ページ数 100', 'page_count' => 100, 'price' => 10450],
    ['name' => '学習ページ数 150', 'page_count' => 150, 'price' => 15675],
    ['name' => '学習ページ数 200', 'page_count' => 200, 'price' => 20900],
    ['name' => '学習ページ数 250', 'page_count' => 250, 'price' => 24750],
    ['name' => '学習ページ数 300', 'page_count' => 300, 'price' => 28050],
]
```

### 2. contracts（契約情報）

| カラム名 | 型 | 制約 | 説明 |
|---------|-----|------|------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | 契約ID |
| contract_plan_id | BIGINT UNSIGNED | FK(contract_plans.id), NOT NULL | 契約プランID |
| payment_id | BIGINT UNSIGNED | FK(payments.id), NULL | 決済ID |
| status | ENUM | NOT NULL, DEFAULT 'draft' | 契約ステータス |
| company_name | VARCHAR(255) | NOT NULL | 会社名 |
| company_name_kana | VARCHAR(255) | NULL | 会社名（フリガナ） |
| department | VARCHAR(255) | NULL | 部署名 |
| position | VARCHAR(255) | NULL | 役職 |
| contact_name | VARCHAR(255) | NOT NULL | 担当者名 |
| contact_name_kana | VARCHAR(255) | NULL | 担当者名（フリガナ） |
| email | VARCHAR(255) | NOT NULL | メールアドレス |
| phone | VARCHAR(255) | NOT NULL | 電話番号 |
| postal_code | VARCHAR(255) | NULL | 郵便番号 |
| prefecture | VARCHAR(255) | NULL | 都道府県 |
| city | VARCHAR(255) | NULL | 市区町村 |
| address_line1 | VARCHAR(255) | NULL | 番地 |
| address_line2 | VARCHAR(255) | NULL | 建物名 |
| desired_start_date | DATE | NOT NULL | 利用開始希望日 |
| actual_start_date | DATE | NULL | 実際の利用開始日 |
| end_date | DATE | NULL | 利用終了日 |
| notes | TEXT | NULL | 備考 |
| created_at | TIMESTAMP | NULL | 作成日時 |
| updated_at | TIMESTAMP | NULL | 更新日時 |

**ステータス:**
- `draft`: 下書き（入力中）
- `pending_payment`: 決済待ち
- `active`: 有効
- `canceled`: キャンセル
- `expired`: 期限切れ

**インデックス:**
- `INDEX(status, created_at)`
- `INDEX(email)`
- `INDEX(desired_start_date)`

## 申込フロー

### 1. 申込フォーム入力（/contract/create）

**入力項目:**

#### 1.1 申込企業情報
- 会社名（必須）
- 会社名（フリガナ）※全角カタカナ
- 部署名
- 役職
- 担当者名（必須）
- 担当者名（フリガナ）※全角カタカナ
- メールアドレス（必須）
- 電話番号（必須）※数字とハイフン
- 郵便番号 ※7桁（ハイフン有無可）
- 都道府県（プルダウン）
- 市区町村
- 番地
- 建物名

#### 1.2 契約内容の選択
- 契約プラン選択（必須）※6プランから選択
- 利用開始希望日（必須）※カレンダー入力、本日以降

### 2. 申込内容確認（/contract/confirm）

- 入力内容の確認画面を表示
- 「戻る」ボタンで修正可能
- 「決済へ進む」ボタンで次へ

### 3. 契約・決済データ作成（/contract/store）

**処理内容:**
1. Contractレコード作成（status: draft）
2. Paymentレコード作成（status: created）
   - order_no: `ORD-{YmdHis}-{contract_id}`
   - amount: プラン料金
   - currency: JPY
   - payment_method: credit_card
3. ContractにPayment IDを紐付け（status: pending_payment）
4. F-REGI設定取得
5. F-REGIへのPOSTパラメータ生成
6. F-REGI決済画面へリダイレクト

### 4. F-REGI決済画面

- F-REGI側でクレジットカード情報を入力
- 決済処理実行

### 5. F-REGI通知受領（/api/fregi/notify）

**処理内容:**
1. チェックサム検証
2. Paymentステータス更新（冪等処理）
   - SUCCESS → paid
   - FAILURE → failed
   - CANCEL → canceled
3. Contractステータス更新
   - paid → active
   - failed/canceled → pending_payment（再決済可能）

### 6. 戻りURL（/return/success）

- 決済結果表示
- 契約完了画面へ遷移（/contract/complete）

## バリデーションルール

```php
[
    'contract_plan_id' => 'required|exists:contract_plans,id',
    'company_name' => 'required|string|max:255',
    'company_name_kana' => 'nullable|string|max:255|regex:/^[ァ-ヶー\s]+$/u',
    'department' => 'nullable|string|max:255',
    'position' => 'nullable|string|max:255',
    'contact_name' => 'required|string|max:255',
    'contact_name_kana' => 'nullable|string|max:255|regex:/^[ァ-ヶー\s]+$/u',
    'email' => 'required|email|max:255',
    'phone' => 'required|string|regex:/^[0-9\-]+$/',
    'postal_code' => 'nullable|string|regex:/^\d{3}-?\d{4}$/',
    'prefecture' => 'nullable|string|max:255',
    'city' => 'nullable|string|max:255',
    'address_line1' => 'nullable|string|max:255',
    'address_line2' => 'nullable|string|max:255',
    'desired_start_date' => 'required|date|after_or_equal:today',
]
```

## URL設計

| URL | メソッド | 説明 |
|-----|---------|------|
| /contract/create | GET | 申込フォーム表示 |
| /contract/confirm | POST | 申込内容確認 |
| /contract/store | POST | 契約・決済作成 |
| /contract/complete | GET | 申込完了画面 |
| /admin/contracts | GET | 契約一覧（管理画面） |
| /admin/contracts/{id} | GET | 契約詳細（管理画面） |

## モデル

### ContractPlan

**リレーション:**
- `hasMany(Contract)`: 契約

**スコープ:**
- `active()`: 有効なプランのみ取得

**アクセサ:**
- `formatted_price`: 料金を「5,500円」形式で取得

### Contract

**リレーション:**
- `belongsTo(ContractPlan)`: 契約プラン
- `belongsTo(Payment)`: 決済情報

**アクセサ:**
- `full_address`: 完全な住所を取得
- `status_label`: ステータスの日本語ラベル

## ビュー

### 申込フォーム（contracts/create.blade.php）
- 2カラムグリッドレイアウト
- プラン選択はカード形式
- Font Awesome アイコン使用
- Tailwind CSS スタイリング

### 確認画面（contracts/confirm.blade.php）
- 入力内容の読みやすい表示
- 隠しフィールドで全データ送信
- 戻る/決済へ進むボタン

### 完了画面（contracts/complete.blade.php）
- 契約情報・決済情報の表示
- 今後の流れの案内
- トップページへのリンク

### 管理画面（admin/contracts/index.blade.php）
- 契約一覧テーブル
- ステータス別色分け
- ページネーション対応

### 管理画面（admin/contracts/show.blade.php）
- 契約詳細情報
- 申込企業情報
- 決済情報・イベント履歴

## セキュリティ

### CSRF保護
- 申込フォーム: CSRF トークン必須
- F-REGI通知: CSRF除外（`VerifyCsrfToken.php`）

### バリデーション
- サーバーサイドバリデーション必須
- クライアントサイドバリデーション推奨

### 個人情報保護
- カード情報は当システムで扱わない（F-REGI側で入力）
- 通知ペイロードは暗号化推奨

## 今後の拡張

### 機能追加候補
1. メール通知（申込完了、決済完了）
2. 契約更新・解約機能
3. 請求書発行
4. 利用状況レポート
5. プラン変更機能

### 改善候補
1. 住所自動入力（郵便番号API連携）
2. 決済方法の追加（コンビニ決済、銀行振込等）
3. 多言語対応
4. PDF出力（契約書、領収書）

## 変更履歴

| 日付 | 変更者 | 変更内容 |
|------|-------|---------|
| 2026-01-07 | AI | 初版作成 |

