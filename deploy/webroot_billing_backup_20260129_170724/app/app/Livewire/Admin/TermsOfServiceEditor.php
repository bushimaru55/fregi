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
    
    // HTMLソース編集モードかどうか
    public bool $isSourceMode = false;
    
    // HTMLソース編集用のテキスト
    public string $sourceHtml = '';

    public function mount(): void
    {
        // DBから現在の利用規約を取得
        $termsOfService = SiteSetting::getValue('terms_of_service', '');

        $this->form->fill([
            'content_html' => $termsOfService,
        ]);
        
        // ソース編集用にも初期値をセット
        $this->sourceHtml = $termsOfService;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                RichEditor::make('content_html')
                    ->label('利用規約本文（ビジュアルエディタ）')
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

    /**
     * ビジュアルモードとソースモードを切り替え
     */
    public function toggleSourceMode(): void
    {
        if ($this->isSourceMode) {
            // ソースモード → ビジュアルモードに切り替え
            // ソースの内容をRichEditorに反映
            $this->form->fill([
                'content_html' => $this->sourceHtml,
            ]);
        } else {
            // ビジュアルモード → ソースモードに切り替え
            // RichEditorの内容をソースに反映
            $data = $this->form->getState();
            $this->sourceHtml = $data['content_html'] ?? '';
        }
        
        $this->isSourceMode = !$this->isSourceMode;
    }

    public function save(): void
    {
        // ソースモードの場合はソースから、ビジュアルモードの場合はフォームから取得
        if ($this->isSourceMode) {
            $html = $this->sourceHtml;
        } else {
            $data = $this->form->getState();
            $html = $data['content_html'] ?? '';
        }

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
