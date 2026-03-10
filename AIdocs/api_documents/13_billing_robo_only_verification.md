# 請求管理ロボ API のみの検証手順

ROBOT PAYMENT（決済ゲートウェイ）は使わず、**請求管理ロボ側の API 連携だけ**を確認する手順です。

---

## 構築済みの請求管理ロボ連携（仕様準拠）

請求管理ロボのAPI仕様に従い、**即時決済は API 5（即時決済・請求書合算）で実施**しています。トークンは API 2 のみで使用し、決済ゲートウェイ（gateway_token.aspx）は呼びません。

| API | 役割 | 呼び出しタイミング |
|-----|------|---------------------|
| **API 1** 請求先登録更新 | 契約情報を請求管理ロボに登録し、billing_code / 決済情報 等を取得 | 決済実行時（`RobotPaymentService::executePayment`） |
| **API 2** クレジットカード登録 | フロントで取得したトークンを請求管理ロボに送り、カードを登録 | 決済実行時（API 1 成功後・API 5 の前） |
| **API 5** 即時決済（請求書合算） | 請求情報登録・請求書発行・クレジット決済を一括実行 | API 2 成功後（gateway は呼ばない） |

- **API 3** 請求情報登録・**API 4** 請求書発行は、決済期限日に請求管理ロボが決済する**通常フロー**用。即時決済の場合は API 5 で一括のため未使用。

---

## 公式API仕様書との対応（クレジットカード登録・トークン方式 3Dセキュア）

請求管理ロボ公式（billing-robo-apispec）の「クレジットカード登録(トークン方式 3Dセキュア利用)」に合わせた実装です。

| 項目 | 仕様 | 本実装 |
|------|------|--------|
| **CPToken.TokenCreate** | aid, cn, ed, fn, ln 必須。md「10」一括払い必須。cvv はオプション利用時 | ✓ aid=store_id, cn, ed, fn, ln, cvv, **md: '10'** を送信 |
| **ThreeDSAdapter.authenticate** | 商品登録なしの場合は **am: 0, tx: 0, sf: 0 固定**。em/pn は請求先(部署)と同一必須 | ✓ 請求管理ロボ利用時は am/tx/sf を **0** に設定（`use_zero_amount_for_3ds`）。em/pn は契約のメール・電話番号 |
| **API 2 リクエスト** | user_id, access_key, billing_code, billing_payment_method_number または code, token, email, tel | ✓ BillingRoboApiClient で付与。number/code は API 1 レスポンスから設定 |
| **カード情報** | サイト内入力後は必ず消去し、サーバーにはトークンのみ送る | ✓ 送信前に cn/ed/cvv/fn/ln をクリア |
| **弊社側設定** | トークン決済利用時はカスタマーサポートへ連絡。設定後、コントロールパネルで「決済データ送信元IP」「決済データ送信元URL」を設定 | 要確認（送信元IP/URL の登録と、**店舗ID（aid）が請求管理ロボ側と一致していること**が ER584 回避に重要） |

- エラー **ER584**（Credit card payment error.）は**決済システムエラー**。3401–3407 は請求管理ロボ側の個別エラー。ER584 が出る場合は店舗ID（aid）の一致・トークン決済の有効化をサポートに確認するとよい。

---

## 段階的確認（店舗ID 133732 とトークン取得）

公式サンプル（カスタマイズ方式）と本実装を照らし、**店舗ID** → **トークン取得** → **3DS** の順で確認する手順です。

### サンプルとの対応一覧

| 項目 | 公式サンプル（カスタマイズ方式） | 本実装（payment.blade.php） |
|------|----------------------------------|------------------------------|
| **読み込み** | jQuery, CPToken.js, EMV3DSAdapter.js | ✓ 同一（@push scripts） |
| **フォーム** | mainform, #tkn, #cn, #ed_year/#ed_month, #fn, #ln, EMV3DS_INPUT_FORM | ✓ id="rp-payment-form", #tkn, #cn, #ed_month/#ed_year, #fn, #ln, #EMV3DS_INPUT_FORM（cvv あり） |
| **CPToken.TokenCreate** | aid: '000000', cn, ed (yy+mm), fn, ln | ✓ aid: storeId（133732）, cn, ed, cvv, fn, ln, **md: '10'**（一括払い） |
| **コールバック** | resultCode !== 'Success' で alert、else で execAuth | ✓ 同一。失敗時は token-create-failed ログ送信＋alert |
| **ThreeDSAdapter.authenticate** | tkn, aid: '000000', am:0, tx:0, sf:0, em, pn | ✓ tkn, aid: storeId, am/tx/sf（請求管理ロボ時は 0）, em, pn（桁のみ） |
| **カード情報消去** | 送信前に #cn, #ed_year, #ed_month, #fn, #ln を空に | ✓ 3DS 成功後 or 3DS 未使用時、#cn, #ed_year, #ed_month, #cvv, #fn, #ln を空にしてから submit |

