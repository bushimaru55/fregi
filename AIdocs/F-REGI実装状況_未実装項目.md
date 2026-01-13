# F-REGI実装状況：未実装項目

## 実装済み項目 ✅

1. **発行受付API（Issue API）**
   - `FregiApiService::issuePayment()` 実装済み
   - テスト環境で動作確認済み

2. **お支払い方法選択画面へのリダイレクト**
   - チェックサム生成：`FregiPaymentService::generatePaymentPageChecksum()` 実装済み
   - URL生成：`FregiApiService::getPaymentPageUrlWithParams()` 実装済み

3. **戻りURL処理**
   - `ReturnController::handle()` 実装済み
   - チェックサム検証：`FregiPaymentService::generateReturnUrlChecksum()` 実装済み
   - 決済ステータス更新実装済み

4. **通知URL処理**
   - `/api/fregi/notify` エンドポイント実装済み
   - `FregiNotifyController::handle()` 実装済み
   - `FregiPaymentService::processNotify()` 実装済み

5. **データベーススキーマ**
   - `payments`テーブルに`settleno`カラム追加済み
   - F-REGI設定管理テーブル実装済み

## 未実装項目 ❌

### ~~1. Contractステータス更新（通知処理時）~~ ✅ 実装完了（2026-01-13）

**仕様書要件（AIdocs_申込フォーム仕様.md 129-139行目）:**
- 通知処理時にContractステータスを更新する必要がある
- 更新ルール：
  - `paid` → `active`
  - `failed/canceled` → `pending_payment`（再決済可能）

**実装内容:**
- ✅ `FregiPaymentService::processNotify()`内でContractステータスを更新する処理を追加
- ✅ `ReturnController::handle()`内でもContractステータスを更新する処理を追加（冪等処理として実装）
- ✅ 決済完了時（`paid`）にContractを`active`に更新し、`actual_start_date`も設定
- ✅ 決済失敗/キャンセル時（`failed/canceled`）にContractを`pending_payment`に設定（再決済可能）

---

### 2. キャンセルAPI（compsettlecancel.cgi）

**現状:**
- `FregiApiService::getApiUrl()`でURL定義はあるが、実装メソッドがない
- 管理画面から決済をキャンセルする機能が未実装

**影響:**
- 管理画面から決済をキャンセルできない
- 決済エラー時の手動キャンセルができない

**推奨対応:**
- `FregiApiService::cancelPayment()`メソッドを実装
- 管理画面の契約詳細画面にキャンセルボタンを追加
- キャンセル処理のコントローラーメソッドを実装

---

### 3. 変更API（compsettlechange.cgi）

**現状:**
- `FregiApiService::getApiUrl()`でURL定義はあるが、実装メソッドがない
- 決済金額変更機能が未実装

**影響:**
- 決済後の金額変更ができない
- プラン変更に伴う決済金額調整ができない

**推奨対応:**
- `FregiApiService::changePayment()`メソッドを実装
- 管理画面から決済金額を変更する機能を実装（必要に応じて）

---

### 4. 情報取得API（compsettleinfo.cgi）

**現状:**
- `FregiApiService::getApiUrl()`でURL定義はあるが、実装メソッドがない
- 決済情報をF-REGIから取得する機能が未実装

**影響:**
- 決済状態の手動同期ができない
- 管理画面で最新の決済状態を取得できない

**推奨対応:**
- `FregiApiService::getPaymentInfo()`メソッドを実装
- 管理画面から手動で決済情報を同期する機能を実装（必要に応じて）

---

### 5. 通知のチェックサム検証

**現状:**
- `FregiPaymentService::verifyNotifySignature()`は常に`true`を返す（TODOコメントあり）

**影響:**
- 通知の真正性を検証できない
- 不正な通知を受け入れる可能性がある

**推奨対応:**
- F-REGI仕様書を確認し、通知のチェックサム検証方法を実装
- セキュリティ上の重要な項目のため、優先度は高い

---

### 6. 管理画面での決済操作UI

**現状:**
- 契約詳細画面（`admin/contracts/show.blade.php`）に決済操作ボタンがない

**影響:**
- 管理画面から決済を操作できない

**推奨対応:**
- 決済キャンセルボタンを追加（キャンセルAPI実装後）
- 決済情報同期ボタンを追加（情報取得API実装後）

---

## 優先度推奨

### 高優先度
1. **Contractステータス更新**（通知処理時）
   - 基本的な機能が動作するために必要

2. **通知のチェックサム検証**
   - セキュリティ上重要

### 中優先度
3. **キャンセルAPI**
   - 業務運用上必要

4. **情報取得API**
   - 決済状態の確認に有用

### 低優先度
5. **変更API**
   - 使用頻度が低い可能性

6. **管理画面での決済操作UI**
   - API実装後に実装

---

## 補足事項

### 実装時の注意点

1. **Contractステータス更新**
   - PaymentとContractのリレーションを確認（`payment->contract`）
   - トランザクション内で更新する
   - 冪等性を保証する（既に更新済みの場合はスキップ）

2. **API実装**
   - F-REGI仕様書（`F-REGI_redirect_ver3_4.pdf`）を参照
   - レスポンスの文字エンコーディング（EUC-JP → UTF-8）に注意
   - エラーハンドリングを適切に実装

3. **チェックサム検証**
   - F-REGI仕様書に記載されている方法を確認
   - タブ区切り文字列の扱いに注意

---

作成日: 2026-01-13
最終更新: 2026-01-13
