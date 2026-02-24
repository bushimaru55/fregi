# 03 Sequence & Callbacks（処理シーケンスと通知）

一次情報：
- 概要・画面フロー：`AIdocs/robotpayment_docs/3ds2_flow_01_overview.md`〜`05_notes.md`
- 自動課金 初回決済：`AIdocs/robotpayment_docs/3ds2_ab_02_initial_no_item.md`
- 初回決済通知：`AIdocs/robotpayment_docs/3ds2_ab_04_initial_result_kickback.md`
- 自動課金通知：`AIdocs/robotpayment_docs/3ds2_ab_06_recurring_kickback.md`

---

## 3.1 全体像（当社実装）
当社は「**トークン方式 + 3DS2.0**」を採用する。エンドユーザーの操作は初回のみ。

### 3.1.1 初回（ユーザー操作あり）
1. フロント（ブラウザ）でカード情報入力
2. トークン作成（RPのトークン作成処理）  
   - 実装方式：ポップアップ方式／カスタム方式（どちらでも良いが、当社はカスタム方式を推奨）
3. 3DS認証（`ThreeDSAdapter.authenticate`）  
   - **決済と同じ値**を指定（`am/tx/sf` もしくは `iid`）
4. 決済実行（加盟店サーバ → `gateway_token.aspx`）  
   - **固定IP必須**（ブラウザ直送不可）
5. 初回決済結果通知（決済結果通知URL）を受信し、当社で永続化

### 3.1.2 2回目以降（ユーザー操作なし）
- 初回決済の次の決済が **自動課金の1回目**  
  - 参照：`AIdocs/robotpayment_docs/3ds2_ab_06_recurring_kickback.md`（P.13）
- 通知は `rt` に関係なく **常にキックバック**（自動課金結果通知URL）  
  - 同上

---

## 3.2 重要な接続制約（固定IP）
`gateway_token.aspx` は接続元IP認証があるため、加盟店サーバ（固定IP）から送信する。  
ブラウザから直接送ると ER003（送信元IPエラー）。  
- 参照：`AIdocs/robotpayment_docs/3ds2_ab_02_initial_no_item.md`（P.6）

### 実装パターン（当社推奨）
- ブラウザ：カード入力 → トークン取得（JS）
- 当社サーバ：トークンと注文情報を受け取り、RPへサーバ間通信（固定IP）で決済リクエスト

---

## 3.3 通知（コールバック）設計

### 3.3.1 初回決済結果通知（決済結果通知URL）
- 通知先：RP管理画面「決済結果通知設定」→「決済結果通知URL」  
  - 参照：`AIdocs/robotpayment_docs/3ds2_ab_04_initial_result_kickback.md`（P.10）
- 注意：**初回決済の通知は「決済結果通知URL」** に来る（自動課金結果通知URLではない）  
  - 同上（注意書き）
- サンプル（一次情報）：`gid` `rst` `cod` `am` `tx` `sf` `ta` `acid` 等が付与される  
  - `acid` は自動課金番号として必ず保存する（後続の停止・変更等のキー）

#### 成功判定
通知先では成功判定が行われるため、レスポンスは **ContentLengthが0以上** となるよう実装する。  
- 参照：同上

### 3.3.2 自動課金結果通知（自動課金結果通知URL）
- 通知先：RP管理画面「決済結果通知設定」→「自動課金結果通知URL」  
  - 参照：`AIdocs/robotpayment_docs/3ds2_ab_06_recurring_kickback.md`（P.13）
- 特徴：`rt` に関係なく **常にキックバック**  
  - 同上

#### 成功判定
初回と同様に ContentLength が0以上。

---

## 3.4 当社の通知受信エンドポイント仕様（推奨）
（URL自体は環境により異なるため、ここでは仕様要件のみ定義する）

### 受信要件
- GETクエリ（キックバック）を受け取り、**原文をそのまま永続化**（改ざん検知・再現性のため）
- `cod`（店舗オーダー番号）で当社注文/契約を特定
- `rst` 等の成功/失敗フラグを保持し、当社の支払状態へ反映
- `acid` を保持（自動課金の識別子）

### 冪等性（必須）
- 同一通知が複数回届いても状態が壊れない（`gid` または `cod`+`acid` 等で一意制約）

---

## 3.5 例：初回合算（買い切り＋月額）のシーケンス
1) フロント：カード入力 → トークン取得  
2) フロント：3DS認証開始（決済と同じ `am/tx/sf` を指定）  
3) サーバ：`gateway_token.aspx` に `am=合算`, `acam=月額` で送信  
4) RP→当社：決済結果通知URL へ初回決済通知（`acid`含む）  
5) 次月以降：RPが自動課金（`acam`）を実行し、自動課金結果通知URLへ通知

（パラメータは `02_parameter_mapping_autobilling.md`）

---

## 3.6 公式サンプルコードとの対応・注意（通常決済仕様書）

J-Payment 提供の「クレジットカード トークン方式 3Dセキュア2.0（通常決済）接続仕様書」サンプルとの対応メモ。

### フロー
- ①トークン作成（CPToken.TokenCreate）→ ②3DS2認証（ThreeDSAdapter.authenticate）→ ③決済 submit  
- 当社はカスタム方式（B）を採用。フォームは自社サーバ（`contract.payment.execute`）へ POST し、サーバから `gateway_token.aspx` へ送信（固定IP要件のためブラウザ直送は行わない）。

### 重要（仕様書 P.16/17）
- **ThreeDSAdapter.authenticate の Success** は「API呼び出し成功」を意味し、**3DS認証の最終成否ではない**。
- **最終の成否は決済レスポンス（gateway 応答・決済結果通知）で判断する。**

### 実装上の禁止
- `rpemvtds` クラスは使用しない（提供JS内部で使用）。当社ビューでは未使用。
