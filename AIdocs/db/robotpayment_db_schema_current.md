# ROBOT PAYMENT 用 DB スキーマ（現行）

04_data_model_and_logging.md および現行マイグレーションに基づく。更新日: 2026-01-30。

---

## payments（拡張後）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint | PK |
| company_id | bigint | 会社ID |
| **provider** | string(32) nullable | 決済プロバイダ（robotpayment） |
| **payment_kind** | string(32) nullable | auto_initial / auto_recurring / normal |
| **merchant_order_no** | string(64) nullable | 当社 cod |
| **rp_gid** | string(64) nullable | RP 決済番号 gid |
| **rp_acid** | string(64) nullable | RP 自動課金番号 acid |
| contract_id | bigint nullable | 契約ID |
| orderid | string | 自社採番（FREGI 名のまま。RP では cod と merchant_order_no で運用可） |
| settleno, receiptno, slipno | string nullable | FREGI 用（RP では未使用） |
| amount | int | 金額 |
| **amount_initial** | int nullable | 初回請求合計 |
| **amount_recurring** | int nullable | 次月以降請求合計 |
| currency, payment_method | string | |
| status | enum | created, waiting_notify, paid, failed, ... |
| requested_at, notified_at, completed_at | timestamp nullable | |
| **paid_at** | timestamp nullable | 決済成立日時 |
| failure_reason, raw_notify_payload | text/json nullable | |
| timestamps | | |

**太字** = ROBOT PAYMENT 用に追加したカラム。

---

## payment_events（拡張後）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint | PK |
| payment_id | bigint FK | 決済ID |
| event_type | string(50) | rp_initial_kickback / rp_recurring_kickback 等 |
| **raw_query** | text nullable | 通知クエリ文字列の原文 |
| **rp_gid** | string(64) nullable | 冪等キー |
| **rp_acid** | string(64) nullable | 自動課金番号 |
| payload | json nullable | 解析結果 |
| created_at | timestamp | 受信日時 |

冪等はアプリ側で `rp_gid`（および recurring 時は `rp_acid`）の重複チェックで担保。

---

## contract_items（拡張後）

| カラム | 型 | 説明 |
|--------|-----|------|
| ... | | 既存 |
| **billing_type** | string(20) default 'one_time' | monthly / one_time スナップショット |

購入パターン判定（A/B/C）は契約に紐づく contract_items の billing_type 集約で行う。

---

## contracts

変更なし。`status` に pending_payment を利用。`payment_id` で初回決済の Payment を紐付ける。
