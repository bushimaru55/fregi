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
        return [
            // 契約プラン
            'contract_plan_id' => ['required', 'exists:contract_plans,id'],
            
            // 申込企業情報
            'company_name' => ['required', 'string', 'max:255'],
            'company_name_kana' => ['nullable', 'string', 'max:255', 'regex:/^[ァ-ヶー\s]+$/u'],
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
            
            // 契約内容
            'desired_start_date' => ['required', 'date', 'after_or_equal:today'],
            
            // 利用規約への同意
            'terms_agreed' => ['required', 'accepted'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'contract_plan_id' => '契約プラン',
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
            'desired_start_date' => '利用開始希望日',
            'terms_agreed' => '利用規約への同意',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_name_kana.regex' => '会社名（フリガナ）は全角カタカナで入力してください。',
            'contact_name_kana.regex' => '担当者名（フリガナ）は全角カタカナで入力してください。',
            'phone.regex' => '電話番号は数字とハイフンのみで入力してください。',
            'postal_code.regex' => '郵便番号は7桁の数字で入力してください（ハイフン有無可）。',
            'desired_start_date.after_or_equal' => '利用開始希望日は本日以降の日付を選択してください。',
            'terms_agreed.required' => '利用規約への同意が必要です。',
            'terms_agreed.accepted' => '利用規約への同意が必要です。',
        ];
    }
}
