# 月額課金実装計画（authm.cgi統一版）

作成日: 2026-01-13
更新日: 2026-01-13

## 実装方針

**選択肢A: 両方にauthm.cgiを使用（統一API）**

一回限りの決済と月額課金の両方にauthm.cgiを使用します。

### メリット
- ✅ 一つのAPIで両方の決済タイプに対応
- ✅ コードの統一化が可能
- ✅ 月額課金の自動引き落としが可能

### デメリット（対応が必要）
- ⚠️ カード情報を自社サイトで入力する必要がある（初回のみ）
- ⚠️ セキュリティ対応が必要（PCI DSS準拠など）
- ⚠️ カード情報入力フォームの実装が必要

## 実装ステップ

### Phase 1: APIサービスの実装

#### 1.1 FregiApiServiceにauthm.cgiメソッドを追加
- **メソッド名**: `authorizePayment()`
- **機能**: オーソリ処理（authm.cgi）を実行
- **パラメータ**: 
  - 必須: SHOPID, PAY, PAN1-4, CARDEXPIRY1-2, CARDNAME
  - 任意: ID, CUSTOMERID, MONTHLY, MONTHLYMODE等
- **レスポンス**: OK/NG判定、承認番号、取引番号、CUSTOMERID（月額課金の場合）

#### 1.2 FregiApiServiceにsalem.cgiメソッドを追加
- **メソッド名**: `processMonthlySale()`
- **機能**: 月次売上処理（salem.cgi）を実行
- **パラメータ**: SHOPID, CUSTOMERID, PAY, SALEDATE等
- **レスポンス**: OK/NG判定、承認番号、取引番号

#### 1.3 getApiUrlメソッドの拡張
- authm.cgi、salem.cgi等のエンドポイントを追加

### Phase 2: カード情報入力と初回決済

#### 2.1 カード情報入力フォームの実装
- **対象ファイル**: `resources/views/contracts/confirm.blade.php`
- **入力項目**:
  - カード番号（PAN1-4）: 4桁ずつ4つ
  - 有効期限（CARDEXPIRY1-2）: 月/年
  - カード名義（CARDNAME）
  - セキュリティコード（SCODE）: 任意
- **セキュリティ対策**:
  - HTTPS必須
  - カード情報の暗号化送信
  - PCI DSS準拠の検討（将来的に）

#### 2.2 フォームバリデーションの追加
- **対象ファイル**: `app/Http/Requests/ContractRequest.php`
- **バリデーション項目**:
  - PAN1-4: 必須、4桁数字
  - CARDEXPIRY1: 必須、01-12
  - CARDEXPIRY2: 必須、2桁または4桁数字
  - CARDNAME: 必須、45文字以内
  - SCODE: 任意、3桁または4桁数字

#### 2.3 ContractControllerの決済処理をauthm.cgiに切り替え
- **対象メソッド**: `store()`
- **変更内容**:
  - compsettleapply.cgi（リダイレクト決済）からauthm.cgi（オーソリ処理）に切り替え
  - billing_typeに応じてMONTHLYパラメータを設定
    - `billing_type='one_time'`: MONTHLY=0または省略
    - `billing_type='monthly'`: MONTHLY=1, MONTHLYMODE=0
  - カード情報をAPIパラメータとして送信
  - 承認番号、取引番号を取得・保存
  - 月額課金の場合はCUSTOMERIDを取得・保存

#### 2.4 CUSTOMERIDの生成・保存機能
- **CUSTOMERID生成ロジック**:
  - 形式: `CUST` + 契約ID（パディング）+ タイムスタンプ
  - 最大20文字以内
- **保存**: Contractテーブルのcustomer_idカラムに保存
- **生成タイミング**: 月額課金の場合、authm.cgi実行時に生成（またはF-REGIから返却される場合はそれを保存）

### Phase 3: 月次売上処理と通知

#### 3.1 月次売上処理コマンドの実装
- **コマンド名**: `ProcessMonthlySales`
- **処理内容**:
  - 月額課金契約（billing_type='monthly', status='active'）を取得
  - 各契約についてsalem.cgi APIを呼び出し
  - 成功・失敗をログに記録
  - 失敗時の再試行ロジック（必要に応じて）

#### 3.2 Cronスケジュールの設定
- **対象ファイル**: `app/Console/Kernel.php`
- **スケジュール**: 毎月1日の深夜（例: 02:00）に実行
- **コマンド**: `php artisan monthly-sales:process`

#### 3.3 承認結果通知URLの実装
- **エンドポイント**: `/api/fregi/auth-notify`
- **コントローラー**: `Api/FregiAuthNotifyController`
- **処理内容**:
  - 通知パラメータの検証
  - 契約・決済ステータスの更新
  - ログ記録

### Phase 4: 既存コードの整理

#### 4.1 compsettleapply.cgi関連コードの整理
- **対象ファイル**: 
  - `FregiApiService::issuePayment()`: 非推奨化または削除
  - `ContractController::store()`: authm.cgiに切り替え
- **対応方針**: 
  - 段階的にauthm.cgiに移行
  - compsettleapply.cgi関連コードは残すが、使用しない（後方互換性のため）

### Phase 5: テストと調整

#### 5.1 テスト環境での動作確認
- 一回限り決済のテスト（MONTHLY=0）
- 月額課金のテスト（MONTHLY=1）
- 月次売上処理のテスト

## 実装の優先順位

1. **Phase 1: APIサービスの実装**
   - FregiApiServiceにauthm.cgiメソッドを追加
   - FregiApiServiceにsalem.cgiメソッドを追加

2. **Phase 2: カード情報入力と初回決済**
   - カード情報入力フォームの実装
   - ContractControllerの決済処理をauthm.cgiに切り替え
   - CUSTOMERIDの生成・保存

3. **Phase 3: 月次売上処理と通知**
   - 月次売上処理コマンドの実装
   - Cronスケジュールの設定
   - 承認結果通知URLの実装

4. **Phase 4: 既存コードの整理**
   - compsettleapply.cgi関連コードの整理

5. **Phase 5: テストと調整**
   - テスト環境での動作確認

## 重要な仕様

### authm.cgiのパラメータ設定

#### 一回限りの決済（billing_type='one_time'）
```
MONTHLY=0 または 省略
MONTHLYMODE: 省略
CUSTOMERID: 省略可能
```

#### 月額課金（billing_type='monthly'）
```
MONTHLY=1
MONTHLYMODE=0
CUSTOMERID: 生成して指定
```

## 注意事項

### セキュリティ
- カード情報の直接入力はPCI DSS準拠の検討が必要
- カード情報の暗号化・安全な送信が必要
- カード情報をDBに保存しない（CUSTOMERIDのみ保存）
- HTTPS必須

### F-REGI管理画面での設定
- 月次課金サービスの有効化
- 承認結果通知URLの設定（`203.76.164.2`をホワイトリストに登録）
- 自動決済のスケジュール設定（必要に応じて）

### エラーハンドリング
- オーソリ処理のエラーハンドリング
- 月次売上処理のエラーハンドリング
- 再試行ロジックの実装

## 参考資料

- `AIdocs/F-REGI月額課金仕様書（authm.cgi）.md`
- `AIdocs/F-REGI_API選択_一回限りと月額課金の比較.md`
- `AIdocs/月額プランの自動引き落としについて.md`
