# Phase 2: 月額課金決済テスト手順

作成日: 2026-01-13

## テスト対象

- **プラン**: テスト用 月額課金プラン（ID: 22, ¥5,000）
- **決済方法**: F-REGI authm.cgi API（MONTHLY=1, MONTHLYMODE=0）
- **テストカード**: 4980-1111-1111-1111（セキュリティコード: 111）

## テスト手順

### 1. 公開申込フォームにアクセス

URL: `http://localhost:8080/billing/contract/create?plans=22`

**確認事項**:
- ✅ 月額課金プラン（ID: 22）のみが表示される
- ✅ 他のプランが表示されない

### 2. 申込フォームに入力

必須項目:
- 会社名: テスト会社株式会社
- 担当者名: テスト 太郎
- メールアドレス: test@example.com
- 電話番号: 03-1234-5678
- 契約プラン: テスト用 月額課金プラン（ID: 22）を選択
- 利用開始希望日: 今日以降の日付を選択
- 利用規約への同意: ✅ チェック

### 3. 「確認画面へ」ボタンをクリック

**確認事項**:
- ✅ 確認画面に遷移する
- ✅ カード情報入力フォームが表示される

### 4. カード情報を入力

- カード番号: 4980-1111-1111-1111
  - PAN1: 4980
  - PAN2: 1111
  - PAN3: 1111
  - PAN4: 1111
- 有効期限（月）: 10
- 有効期限（年）: 30
- カード名義: TEST USER
- セキュリティコード: 111（承認成功用）

### 5. 「決済へ進む」ボタンをクリック

**確認事項**:
- ✅ 正常に決済処理が完了する
- ✅ 完了画面にリダイレクトされる
- ✅ エラーが発生しない

## データベース確認

テスト実行後、以下のSQLでデータを確認します：

```sql
-- 最新の契約を確認（customer_idが保存されているか）
SELECT id, contract_plan_id, status, customer_id, billing_type, created_at 
FROM contracts 
ORDER BY id DESC 
LIMIT 1;

-- 最新の決済を確認
SELECT id, contract_id, status, receiptno, slipno, orderid, created_at 
FROM payments 
ORDER BY id DESC 
LIMIT 1;

-- 契約プランのbilling_typeを確認
SELECT id, name, billing_type, price 
FROM contract_plans 
WHERE id = 22;
```

## 期待される結果

### 契約（contracts）テーブル
- `status`: `active`
- `customer_id`: 生成されたCUSTOMERID（例: `CUST00000120260113070744`形式、最大20文字）
- `billing_type`: プランから取得（`monthly`）

### 決済（payments）テーブル
- `status`: `paid`
- `receiptno`: F-REGIから返された承認番号（auth_code）
- `slipno`: F-REGIから返された取引番号（seqno）

### ログ確認

```bash
cd /Users/dfn4459wgl/Desktop/billing/app
docker-compose exec app tail -n 100 storage/logs/contract_payment.log | grep -A 20 "決済処理開始"
```

**確認事項**:
- `MONTHLY=1` が設定されている
- `MONTHLYMODE=0` が設定されている
- `CUSTOMERID` が生成されて送信されている
- 決済処理が正常に完了している

## テスト結果記録

テスト実施後、以下の情報を記録します：

- [ ] テスト日時
- [ ] テスト結果（成功/失敗）
- [ ] エラーメッセージ（ある場合）
- [ ] customer_idの値
- [ ] 決済ステータス
- [ ] 備考