### 確認ステップ（実行順）

1. **店舗ID 133732 が渡っているか**
   - 決済ページ（`/billing/contract/payment`）を開く。
   - ページ下部の「検証用」表示で **店舗ID: 133732** が出ているか確認（`APP_DEBUG=true` 時のみ表示）。
   - またはブラウザの開発者ツールでコンソールに `店舗ID(検証用): 133732` が出力されているか確認。
   - `.env` に `ROBOTPAYMENT_STORE_ID=133732` が入っているか、`php artisan config:clear` 済みかも確認。

2. **トークン取得（CPToken.TokenCreate）が成功しているか**
   - カード情報を入力し「支払いを実行する」を押す。
   - **トークン作成に失敗**した場合はアラートとログ（`token-create-failed`）で内容を確認。
   - **トークン作成に成功**した場合、3DS が有効なら認証画面へ進み、無効ならそのままフォーム送信される。いずれも「トークンが取得できませんでした」と出ずに進めば、TokenCreate は成功している。

3. **3Dセキュア認証のパラメータ**
   - 請求管理ロボ利用時は am, tx, sf が **0** で渡る（`use_zero_amount_for_3ds`）。ログ [ER584_DEBUG] の `frontend.am/tx/sf/use_zero_amount` で確認可能。

4. **カード情報の消去**
   - 送信前に画面上のカード番号・有効期限・CVV・名義がクリアされていること。本実装では 3DS 成功コールバック内、または ThreeDSAdapter 未読み込み時の分岐でクリアしてから `submit()` している。

5. **API 2 まで進んだが ER584 の場合**
   - [ER584_DEBUG] で token / api2_response / env を確認。店舗ID（aid）の一致・トークン決済の有効化は請求管理ロボ側の設定とサポート確認が必要。

### 検証用クレジット情報（テストカード）

| 項目 | 値 |
|------|-----|
| カード番号 | 4444333322221111 |
| CVV | 123 |
| 有効期限 | 当月以降（MM/YY） |

※ トークンはブラウザの CPToken でしか取得できません。上記は決済ページのフォームから入力して「支払いを実行する」で利用してください。

### API 送信テスト（コマンドで API 1 → API 2 を実行）

店舗IDと**ダミートークン**でサーバー側の API 1 → API 2 送信だけを確認するコマンドです（トークンはブラウザでしか取得できないため、API 2 はダミーで送信し、接続・レスポンス内容を確認します）。

```bash
cd app
php artisan robotpayment:api2-test
```

- 成功条件: コマンドが正常終了し、contract_payment ログに「請求管理ロボ API 1 請求先登録完了」「請求管理ロボ API 2 リクエスト」が出ること。API 2 はダミートークンのため ER584 等で失敗する想定。失敗時の詳細は `[ER584_DEBUG]` で確認。
- オプション: `--token=任意の文字列` で送信トークンを指定可能。
- ログ確認: `php artisan robotpayment:show-test-log --lines=80` または `grep '[ER584_DEBUG]' storage/logs/contract_payment.log`

---

## 公式マニュアル：設定完了後にコントロールパネルで行う設定

請求管理ロボ／トークン方式 3Dセキュアのマニュアルより、**設定が完了したあと**にコントロールパネルで行う設定は以下です。

- **「設定」** → **「決済システム」**
- **「決済ゲートウェイ＆CTI決済設定」** → **「決済データ送信元IP」**
- **「PC用決済フォーム設定」** → **「決済データ送信元URL」**

トークンは **JavaScript の非同期通信**で生成されるため、対応ブラウザは次のいずれか（いずれも最新版）です。

- Microsoft Edge / Google Chrome / Firefox / Safari

**カスタマーサポート（請求管理ロボ）**

