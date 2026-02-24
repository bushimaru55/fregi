# 05 Operations（運用・例外対応）

本書は「本番運用で事故らない」ための運用ルールを定義する。

---

## 5.1 初回上乗せ（買い切り＋月額）運用の基本
当社の推奨運用は、**手動で金額変更しない**方式である。

- 初回（合算）：`am = 買い切り + 月額`
- 次月以降：`acam = 月額のみ`

これにより、
- エンドユーザーは初回の決済操作1回のみ
- RP管理画面で「次月以降を月額に戻す」手動作業が不要
- 変更し忘れによる次月の過請求を防止

（パラメータは `02_parameter_mapping_autobilling.md`）

---

## 5.2 課金日運用（標準：毎月1日）
- 標準：`ac1=1`
- 課金開始日：`ac4` は申込日から算出して必ず指定（次回課金の事故を防ぐ）

### 例外（将来拡張）
- 末日課金（`ac1=99`）や、申込日基準などが要件化された場合は `02_parameter_mapping_autobilling.md` に追記する。

---

## 5.3 エラー運用（最低限）
### ER003（送信元IPエラー）
原因：ブラウザ等の固定IPでない送信元から `gateway_token.aspx` に直接送信している。  
対策：加盟店サーバ（固定IP）からサーバ間通信に統一する。  
- 一次情報：`AIdocs/robotpayment_docs/3ds2_ab_02_initial_no_item.md`

### 3DS認証：Success ≠ 認証成功
3DS認証APIの `Success` は「呼び出し成功」であり、認証成功を意味しない。  
決済処理結果で最終判定する。  
- 参照：`AIdocs/robotpayment_docs/3ds2_np_12_3ds_auth_no_item_spec.md`（callback注記）

---

## 5.4 通知受信運用
- 初回決済通知：決済結果通知URL
- 自動課金通知：自動課金結果通知URL（常にキックバック）

通知受信の成功判定が行われるため、通知先は ContentLength 0以上を返す。  
- 参照：`AIdocs/robotpayment_docs/3ds2_ab_04_initial_result_kickback.md` / `..._06_recurring_kickback.md`

---

## 5.5 停止・復帰・情報変更（要件化したら追記）
一次情報として「自動課金情報変更、停止」仕様が存在する。  
- `AIdocs/robotpayment_docs/` 内の該当資料（自動課金情報変更・停止）のmdを参照
現時点では当社要件に入っていないため、運用手順は `99_open_questions.md` に回す。
