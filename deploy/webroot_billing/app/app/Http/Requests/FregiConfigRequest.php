<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FregiConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: 認証・認可の実装が必要
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'environment' => ['required', 'string', 'in:test,prod'],
            'shopid' => ['required', 'string', 'max:255'],
            'notify_url' => ['required', 'url'],
            'return_url_success' => ['required', 'url'],
            'return_url_cancel' => ['required', 'url'],
            'is_active' => ['sometimes', 'boolean'],
            'change_reason' => ['nullable', 'string', 'max:500'],
        ];

        // パスワードバリデーション
        // 1. パスワード変更チェックボックスが有効な場合：必須
        // 2. 初回保存時（connect_password_encが空の場合）：必須
        $config = \App\Models\FregiConfig::where('company_id', 1)->first();
        $isFirstTime = !$config || empty($config->connect_password_enc);
        
        if ($this->has('change_password') && $this->input('change_password')) {
            $rules['connect_password'] = ['required', 'string'];
        } elseif ($isFirstTime) {
            // 初回保存時はconnect_passwordが必須
            $rules['connect_password'] = ['required', 'string'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'connect_password.required' => '接続パスワードは必須です。',
            'shopid.required' => 'SHOP IDは必須です。',
            'environment.in' => '環境はtestまたはprodである必要があります。',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'shopid' => 'SHOP ID',
            'connect_password' => '接続パスワード',
            'notify_url' => '通知URL',
            'return_url_success' => '成功時戻りURL',
            'return_url_cancel' => 'キャンセル時戻りURL',
        ];
    }
}
