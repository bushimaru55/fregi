# CHANGELOG

## 2026-01-30
- 実装反映: DB 拡張（payments / payment_events / contract_items）、パターン判定・金額計算サービス、決済ページ、gateway_token サーバ送信、初回/自動課金通知の冪等受信
- スキーマ文書を `AIdocs/db/robotpayment_db_schema_current.md` に追加
- 申込フロー: ROBOTPAYMENT_ENABLED 時は confirm 後→決済ページ→実行時に契約・明細作成

## 2026-02-03
- 初版作成（当社確定仕様）
- 方式A（初回上乗せ）：初回 `am` は合算、次月以降 `acam` は月額のみで固定する方針を確定
- 自動課金の通知先（初回=決済結果通知URL、次月以降=自動課金結果通知URL）を明確化
