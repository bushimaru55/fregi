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
            'company_id' => ['required', 'integer'],
            'environment' => ['required', 'string', 'in:test,prod'],
            'shop_id' => ['required', 'string'],
            'notify_url' => ['required', 'url'],
            'return_url_success' => ['required', 'url'],
            'return_url_cancel' => ['required', 'url'],
            'is_active' => ['sometimes', 'boolean'],
            'change_reason' => ['nullable', 'string', 'max:500'],
        ];

        // 新規作成時はパスワード必須、更新時は変更する場合のみ必須
        if ($this->isMethod('POST')) {
            $rules['connect_password'] = ['required', 'string'];
        } else {
            // 更新時: パスワード変更チェックボックスが有効な場合のみ必須
            if ($this->has('change_password') && $this->input('change_password')) {
                $rules['connect_password'] = ['required', 'string'];
            }
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
            'company_id.required' => '会社IDは必須です。',
            'environment.in' => '環境はtestまたはprodである必要があります。',
        ];
    }
}