- TEL: 03-4405-0665（平日 9:00–18:00）
- Mail: support@billing-robo.jp  
店舗ID紐づけ・トークン決済の有効化や送信元IP/URLの許否については、不明時はこちらに問い合わせると確実です。

---

## ROBOT PAYMENT 管理画面と .env の対応（API 2 用）

決済システムコントロールパネル（ROBOT PAYMENT 管理画面）の設定と、本システムの `app/.env` が一致している必要があります。

| 管理画面の項目 | 設定例（ペースト内容より） | app/.env で確認すること |
|----------------|----------------------------|--------------------------|
| **店舗ID** | **133732**（6桁） | `ROBOTPAYMENT_STORE_ID=133732` であること。**未設定や別の値だと API 2 で ER584 になる。** |
| 決済データ送信元IP | 153.131.158.132 | **請求管理ロボが受け取るリクエストの送信元＝Laravel アプリが API を呼び出すときの「外向きIP」**がこの一覧に含まれる必要がある。下記「自宅・ローカル環境と送信元IP」を参照。 |
| 決済データ送信元URL（PC） | http://localhost:8080 | ブラウザで決済ページを開くURL（例: http://localhost:8080）がこの値と一致、または「複数決済データ送信元URL」に含まれること。`http://127.0.0.1:8080` で開く場合は複数URLへ追加を推奨。 |
| テストモード | テストモード | 検証時はテストモードで問題なし。本番は本番モードに切り替える。 |

### キャプチャから確認した現在の設定（参考）

コントロールパネルのキャプチャから読み取れる設定は以下の通りです。ER584 の切り分け時に参照してください。

| 画面・セクション | 項目 | 現在の設定 | ER584 観点 |
|------------------|------|------------|------------|
| 決済ゲートウェイ＆CTI決済設定 | 決済データ送信元IP | 153.131.158.132 | サーバから請求管理ロボ API を呼ぶ送信元IPがこの値（または一覧）に含まれるか確認。 |
| PC用決済フォーム設定 | 決済データ送信元URL | http://localhost:8080 | **決済ページを開くブラウザのオリジン**（例: `http://localhost:8080`）と一致させる。`http://127.0.0.1:8080` で開いている場合は「複数決済データ送信元URL」に追加するか、送信元URL を変更。 |
| PC用決済フォーム設定 | 複数決済データ送信元URL | 追加なし | 別オリジン（127.0.0.1 や別ポート）で検証する場合はここに追加。 |
| 携帯用決済フォーム設定 | 決済データ送信元URL | **未設定（空白）** | スマートフォンや同一サイトのモバイル表示でトークン取得する場合は要設定。PC のみの検証なら未設定でも可の可能性あり。 |
| 動作設定 | テストモード | テストモード | 検証時は問題なし。 |
| 商品登録時の自動課金の初期値 | 自動課金(初期値) | する | 3DS に am/tx/sf=0 を送る実装と整合。 |

**重要**: トークンはブラウザ上の JavaScript で生成されるため、「決済データ送信元URL」は**ブラウザで表示している決済ページのオリジン**（スキーム＋ホスト＋ポート）と一致している必要があります。localhost:8080 で設定している場合は、必ず `http://localhost:8080` で決済ページを開いて検証してください。

### 自宅・ローカル環境と送信元IP

**決済データ送信元IP**は、**請求管理ロボ側が「どこのIPからAPIリクエストが来たか」として見ている値**です。  
つまり「Laravel アプリが API 1 / API 2 を叩きに出るときの**サーバの外向き（グローバル）IP**」が、コントロールパネルの「決済データ送信元IP」の一覧に含まれている必要があります。

- **自宅のノートPCなど、グローバルIPを固定で持っていない環境**では、そのPCからインターネットに出る際のIPは**プロバイダから割り当てられたその時点のグローバルIP**です。localhost や 127.0.0.1 をどこに設定しても、**許可IPリストとは別の話**です。許可されるのは「そのPCの今のグローバルIP」であり、コントロールパネルに 153.131.158.132 のような別サーバのIPだけが書いてある場合、**自宅からのリクエストは送信元IPが一致せず、拒否またはエラー（ER584 の一因になり得る）になる可能性が高い**です。
- **対処の例**  
  - いまの自宅のグローバルIPを確認し（例: ブラウザで「自分のIP」で検索）、コントロールパネルの「決済データ送信元IP」に**追加**する（複数登録可能な場合）。  
  - または、カスタマーサポート（support@billing-robo.jp）に「ローカル検証用にいまの送信元IPを許可リストに追加してほしい」と依頼する。  
  - あるいは、**送信元IPが既に許可されている環境**（例: 153.131.158.132 のサーバにアプリをデプロイしてそこで実行する、VPNでそのIPから出るようにする）で検証する。

