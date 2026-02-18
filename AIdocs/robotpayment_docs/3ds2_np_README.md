# 3DS2（トークン方式）通常決済 接続仕様書 → Markdown化（ファイル重複回避版）

対象PDF:
- クレジットカード  トークン方式 3Dセキュア2.0(通常決済) 接続仕様書.pdf

## 生成ファイル一覧（このフォルダ内で完結 / 既存ファイル名と衝突しない命名）
- 3ds2_np_README.md
- 3ds2_np_01_overview.md
- 3ds2_np_02_browsers.md
- 3ds2_np_03_payment_no_item.md
- 3ds2_np_04_payment_with_item.md
- 3ds2_np_05_payment_validity_check.md
- 3ds2_np_06_result_notify.md
- 3ds2_np_07_sample_popup_html.md
- 3ds2_np_08_sample_popup_js.md
- 3ds2_np_09_sample_custom_html.md
- 3ds2_np_10_sample_custom_js.md
- 3ds2_np_11_token_create_custom_spec.md
- 3ds2_np_12_3ds_auth_no_item_spec.md
- 3ds2_np_13_3ds_auth_with_item_spec.md
- 3ds2_np_99_gaps_and_questions.md

## 注意（重要）
- 本PDFの「目次」には「トークン作成（ポップアップ方式）仕様」が記載されていますが、本文側では該当の“仕様ページ”が確認できませんでした（本文にあるのはサンプル実装と「トークン作成（カスタマイズ方式）仕様」）。
  - 実装上は「自動課金決済」仕様書側に同名セクションがあるため、そちらを参照して補完できる可能性があります（ただし、正式には提供元へ確認推奨）。
