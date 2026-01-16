<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FregiConfigRequest;
use App\Models\FregiConfig;
use App\Services\FregiConfigService;
use Illuminate\Http\Request;

class FregiConfigController extends Controller
{
    private FregiConfigService $configService;

    public function __construct(FregiConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $configs = FregiConfig::orderBy('company_id')
            ->orderBy('environment')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.fregi-configs.index', compact('configs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.fregi-configs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FregiConfigRequest $request)
    {
        try {
            $data = $request->validated();
            $data['connect_password'] = $data['connect_password'] ?? null;
            $data['updated_by'] = auth()->id() ?? 'system'; // TODO: 認証実装後に対応

            $config = $this->configService->createConfig($data);

            return redirect()
                ->route('admin.fregi-configs.show', $config)
                ->with('success', 'F-REGI設定を登録しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '登録に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(FregiConfig $fregiConfig)
    {
        return view('admin.fregi-configs.show', compact('fregiConfig'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FregiConfig $fregiConfig)
    {
        return view('admin.fregi-configs.edit', compact('fregiConfig'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FregiConfigRequest $request, FregiConfig $fregiConfig)
    {
        try {
            $data = $request->validated();
            
            // パスワード変更チェック
            if (!($request->has('change_password') && $request->input('change_password'))) {
                unset($data['connect_password']);
            }

            $data['updated_by'] = auth()->id() ?? 'system'; // TODO: 認証実装後に対応

            $config = $this->configService->updateConfig($fregiConfig, $data);

            return redirect()
                ->route('admin.fregi-configs.show', $config)
                ->with('success', 'F-REGI設定を更新しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FregiConfig $fregiConfig)
    {
        // TODO: 削除機能の実装（必要に応じて）
        return back()->withErrors(['error' => '削除機能は未実装です。']);
    }
}
