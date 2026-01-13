# 修正内容：存在しないプランIDのエラーハンドリング

作成日: 2026-01-13

## 問題点

存在しないプランID（例: `?plans=9999`）でアクセスした場合、何も表示されず、メッセージも表示されない問題がありました。

## 原因

1. `ContractController::create()` メソッドで、プランが0件の場合でもエラーハンドリングがなく、そのままビューに渡していた
2. ビュー側でも `@foreach($plans as $plan)` でプランが0件の場合は何も表示されない（エラーメッセージも表示されない）

## 修正内容

### 1. ContractController::create() の修正

プランIDが指定されているのに、該当するプランが見つからない場合は404エラーを返すように修正しました。

**修正箇所**: `app/app/Http/Controllers/ContractController.php`

```php
// plansパラメータによる絞り込み（複数プランID）
if ($request->has('plans')) {
    $planIdsString = $request->input('plans');
    $planIds = array_filter(array_map('intval', explode(',', $planIdsString)));
    if (!empty($planIds)) {
        $query->whereIn('id', $planIds);
        $hasPlanIdsParam = true; // フラグを追加
    }
}

$plans = $query->orderBy('display_order')->get();

// プランIDが指定されているのに、該当するプランが見つからない場合は404
if ($hasPlanIdsParam && $plans->isEmpty()) {
    Log::channel('contract_payment')->warning('指定されたプランが見つかりません', [
        'url' => $request->fullUrl(),
        'plan_ids' => $planIds,
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'timestamp' => now()->toIso8601String(),
    ]);
    abort(404, '指定されたプランが見つかりません。プランが存在しないか、現在公開されていません。');
}
```

### 2. 404エラーページの作成

公開ページ用のカスタム404エラーページを作成しました。

**作成ファイル**: `app/resources/views/errors/404.blade.php`

- `layouts.public` レイアウトを使用
- エラーメッセージを表示
- トップページと申込フォームへのリンクを提供

## 動作確認

### テストケース1: 存在しないプランIDでアクセス
- URL: `http://localhost:8080/billing/contract/create?plans=9999`
- 期待結果: 404エラーページが表示され、「指定されたプランが見つかりません。プランが存在しないか、現在公開されていません。」というメッセージが表示される

### テストケース2: 非公開プランIDでアクセス
- URL: `http://localhost:8080/billing/contract/create?plans={非公開プランID}`
- 期待結果: 404エラーページが表示される

### テストケース3: パラメータなしでアクセス
- URL: `http://localhost:8080/billing/contract/create`
- 期待結果: 全てのアクティブなプランが表示される（従来通り）

## ログ出力

存在しないプランIDでアクセスした場合、以下のログが出力されます：

```
[contract_payment] 警告: 指定されたプランが見つかりません
{
    "url": "http://localhost:8080/billing/contract/create?plans=9999",
    "plan_ids": [9999],
    "ip": "...",
    "user_agent": "...",
    "timestamp": "..."
}
```

## 注意事項

- パラメータなし（`/contract/create`）でアクセスした場合は、従来通り全てのアクティブなプランが表示されます
- プランIDが指定されている場合のみ、該当プランが見つからないと404エラーになります
- エラーメッセージはユーザーに分かりやすく表示されます
