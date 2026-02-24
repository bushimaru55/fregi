# API ドキュメント

本ディレクトリには、当システムが連携する API の仕様を記述した Markdown ファイルを格納します。

- **目的**: AI および開発者が **現行** API 仕様を参照できるようにする。
- **作成方針**: ウェブ上のマニュアルの内容を読み取り、本ディレクトリ内の md ファイルに整理して記載する。
- **注意**: 旧連携（ROBOT PAYMENT トークン＋3DS2）のドキュメントは [../archive_robotpayment_token_3ds2/](../archive_robotpayment_token_3ds2/) に退避してあり**利用できません**。実装・仕様確認は本 api_documents および公式マニュアルを参照してください。

## 請求管理ロボ API

- **公式マニュアル**: [billing-robo-apispec | 請求管理ロボのAPI仕様書](https://apispec.billing-robo.jp/)
- **システム要件範囲**: [02_scope_billing_robo.md](02_scope_billing_robo.md) … 本システムの請求管理ロボ連携の範囲（請求先登録更新・クレジットカード登録・請求情報登録更新・請求書発行・即時決済）と対応API一覧。
- **実行計画**: [08_execution_plan_billing_robo.md](08_execution_plan_billing_robo.md) … 方針（商品マスタは本システムを正・請求管理ロボは実行層）とタスク概要。
- **デモ環境接続**: [09_demo_connection_billing_robo.md](09_demo_connection_billing_robo.md) … デモ（https://demo.billing-robo.jp/）接続に必要な情報の箇条書き。
- **概要・一覧**: [00_billing_robo_api_overview.md](00_billing_robo_api_overview.md) … Base URL・API一覧・Webhook・注釈・共通エラー・SSL推奨をまとめたドキュメント。
- **シーケンス**: [01_billing_robo_api_sequence.md](01_billing_robo_api_sequence.md) … 請求管理ロボ_APIシーケンス図.pdf を参照し、請求先登録〜商品参照までの流れを決済手段別（銀行振込・クレジットカード・バンクチェック・RP口座振替・RL・その他口座振替・コンビニ払込票）に整理したドキュメント。

### 個別API詳細（範囲1〜5）

- **1. 請求先登録更新**: [03_api_01_billing_bulk_upsert.md](03_api_01_billing_bulk_upsert.md) … `POST /api/v1.0/billing/bulk_upsert` のリクエスト・レスポンス・エラー一覧。
- **2. クレジットカード登録（トークン方式 3Dセキュア利用）**: [04_api_02_credit_card_token.md](04_api_02_credit_card_token.md) … `POST /api/v1.0/billing_payment_method/credit_card_token` のトークン取得フロー・リクエスト・レスポンス・エラー一覧。
- **3. 請求情報登録更新**: [05_api_03_demand_bulk_upsert.md](05_api_03_demand_bulk_upsert.md) … `POST /api/v1.0/demand/bulk_upsert` のリクエスト・レスポンス・エラー一覧。
- **4. 請求書発行**: [06_api_04_bulk_issue_bill_select.md](06_api_04_bulk_issue_bill_select.md) … `POST /api/v1.0/demand/bulk_issue_bill_select` のリクエスト・レスポンス・エラー一覧（リクエスト上限1000件）。
- **5. 即時決済**: [07_api_05_bulk_register.md](07_api_05_bulk_register.md) … `POST /api/demand/bulk_register` の請求情報登録・請求書発行・クレジット決済一括処理（クレジットカードのみ、v1.0 なし）。

個別APIの詳細が必要な場合は、上記 md または公式マニュアルの該当ページを参照してください。