**API 2（クレジットカード登録）で ER584 が出る場合の確認:**

1. `app/.env` に **`ROBOTPAYMENT_STORE_ID=133732`** が設定されているか。
2. **決済データ送信元IP**: アプリを動かしているサーバの**外向きグローバルIP**が、コントロールパネルの「決済データ送信元IP」に含まれているか。自宅PCの場合は上記「自宅・ローカル環境と送信元IP」を参照。
3. **決済ページを開いているURL**が、コントロールパネルの「PC用決済フォーム設定」の「決済データ送信元URL」と一致しているか（`http://127.0.0.1:8080` の場合は複数URLへ追加）。
4. 設定・変更後は `php artisan config:clear` を実行し、アプリを再読み込みしてから再度決済を試す。

---

## ER584 原因特定用ログ [ER584_DEBUG]

API 2 が失敗したときだけ、**1 回の決済試行につき 1 本**の `[ER584_DEBUG]` ログが `contract_payment` チャネルに出力されます。5名協議で決めたスキーマで、次の 1 回の検証で原因を切り分けできるようにしています。詳細は `AIdocs/api_documents/ER584_debug_log_design.md` を参照してください。

**ログの見方**

- 直近の contract_payment ログ（[ER584_DEBUG] 含む）を表示:
  ```bash
  php artisan robotpayment:show-test-log --lines=200
  ```
- ファイルで [ER584_DEBUG] だけ grep する例:
  ```bash
  grep '\[ER584_DEBUG\]' storage/logs/contract_payment.log
  ```
- 1 本のログに含まれる主な項目:
  - **correlation_id**: その決済リクエストの一意識別子
  - **token**: length, age_ms, hash_prefix_12, duplicate_detected, looks_base64, trimmed
  - **frontend**: aid, am, tx, sf, use_zero_amount, em_len, pn_len（3DS 送信値との整合確認用）
  - **api2_request** / **api2_response**: 請求管理ロボへ送った内容と返却エラー
  - **env**: store_id, billing_robo_base_url
  - **request**: ip, user_agent（送信元・環境の確認用）

---

## フロー（商品申し込み → クレジット決済）と確認先

申込〜確認〜決済〜完了までの一連の流れと、**どのシステムのどの画面で確認できるか**を下表に示します。

| ステップ | 画面・処理 | 実装 | 確認できるシステム | 確認できる画面・方法 |
|----------|------------|------|---------------------|------------------------|
| 1 | 申込フォーム表示 | `GET /contract/create` | 本システム（Laravel） | お客様向け画面：`/billing/contract/create` または `/contract/create` |
| 2 | 必須入力・プラン選択・確認画面へ | `POST /contract/confirm` | 本システム | お客様向け確認画面：`/billing/contract/confirm`（POST 送信後） |
| 3 | 確認送信 → 決済ページへ | `robotpayment.enabled` 時のみリダイレクト | 本システム | リダイレクト先の決済ページ URL で確認（次のステップの画面） |
| 4 | 決済ページでカード入力・トークン取得 | CPToken.js（ROBOT PAYMENT 提供） | 本システム＋ROBOT PAYMENT | 本システム：お客様向け決済ページ `/billing/contract/payment`。トークン取得：ブラウザ開発者ツール（Network）または本システムログ「決済実行リクエスト受付」の `tkn_length` |
| 5 | 決済実行ボタン | `POST /contract/payment/execute`（tkn ＋ セッション） | 本システム | 同上の決済ページから送信。結果は画面遷移（完了 or エラー表示）で確認 |
| 6 | 契約・明細・Payment 作成 | `RobotPaymentService::executePayment` | 本システム | **管理画面**：`/admin/contracts` で契約一覧・詳細。**DB**：`contracts` / `contract_items` / `payments` テーブル。**ログ**：`contract_payment` チャネル |
| 7 | API 1 請求先登録 | 請求管理ロボ連携時 | 請求管理ロボ／本システム | **請求管理ロボ**：管理画面にログイン → 請求先一覧で `billing_code`（例: BC00000001）や会社名で検索。**本システム**：ログ「請求管理ロボ API 1 請求先登録完了」、DB の `contracts.billing_code` |
| 8 | API 2 クレジットカード登録 | トークンを API 2 のみで使用 | 請求管理ロボ／本システム | **請求管理ロボ**：管理画面 → 請求先詳細 → 決済情報（クレジットカード登録済み）。**本システム**：ログ「請求管理ロボ API 2 クレジットカード登録完了」 |
| 9 | API 5 即時決済 | 請求情報＋請求書＋決済を一括（gateway は呼ばない） | 請求管理ロボ／本システム | **請求管理ロボ**：管理画面 → 請求書一覧・請求情報・入金／決済結果。**本システム**：ログ「請求管理ロボ API 5 即時決済完了」 |
| 10 | 成功時は完了ページへ | 署名付き `contract.complete` へリダイレクト | 本システム | お客様向け完了画面：`/billing/contract/complete/{id}?signature=...`（署名付き・有効期限あり） |

