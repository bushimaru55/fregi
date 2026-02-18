# 04 Data Model & Logging（DB保存方針・ログ）

本書は「実装で必ず残すべきデータ」を確定し、事故（照合不能、二重課金疑い、問い合わせ対応不可）を防ぐ。

参照：現行DBスキーマ（2026-02-03時点）  
- `AIdocs/db/robotpayment_db_schema_current.md`

---

## 4.1 保存の原則（当社）
1. **外部から来た通知は原文を保存**（解析結果だけ保存しない）
2. 識別子は必ず保存：`cod`（当社オーダー番号）、`gid`（取引ID）、`acid`（自動課金番号）
3. 金額は **初回** と **次月以降** を分けて保存：
   - 初回：`am/tx/sf/ta`
   - 次月以降：`acam/actx/acsf`
4. 冪等性を担保する（同通知の再送に耐える）

---

## 4.2 推奨テーブル設計（現行スキーマに合わせた拡張）
現行スキーマに `payments` / `payment_events` が存在する前提で、最小の拡張方針を定義する。  
（テーブル名が異なる場合は、同等概念のテーブルに適用する）

### 4.2.1 payments（決済レコード）
#### 追加/保持すべき項目（推奨）
- `provider`：`robotpayment`
- `payment_kind`：`auto_initial`（自動課金初回）/ `auto_recurring`（自動課金）/ `normal`（通常決済）
- `merchant_order_no`：当社 `cod`
- `rp_gid`：RP `gid`
- `rp_acid`：RP `acid`（自動課金番号）
- `amount_initial`：初回請求合計（`ta` があれば `ta` を優先）
- `amount_recurring`：次月以降の請求合計（`acam+actx+acsf`）
- `status`：`pending` / `succeeded` / `failed`
- `paid_at`：決済成立日時（通知受信時刻と区別するなら `rp_paid_at`）

> 初回合算（買い切り＋月額）は `payment_kind=auto_initial` とし、`amount_initial` に合算を保存する。

### 4.2.2 payment_events（通知・レスポンスの生保存）
#### 必須
- `payments.id`（FK）または `merchant_order_no` に紐付く参照
- `event_type`：`rp_initial_kickback` / `rp_recurring_kickback` 等
- `raw_query`：通知のクエリ文字列をそのまま（URLデコード前も保持推奨）
- `parsed_json`：解析結果（任意）
- `received_at`：受信日時
- 一意制約：`(event_type, rp_gid)` or `(event_type, rp_gid, rp_acid)` など

---

## 4.3 contract / contract_items への保存（明細スナップショット）
「申込時点の合意内容」を復元できるよう、`contract_items` へスナップショットを保存する。

### contract_items（推奨）
- `billing_type`：`monthly` / `one_time`
- `name_snapshot`
- `price_snapshot`
- `rp_mode_snapshot`：`item_registered` / `amount_specified`
- `rp_iid_snapshot`：商品登録ありの場合のみ

> 初回合算の請求は `payments.amount_initial` に合算で残すが、**内訳（買い切り・月額）は contract_items で復元**できるようにする。

---

## 4.4 セキュリティ・コンプライアンス
- **カード番号等の機微情報は当社DBに保存しない**（トークン方式を前提）
- 通知受信エンドポイントは公開URLとなるため、ログにはP II（メール/電話等）を過剰に出力しない  
  - ただし、照合に必要な最小項目（`cod`/`gid`/`acid`）は必ず保持

---

## 4.5 監査・問い合わせ対応（必須ログ）
問い合わせで最短回答するため、以下は必ず追えるようにする。
- 当社注文（契約）ID → `cod`
- `cod` → 初回決済 `payments` / `payment_events`
- `cod` → `acid`（自動課金番号）
- `acid` → 自動課金の全通知履歴（成功/失敗）
