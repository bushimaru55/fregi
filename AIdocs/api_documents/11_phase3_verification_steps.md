# フェーズ3 実装確認手順（請求先・決済手段 API 1・2）

フェーズ3で実装した「API 1 請求先登録更新」「API 2 クレジットカード登録」および契約フローとの連携を、ステップバイステップで確認する手順です。

---

## 前提条件の確認

### Step 0-1: 請求管理ロボの接続設定

1. **対象ファイル**: `app/.env` または `deploy/webroot_billing/app/.env`（ローカルでは `app/.env`）
2. 次の3つが設定されていることを確認する。
   - `BILLING_ROBO_BASE_URL`（例: `https://demo.billing-robo.jp`）
   - `BILLING_ROBO_USER_ID`（管理画面ログインID・メール形式）
   - `BILLING_ROBO_ACCESS_KEY`
3. **疎通テスト**で成功することを確認する。
   ```bash
   cd /path/to/billing
   docker compose run --rm -e BILLING_ROBO_BASE_URL=... -e BILLING_ROBO_USER_ID=... -e BILLING_ROBO_ACCESS_KEY=... app php artisan billing-robo:ping
   ```
   - 成功時: 「疎通成功（HTTP 200）」と表示される。

### Step 0-2: ログの出先

- フェーズ3の API 1・API 2 の呼び出し結果は **`contract_payment`** チャンネルに出力される。
- **ログファイル**: `app/storage/logs/contract-payment-YYYY-MM-DD.log`（Daily ローテーション）
- 直近のログを確認するコマンド:
  ```bash
  docker compose run --rm app php artisan robotpayment:show-test-log --lines=200
  ```

### Step 0-3: DB の確認方法（ローカル Docker）

- 契約テーブル: `contracts` の `billing_code`, `billing_individual_number`, `billing_individual_code`
- 決済テーブル: `payments` の `billing_payment_method_number`, `billing_payment_method_code`, `merchant_order_no`（= 店舗オーダー番号 cod）
- 例（MySQL でコンテナ内から）:
  ```bash
  docker compose exec db mysql -u billing -pbilling_pass billing -e "
  SELECT id, billing_code, billing_individual_number, billing_individual_code FROM contracts ORDER BY id DESC LIMIT 3;
  SELECT id, contract_id, merchant_order_no, billing_payment_method_number, billing_payment_method_code FROM payments WHERE provider='robotpayment' ORDER BY id DESC LIMIT 3;
  "
  ```

---

## 確認パターンA: 決済なしで申込保存（API 1 のみ発火）

ROBOT PAYMENT を無効にした状態で「申込内容を送信」すると、`ContractController::store()` が実行され、請求管理ロボの設定があれば **API 1 だけ** が呼ばれます。決済画面は通らないため、API 2 は実行されません。

### Step A-1: ROBOT PAYMENT を無効にする

1. `app/.env` で次を設定する。
   - `ROBOTPAYMENT_ENABLED=false`
2. 設定をクリアする。
   ```bash
   docker compose run --rm app php artisan config:clear
   ```

### Step A-2: 申込フォームから「申込内容を送信」まで実行

1. ブラウザで **申込フォーム** を開く。
   - ローカル: `http://localhost:8080/billing/contract/create`
2. 必須項目を入力し、**確認画面へ** をクリックする。
3. 確認画面で **申込内容を送信**（または「この内容で申し込む」）をクリックする。
   - 請求管理ロボ・ROBOT PAYMENT が無効のため、**完了ページ**（`/contract/complete/{id}`）に遷移する想定。

### Step A-3: API 1 が呼ばれているか確認（ログ）

1. 直近の `contract_payment` ログを表示する。
   ```bash
   docker compose run --rm app php artisan robotpayment:show-test-log --lines=100
   ```
2. 次のいずれかが含まれることを確認する。
   - **成功時**: `請求管理ロボ API 1 請求先登録完了（申込保存時）` および `contract_id`, `billing_code`
   - **失敗時**: `請求管理ロボ API 1 失敗（申込保存時）` または `請求管理ロボ API 1 例外（申込保存時）` と `error` / `message`

### Step A-4: DB に請求先が保存されているか確認

1. 直近で作成された契約の ID を確認する（管理画面の契約一覧または DB の `contracts.id` の MAX）。
2. その契約で次のコマンドを実行する（`{契約ID}` を実際の ID に置き換え）。
   ```bash
   docker compose exec db mysql -u billing -pbilling_pass billing -e "
   SELECT id, billing_code, billing_individual_number, billing_individual_code FROM contracts WHERE id = {契約ID};
   "
   ```