- **本システム**＝この Laravel アプリ（契約・申込・決済実行のオーケストレーション）。
- **請求管理ロボ**＝請求管理ロボの管理画面（https://demo.billing-robo.jp 等）。請求先・決済情報・請求書・入金の確認。
- **ROBOT PAYMENT**＝トークン取得用の JavaScript（CPToken.js）を提供。カード決済の実行は請求管理ロボ API 5 経由のため、決済結果は請求管理ロボ側で確認。

---

## 動作確認方法（申込〜決済〜完了の一連フロー）

請求管理ロボ連携で「申込 → 確認 → 決済ページ → カード入力 → 決済実行 → 完了」まで通すときの確認手順です。

### 事前確認

- `app/.env` に次が設定されていること。
  - `ROBOTPAYMENT_ENABLED=true`
  - `ROBOTPAYMENT_STORE_ID=133732`（ROBOT PAYMENT 管理画面の店舗IDと一致）
  - `BILLING_ROBO_BASE_URL=` / `BILLING_ROBO_USER_ID=` / `BILLING_ROBO_ACCESS_KEY=` が設定済み
- Docker 利用時はコンテナが起動していること（`docker compose up -d` 等）。
- 必要に応じて `php artisan config:clear` を実行済みであること。

### 手順 1: ブラウザで申込〜決済実行

1. ブラウザで **`http://localhost:8080/billing/contract/create`** を開く（`127.0.0.1:8080` の場合はそのURL。ROBOT PAYMENT の「決済データ送信元URL」に登録されていること）。
2. 必須項目（会社名・担当者名・メール・電話番号・住所など）を入力し、プランを選択して **「確認画面へ」** をクリック。
3. 確認画面で内容を確認し、**「申込内容を送信」**（または決済ページへ進むボタン）をクリック。
4. 決済ページに遷移したら、**クレジットカード情報**（カード番号・有効期限・セキュリティコード・名義）を入力し、**「支払いを実行する」** をクリック。
5. 3Dセキュアのポップアップや認証が表示された場合は、仕様に従って操作する（テスト環境ではスキップされる場合あり）。
6. 結果を確認する。
   - **成功時**: 完了ページ（`/billing/contract/complete/...?signature=...`）に遷移する。
   - **失敗時**: 決済ページにエラーメッセージが表示される（例: 「クレジットカード登録に失敗しました」）。

### 手順 2: 本システムのログで確認

```bash
cd /path/to/billing/app
docker compose exec app php artisan robotpayment:show-test-log --lines=50
```

**成功時のログ例（順に出現）:**

- `請求先部署にデフォルト決済手段を紐付け完了`
- `請求管理ロボ API 1 請求先登録完了`（`billing_code`, `cod` あり）
- `請求管理ロボ API 2 クレジットカード登録完了` または `請求管理ロボ API 2 レスポンス` で `error` が無い
- `請求管理ロボ API 5 即時決済完了`
- `決済実行成功`

**失敗時の確認:**

- `請求管理ロボ API 2 エラー` かつ `ER584` → 店舗ID（`ROBOTPAYMENT_STORE_ID`）の一致・トークン決済の有効化を確認（本文「ROBOT PAYMENT 管理画面と .env の対応」参照）。
- `請求管理ロボ API 1 失敗` → 請求管理ロボの user_id / access_key や接続元IPを確認。

### 手順 3: 請求管理ロボ管理画面で確認

