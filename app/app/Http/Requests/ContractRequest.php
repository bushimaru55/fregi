<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 公開申込フォームなので認証不要
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeName = $this->route()->getName();
        
        $rules = [
            // 契約プラン
            'contract_plan_id' => ['required', 'exists:contract_plans,id'],
            
            // オプション商品
            'option_product_ids' => ['nullable', 'array'],
            'option_product_ids.*' => [
                'exists:products,id',
                function ($attribute, $value, $fail) {
                    $product = \App\Models\Product::find($value);
                    if (!$product || $product->type !== 'option' || !$product->is_active) {
                        $fail('選択されたオプション商品が無効です。');
                    }
                },
            ],
            
            // 申込企業情報
            'company_name' => ['required', 'string', 'max:255'],
            'company_name_kana' => ['nullable', 'string', 'max:255', 'regex:/^[ァ-ヶー\s0-9０-９]+$/u'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_name_kana' => ['nullable', 'string', 'max:255', 'regex:/^[ァ-ヶー\s]+$/u'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9\-]+$/'],
            'postal_code' => ['nullable', 'string', 'regex:/^\d{3}-?\d{4}$/'],
            'prefecture' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            
            // ご利用情報
            'usage_url_domain' => ['required', 'string', 'max:255'],
            'import_from_trial' => ['nullable', 'boolean'],
            
            // 利用規約への同意
            'terms_agreed' => ['required', 'accepted'],
        ];
        
        // カード情報は決済処理（store）時のみ必須
        // 確認画面表示（confirm）時は不要
        if ($routeName === 'contract.store') {
            $rules = array_merge($rules, [
                // カード情報（authm.cgi用）
                'pan1' => ['required', 'string', 'regex:/^\d{4}$/'],
                'pan2' => ['required', 'string', 'regex:/^\d{4}$/'],
                'pan3' => ['required', 'string', 'regex:/^\d{4}$/'],
                'pan4' => ['required', 'string', 'regex:/^\d{4}$/'],
                'card_expiry_month' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])$/'],
                'card_expiry_year' => ['required', 'string', 'regex:/^\d{2,4}$/'],
                'card_name' => ['required', 'string', 'max:45'],
                'scode' => ['nullable', 'string', 'regex:/^\d{3,4}$/'],
            ]);
        }
        
        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'contract_plan_id' => '契約プラン',
            'option_product_ids' => 'オプション商品',
            'option_product_ids.*' => 'オプション商品',
            'company_name' => '会社名',
            'company_name_kana' => '会社名（フリガナ）',
            'department' => '部署名',
            'position' => '役職',
            'contact_name' => '担当者名',
            'contact_name_kana' => '担当者名（フリガナ）',
            'email' => 'メールアドレス',
            'phone' => '電話番号',
            'postal_code' => '郵便番号',
            'prefecture' => '都道府県',
            'city' => '市区町村',
            'address_line1' => '番地',
            'address_line2' => '建物名',
            'usage_url_domain' => 'ご利用URL・ドメイン',
            'import_from_trial' => '体験版からのインポートを希望する',
            'terms_agreed' => '利用規約への同意',
            'pan1' => 'カード番号（1〜4桁目）',
            'pan2' => 'カード番号（5〜8桁目）',
            'pan3' => 'カード番号（9〜12桁目）',
            'pan4' => 'カード番号（13〜16桁目）',
            'card_expiry_month' => 'カード有効期限（月）',
            'card_expiry_year' => 'カード有効期限（年）',
            'card_name' => 'カード名義',
            'scode' => 'セキュリティコード',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_name_kana.regex' => '会社名（フリガナ）は全角カタカナ・数字で入力してください。',
            'contact_name_kana.regex' => '担当者名（フリガナ）は全角カタカナで入力してください。',
            'phone.regex' => '電話番号は数字とハイフンのみで入力してください。',
            'postal_code.regex' => '郵便番号は7桁の数字で入力してください（ハイフン有無可）。',
            'desired_start_date.after_or_equal' => '利用開始希望日は本日以降の日付を選択してください。',
            'terms_agreed.required' => '利用規約への同意が必要です。',
            'terms_agreed.accepted' => '利用規約への同意が必要です。',
            'pan1.regex' => 'カード番号（1〜4桁目）は4桁の数字で入力してください。',
            'pan2.regex' => 'カード番号（5〜8桁目）は4桁の数字で入力してください。',
            'pan3.regex' => 'カード番号（9〜12桁目）は4桁の数字で入力してください。',
            'pan4.regex' => 'カード番号（13〜16桁目）は4桁の数字で入力してください。',
            'card_expiry_month.regex' => 'カード有効期限（月）は01〜12の2桁の数字で入力してください。',
            'card_expiry_year.regex' => 'カード有効期限（年）は2桁または4桁の数字で入力してください。',
            'card_name.max' => 'カード名義は45文字以内で入力してください。',
            'scode.regex' => 'セキュリティコードは3桁または4桁の数字で入力してください。',
        ];
    }

    /**
     * Handle a failed validation attempt.
     * 1ページ目（申込フォーム）のバリデーションエラーは確認画面に進む前に弾く
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // バリデーションエラー時は通常の処理（back()に戻る）
        // これにより、1ページ目のエラーは確認画面（2ページ目）に進む前に表示される
        parent::failedValidation($validator);
    }
}