3. **期待**: 請求管理ロボの設定があり、API 1 が成功していれば、`billing_code` に値が入っている（例: `BC00000001` のような形式）。`billing_individual_number` または `billing_individual_code` もレスポンスに応じて入ることがある。
4. 決済なしフローでは Payment は自動作成されないため、`payments` に `provider='robotpayment'` の行が増えていない場合もある（サービス側で「Payment が無い場合は作成」する実装のため、API 1 成功時は 1 件作成される）。

---

## 確認パターンB: 決済ありフロー（API 1 → gateway → API 2）

ROBOT PAYMENT を有効にし、請求管理ロボの設定も行った状態で、**確認画面送信 → 決済ページ → カード情報送信** まで行うと、**API 1 → 決済 gateway（cod 使用）→ 成功時 API 2** の順で実行されます。

### Step B-0: ローカルで決済フローを検証する場合（ROBOT PAYMENT 管理画面の設定）

**ローカル（Docker / localhost）から決済まで行う場合**は、**決済システムコントロールパネル**（https://credit.j-payment.co.jp/cp/SignIn.aspx）で次を設定する。

| 設定場所 | 設定項目 | 設定内容 |
|----------|----------|----------|
| 設定 → 決済システム → **決済ゲートウェイ＆CTI決済設定** | 決済データ送信元IP | ローカルの送信元IP（Docker の場合は `docker compose exec app curl -s -4 ifconfig.me` で確認した値）。未設定だと ER003。 |
| 設定 → 決済システム → **PC用決済フォーム設定** | 決済データ送信元URL（または複数決済データ送信元URL） | `http://localhost:8080` または `http://127.0.0.1:8080`（ブラウザで開くオリジンに合わせる）。未設定だとリファラーエラー。 |

※ デモ契約で credit.j-payment.co.jp にログインできない場合は、ROBOT PAYMENT サポート（support@billing-robo.jp）に上記の登録を依頼する。詳細は [13_billing_robo_only_verification.md](13_billing_robo_only_verification.md) の「ローカル環境での ROBOT PAYMENT 連携」「ROBOT PAYMENT 管理画面で設定する箇所」を参照。

### Step B-1: 決済・請求管理ロボの設定を有効にする

1. `app/.env` で次を設定する。
   - `ROBOTPAYMENT_ENABLED=true`
   - `ROBOTPAYMENT_STORE_ID`（決済システムの店舗ID・6桁。決済システムCPの画面に表示）
   - 必要に応じて `ROBOTPAYMENT_ACCESS_KEY`
   - `BILLING_ROBO_BASE_URL`, `BILLING_ROBO_USER_ID`, `BILLING_ROBO_ACCESS_KEY`（パターンA と同様）
2. 設定をクリアする。
   ```bash
   docker compose run --rm app php artisan config:clear
   ```

### Step B-2: 確認画面まで進み、決済ページへ遷移する

1. ブラウザで **申込フォーム** を開く。
   - ローカル: `http://localhost:8080/billing/contract/create`
2. 必須項目を入力し、**確認画面へ** をクリックする。
3. 確認画面で **申込内容を送信** をクリックする。
4. **期待**: ROBOT PAYMENT 有効のため、**決済ページ**（`/contract/payment`）にリダイレクトされる。

### Step B-3: 決済を実行する（テストカード）

1. 決済ページで、決済システム（ROBOT PAYMENT）のテストカード情報を入力する。
   - テストカード番号・有効期限・名義等は、決済システムのテスト環境仕様に従う。
2. **支払う**（または同等のボタン）をクリックし、トークン取得 → サーバーへ送信まで完了する。
3. **期待**: 決済が成功すると、完了ページやサンクスページに遷移する（実装に依存）。

### Step B-4: API 1 が決済フローで呼ばれているか確認（ログ）

1. 直近の `contract_payment` ログを表示する。
   ```bash
   docker compose run --rm app php artisan robotpayment:show-test-log --lines=150
   ```
2. 次のログが **決済実行の前後** で出ていることを確認する。
   - `請求管理ロボ API 1 請求先登録完了` および `contract_id`, `billing_code`, `cod`
   - 失敗時は `請求管理ロボ API 1 失敗（決済は cod=契約ID で継続）` または `請求管理ロボ API 1 例外（〜）`

### Step B-5: Payment に cod と決済情報が保存されているか確認

