<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

class SiteSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * サニタイズ済みHTMLを表示する
     */
    public function index()
    {
        // 決済タイプ別の利用規約（サニタイズ済みHTML）
        $termsBySelection = SiteSetting::getAllTermsOfService();
        $billingSelectionLabels = SiteSetting::billingSelectionLabels();
        // トップページのURLを取得
        $topPageUrl = SiteSetting::getTextValue('top_page_url', '');
        // 製品ページのURLを取得
        $productPageUrl = SiteSetting::getTextValue('product_page_url', '');
        // 返信メール設定を取得
        $replyMailHeader = SiteSetting::getTextValue('reply_mail_header', '');
        $replyMailFooter = SiteSetting::getTextValue('reply_mail_footer', '');
        
        return view('admin.site-settings.index', compact(
            'termsBySelection',
            'billingSelectionLabels',
            'topPageUrl',
            'productPageUrl',
            'replyMailHeader',
            'replyMailFooter'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     * Livewireコンポーネントがデータ取得を担当するためシンプル化
     */
    public function edit()
    {
        // Livewireコンポーネントが直接DBからデータを取得するため
        // ここでは特にデータを渡さない
        return view('admin.site-settings.edit');
    }

    /**
     * Update the specified resource in storage.
     * 従来のフォーム経由での更新用（Livewire以外からの更新に対応）
     */
    public function update(Request $request)
    {
        $allowedSelections = array_keys(SiteSetting::billingSelectionLabels());

        $validator = Validator::make($request->all(), [
            'terms_of_service' => 'required|string',
            'billing_selection' => 'nullable|string|in:' . implode(',', $allowedSelections),
        ], [
            'terms_of_service.required' => '利用規約の内容を入力してください。',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $html = $request->input('terms_of_service');
            $billingSelection = $request->input('billing_selection', 'one_time');

            // HTMLをサニタイズ（rich_htmlプロファイル使用）
            $cleanHtml = Purifier::clean($html, 'rich_html');

            SiteSetting::setTermsOfService($billingSelection, $cleanHtml);

            $label = SiteSetting::billingSelectionLabels()[$billingSelection] ?? $billingSelection;

            return redirect()
                ->route('admin.site-settings.index', ['billing_selection' => $billingSelection])
                ->with('success', "利用規約（{$label}）を更新しました。");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * トップページのURL編集画面を表示
     */
    public function editTopPageUrl()
    {
        $topPageUrl = SiteSetting::getTextValue('top_page_url', '');
        
        return view('admin.site-settings.edit-top-page-url', compact('topPageUrl'));
    }

    /**
     * トップページのURLを更新
     */
    public function updateTopPageUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'top_page_url' => ['required', 'url', 'max:255'],
        ], [
            'top_page_url.required' => 'トップページのURLを入力してください。',
            'top_page_url.url' => '有効なURL形式で入力してください。',
            'top_page_url.max' => 'URLは255文字以内で入力してください。',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $url = $request->input('top_page_url');
            
            // DBに保存（テキスト形式）
            SiteSetting::setTextValue(
                'top_page_url',
                $url,
                '決済完了画面の「トップへ戻る」ボタンのリンク先URL'
            );

            return redirect()
                ->route('admin.site-settings.index')
                ->with('success', 'トップページのURLを更新しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 製品ページのURL編集画面を表示
     */
    public function editProductPageUrl()
    {
        $productPageUrl = SiteSetting::getTextValue('product_page_url', '');
        
        return view('admin.site-settings.edit-product-page-url', compact('productPageUrl'));
    }

    /**
     * 製品ページのURLを更新
     */
    public function updateProductPageUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_page_url' => ['required', 'url', 'max:255'],
        ], [
            'product_page_url.required' => '製品ページのURLを入力してください。',
            'product_page_url.url' => '有効なURL形式で入力してください。',
            'product_page_url.max' => 'URLは255文字以内で入力してください。',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $url = $request->input('product_page_url');
            
            // DBに保存（テキスト形式）
            SiteSetting::setTextValue(
                'product_page_url',
                $url,
                '公開ページヘッダーの「製品ページへ戻る」ボタンのリンク先URL'
            );

            return redirect()
                ->route('admin.site-settings.index')
                ->with('success', '製品ページのURLを更新しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 返信メール設定の編集画面を表示
     */
    public function editReplyMail()
    {
        $replyMailHeader = SiteSetting::getTextValue('reply_mail_header', '');
        $replyMailFooter = SiteSetting::getTextValue('reply_mail_footer', '');

        return view('admin.site-settings.edit-reply-mail', compact('replyMailHeader', 'replyMailFooter'));
    }

    /**
     * 返信メール設定を更新
     */
    public function updateReplyMail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reply_mail_header' => ['nullable', 'string', 'max:10000'],
            'reply_mail_footer' => ['nullable', 'string', 'max:10000'],
        ], [
            'reply_mail_header.max' => '上部文章は10000文字以内で入力してください。',
            'reply_mail_footer.max' => '下部文章は10000文字以内で入力してください。',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // 空の場合は ConvertEmptyStringsToNull で null になるため、必ず string に正規化する
            $header = (string) ($request->input('reply_mail_header') ?? '');
            $footer = (string) ($request->input('reply_mail_footer') ?? '');

            // DBに保存（テキスト形式）
            SiteSetting::setTextValue(
                'reply_mail_header',
                $header,
                '申込者への返信メールの上部文章'
            );
            
            SiteSetting::setTextValue(
                'reply_mail_footer',
                $footer,
                '申込者への返信メールの下部文章'
            );

            return redirect()
                ->route('admin.site-settings.index')
                ->with('success', '返信メール設定を更新しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 請求サイクル設定のデフォルト値（API用: 0=当月, 1=翌月, 2=翌々月 / 99=末日）
     */
    private function getDefaultBillingCycleSchedule(): array
    {
        // start_date を「課金開始月の1日」で送る前提のオフセット。
        // 請求書発行=start_dateの前月末日（-1/99）、決済期限=start_date月の1日（0/1）。
        // within/after で同じ値（差分は start_date 側で吸収される）。
        $block = [
            'issue_month' => -1,
            'issue_day' => 99,
            'sending_month' => -1,
            'sending_day' => 99,
            'deadline_month' => 0,
            'deadline_day' => 1,
        ];
        return ['within' => $block, 'after' => $block];
    }

    /**
     * 請求サイクル設定の編集画面を表示
     */
    public function editBillingCycle()
    {
        $raw = SiteSetting::getTextValue('billing_cycle_schedule', '');
        $schedule = $raw !== '' ? json_decode($raw, true) : null;
        $defaults = $this->getDefaultBillingCycleSchedule();
        if (!is_array($schedule) || !isset($schedule['within'], $schedule['after'])) {
            $schedule = $defaults;
        } else {
            $schedule = [
                'within' => array_merge($defaults['within'], $schedule['within'] ?? []),
                'after' => array_merge($defaults['after'], $schedule['after'] ?? []),
            ];
        }
        return view('admin.site-settings.edit-billing-cycle', compact('schedule'));
    }

    /**
     * 請求サイクル設定を更新
     */
    public function updateBillingCycle(Request $request)
    {
        $monthIn = 'in:-2,-1,0,1,2';
        $dayIn = 'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,99';
        $rules = [
            'within_issue_month' => "required|integer|{$monthIn}",
            'within_issue_day' => "required|integer|{$dayIn}",
            'within_sending_month' => "required|integer|{$monthIn}",
            'within_sending_day' => "required|integer|{$dayIn}",
            'within_deadline_month' => "required|integer|{$monthIn}",
            'within_deadline_day' => "required|integer|{$dayIn}",
            'after_issue_month' => "required|integer|{$monthIn}",
            'after_issue_day' => "required|integer|{$dayIn}",
            'after_sending_month' => "required|integer|{$monthIn}",
            'after_sending_day' => "required|integer|{$dayIn}",
            'after_deadline_month' => "required|integer|{$monthIn}",
            'after_deadline_day' => "required|integer|{$dayIn}",
        ];
        $validator = Validator::make($request->all(), $rules, [
            'within_issue_month.required' => '月末5営業日以内の「発行日（月）」を選択してください。',
            'after_issue_month.required' => '月末5営業日以降の「発行日（月）」を選択してください。',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $schedule = [
            'within' => [
                'issue_month' => (int) $request->input('within_issue_month'),
                'issue_day' => (int) $request->input('within_issue_day'),
                'sending_month' => (int) $request->input('within_sending_month'),
                'sending_day' => (int) $request->input('within_sending_day'),
                'deadline_month' => (int) $request->input('within_deadline_month'),
                'deadline_day' => (int) $request->input('within_deadline_day'),
            ],
            'after' => [
                'issue_month' => (int) $request->input('after_issue_month'),
                'issue_day' => (int) $request->input('after_issue_day'),
                'sending_month' => (int) $request->input('after_sending_month'),
                'sending_day' => (int) $request->input('after_sending_day'),
                'deadline_month' => (int) $request->input('after_deadline_month'),
                'deadline_day' => (int) $request->input('after_deadline_day'),
            ],
        ];

        SiteSetting::setTextValue(
            'billing_cycle_schedule',
            json_encode($schedule, JSON_UNESCAPED_UNICODE),
            '請求サイクル（月末5営業日ルール）発行日・送付日・決済期限の相対月/日'
        );

        return redirect()
            ->route('admin.site-settings.index')
            ->with('success', '請求サイクル設定を更新しました。');
    }
}