1. **請求管理ロボ**（https://demo.billing-robo.jp 等）にログインする。
2. **請求先一覧**で、申込で入力した会社名や `billing_code`（ログの `BC000000xx`）で検索する。
3. 該当請求先の **詳細** を開く。
   - **決済情報**: クレジットカードが「登録完了」になっていること（API 2 成功時）。
   - **請求書・入金**: 即時決済が成功していれば、請求書・入金に反映されていること（API 5 成功時）。

### 手順 4: 本システムの DB または管理画面で確認（任意）

- **契約一覧**: `/admin/contracts` で直近の契約が作成されていること。
- **DB**: `contracts.billing_code` に値が入っていること。`payments` の `status` が成功時は `waiting_notify` 等、失敗時は `failed` になっている場合あり。

### まとめ: 成功の目安

| 確認場所 | 成功の目安 |
|----------|------------|
| ブラウザ | 完了ページに遷移し、エラーが表示されない |
| 本システムログ | 「API 1 請求先登録完了」→「API 2 クレジットカード登録完了」→「API 5 即時決済完了」→「決済実行成功」が順に出る |
| 請求管理ロボ管理画面 | 請求先の決済情報でクレジットカードが「登録完了」、請求書・入金に即時決済が反映されている |

---

## 前提: .env の設定

`app/.env` に次が設定されていること。

- `BILLING_ROBO_BASE_URL=https://demo.billing-robo.jp`
- `BILLING_ROBO_USER_ID=`（管理画面ログインID・メール形式）
- `BILLING_ROBO_ACCESS_KEY=`（API用アクセスキー）

決済は使わないため、次で問題ありません。

- `ROBOTPAYMENT_ENABLED=false`

---

## 検証手順（API 1 のみ）

1. **疎通確認**
   ```bash
   cd /path/to/billing
   docker compose run --rm app php artisan billing-robo:ping
   ```
   - 「疎通成功（HTTP 200）」であること。

2. **申込フォームから送信**
   - ブラウザで `http://localhost:8080/billing/contract/create` を開く。
   - 必須項目を入力 → 確認画面へ → **申込内容を送信**。
   - `ROBOTPAYMENT_ENABLED=false` のため、決済ページには行かず **完了ページ** に遷移する。

3. **API 1 の結果をログで確認**
   ```bash
   docker compose run --rm app php artisan robotpayment:show-test-log --lines=80
   ```
   - **成功**: `請求管理ロボ API 1 請求先登録完了（申込保存時）` と `billing_code` が出ていること。
   - **失敗**: `請求管理ロボ API 1 失敗（申込保存時）` または `請求管理ロボ API 1 例外（申込保存時）` の `error` / `message` を確認。

4. **DB で請求先の反映を確認**
   ```bash
   docker compose exec db mysql -u billing -pbilling_pass billing -e "
   SELECT id, billing_code, billing_individual_number, billing_individual_code FROM contracts ORDER BY id DESC LIMIT 3;
   "
   ```
   - 直近の契約で `billing_code` に値（例: BC00000009）が入っていれば API 1 成功。

---

## 請求管理ロボ管理画面での確認

- 管理画面（例: https://demo.billing-robo.jp）にログインし、**請求先一覧**で、上記 `billing_code` または契約の会社名・部署で検索し、登録内容が一致していることを確認する。

---

## ROBOT PAYMENT なしでできること・できないこと

| 機能 | 請求管理ロボのみで実現可能か | 備考 |
|------|------------------------------|------|
| 請求先登録（API 1） | ✅ 可能 | 申込保存時に API 1 のみ実行。決済手段は「クレジットカード（あとで登録）」の枠だけ作成される。 |
| 請求情報登録（API 3）・請求書発行（API 4） | ✅ 可能 | 決済手段を銀行振込等にすれば、ROBOT PAYMENT 不要で請求書まで発行できる。 |
| 即時決済（API 5） | △ 決済手段による | クレジットの即時決済は、**あらかじめカードが登録済み**である必要がある。 |
| クレジットカード登録（API 2）・初回カード決済 | ❌ 不可 | トークン取得に **決済システム（ROBOT PAYMENT）の JavaScript（CPToken.js）** が必須。請求管理ロボ API 2 は「トークンを受け取って登録する」だけなので、トークン取得元がなければカード登録も初回決済もできない。 |

