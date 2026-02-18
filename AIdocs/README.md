# AIdocs（クレジットカード トークン方式 仕様）

本システムの決済方法は **クレジットカード トークン方式**（ROBOT PAYMENT 社のトークン方式 + 3Dセキュア2.0）です。  
AIdocs には、この決済方式に必要な仕様・一次情報・DB・接続情報のみを格納しています。この決済方式以外のドキュメントは整理のため削除済みです。

---

## 1. 決済方法の明示

- **正式名称**: クレジットカード トークン方式（ROBOT PAYMENT トークン方式 + 3Dセキュア2.0）
- **通常決済**: 決済処理（商品登録なし）仕様（金額 am/tx/sf を送信）。公式PDF: `実装資料クレジットカード  トークン方式 3Dセキュア2.0(通常決済) 接続仕様書.pdf`（P.6 等）
- **自動課金初回**: 初回に am/tx/sf と acam/actx/acsf を指定し、2回目以降はRPが自動課金

---

## 2. ドキュメント一覧

| 役割 | パス | 説明 |
|------|------|------|
| **当社実装仕様** | [payment_integration_robotpayment/](payment_integration_robotpayment/) | スコープ・購入パターン・パラメータ・シーケンス・データモデル・運用・未決事項。接続に必要な情報、デモ・ローカル手順、店舗ID・リファラー調査結果、payment_events 整合性確認 |
| **一次仕様（RP仕様のMarkdown化）** | [robotpayment_docs/](robotpayment_docs/) | 通常決済: `3ds2_np_*`。自動課金: `3ds2_ab_*`。フロー概要: `3ds2_flow_*`。PDF の Markdown 化資料 |
| **公式PDF（通常決済）** | [実装資料クレジットカード  トークン方式 3Dセキュア2.0(通常決済) 接続仕様書.pdf](実装資料クレジットカード  トークン方式 3Dセキュア2.0(通常決済) 接続仕様書.pdf) | P.6 決済処理（商品登録なし）等 |
| **DBスキーマ** | [db/robotpayment_db_schema_current.md](db/robotpayment_db_schema_current.md) | payments / payment_events 等の現行スキーマ |
| **請求管理ロボAPI（トークン登録）** | [rp/クレジットカード登録_トークン方式_3Dセキュア利用_20260205-141519.md](rp/クレジットカード登録_トークン方式_3Dセキュア利用_20260205-141519.md) | トークン方式でのカード登録API（billing_payment_method/credit_card_token） |

---

## 3. 推奨読み方

1. [payment_integration_robotpayment/00_scope.md](payment_integration_robotpayment/00_scope.md) … 適用範囲・対象パターン
2. [payment_integration_robotpayment/接続に必要な情報.md](payment_integration_robotpayment/接続に必要な情報.md) … 店舗ID・通知URL・環境変数
3. [payment_integration_robotpayment/03_sequence_and_callbacks.md](payment_integration_robotpayment/03_sequence_and_callbacks.md) … 処理シーケンス・通知
4. [payment_integration_robotpayment/04_data_model_and_logging.md](payment_integration_robotpayment/04_data_model_and_logging.md) … 保存方針・ログ
5. [payment_integration_robotpayment/05_operations.md](payment_integration_robotpayment/05_operations.md) … 運用・エラー対応

デモ・ローカル開発時は [payment_integration_robotpayment/デモアカウント_ローカル開発テスト.md](payment_integration_robotpayment/デモアカウント_ローカル開発テスト.md) と [payment_integration_robotpayment/ローカル動作確認手順.md](payment_integration_robotpayment/ローカル動作確認手順.md) を参照してください。  
テストモードで1ステップずつ接続検証する場合は [payment_integration_robotpayment/テストモード接続検証手順.md](payment_integration_robotpayment/テストモード接続検証手順.md) と [payment_integration_robotpayment/検証計画_ステップ別.md](payment_integration_robotpayment/検証計画_ステップ別.md)（ステップ別の区切り・ログ確認ルール）を参照し、`php artisan robotpayment:verify-config` および `robotpayment:show-test-log` を利用してください。トラブル時は AIdocs のログ確認ルールに従い、ログを取得・確認してから原因追求すること。