1. 直近で作成された契約 ID を確認する。
2. その契約に紐づく `payments`（`provider='robotpayment'`）を確認する。
   ```bash
   docker compose exec db mysql -u billing -pbilling_pass billing -e "
   SELECT id, contract_id, merchant_order_no, billing_payment_method_number, billing_payment_method_code, status
   FROM payments WHERE provider='robotpayment' ORDER BY id DESC LIMIT 3;
   "
   ```
3. **期待**:
   - API 1 が成功している場合、`merchant_order_no` に請求管理ロボから返却された **cod**（店舗オーダー番号）が入っている（契約IDだけの文字列ではない）。
   - `billing_payment_method_number` または `billing_payment_method_code` に値が入っている。

### Step B-6: 契約の請求先が保存されているか確認

1. 同じ契約 ID で `contracts` を確認する。
   ```bash
   docker compose exec db mysql -u billing -pbilling_pass billing -e "
   SELECT id, billing_code, billing_individual_number, billing_individual_code FROM contracts WHERE id = {契約ID};
   "
   ```
2. **期待**: `billing_code` および `billing_individual_number` または `billing_individual_code` に値が入っている。

### Step B-7: API 2（クレジットカード登録）が呼ばれているか確認（ログ）

1. 決済が **成功** した場合、同じ `contract_payment` ログに次のいずれかが出ていることを確認する。
   - **成功時**: `請求管理ロボ API 2 クレジットカード登録完了` および `contract_id`
   - **失敗時**: `請求管理ロボ API 2 クレジットカード登録失敗` または `請求管理ロボ API 2 例外` と `error` / `message`
2. 請求管理ロボのデモ環境にログインし、該当する請求先の決済手段にクレジットカードが登録されているかもあわせて確認できる（任意）。

---

## トラブルシュート

| 現象 | 確認ポイント |
|------|--------------|
| API 1 のログが一切出ない | `config('billing_robo.base_url')` と `config('billing_robo.user_id')` が空でないか。`config:clear` 後に再度実行しているか。 |
| 「請求管理ロボ API 1 失敗」が出る | ログの `error` 内容を確認。401 なら user_id/access_key または接続元IP。400 ならリクエストパラメータ（必須項目不足など）。 |
| `billing_code` が DB に残らない | API 1 のレスポンス解析で `billing[0].code` が取れているか。レスポンス形式が仕様と異なっていないか。 |
| 決済は成功するが API 2 のログが出ない | 契約に `billing_code` が入っているか。Payment に `billing_payment_method_number` または `billing_payment_method_code` が入っているか。 |
| gateway に送る cod が契約IDのまま | API 1 が失敗しているか、Payment の `merchant_order_no` が更新される前に `buildGatewayParams` が呼ばれていないか。`payment->refresh()` のあと `$payment` を `buildGatewayParams` に渡しているか。 |

---

## チェックリスト（実施後につける）

- [ ] Step 0-1: 請求管理ロボの接続設定と疎通テストが成功した
- [ ] Step 0-2 / 0-3: ログの出先と DB の確認方法を把握した
- [ ] パターンA: 決済なしで申込保存し、ログで「API 1 請求先登録完了（申込保存時）」を確認した
- [ ] パターンA: 該当契約の `contracts.billing_code` に値が入っていることを確認した
- [ ] パターンB: 決済ありフローで決済ページまで遷移した
- [ ] パターンB: テストカードで決済を実行した
- [ ] パターンB: ログで「API 1 請求先登録完了」と「API 2 クレジットカード登録完了」を確認した
- [ ] パターンB: `payments.merchant_order_no` に API 1 の cod が入っていることを確認した
- [ ] パターンB: `contracts.billing_code` および `payments.billing_payment_method_*` に値が入っていることを確認した

---

## 請求管理ロボ 管理画面（GUI）での確認手順

請求管理ロボの管理画面を開いている状態で、API 1・API 2 で登録した「請求先」「請求先部署」「決済情報」が正しく反映されているかを確認する操作です。メニュー名はバージョンにより異なる場合があります。

---

### 1. ログインとトップの確認

1. **デモ環境のURL**: `https://demo.billing-robo.jp/`（または `:10443` 付き）
2. **ログインID**: `.env` の `BILLING_ROBO_USER_ID`（メール形式）
3. **パスワード**: 請求管理ロボから発行されたログイン用パスワード
4. ログイン後、トップまたはダッシュボードが表示されていることを確認する。

---

### 2. 請求先一覧を開く