**まとめ**: 請求先登録・請求情報・請求書発行まででよく、クレジット決済を使わない（銀行振込・後払い等）なら **請求管理ロボ API のみで機能実現可能**。**クレジットカードの申込時決済やカード登録**を行う場合は、トークン発行のため **ROBOT PAYMENT（credit.j-payment.co.jp）との連携が必須**。

---

## ローカル環境での ROBOT PAYMENT 連携（可能です）

**ローカル環境（Docker / localhost）からでも ROBOT PAYMENT 連携は可能**です。以下の 2 点を **決済システムコントロールパネル**（credit.j-payment.co.jp）で設定すれば、トークン取得〜ゲートウェイ送信までローカルで検証できます。

| 設定項目 | 設定内容 | 備考 |
|----------|----------|------|
| **決済データ送信元IP** | ローカル環境の**送信元IP**（例: Docker の場合は `docker compose exec app curl -s -4 ifconfig.me` で確認した値） | 未設定だと ER003（送信元IPの認証に失敗しました）になる。 |
| **決済データ送信元URL**（リファラー） | `http://localhost:8080` または `http://127.0.0.1:8080`（実際にブラウザで開くオリジンに合わせる） | 「PC用決済フォーム設定」内。未設定だと「店舗設定からリファラーURLを設定してください」になる。**localhost の登録は公式ドキュメントで案内されている**。 |

- 設定場所: **決済システムコントロールパネル**（https://credit.j-payment.co.jp/cp/SignIn.aspx）→「設定」→「決済システム」→「決済ゲートウェイ＆CTI決済設定」（送信元IP）／「PC用決済フォーム設定」（送信元URL）。
- デモ契約で credit.j-payment.co.jp にログインできない場合は、ROBOT PAYMENT サポート（support@billing-robo.jp）に「送信元IPの登録」と「ローカル開発用の決済データ送信元URL（例: http://localhost:8080）の登録」を依頼する。
- **決済結果通知**だけは、localhost はインターネットから叩けないため **ngrok 等で公開URLを用意**するか、通知の検証はステージングで行う。

**詳細手順**: `AIdocs/archive_robotpayment_token_3ds2/payment_integration_robotpayment/デモアカウント_ローカル開発テスト.md`（2.4 決済データ送信元URL、2.2 通知URL 等）を参照。

---

## ROBOT PAYMENT（テスト環境）管理画面で設定する箇所

以下は **決済システムコントロールパネル**（https://credit.j-payment.co.jp/cp/SignIn.aspx）の「設定」→「決済システム」で行う設定です。請求管理ロボ（billing-robo.jp）の画面とは別です。

| # | 設定項目 | 画面での場所 | 設定内容 | 必須 |
|---|----------|--------------|----------|------|
| 1 | **決済データ送信元IP** | 決済ゲートウェイ＆CTI決済設定 | アプリが稼働するサーバ（またはローカル Docker）の**送信元IPv4**。未設定だと ER003。 | ✅ 決済実行する場合 |
| 2 | **決済データ送信元URL** | PC用決済フォーム設定 | 決済ページを表示する**ブラウザのオリジン**。**メインの「決済データ送信元URL」が優先チェックされる**（「複数決済データ送信元URL」だけでは不十分）。ローカル検証時はメインURLを `http://localhost:8080` に変更すること。本番では `https://billing-robo.jp/` に戻す。 | ✅ カード入力〜トークン取得する場合 |
| 3 | **店舗ID** | 画面上部などに表示 | 6桁の店舗ID。`.env` の `ROBOTPAYMENT_STORE_ID` に同じ値を設定する。 | ✅ |
| 4 | 決済結果通知URL（初回・自動課金） | 請求管理ロボ側の「決済結果通知設定」で登録 | 本システムの `/api/robotpayment/notify-initial` 等の**絶対URL**。ローカルは ngrok で公開URLを用意するか、通知はステージングで検証。 | 通知を受ける場合 |

**ローカルで決済まで検証するとき**: 上記 1・2・3 を設定すれば、トークン取得〜ゲートウェイ送信まで可能。4 は省略可（通知だけ別環境で検証）。

**リファラーエラーが出たとき**: トークン作成失敗時にログに `page_origin` が記録される。`php artisan robotpayment:show-test-log --lines=30` で確認し、表示されたオリジンを「**決済データ送信元URL（メイン）**」に設定する。ROBOT PAYMENT の仕様上、メインURLが優先チェックされ、「複数決済データ送信元URL」だけでは通らない。本番デプロイ時はメインURLを `https://billing-robo.jp/` に戻すこと。

