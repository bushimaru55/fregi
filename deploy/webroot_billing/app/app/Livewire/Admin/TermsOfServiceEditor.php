<?php

namespace App\Livewire\Admin;

use App\Models\SiteSetting;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Mews\Purifier\Facades\Purifier;

class TermsOfServiceEditor extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    /** 編集中の決済タイプ（billing_selection） */
    public string $billingSelection = 'one_time';

    // HTMLソース編集モードかどうか
    public bool $isSourceMode = false;

    // HTMLソース編集用のテキスト
    public string $sourceHtml = '';

    public function mount(?string $billingSelection = null): void
    {
        $labels = SiteSetting::billingSelectionLabels();
        $fromQuery = request()->query('billing_selection');
        $initial = $billingSelection ?? $fromQuery ?? 'one_time';
        if (!array_key_exists($initial, $labels)) {
            $initial = 'one_time';
        }
        $this->billingSelection = $initial;
        $this->loadTermsForSelection($this->billingSelection);
    }

    /**
     * タブ切替（未保存の変更がある場合は確認ダイアログ後に呼ばれる想定）
     */
    public function switchBillingSelection(string $selection): void
    {
        $labels = SiteSetting::billingSelectionLabels();
        if (!array_key_exists($selection, $labels)) {
            return;
        }

        if ($selection === $this->billingSelection) {
            return;
        }

        $this->billingSelection = $selection;
        $this->isSourceMode = false;
        $this->loadTermsForSelection($selection);
    }

    protected function loadTermsForSelection(string $selection): void
    {
        $termsOfService = SiteSetting::getTermsOfService($selection);

        $this->form->fill([
            'content_html' => $termsOfService,
        ]);

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
            $this->form->fill([
                'content_html' => $this->sourceHtml,
            ]);
        } else {
            // ビジュアルモード → ソースモードに切り替え
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

        SiteSetting::setTermsOfService($this->billingSelection, $cleanHtml);

        $label = SiteSetting::billingSelectionLabels()[$this->billingSelection] ?? $this->billingSelection;
        session()->flash('success', "利用規約（{$label}）を更新しました。");

        $this->redirect(route('admin.site-settings.index', [
            'billing_selection' => $this->billingSelection,
        ]));
    }

    public function render()
    {
        return view('livewire.admin.terms-of-service-editor', [
            'billingSelectionLabels' => SiteSetting::billingSelectionLabels(),
        ]);
    }
}