1. 左メニューまたは画面上部メニューから、次のいずれかを探してクリックする。
   - **「請求先」** または **「請求先一覧」** または **「取引先」**
   - 英語表記の場合は **「Billing」** など
2. **請求先の一覧画面**が表示される。
3. 一覧に「請求先コード」「請求先名」などの列があることを確認する。

※ メニューが階層になっている場合は、「マスタ」「取引先管理」などの配下に「請求先」がある場合があります。画面上の**ヘルプ・ガイド**や**？**アイコンでメニュー構成を確認できます。

---

### 3. API 1 で登録した請求先を探す

1. 請求先一覧で、**検索**または**フィルタ**があれば使う。
2. **請求先コード**で検索する場合、本システムで登録したコードを入力する。
   - 形式の例: **`BC00000001`**（契約ID 1 のとき）、**`BC00000012`**（契約ID 12 のとき）
   - 本システムの DB で確認: `contracts.billing_code` の値
3. 該当する**請求先コード**の行をクリックし、**請求先の詳細・編集画面**を開く。

**確認したいこと（API 1 の反映）**

- **請求先名**に、申込時に入力した**会社名**が表示されているか
- **請求先コード**が、上記の形式（BC + 8桁の契約ID）になっているか

---

### 4. 請求先部署を確認する

1. 請求先の詳細画面で、**「請求先部署」**「部署」**「宛先」** などのタブまたはセクションを探してクリックする。
2. 部署が一覧で表示される。
3. 本システムから送信した**1件の請求先部署**が登録されていることを確認する。

**確認したいこと**

- **部署名**: 申込時の「部署」または「会社名」が使われている
- **宛名・住所**: 担当者名・住所（郵便番号・都道府県・市区町村・番地）
- **メールアドレス・電話番号**: 申込時の値が入っている
- **請求先部署番号**または**請求先部署コード**: 画面に表示されていれば、本システムの `contracts.billing_individual_number` / `billing_individual_code` と一致する（DB と照合用）

---

### 5. 決済情報（決済手段）を確認する

1. 同じ請求先の詳細画面で、**「決済情報」**「決済手段」**「支払方法」** などのタブまたはセクションを探してクリックする。
2. 決済手段の一覧が表示される。
3. **「クレジットカード」** の行が 1 件あることを確認する。

**API 1 の反映**

- 決済手段として**クレジットカード**（名称は「クレジットカード」など）が 1 件登録されている
- **決済情報番号**や**決済情報コード**が表示されていれば、本システムの `payments.billing_payment_method_number` / `billing_payment_method_code` と一致する

**API 2 の反映（決済実行後）**

- クレジットカードの**登録状態**が「登録完了」や「カード登録済」などになっているか
- 未登録の場合は「未登録」「あとで登録」などの表示のまま（API 2 が未実行または失敗の可能性）

---

### 6. 操作が分からない場合

- 画面上の**デジタルガイド**や**ヘルプ**を開く。
- 請求管理ロボの**ヘルプサイト**: https://keirinomikata.zendesk.com/hc/ja  
  メニュー名や画面構成はバージョンで異なるため、ヘルプの「請求先」「決済」などのキーワードで検索するとよい。
- デモ環境の**サポート**に、「請求先一覧の開き方」「決済手段の確認場所」を問い合わせる。

---

### 7. 確認の流れまとめ（チェックリスト）

| 確認項目 | 画面での操作目安 | 期待する状態 |
|----------|------------------|--------------|
| 請求先が登録されている | 請求先一覧 → 請求先コードで検索 | 該当の請求先コード（例: BC00000001）の行が存在する |
| 請求先名が正しい | 請求先詳細を開く | 申込時の会社名が表示されている |
| 請求先部署が 1 件ある | 請求先詳細 → 請求先部署タブ | 部署名・宛名・メール・電話が申込内容と一致する |
| 決済手段にクレジットがある | 請求先詳細 → 決済情報タブ | 「クレジットカード」が 1 件ある |
| カード登録済み（API 2 後） | 同上 | クレジットカードの状態が「登録完了」等になっている |

---

## 参照

- [10_implementation_order_billing_robo.md](10_implementation_order_billing_robo.md) … フェーズ一覧
- [03_api_01_billing_bulk_upsert.md](03_api_01_billing_bulk_upsert.md) … API 1 仕様
- [04_api_02_credit_card_token.md](04_api_02_credit_card_token.md) … API 2 仕様
- [09_demo_connection_billing_robo.md](09_demo_connection_billing_robo.md) … デモ接続情報