---

## 事前に設定しておくべき点（一覧）

運用開始前に、**ROBOT PAYMENT（決済システムCP）** と **請求管理ロボ** の両方で以下を設定してください。

### ROBOT PAYMENT 側（決済システムコントロールパネル）

**ログイン**: https://credit.j-payment.co.jp/cp/SignIn.aspx  
**メニュー**: 設定 → 決済システム

| # | 設定項目 | 画面 | 設定内容 | 必須 |
|---|----------|------|----------|------|
| 1 | 決済データ送信元IP | 決済ゲートウェイ＆CTI決済設定 | 本システムが稼働するサーバの**送信元IPv4**（Docker の場合は `docker compose exec app curl -s -4 ifconfig.me` で確認）。未設定だと ER003。 | 決済実行する場合 |
| 2 | 決済データ送信元URL（メイン） | PC用決済フォーム設定 | 決済ページの**ブラウザのオリジン**。ローカル検証時は `http://localhost:8080`。本番は `https://billing-robo.jp/` 等。メインが優先チェックされる。 | カード入力〜トークン取得する場合 |
| 3 | 店舗ID | 画面上部に表示 | 6桁。`.env` の `ROBOTPAYMENT_STORE_ID` に同じ値を設定。 | 必須 |
| 4 | クレジットカード決済完了後転送先URL | PC用決済フォーム設定 | 決済完了後のリダイレクト先（例: 請求管理ロボのカード登録完了ページ）。本システムの完了URLにする場合はそのURL。 | 任意 |

※ 決済結果通知URL（初回・自動課金）は **請求管理ロボ側** の「決済結果通知設定」で登録する（下記）。

### 請求管理ロボ側

**ログイン**: https://demo.billing-robo.jp/（デモ）または https://billing-robo.jp/（本番）  
**取得・設定**: 管理画面ログインID（メール形式）・API用アクセスキーを取得し、`.env` の `BILLING_ROBO_USER_ID` / `BILLING_ROBO_ACCESS_KEY` に設定。

| # | 設定項目 | 画面・備考 | 必須 |
|---|----------|------------|------|
| 1 | ユーザーID（管理画面ログインID） | 契約・サポートで発行。`.env` の `BILLING_ROBO_USER_ID`。 | API 1〜5 を使う場合 |
| 2 | アクセスキー | 同上。`.env` の `BILLING_ROBO_ACCESS_KEY`。 | 同上 |
| 3 | 決済結果通知URL（初回・自動課金） | 管理画面の「決済結果通知設定」で、本システムの **絶対URL** を登録（例: `https://example.com/billing/api/robotpayment/notify-initial`）。ローカルは ngrok で公開URLを用意するか、通知はステージングで検証。 | 決済結果通知を受け取る場合 |
| 4 | 接続元IPの許可（運用している場合） | 請求管理ロボで送信元IP制限をかけている場合は、本システムのサーバIPを許可リストに登録。401 / エラー13 対策。 | 制限している場合 |

### 本システム（.env）で対応する項目

| 用途 | 設定する主な項目 |
|------|------------------|
| 決済なし（申込保存・API 1 のみ） | `BILLING_ROBO_BASE_URL`, `BILLING_ROBO_USER_ID`, `BILLING_ROBO_ACCESS_KEY`。`ROBOTPAYMENT_ENABLED=false`。 |
| 決済あり（API 1〜4 まで実行） | 上記に加え `ROBOTPAYMENT_ENABLED=true`, `ROBOTPAYMENT_STORE_ID`（決済システムCPの店舗IDと一致）。 |
| 決済結果通知を受ける | `ROBOTPAYMENT_NOTIFY_INITIAL_URL` / `ROBOTPAYMENT_NOTIFY_RECURRING_URL`（参照用）。実際の受信には請求管理ロボの「決済結果通知設定」に同じURLを登録。 |

---

## 参照

- 詳細な確認手順（パターンA/B）: [11_phase3_verification_steps.md](11_phase3_verification_steps.md)
- 環境変数チェックリスト: [12_env_checklist.md](12_env_checklist.md)
- 決済手段別シーケンス: [01_billing_robo_api_sequence.md](01_billing_robo_api_sequence.md)
