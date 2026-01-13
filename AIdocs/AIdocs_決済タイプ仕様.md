# 決済タイプ（一回限り/月額課金）仕様書

作成日: 2026-01-13  
最終更新: 2026-01-13

## 概要

契約プランに決済タイプ（一回限り/月額課金）を追加し、商品によって異なる決済方式をサポートする仕様を定義します。

**重要:** 各契約プラン（商品）に対して、決済タイプは必ず1対1で紐づきます。

## データベース設計

### contract_plansテーブルへの追加

| カラム名 | 型 | 制約 | 説明 |
|---------|-----|------|------|
| billing_type | ENUM | NOT NULL, DEFAULT 'one_time' | 決済タイプ（one_time: 一回限り, monthly: 月額課金） |

**1対1の紐づけ保証:**
- 各契約プランは必ず1つの決済タイプを持つ（1対1の関係）
- `NOT NULL`制約により、NULL値は許可されない
- `DEFAULT 'one_time'`により、新規作成時のデフォルト値が設定される

**値の定義:**
- `one_time`: 一回限りの決済（リダイレクト決済）
- `monthly`: 月額課金（F-REGI月次課金サービス）

**デフォルト値:** `one_time`

## 実装内容

### 1. データベーススキーマ

**マイグレーション:** `2026_01_13_025452_add_billing_type_to_contract_plans_table.php`

```php
$table->enum('billing_type', ['one_time', 'monthly'])
    ->default('one_time')
    ->after('price')
    ->comment('決済タイプ（one_time: 一回限り, monthly: 月額課金）');
```

### 2. モデル（ContractPlan）

**追加フィールド:**
- `billing_type` - fillableに追加

**追加メソッド:**
- `getBillingTypeLabelAttribute()`: 決済タイプのラベルを取得（一回限り/月額課金）
- `scopeOneTime($query)`: 一回限りの決済プランのみを取得
- `scopeMonthly($query)`: 月額課金プランのみを取得

**修正メソッド:**
- `getFormattedPriceAttribute()`: 月額課金の場合は「/月」を付加

### 3. 契約プラン管理画面

**フォーム（_form.blade.php）:**
- 決済タイプ選択フィールドを追加（プルダウン）
  - 一回限り
  - 月額課金
- JavaScriptで決済タイプに応じて料金の単位表示を変更（「円」→「円/月」）

**一覧画面（index.blade.php）:**
- 決済タイプ列を追加
- 月額課金: 青色バッジ「月額課金」
- 一回限り: グレー色バッジ「一回限り」

**コントローラー（ContractPlanController）:**
- `store()`: `billing_type`のバリデーションを追加（required|in:one_time,monthly）
- `update()`: 同様に`billing_type`のバリデーションを追加

### 4. 決済処理（ContractController）

**store()メソッド:**
- プランの`billing_type`を確認
- `monthly`の場合: `MONTHLYMODE = '0'`パラメータを追加してF-REGI発行受付APIを呼び出す
- `one_time`の場合: `MONTHLYMODE`パラメータを省略（デフォルト: `1:随時課金`）してF-REGI発行受付APIを呼び出す

**MONTHLYMODEパラメータ:**
- パラメータ名: `MONTHLYMODE`（カード月次登録フラグ）
- `billing_type = 'monthly'`: `MONTHLYMODE = '0'`（月次課金）
- `billing_type = 'one_time'`: `MONTHLYMODE`を省略（デフォルト: `1:随時課金`）

### 5. 表示・UI

**料金表示:**
- 一回限り: `5,500円`
- 月額課金: `5,500円/月`

**管理画面での表示:**
- 契約プラン一覧: 決済タイプをバッジで表示
- 契約プラン詳細: 決済タイプを表示（実装が必要）

## 決済フロー

### 一回限りの決済（billing_type = 'one_time'）

1. ユーザーが申込フォームからプランを選択
2. 決済情報を作成
3. F-REGI発行受付API（`compsettleapply.cgi`）を呼び出し
4. お支払い方法選択画面へリダイレクト
5. ユーザーがカード情報を入力
6. 決済処理実行
7. 通知受領（/api/fregi/notify）
8. 戻りURL処理（/return）
9. 契約完了

**現在の実装:** ✅ 実装済み

### 月額課金（billing_type = 'monthly'）

1. ユーザーが申込フォームからプランを選択
2. 決済情報を作成
3. F-REGI発行受付API（`compsettleapply.cgi`）を呼び出し（`MONTHLYMODE = '0'`を送信）
4. お支払い方法選択画面へリダイレクト
5. ユーザーがカード情報を入力
6. 決済処理実行（初回決済）
7. 通知受領（/api/fregi/notify）
8. 戻りURL処理（/return）
9. 契約完了
10. **毎月自動的に決済が実行される（F-REGI側で処理）**

**現在の実装:** ✅ 実装済み（MONTHLYMODE = '0'を送信）

## 実装済み項目（2026-01-13更新）

### MONTHLYMODEパラメータの実装

月額課金の基本的な実装は完了しています：

1. **MONTHLYMODEパラメータの送信**
   - `billing_type = 'monthly'`の場合、`MONTHLYMODE = '0'`を送信
   - `billing_type = 'one_time'`の場合、`MONTHLYMODE`を省略（デフォルト: `1:随時課金`）

2. **F-REGI発行受付APIへの対応**
   - 月額課金の場合も、通常のリダイレクト決済API（`compsettleapply.cgi`）を使用
   - `MONTHLYMODE = '0'`を送信することで、月次課金として処理される

## 今後の拡張（必要に応じて）

月額課金をさらに充実させるためには、以下を検討してください：

1. **F-REGI管理画面での月次課金サービス設定**
   - F-REGI管理画面で月次課金サービスの設定を確認
   - 自動決済のスケジュール設定を確認

2. **月額課金の通知処理**
   - 毎月の自動決済通知を受信する処理を実装
   - 通知パラメータの仕様を確認

3. **月額課金の管理機能**
   - 契約プラン詳細画面で決済タイプを表示
   - 月額課金契約の一覧表示
   - 月額課金契約のキャンセル機能

## 推奨事項

### 次のステップ（必要に応じて）

1. **テスト環境での動作確認**
   - F-REGIテスト環境で月額課金プランの決済をテスト
   - `MONTHLYMODE = '0'`が正しく送信されているか確認
   - 月次課金として処理されることを確認

2. **F-REGI管理画面での確認**
   - F-REGI管理画面で月次課金サービスの設定を確認
   - 自動決済の設定を確認

3. **月額課金の自動決済確認**
   - 月額課金契約の自動決済が正常に動作するか確認
   - 毎月の自動決済通知を受信できるか確認

4. **管理機能の拡張（必要に応じて）**
   - 月額課金契約の管理機能を実装
   - 月額課金契約のキャンセル機能を実装

---

作成日: 2026-01-13  
最終更新: 2026-01-13（MONTHLYMODE実装完了）
