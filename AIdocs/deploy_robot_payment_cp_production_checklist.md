# 本番デプロイ: ROBOT PAYMENT 決済システム CP 登録チェックリスト

**前提**: Plesk で SSH 不可。アプリ URL は `https://dschatbot.ai/webroot/billing`（[deploy/AIdocs/deploy_rules.md](../deploy/AIdocs/deploy_rules.md)）。

**環境変数テンプレート**: [本番環境.envテンプレート.txt](本番環境.envテンプレート.txt)

以下は **決済システムコントロールパネル**（credit.j-payment.co.jp）および関連ドキュメント [12_env_checklist.md](api_documents/12_env_checklist.md) / [13_billing_robo_only_verification.md](api_documents/13_billing_robo_only_verification.md) に基づく。

---

## 1. 本番サーバ情報（登録前に確定すること）

| 項目 | 設定例・メモ |
|------|----------------|
| 決済データ送信元 URL | `https://dschatbot.ai/webroot/billing`（**APP_URL** とオリジン一致。末尾の有無は CP の指定に合わせる） |
| 決済データ送信元 IP | 本番 Web サーバの**外向きグローバル IP**（Plesk / ホスティング提供元で確認） |
| 店舗 ID | `ROBOTPAYMENT_STORE_ID`（6 桁）。CP で確認 |
| 本番 / テストモード | 本番運用時は CP 側を本番モードに切替（ドキュメント上の注意あり） |

---

## 2. CP で登録する項目（チェック）

- [ ] **決済データ送信元 IP** に本番サーバ IP を登録
- [ ] **決済データ送信元 URL** に `https://dschatbot.ai/webroot/billing`（または CP が求める形式）
- [ ] **決済結果通知 URL**（初回・継続）を、アプリのルート実装に合わせ **RP 管理画面**に登録（`.env` の `ROBOTPAYMENT_NOTIFY_*` は参照用。実 URL はルート定義と一致させる）
- [ ] `.env` で `ROBOTPAYMENT_ENABLED=true`、店舗 ID・アクセスキー等を本番値に設定したうえで FTP アップロード
- [ ] 請求管理ロボ連携時は **店舗 ID（aid）** がロボ側設定と一致しているか確認（ER584 回避）

---

## 3. デプロイ後の確認（手動）

1. `https://dschatbot.ai/webroot/billing/build/manifest.json` が **200**
2. 申込フォーム → 決済ページで **リファラーエラー**が出ないこと
3. テストカードで決済〜完了画面まで（運用ポリシーに従う）

---

## 4. 参照

- [api_documents/12_env_checklist.md](api_documents/12_env_checklist.md)
- [api_documents/13_billing_robo_only_verification.md](api_documents/13_billing_robo_only_verification.md)
