# 申し込み一覧：条件検索・CSV出力仕様

## 対象URL
- 一覧: `http://localhost:8080/billing/admin/contracts`（本番は `/billing/admin/contracts`）

## 条件検索

一覧表示は検索条件に応じてフィルタされる。GET パラメータで指定。

| パラメータ | 説明 | 例 |
|-----------|------|-----|
| keyword | 会社名・担当者・メールの部分一致（OR） | `keyword=株式会社` |
| status | 契約ステータス（contract_statuses.code） | `status=applied` |
| contract_plan_id | 契約プランID | `contract_plan_id=1` |
| created_from | 申込日（以上）YYYY-MM-DD | `created_from=2026-01-01` |
| created_to | 申込日（以下）YYYY-MM-DD | `created_to=2026-01-31` |

- 未指定の項目は「すべて」として扱う。
- ページネーションは `withQueryString()` により検索条件を維持。

## CSV出力

- リンク: 一覧画面上部の「CSV出力（検索条件に一致する件のみ）」。
- 現在の一覧に適用されている検索条件（GET クエリ）をそのまま CSV 出力にも適用し、**条件に一致する申し込みのみ**を出力する。
- 検索条件なしの場合は全件出力。
- 出力項目: ID, 会社名, 担当者, プラン, 金額, ステータス, 申込日。
- ファイル名: `申し込み一覧_YYYYmmddHHiiss.csv`（BOM付きUTF-8）。

## 削除

- 一覧の各行に「削除」ボタンがあり、`POST` + `_method=DELETE` で `admin.contracts.destroy` に送信する。
- `/billing/` 等のサブディレクトリで動作している場合、フォームの送信先が正しく `/billing/admin/contracts/{id}` となるよう、`AppServiceProvider::boot()` でリクエストのベースパスまたは `APP_URL` のパスを検出し `URL::forceRootUrl()` を設定している。これにより削除送信先のURLが誤ってルート直下にならず、削除が実行される。

## 実装メモ

- `ContractController::queryWithFilters(Request)` で一覧・CSV共通のクエリを組み立て。
- `index()`: 上記クエリで `paginate(20)->withQueryString()`、`statuses` / `plans` をドロップダウン用に渡す。
- `exportCsv(Request)`: 同じ `queryWithFilters($request)` で `chunk(500)` しながらストリーム出力。
- ビューで CSV リンクは `route('admin.contracts.export', request()->query())` で現在のクエリを付与。
- 削除: `ContractController::destroy(Contract $contract)` で `$contract->delete()` 後、一覧へリダイレクト。

## 変更履歴
- 2026-01-30: 削除ボタンがサブディレクトリで動作しない問題を修正（URL::forceRootUrl による送信先URLの補正）。削除仕様・実装メモを追記。
- 2026-01-30: 条件検索・条件付きCSV出力を追加。
