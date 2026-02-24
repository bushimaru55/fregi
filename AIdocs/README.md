# AIdocs（決済・連携 仕様）

本システムの決済連携方式は **新連携方式** に移行しています。  
AIdocs には、決済・連携に必要な仕様・一次情報・DB・接続情報を格納します。

---

## 1. 決済・連携について

- **現行**: 新連携方式（名称・正式仕様は別途ドキュメントに記載予定）。
- **旧連携（利用不可）**: ROBOT PAYMENT トークン方式 + 3Dセキュア2.0 のドキュメントは [archive_robotpayment_token_3ds2/](archive_robotpayment_token_3ds2/) に退避済み。**旧仕様は利用できません。新規実装・現行仕様の確認には使用せず、過去実装・障害調査時のみ参照すること。**

---

## 2. ドキュメント一覧

| 役割 | パス | 説明 |
|------|------|------|
| **API 仕様** | [api_documents/](api_documents/) | API 連携の仕様を記述した md ファイル。請求管理ロボ連携の範囲は [api_documents/02_scope_billing_robo.md](api_documents/02_scope_billing_robo.md)、方針と実行計画は [api_documents/08_execution_plan_billing_robo.md](api_documents/08_execution_plan_billing_robo.md) を参照。 |
| **旧連携（参照用・利用不可）** | [archive_robotpayment_token_3ds2/](archive_robotpayment_token_3ds2/) | 旧 ROBOT PAYMENT トークン＋3DS2。**仕様は利用できません。** 誤った参照を防ぐため、新規開発では使用しないこと。過去実装・障害調査時のみ参照。 |

新連携の仕様書・ドキュメント一覧は、構成が決まり次第ここに追加します。

---

## 3. 推奨読み方

新連携の仕様書は別途追加予定です。  
**現行の仕様・実装計画には [api_documents/](api_documents/)（請求管理ロボ API 等）を参照してください。** 旧連携の過去実装・障害調査時のみ [archive_robotpayment_token_3ds2/](archive_robotpayment_token_3ds2/) を参照し、新規開発で旧仕様を参照しないよう注意してください。
