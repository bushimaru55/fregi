# F-REGI ユーザーID・顧客ID 確認結果

確認日時: 2026-01-13

## 確認内容

F-REGI管理画面の取引一覧で「ユーザーID」と「顧客ID」が空欄になっているため、これらのパラメータが任意（オプション）かどうかを確認しました。

## 確認結果

### ✅ **ユーザーID（USERID）と顧客ID（CUSTOMERID）は任意パラメータです**

## 根拠

### 1. サンプルプログラム（compsettleapply_sample.php）の確認

F-REGIから提供されているサンプルプログラム（`compsettleapply_sample.php`）には、以下のパラメータが含まれています：

**必須パラメータ（サンプルに含まれる）:**
- `SHOPID` - 店舗ID（必須）
- `ID` - 伝票番号（必須）
- `PAY` - 金額（必須）

**任意パラメータ（サンプルに含まれるが、必須ではない）:**
- `USERNAME1` - 購入者名（姓）
- `USERNAME2` - 購入者名（名）
- `USERNAMEKANA1` - 購入者名カナ（姓）
- `USERNAMEKANA2` - 購入者名カナ（名）
- `USERTEL` - 購入者電話番号
- `ITEMTITLE` - 商品名
- `ITEMNAME` - pay-easy用商品名
- `ITEMNAMEKANA` - pay-easy用商品名カナ
- `EXPIRE` - 有効期限
- `CHARCODE` - 文字コード
- `QUIET` - サイレントモードフラグ

**重要:** `USERID`や`CUSTOMERID`はサンプルプログラムに含まれていません。

### 2. 現在の実装コードの確認

現在の実装（`ContractController.php`、`FregiApiService.php`）では、発行受付APIに送信しているパラメータは：

```php
$apiParams = [
    'SHOPID' => $fregiConfig->shopid,
    'ID' => $payment->orderid, // 伝票番号（最大20文字）
    'PAY' => (string)$payment->amount, // 金額
];
```

必須パラメータチェック（`FregiApiService::issuePayment()`）でも、以下の3つのみをチェックしています：

```php
if (empty($params['SHOPID']) || empty($params['ID']) || empty($params['PAY'])) {
    throw new \Exception('必須パラメータが不足しています（SHOPID, ID, PAY）');
}
```

`USERID`や`CUSTOMERID`は必須パラメータチェックに含まれていません。

### 3. F-REGI管理画面での表示

F-REGI管理画面の取引一覧で「ユーザーID」と「顧客ID」が空欄になっているのは、これらのパラメータを送信していないためです。これは正常な動作です。

## 結論

**ユーザーID（USERID）と顧客ID（CUSTOMERID）は任意パラメータです。**

- 発行受付API（`compsettleapply.cgi`）に送信する必須パラメータは以下の3つのみ：
  1. `SHOPID` - 店舗ID
  2. `ID` - 伝票番号
  3. `PAY` - 金額

- `USERID`や`CUSTOMERID`は送信しなくても決済処理は正常に動作します。

- F-REGI管理画面で空欄になるのは正常な動作です。

## 補足情報

### USERID・CUSTOMERIDの用途（推測）

F-REGIでは、ユーザーIDや顧客IDは以下のような用途で使用される可能性があります：

1. **会員管理システムとの連携**
   - 自社の会員管理システムと連携する場合、ユーザーIDや顧客IDを送信することで、F-REGI側で管理できます

2. **定期課金サービスとの連携**
   - 定期課金サービスを利用する場合、顧客を識別するために使用される可能性があります

3. **分析・レポート機能**
   - F-REGI管理画面で顧客を識別するために使用される可能性があります

現在のシステムでは、これらのパラメータを送信していないため、F-REGI管理画面で空欄になっていますが、決済処理には影響しません。

## 推奨事項

現在の実装で問題ありません。USERIDやCUSTOMERIDは送信しなくても決済処理は正常に動作します。

もし将来的に会員管理システムと連携する必要がある場合は、以下のパラメータを追加することもできます：

- `USERID` - ユーザーID（最大20文字）
- `CUSTOMERID` - 顧客ID（最大20文字）

ただし、これらのパラメータを追加する場合は、F-REGI仕様書で詳細を確認することをお勧めします。

---

作成日: 2026-01-13
