# 請求管理ロボ API 連携のシステム要件範囲

本システムの請求管理ロボ連携において、**システム要件の範囲**を以下とします。  
（旧連携仕様は利用できません。本ドキュメントおよび [請求管理ロボのAPI仕様書](https://apispec.billing-robo.jp/) を参照してください。）

---

## 範囲（5項目）

1. **請求先登録更新**
2. **クレジットカード登録（トークン方式 3Dセキュア利用）**
3. **請求情報登録更新**
4. **請求書発行**
5. **即時決済**

本システムの請求管理ロボ連携は以上を範囲とし、範囲外のAPI（入金登録・消込・請求書送付郵送等）は本要件では対象としません。

---

## 対応 API 一覧

| 範囲（要件） | 請求管理ロボ API 名 | Method | Path |
|--------------|---------------------|--------|------|
| 1. 請求先登録更新 | 請求先登録更新 | POST | `/api/v1.0/billing/bulk_upsert` |
| 2. クレジットカード登録（トークン方式 3Dセキュア利用） | クレジットカード登録（トークン方式 3Dセキュア利用） | POST | `/api/v1.0/billing_payment_method/credit_card_token` |
| 3. 請求情報登録更新 | 請求情報登録更新 | POST | `/api/v1.0/demand/bulk_upsert` |
| 4. 請求書発行 | 請求書発行 | POST | `/api/v1.0/demand/bulk_issue_bill_select` |
| 5. 即時決済 | 即時決済 請求書合算 | POST | `/api/demand/bulk_register` |

---

## フロー

通常、**1 → 2 → 3 → 4 または 5** の順序で利用します。請求書発行（4）と即時決済（5）の使い分けは、公式シーケンス・仕様に従ってください。

---

## 参照

- [00_billing_robo_api_overview.md](00_billing_robo_api_overview.md) … API一覧・Base URL・共通エラー等
- [01_billing_robo_api_sequence.md](01_billing_robo_api_sequence.md) … 請求先登録〜商品参照のシーケンス（決済手段別）
- [請求管理ロボのAPI仕様書](https://apispec.billing-robo.jp/) … 公式マニュアル
