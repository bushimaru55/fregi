<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SiteSetting;
use App\Mail\NotificationTestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    /**
     * 管理者一覧
     */
    public function index(): View
    {
        $users = User::orderBy('created_at', 'desc')->get();
        $notificationEmail = SiteSetting::getTextValue('notification_email', '');
        return view('admin.users.index', compact('users', 'notificationEmail'));
    }

    /**
     * 管理者新規作成フォーム
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * 管理者新規作成処理
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => '名前を入力してください。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '有効なメールアドレス形式で入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'password.required' => 'パスワードを入力してください。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => 'パスワード（確認）が一致しません。',
        ]);

        try {
            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', '管理者を登録しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '登録に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 管理者編集フォーム
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * 管理者更新処理
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => '名前を入力してください。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '有効なメールアドレス形式で入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => 'パスワード（確認）が一致しません。',
        ]);

        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            // パスワードが入力されている場合のみ更新
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            return redirect()
                ->route('admin.users.index')
                ->with('success', '管理者情報を更新しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 管理者削除処理
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            // 自分自身は削除できないようにする
            if ($user->id === auth()->id()) {
                return redirect()
                    ->route('admin.users.index')
                    ->withErrors(['error' => '自分自身を削除することはできません。']);
            }

            $user->delete();

            return redirect()
                ->route('admin.users.index')
                ->with('success', '管理者を削除しました。');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['error' => '削除に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 送信先メールアドレス編集画面
     */
    public function editNotificationEmail(): View
    {
        $notificationEmail = SiteSetting::getTextValue('notification_email', '');
        
        return view('admin.users.edit-notification-email', compact('notificationEmail'));
    }

    /**
     * 送信先メールアドレス更新
     */
    public function updateNotificationEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notification_email' => ['required', 'email', 'max:255'],
        ], [
            'notification_email.required' => '送信先メールアドレスを入力してください。',
            'notification_email.email' => '有効なメールアドレス形式で入力してください。',
            'notification_email.max' => 'メールアドレスは255文字以内で入力してください。',
        ]);

        try {
            SiteSetting::setTextValue(
                'notification_email',
                $validated['notification_email'],
                '申込受付時の通知メール送信先アドレス'
            );

            return redirect()
                ->route('admin.users.index')
                ->with('success', '送信先メールアドレスを更新しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 送信テスト：登録済みの送信先メールアドレスにテストメールを送信
     */
    public function sendTestNotificationEmail(): RedirectResponse
    {
        $notificationEmail = SiteSetting::getTextValue('notification_email', '');

        if (empty($notificationEmail)) {
            return redirect()
                ->route('admin.users.edit-notification-email')
                ->withErrors(['error' => '送信先メールアドレスが未設定です。先にアドレスを保存してください。']);
        }

        try {
            Mail::to($notificationEmail)->send(new NotificationTestMail());
            Log::channel('mail')->info('送信テストメール送信完了', ['to' => $notificationEmail]);

            return redirect()
                ->route('admin.users.edit-notification-email')
                ->with('success', '送信テストメールを送信しました。' . $notificationEmail . ' をご確認ください。');
        } catch (\Throwable $e) {
            $previous = $e->getPrevious();
            Log::channel('mail')->error('送信テストメール送信エラー', [
                'to' => $notificationEmail,
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'previous_message' => $previous ? $previous->getMessage() : null,
                'previous_class' => $previous ? get_class($previous) : null,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('admin.users.edit-notification-email')
                ->withErrors(['error' => '送信に失敗しました: ' . $e->getMessage()]);
        }
    }
}
