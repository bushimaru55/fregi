# F-REGI決済システム 現行仕様確認資料

**作成日:** 2026-01-20  
**目的:** F-REGI決済担当者との打ち合わせ用  
**対象システム:** 請求管理システム（Billing System）

---

## 目次

1. [システム概要](#1-システム概要)
2. [実装済み機能](#2-実装済み機能)
3. [F-REGI API連携仕様](#3-f-regi-api連携仕様)
4. [決済フロー](#4-決済フロー)
5. [環境設定](#5-環境設定)
6. [エラーハンドリング](#6-エラーハンドリング)
7. [ログ・監視機能](#7-ログ監視機能)
8. [確認事項・質問事項](#8-確認事項質問事項)

---

## 1. システム概要

### 1.1 システム構成

- **フレームワーク:** Laravel 10
- **データベース:** MySQL
- **決済ゲートウェイ:** F-REGI
- **本番環境:** Plesk（SSH/CLI不可）

### 1.2 使用するAPI（重要）

**✅ 本システムで使用するAPI:**

| 決済タイプ | 使用API | エンドポイント | 主なパラメータ | カード情報の扱い |
|-----------|---------|---------------|--------------|----------------|
| **一回限りの決済** | **authm.cgi**<br>（オーソリ処理） | テスト: `https://ssl.f-regi.com/connecttest/authm.cgi`<br>本番: `https://ssl.f-regi.com/connect/authm.cgi` | `MONTHLY=0`<br>（即時決済） | 毎回送信が必要 |
| **月額課金** | **authm.cgi**<br>（オーソリ処理） | 同上 | `MONTHLY=1`<br>`MONTHLYMODE=0`<br>`CUSTOMERID=<自動生成>` | 初回のみ送信<br>（顧客登録後はCUSTOMERIDで処理） |

**❌ 本システムで使用しないAPI:**
- **compsettleapply.cgi**（リダイレクト決済）: 使用していません

**重要なポイント:**
- **一回限り・月額課金の両方で、同じAPI（authm.cgi）を使用しています**
- `MONTHLY` パラメータの値（`0` または `1`）で決済タイプを区別します
- リダイレクト決済（compsettleapply.cgi）ではなく、**直接API呼び出し方式**を採用しています

### 1.3 決済方式の詳細

#### 一回限りの決済

- **API:** authm.cgi
- **パラメータ:** `MONTHLY=0`（即時決済）
- **カード情報:** 毎回送信が必要
- **顧客登録:** なし（F-REGI側に顧客として登録されない）

#### 月額課金

- **API:** authm.cgi
- **パラメータ:** 
  - `MONTHLY=1`（月次決済）
  - `MONTHLYMODE=0`（月次課金、データクリーニング有り）
  - `CUSTOMERID=<自動生成>`（例: `CUST202601200108540011`）
- **カード情報:** 初回のみ送信（顧客登録時）
- **顧客登録:** あり（F-REGI側に顧客として登録される）
- **月次自動引き落とし:** 今後実装予定（salem.cgiでCUSTOMERIDを使用）

### 1.4 顧客管理

- **カード情報:** F-REGI側で管理（自社DBには保存しない）
- **CUSTOMERID:** 自社DBに保存し、月次売上処理（salem.cgi）で使用予定
- **カード情報入力:** 初回登録時のみ、自社サイトでカード情報を入力

---

## 2. 実装済み機能

### 2.1 実装済みAPI

✅ **オーソリ処理（authm.cgi）** - **すべての決済で使用**
- **一回限りの決済:** `MONTHLY=0` で実行
- **月額課金の初回登録:** `MONTHLY=1`, `MONTHLYMODE=0`, `CUSTOMERID` で実行（顧客登録含む）
- 詳細ログ出力
- カード情報は都度送信（一回限り）または初回のみ送信（月額課金）

**注意:** 
- 本システムでは**リダイレクト決済（compsettleapply.cgi）は使用していません**
- すべての決済を**直接API（authm.cgi）で処理**しています

✅ **戻りURL処理（return）**
- 決済完了・キャンセル・失敗の処理
- ステータスに応じた画面遷移
- **注意:** 現在はauthm.cgiで直接処理するため、戻りURLは主にエラー処理用

⏳ **月次売上処理（salem.cgi）** - 未実装（今後の実装予定）
- 月額課金の2回目以降の自動引き落としに使用予定
- CUSTOMERIDのみを指定して実行

### 2.2 管理画面機能

✅ F-REGI設定管理（編集のみ）
- SHOP ID
- 接続パスワード（暗号化保存）
- 環境設定（test/prod）
- 会社ID（company_id=1で固定運用）

✅ 契約プラン管理
- プラン作成・編集
- 月額/一回限りの設定
- 料金設定

✅ 契約管理
- 契約一覧表示
- 契約詳細表示
- 決済状況確認

---

## 3. F-REGI API連携仕様

### 3.1 使用するAPI

**本システムで使用するAPI:**

| API | 用途 | 実装状況 | 備考 |
|-----|------|---------|------|
| **authm.cgi** | 一回限り・月額課金の決済処理 | ✅ 実装済み | **すべての決済で使用** |
| **return** | 戻りURL処理 | ✅ 実装済み | 主にエラー処理用 |
| **salem.cgi** | 月次売上処理（自動引き落とし） | ⏳ 未実装 | 今後の実装予定 |

**本システムで使用しないAPI:**

| API | 用途 | 使用しない理由 |
|-----|------|--------------|
| **compsettleapply.cgi** | リダイレクト決済 | 直接API（authm.cgi）で処理するため |

### 3.2 エンドポイント

#### オーソリ処理（authm.cgi） - **すべての決済で使用**

**テスト環境:**
```
https://ssl.f-regi.com/connecttest/authm.cgi
```

**本番環境:**
```
https://ssl.f-regi.com/connect/authm.cgi
```

**環境切り替え:**
- `.env` ファイルの `FREGI_ENV` で制御
- `FREGI_ENV=test` → テスト環境
- `FREGI_ENV=prod` → 本番環境
- デフォルト: `test`

**重要な点:**
- **一回限りの決済**も**月額課金**も、同じ `authm.cgi` を使用
- `MONTHLY` パラメータで決済タイプを区別

### 3.2 オーソリ処理（authm.cgi）のパラメータ

#### 必須パラメータ

| パラメータ名 | 説明 | 形式・制約 | 実装上の設定値 |
|------------|------|-----------|--------------|
| `SHOPID` | 店舗ID | - | F-REGI管理画面で設定 |
| `PAY` | 金額（カンマ不要） | - | `$payment->amount`（整数） |
| `PAN1` | カード番号1~4桁目 | 半角数字4桁 | フォーム入力値 |
| `PAN2` | カード番号5~8桁目 | 半角数字4桁 | フォーム入力値 |
| `PAN3` | カード番号9~12桁目 | 半角数字4桁 | フォーム入力値 |
| `PAN4` | カード番号13~16桁目 | 半角数字4桁 | フォーム入力値 |
| `CARDEXPIRY1` | 有効期限（月） | 半角数字2桁（01-12） | フォーム入力値（2桁変換済み） |
| `CARDEXPIRY2` | 有効期限（年） | 半角数字2桁 | フォーム入力値（4桁→2桁変換） |
| `CARDNAME` | カード名義 | ASCII文字45文字以内 | フォーム入力値（大文字変換） |

#### 任意パラメータ

| パラメータ名 | 説明 | 実装上の設定値 | 備考 |
|------------|------|--------------|------|
| `ID` | 伝票番号（取引識別用） | `$payment->orderid` | 自動生成（例: `RD202601200108540011`） |
| `SCODE` | セキュリティコード | フォーム入力値 | 入力がある場合のみ送信 |
| `IP` | 接続元IPアドレス | `$request->ip()` | 必須ではないが送信 |
| `CHARCODE` | 文字コード | `euc` | デフォルトで設定 |
| `MONTHLY` | 月次決済フラグ | `1`（月額）<br>`0`（一回限り） | 契約プランの `billing_type` に応じて設定 |
| `MONTHLYMODE` | 課金タイプ | `0`（月次課金） | 月額課金の場合のみ設定 |
| `CUSTOMERID` | 顧客番号 | `$contract->generateCustomerId()` | 月額課金の場合のみ自動生成 |

#### パラメータ設定ロジック

```php
// 一回限りの決済
$apiParams = [
    'SHOPID' => $fregiConfig->shopid,
    'ID' => $payment->orderid,
    'PAY' => (string)$payment->amount,
    'PAN1' => $pan1,
    'PAN2' => $pan2,
    'PAN3' => $pan3,
    'PAN4' => $pan4,
    'CARDEXPIRY1' => $cardExpiryMonth,
    'CARDEXPIRY2' => $cardExpiryYear,
    'CARDNAME' => $cardName,
    'IP' => $request->ip(),
    'MONTHLY' => '0',  // 即時決済
    'CHARCODE' => 'euc',
];

// 月額課金の場合の追加パラメータ
if ($billingType === 'monthly') {
    $customerId = $contract->generateCustomerId();
    $apiParams['MONTHLY'] = '1';
    $apiParams['MONTHLYMODE'] = '0';  // 月次課金
    $apiParams['CUSTOMERID'] = $customerId;
}
```

### 3.3 レスポンス処理

#### 成功時（result=OK）

```
result=OK
auth_code=...
seqno=...
CUSTOMERID=...（月額課金の場合のみ）
```

**処理内容:**
- `auth_code`, `seqno` を `payments` テーブルに保存
- 月額課金の場合、`CUSTOMERID` を `contracts` テーブルに保存
- 契約ステータスを `active` に更新
- 決済完了画面にリダイレクト

#### 失敗時（result=NG）

```
result=NG
error_code=...
error_message=...
```

**処理内容:**
- エラー情報を `payment_events` テーブルに記録
- 確認画面にリダイレクトしてエラーメッセージを表示
- トランザクションは自動的にロールバック

### 3.4 戻りURL処理（return）

**エンドポイント:**
```
/webroot/billing/return?ID=...&STATUS=...
```

**STATUSパラメータ:**
- `OK`: 決済成功 → 完了画面にリダイレクト
- `CANCEL`: キャンセル → キャンセル画面を表示
- `NG`: 決済失敗 → 失敗画面を表示

**処理内容:**
- `ID`（orderid）で `Payment` を検索
- `STATUS` に応じて契約ステータスを更新
- 適切な画面を表示

---

## 4. 決済フロー

### 4.1 一回限りの決済フロー

```
1. 申込フォーム表示 (/contract/create)
   ↓
2. フォーム入力（会社情報、契約プラン、カード情報）
   ↓
3. 確認画面表示 (POST /contract/confirm)
   ↓
4. 「決済へ進む」ボタンクリック (POST /contract/store)
   ↓
5. 契約・決済情報をDBに保存
   ↓
6. F-REGIオーソリ処理（authm.cgi）実行
   - MONTHLY=0 で送信
   ↓
7. レスポンス受信
   - result=OK → 完了画面にリダイレクト
   - result=NG → 確認画面にエラー表示
```

### 4.2 月額課金フロー

```
1. 申込フォーム表示 (/contract/create)
   ↓
2. フォーム入力（会社情報、月額プラン、カード情報）
   ↓
3. 確認画面表示 (POST /contract/confirm)
   ↓
4. 「決済へ進む」ボタンクリック (POST /contract/store)
   ↓
5. 契約・決済情報をDBに保存
   - CUSTOMERIDを自動生成（例: `CUST202601200108540011`）
   ↓
6. F-REGIオーソリ処理（authm.cgi）実行
   - MONTHLY=1
   - MONTHLYMODE=0
   - CUSTOMERID=<生成したID>
   ↓
7. レスポンス受信
   - result=OK → CUSTOMERIDを保存 → 完了画面
   - result=NG → 確認画面にエラー表示
```

**今後の実装予定:**
- 月次売上処理（salem.cgi）で、保存された `CUSTOMERID` を使用して月次自動引き落としを実行

---

## 5. 環境設定

### 5.1 環境変数

| 変数名 | 説明 | デフォルト値 | 必須 |
|-------|------|------------|------|
| `FREGI_ENV` | F-REGI接続環境 | `test` | いいえ |
| `FREGI_SECRET_KEY` | 接続パスワード暗号化キー | - | **はい** |

### 5.2 FREGI_ENVの設定

```env
# テスト環境に接続
FREGI_ENV=test

# 本番環境に接続
FREGI_ENV=prod
```

**重要な注意点:**
- `FREGI_ENV` は `APP_ENV` とは**独立**して設定できます
- 本番サーバ（`APP_ENV=production`）でも `FREGI_ENV=test` を設定すればテスト環境に接続可能
- 設定変更後は、Plesk環境では `bootstrap/cache/config.php` を削除してConfig Cacheを再生成する必要があります

### 5.3 FREGI_SECRET_KEYの生成

**生成方法（macOS/Linux）:**
```bash
openssl rand -base64 32
```

**生成方法（Python）:**
```bash
python3 - <<'PY'
import os
import base64
print(base64.b64encode(os.urandom(32)).decode('utf-8'))
PY
```

**用途:**
- F-REGI設定の接続パスワードを暗号化してDBに保存するためのキー
- このキーが変更されると、既存の暗号化パスワードが復号できなくなります

---

## 6. エラーハンドリング

### 6.1 エラーレスポンス処理

**F-REGI APIからのエラーレスポンス:**
```php
// result=NG の場合
[
    'result' => 'NG',
    'error_code' => '...',
    'error_message' => '...'
]
```

**処理内容:**
1. エラー情報を `payment_events` テーブルに記録
   - `event_type`: `fregi_authorize_failed`
   - `payload`: エラー情報を含むJSON
2. 確認画面にリダイレクトしてエラーメッセージを表示
3. DBトランザクションは自動的にロールバック

### 6.2 カスタムエラーメッセージ

**よくあるエラーと対処:**
- **F-REGI設定が未登録:** DBに設定レコードが存在しない、または `FREGI_ENV` が不一致
- **必須パラメータ不足:** カード情報の入力漏れ
- **HTTPリクエストエラー:** ネットワークエラー、F-REGIサーバー障害

### 6.3 エラー時のログ出力

すべてのエラーは以下の情報を含む詳細ログを出力します：
- エラーメッセージ、エラーコード、ファイル名、行番号
- スタックトレース
- リクエスト情報（メソッド、URL、ヘッダー、パラメータ）
- セッション情報
- 環境情報（FREGI_ENV、APP_ENV、PHP/Laravelバージョン）

---

## 7. ログ・監視機能

### 7.1 ログファイル

**ログファイルの場所:**
```
/storage/logs/contract-payment-YYYY-MM-DD.log
```

**ログ保持期間:** 30日間

### 7.2 ログ出力内容

#### 決済フロー関連

| ログメッセージ | レベル | 出力タイミング |
|--------------|--------|--------------|
| `申込フォーム表示` | INFO | `/contract/create` アクセス時 |
| `確認画面表示（POST）` | INFO | `/contract/confirm` POST処理時 |
| `決済処理開始` | INFO | `/contract/store` POST処理開始時 |
| `契約作成完了` | INFO | 契約レコード作成時 |
| `決済情報作成完了` | INFO | 決済レコード作成時 |
| `F-REGIオーソリ処理開始` | INFO | authm.cgi API呼び出し前 |
| `決済処理完了` | INFO | 決済成功時 |
| `決済処理エラー（詳細）` | ERROR | エラー発生時 |

#### イベントログ（payment_eventsテーブル）

| event_type | 説明 | payloadに含まれる情報 |
|-----------|------|---------------------|
| `fregi_authorize_request` | APIリクエスト前 | url, fregi_env, shopid, orderid, amount, billing_type |
| `fregi_authorize_response` | APIレスポンス受信 | result, auth_code, seqno, customer_id, error_message |
| `fregi_authorize_success` | 決済成功 | auth_code, seqno, customer_id |
| `fregi_authorize_failed` | 決済失敗 | error_message, error_code |

**注意:** カード番号、セキュリティコード等の秘密情報はすべてマスク（`****`）して出力されます。

### 7.3 ブラウザコンソールログ

JavaScriptエラーもブラウザの開発者ツール（Console）に詳細情報を出力します：
- エラーメッセージ、ファイル名、行番号
- スタックトレース
- URL、User-Agent、タイムスタンプ

---

## 8. 確認事項・質問事項

### 8.1 実装に関する確認事項

#### ✅ 確認済み（実装済み）

1. **オーソリ処理（authm.cgi）のパラメータ形式**
   - ✅ パラメータ名、形式、必須/任意の区別は正しく実装済み

2. **月額課金のパラメータ設定**
   - ✅ `MONTHLY=1`, `MONTHLYMODE=0`, `CUSTOMERID` は正しく送信

3. **文字コード（CHARCODE）**
   - ✅ デフォルトで `euc` を設定

4. **ID（伝票番号）の生成形式**
   - ✅ 形式: `RD{YYYYMMDDHHMMSS}{連番6桁}` （例: `RD202601200108540011`）

5. **CUSTOMERIDの生成形式**
   - ✅ 形式: `CUST{YYYYMMDDHHMMSS}{連番6桁}` （例: `CUST202601200108540011`）

#### ❓ 確認が必要な事項

1. **月次売上処理（salem.cgi）の仕様**
   - ❓ パラメータ形式、必須パラメータ
   - ❓ 実行タイミング（毎月の自動実行方法）
   - ❓ エラーハンドリング方法
   - ❓ 実行結果の通知方法（Webhook等）

2. **カード情報の保存・管理**
   - ❓ 初回登録後、カード情報を更新する場合はどうすべきか？
   - ❓ カード有効期限切れ時の通知方法
   - ❓ カード削除・変更のAPI（compsettlechange.cgi等）の使用可否

3. **顧客管理（CUSTOMERID）**
   - ❓ 既存顧客のCUSTOMERIDを取得する方法
   - ❓ F-REGI管理画面で登録された顧客のCUSTOMERIDを自社システムと連携する方法
   - ❓ 顧客削除・停止の処理方法

4. **エラーハンドリング**
   - ❓ 特定のエラーコードに対する推奨される処理方法
   - ❓ 再試行ロジックの実装要否

5. **テスト環境**
   - ❓ テスト環境でのテストカード番号
   - ❓ テスト環境での制限事項
   - ❓ テスト環境から本番環境への移行手順

### 8.2 運用に関する確認事項

#### ❓ 確認が必要な事項

1. **決済承認処理**
   - ❓ 承認保留時の処理方法
   - ❓ 承認待ち時間の目安

2. **決済取消・返金処理**
   - ❓ 取消（compsettlecancel.cgi）の使用可否
   - ❓ 返金処理の手順
   - ❓ 取消可能な期間・条件

3. **月次自動引き落とし**
   - ❓ 実行日（毎月何日か）
   - ❓ 実行時間帯
   - ❓ 引き落とし失敗時の通知方法

4. **レポート・管理画面**
   - ❓ 決済履歴の確認方法（F-REGI管理画面）
   - ❓ 月次売上レポートの取得方法

### 8.3 セキュリティに関する確認事項

#### ✅ 実装済み

1. **カード情報の保存**
   - ✅ 自社DBには保存しない（F-REGI側で管理）
   - ✅ ログにもマスクして出力

2. **接続パスワードの暗号化**
   - ✅ AES-256-GCMで暗号化してDB保存
   - ✅ `.env` の `FREGI_SECRET_KEY` で管理

#### ❓ 確認が必要な事項

1. **PCI DSS準拠**
   - ❓ カード情報を自社サイトで入力することの要件
   - ❓ SSL/TLSの要件（現在は実装済み）

2. **データ保護**
   - ❓ カード情報の保持期間（F-REGI側）
   - ❓ データ削除の手順

---

## 9. 現在の実装状況まとめ

### ✅ 実装済み

- [x] オーソリ処理（authm.cgi）の実装
- [x] 一回限りの決済
- [x] 月額課金の初回登録（顧客登録含む）
- [x] 戻りURL処理（return）
- [x] エラーハンドリング
- [x] 詳細ログ出力
- [x] F-REGI設定管理画面
- [x] 環境切り替え機能（test/prod）

### ⏳ 今後の実装予定

- [ ] 月次売上処理（salem.cgi）
- [ ] カード情報更新処理（compsettlechange.cgi）
- [ ] 決済取消処理（compsettlecancel.cgi）
- [ ] 月次自動引き落としのスケジュール処理

---

## 10. 連絡先・参照資料

### 10.1 参考資料

- F-REGI クレジットカード処理インターフェース仕様書（月次課金仕様 authm.cgi）Ver2.8 2025/07/23版
- システム内部ドキュメント: `AIdocs/F-REGI設定_最終成果物.md`
- エラー対応手順: `AIdocs/F-REGI設定未登録エラー_対応手順.md`

### 10.2 技術的問い合わせ

本システムの実装に関する技術的な質問は、システム開発チームまでお問い合わせください。

---

**以上**
