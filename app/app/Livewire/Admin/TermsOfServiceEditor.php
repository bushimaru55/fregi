<?php

namespace App\Livewire\Admin;

use App\Models\SiteSetting;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Str;
use Livewire\Component;
use Mews\Purifier\Facades\Purifier;

class TermsOfServiceEditor extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        // DBから現在の利用規約を取得
        $termsOfService = SiteSetting::getValue('terms_of_service', '');

        $this->form->fill([
            'content_html' => $termsOfService,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                RichEditor::make('content_html')
                    ->label('利用規約本文')
                    ->required()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'h2',
                        'h3',
                        'bulletList',
                        'orderedList',
                        'blockquote',
                        'codeBlock',
                        'link',
                        'redo',
                        'undo',
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $html = $data['content_html'] ?? '';

        // HTMLをサニタイズ（rich_htmlプロファイル使用）
        $cleanHtml = Purifier::clean($html, 'rich_html');

        // プレーンテキスト版を生成
        $plainText = Str::squish(strip_tags($cleanHtml));

        // DBに保存（HTMLとテキスト両方）
        SiteSetting::updateOrCreate(
            ['key' => 'terms_of_service'],
            [
                'value' => $cleanHtml,
                'value_text' => $plainText,
                'description' => '利用規約の本文',
            ]
        );

        // 成功メッセージをセッションに保存してリダイレクト
        session()->flash('success', '利用規約を更新しました。');

        $this->redirect(route('admin.site-settings.index'));
    }

    public function render()
    {
        return view('livewire.admin.terms-of-service-editor');
    }
}
