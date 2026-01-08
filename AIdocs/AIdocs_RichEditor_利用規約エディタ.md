# RichEditor 利用規約エディタ仕様書

## 概要

管理画面の利用規約編集機能に、Filament RichEditorを導入し、HTMLリッチテキスト編集を可能にする機能。

## 目的

- ユーザーがリッチテキストエディタで利用規約を編集できるようにする
- 入力されたHTMLをサニタイズしてXSS攻撃を防止
- プレーンテキスト版も保存して全文検索・一覧表示に対応

## 技術スタック

- **Laravel**: 10.x
- **Livewire**: 3.x
- **Filament Forms**: 3.x（RichEditorコンポーネント）
- **HTML Purifier**: mews/purifier（サニタイズ）

## DB設計

### site_settings テーブル

| カラム名 | 型 | 説明 |
|----------|------|------|
| id | BIGINT UNSIGNED | 主キー |
| key | VARCHAR(255) | 設定キー（例: `terms_of_service`） |
| value | TEXT | サニタイズ済みHTML |
| value_text | LONGTEXT | プレーンテキスト版（検索用） |
| description | TEXT | 設定の説明 |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

### マイグレーション

- `2026_01_09_000001_add_value_text_to_site_settings_table.php`
  - `value_text` カラムを追加

## ファイル構成

### コンポーネント

```
app/
├── Livewire/
│   └── Admin/
│       └── TermsOfServiceEditor.php   # Livewireコンポーネント
├── Models/
│   └── SiteSetting.php                # モデル（更新済み）
└── Http/
    └── Controllers/
        └── Admin/
            └── SiteSettingController.php  # コントローラー（更新済み）

resources/views/
├── livewire/
│   └── admin/
│       └── terms-of-service-editor.blade.php  # Livewireビュー
└── admin/
    └── site-settings/
        ├── edit.blade.php   # 編集画面（更新済み）
        └── index.blade.php  # 一覧画面（更新済み）

config/
├── purifier.php   # HTML Purifier設定
└── livewire.php   # Livewire設定
```

## サニタイズ設定

### rich_html プロファイル (`config/purifier.php`)

#### 許可HTMLタグ

```
p, br, strong, b, em, i, u, s, blockquote, h1-h6, ul, ol, li,
a[href|title|target|rel], span[style], div[style], img[src|alt|title|width|height],
table, thead, tbody, tr, th, td, pre, code, hr
```

#### 許可CSSプロパティ

```
color, background-color, font-size, font-weight, font-style,
text-decoration, text-align, line-height, letter-spacing,
margin, margin-left/right/top/bottom, padding, padding-left/right/top/bottom,
border, border-color, border-width, border-style, border-radius,
width, max-width
```

#### 禁止（セキュリティ上）

- `position`, `z-index`, `transform`, `filter` 等は許可しない
- `<script>` タグは許可しない
- 危険なイベントハンドラ（onclick等）は除去される

## 使用方法

### 編集画面

1. 管理画面 → サイト管理 → 編集ボタン
2. RichEditorでテキストを装飾
3. 「更新する」ボタンをクリック
4. HTMLがサニタイズされ、DBに保存

### 表示画面

- index.blade.php: サニタイズ済みHTMLを `{!! !!}` で表示
- 公開フォーム: 同様にサニタイズ済みHTMLを表示

## Livewireコンポーネント詳細

### TermsOfServiceEditor.php

```php
// 主要メソッド

mount(): void
// DBから現在の利用規約HTMLを取得してフォームにセット

form(Form $form): Form
// Filament RichEditorを定義

save(): void
// 1. フォームからHTMLを取得
// 2. Purifierでサニタイズ（rich_htmlプロファイル）
// 3. strip_tagsでプレーンテキスト版を生成
// 4. DBに保存
// 5. index画面にリダイレクト
```

## テスト項目

### セキュリティテスト

- [ ] `<script>alert(1)</script>` が除去される
- [ ] `style="position:fixed"` が除去される
- [ ] `onclick="..."` が除去される

### 機能テスト

- [ ] 太字・斜体・下線が保存される
- [ ] リストが正しく保存される
- [ ] 見出しが正しく保存される
- [ ] リンクが正しく保存される

### 表示テスト

- [ ] 管理画面の一覧でHTMLが正しく表示される
- [ ] 公開フォームでHTMLが正しく表示される

## 変更履歴

| 日付 | 内容 |
|------|------|
| 2026-01-09 | 初版作成 - RichEditor機能追加 |

## 注意事項

1. **CSP（Content Security Policy）**: サイトで厳格なCSPを使用している場合、インラインスタイルがブロックされる可能性あり
2. **既存データ**: 既存のプレーンテキストデータは、RichEditorで開くとそのまま表示される
3. **Livewireセッション**: Livewire使用時はセッション管理に注意
