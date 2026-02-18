# payment_events 整合性確認

## スキーマとモデル・利用箇所の照合結果

### テーブル定義（マイグレーション）

| カラム | 型 | 備考 |
|--------|-----|------|
| id | bigint PK | 作成テーブル |
| payment_id | FK → payments | 作成テーブル、NOT NULL |
| event_type | string(50) | 作成テーブル |
| raw_query | text nullable | 200002 追加 |
| rp_gid | string(64) nullable | 200002 追加、冪等キー |
| rp_acid | string(64) nullable | 200002 追加 |
| payload | json nullable | 作成テーブル |
| created_at | timestamp | 作成テーブル |

- 一意制約: `(event_type, rp_gid)` … 2026_02_06 マイグレーションで追加（重複通知の防止）
- インデックス: `(payment_id, event_type)`, `created_at`, `rp_gid`

### モデル PaymentEvent

- `fillable`: payment_id, event_type, raw_query, rp_gid, rp_acid, payload, created_at … スキーマと一致
- `$timestamps = false` … テーブルに updated_at がないため妥当
- `created_at` は fillable でサービス層から明示設定

### RobotPaymentNotifyService の利用

- **初回通知**: 冪等チェック `(event_type, rp_gid)` → 同一ならスキップ。INSERT は payment_id, event_type, raw_query, rp_gid, rp_acid, payload, created_at。**DB::transaction 内で実行**。
- **自動課金通知**: 冪等チェック `(event_type, rp_gid, rp_acid)` および既存 Payment(rp_gid) の有無。INSERT は Payment 作成後に PaymentEvent を同一 **DB::transaction** 内で作成（不整合対応で 2026-02 に修正）。

### 確認済み・対応済みの点

1. **自動課金通知のトランザクション**  
   Payment と PaymentEvent を別々に作成していたため、PaymentEvent 失敗時に Payment のみ残る不整合があった。  
   → 初回通知と同様、`DB::transaction` 内で Payment 作成＋PaymentEvent 作成に統一済み。
2. **一意制約**  
   `(event_type, rp_gid)` により、同一通知の二重 INSERT を防止。RP が通知ごとに異なる gid を付与する前提で整合。
3. **初回の rp_acid**  
   初回は rp_acid が null になり得る。一意制約は (event_type, rp_gid) のみのため影響なし。
4. **04 仕様との用語**  
   仕様書の「parsed_json」「received_at」は、実装では `payload`（解析結果）・`created_at`（受信日時）に相当。意味として整合。

---

*確認日: 2026-02-06*
