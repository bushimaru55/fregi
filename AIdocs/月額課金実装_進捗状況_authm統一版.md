# 月額課金実装 進捗状況（authm.cgi統一版）

作成日: 2026-01-13
更新日: 2026-01-13

## 実装方針

**選択肢A: 両方にauthm.cgiを使用（統一API）**

一回限りの決済と月額課金の両方にauthm.cgiを使用します。

## 実装ステップと進捗

### Phase 1: APIサービスの実装

#### ✅ 1.1 データベーススキーマの拡張（customer_idカラム追加）
- **ステータス**: 完了
- **マイグレーション**: `2026_01_13_055400_add_customer_id_to_contracts_table.php`
- **変更内容**: `customer_id` (VARCHAR(20), nullable) カラムを追加

#### ✅ 1.2 API選択方針の確定
- **ステータス**: 完了
- **決定内容**: authm.cgiに統一（一回限り・月額課金の両方に対応）

#### ✅ 1.3 FregiApiServiceにauthm.cgiメソッドを追加
- **ステータス**: 完了
- **実装内容**: `authorizePayment()` メソッドの実装
- **パラメータ**: SHOPID, PAY, PAN1-4, CARDEXPIRY1-2, CARDNAME, MONTHLY, MONTHLYMODE, CUSTOMERID等
- **レスポンス**: OK/NG判定、承認番号（auth_code）、取引番号（seqno）、CUSTOMERID（月額課金の場合）

#### ✅ 1.4 FregiApiServiceにsalem.cgiメソッドを追加
- **ステータス**: 完了
- **実装内容**: `processMonthlySale()` メソッドの実装
- **パラメータ**: SHOPID, CUSTOMERID, PAY, SALEDATE等
- **レスポンス**: OK/NG判定、承認番号（auth_code）、取引番号（seqno）

#### ✅ 1.5 getApiUrlメソッドの拡張
- **ステータス**: 完了
- **実装内容**: authm.cgi、salem.cgi等のエンドポイントを追加

### Phase 2: カード情報入力と初回決済

#### ✅ 2.1 カード情報入力フォームの実装
- **ステータス**: 完了
- **実装内容**: `contracts.confirm.blade.php` にカード情報入力フィールドを追加
- **入力項目**: PAN1-4, CARDEXPIRY1-2, CARDNAME, SCODE

#### ✅ 2.2 フォームバリデーションの追加
- **ステータス**: 完了
- **実装内容**: `ContractRequest` にカード情報のバリデーションを追加
- **バリデーション項目**: PAN1-4（4桁数字）、CARDEXPIRY1（01-12）、CARDEXPIRY2（2桁または4桁数字）、CARDNAME（45文字以内）、SCODE（3桁または4桁数字、任意）

#### ✅ 2.3 ContractControllerの決済処理をauthm.cgiに切り替え
- **ステータス**: 完了
- **実装内容**: `store()`メソッドをauthm.cgi対応に修正
- **変更内容**: 
  - compsettleapply.cgiからauthm.cgiに切り替え
  - billing_typeに応じてMONTHLYパラメータを設定
    - `billing_type='one_time'`: MONTHLY=0（即時決済）
    - `billing_type='monthly'`: MONTHLY=1, MONTHLYMODE=0（月次決済）
  - カード情報をAPIパラメータとして送信
  - 承認番号、取引番号を取得・保存
  - 月額課金の場合はCUSTOMERIDを生成・保存
  - 完了画面にリダイレクト

#### ✅ 2.4 CUSTOMERIDの生成・保存機能
- **ステータス**: 完了
- **実装内容**: Contractモデルに`generateCustomerId()`メソッドを追加
- **生成形式**: CUST + 契約ID（6桁パディング）+ タイムスタンプ（最大20文字）

### Phase 3: 月次売上処理と通知

#### ⏳ 3.1 月次売上処理コマンドの実装
- **ステータス**: 未実装
- **作業内容**: `ProcessMonthlySales` コマンドの作成

#### ⏳ 3.2 Cronスケジュールの設定
- **ステータス**: 未実装
- **作業内容**: Kernel.phpでスケジュール設定

#### ⏳ 3.3 承認結果通知URLの実装
- **ステータス**: 未実装
- **作業内容**: `/api/fregi/auth-notify` エンドポイントの実装

### Phase 4: 既存コードの整理

#### ⏳ 4.1 compsettleapply.cgi関連コードの整理
- **ステータス**: 未実装
- **作業内容**: 非推奨化または削除（段階的に移行）

### Phase 5: テストと調整

#### ⏳ 5.1 テスト環境での動作確認
- **ステータス**: 未実装
- **作業内容**: テスト環境での動作確認

## 次のステップ

1. 月次売上処理コマンドの実装
2. Cronスケジュールの設定
3. 承認結果通知URLの実装

## 参考資料

- `AIdocs/F-REGI月額課金仕様書（authm.cgi）.md`
- `AIdocs/F-REGI_API選択_一回限りと月額課金の比較.md`
- `AIdocs/月額課金実装計画_authm統一版.md`
