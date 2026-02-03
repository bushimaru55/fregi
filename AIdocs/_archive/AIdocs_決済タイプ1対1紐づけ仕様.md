# 決済タイプ1対1紐づけ仕様

作成日: 2026-01-13

## 概要

各契約プラン（商品）に対して、決済タイプ（一回限りの決済プラン/月額課金プラン）が必ず1対1で紐づくことを保証する仕様を定義します。

## 仕様

### 1対1の紐づけ保証

各契約プラン（`contract_plans`テーブルの1レコード）に対して、決済タイプ（`billing_type`）が必ず1つ設定されます。

- **1対1の関係**: 1つのプラン = 1つの決済タイプ（必ず設定される）
- **NULL不可**: `billing_type`はNOT NULL制約
- **デフォルト値**: `one_time`（一回限り）
- **可能な値**: `one_time`（一回限り）または `monthly`（月額課金）

## データベース設計

### contract_plansテーブル

| カラム名 | 型 | 制約 | 説明 |
|---------|-----|------|------|
| billing_type | ENUM | NOT NULL, DEFAULT 'one_time' | 決済タイプ（one_time: 一回限り, monthly: 月額課金） |

**データベース制約:**
- `NOT NULL`: `billing_type`は必ず値が設定される（NULL不可）
- `DEFAULT 'one_time'`: 新規作成時に値が指定されない場合のデフォルト値
- `ENUM('one_time', 'monthly')`: 許可される値は`one_time`または`monthly`のみ

## アプリケーションレベルでの保証

### 1. バリデーション

**ContractPlanController::store() / update():**
```php
$validated = $request->validate([
    'billing_type' => 'required|in:one_time,monthly',
    // ... 他のフィールド
]);
```

- `required`: 必須フィールド（空値は不可）
- `in:one_time,monthly`: 許可される値のみ

### 2. フォーム（管理画面）

**resources/views/admin/contract-plans/_form.blade.php:**
- 決済タイプ選択フィールドが`required`属性で設定
- ユーザーは必ず「一回限り」または「月額課金」を選択する必要がある

### 3. モデル

**app/Models/ContractPlan.php:**
- `billing_type`が`$fillable`に含まれている
- デフォルト値はデータベースレベルで設定される

## 実装の確認

### データベースレベル

```sql
-- billing_typeカラムの定義
ALTER TABLE contract_plans 
ADD COLUMN billing_type ENUM('one_time', 'monthly') 
NOT NULL 
DEFAULT 'one_time' 
COMMENT '決済タイプ（one_time: 一回限り, monthly: 月額課金）';
```

### アプリケーションレベル

1. **新規プラン作成時**
   - フォームで`billing_type`が必須入力
   - バリデーションで`required|in:one_time,monthly`をチェック
   - データベースに保存される

2. **既存プラン更新時**
   - フォームで`billing_type`が必須入力
   - バリデーションで`required|in:one_time,monthly`をチェック
   - データベースが更新される

3. **既存データ（マイグレーション適用時）**
   - マイグレーション実行時に、既存レコードに`DEFAULT 'one_time'`が自動的に設定される
   - 全てのプランに`billing_type`が設定される

## 決済タイプの値

| 値 | 説明 |
|-----|------|
| `one_time` | 一回限りの決済プラン |
| `monthly` | 月額課金プラン（決済連携先の月額課金サービス） |

## データ整合性の保証

### 必須チェック

1. **データベースレベル**
   - `NOT NULL`制約により、NULL値は許可されない
   - `ENUM`型により、許可されていない値は保存できない

2. **アプリケーションレベル**
   - フォームバリデーションで必須チェック
   - コントローラーでのバリデーションで必須チェック

3. **既存データの整合性**
   - マイグレーション適用時に、既存レコードにデフォルト値が設定される
   - 全てのプランに`billing_type`が設定されることを保証

## まとめ

- ✅ 各契約プランは必ず1つの決済タイプを持つ（1対1の関係）
- ✅ `billing_type`はNOT NULL制約で、NULL値は許可されない
- ✅ デフォルト値は`one_time`（一回限り）
- ✅ フォームとバリデーションで必須入力として設定
- ✅ データベースとアプリケーションの両レベルで整合性を保証

---

作成日: 2026-01-13
