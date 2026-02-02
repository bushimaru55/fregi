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
        $notificationEmails = SiteSetting::getNotificationEmailsArray();
        return view('admin.users.index', compact('users', 'notificationEmails'));
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
     * 送信先メールアドレス更新（複数対応：1行1件またはカンマ区切り）
     */
    public function updateNotificationEmail(Request $request): RedirectResponse
    {
        $raw = $request->input('notification_email', '');
        $parts = preg_split('/[\r\n,]+/', $raw) ?: [];
        $emails = array_values(array_filter(array_map('trim', $parts)));

        if (empty($emails)) {
            return back()
                ->withInput()
                ->withErrors(['notification_email' => '送信先メールアドレスを1件以上入力してください。']);
        }

        $invalid = [];
        foreach ($emails as $e) {
            if (strlen($e) > 255) {
                $invalid[] = $e . '（255文字以内）';
            } elseif (filter_var($e, FILTER_VALIDATE_EMAIL) === false) {
                $invalid[] = $e;
            }
        }
        if ($invalid !== []) {
            return back()
                ->withInput()
                ->withErrors(['notification_email' => '有効なメールアドレス形式で入力してください: ' . implode(', ', array_slice($invalid, 0, 3)) . (count($invalid) > 3 ? '...' : '')]);
        }

        try {
            $value = implode("\n", $emails);
            SiteSetting::setTextValue(
                'notification_email',
                $value,
                '申込受付時の通知メール送信先アドレス（複数可）'
            );

            return redirect()
                ->route('admin.users.index')
                ->with('success', '送信先メールアドレスを更新しました。（' . count($emails) . '件）');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 送信テスト：登録済みの送信先メールアドレス全てにテストメールを送信
     */
    public function sendTestNotificationEmail(): RedirectResponse
    {
        $notificationEmails = SiteSetting::getNotificationEmailsArray();

        if (empty($notificationEmails)) {
            return redirect()
                ->route('admin.users.edit-notification-email')
                ->withErrors(['error' => '送信先メールアドレスが未設定です。先にアドレスを保存してください。']);
        }

        // #region agent log（本番メール不達の原因切り分け用：設定状態のみ記録、パスワードは記録しない）
        $mailConfig = config('mail.mailers.smtp');
        Log::channel('mail')->info('送信テストメール送信試行', [
            'to_count' => count($notificationEmails),
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_HOST' => $mailConfig['host'] ?? null,
            'MAIL_PORT' => $mailConfig['port'] ?? null,
            'MAIL_USERNAME' => isset($mailConfig['username']) && (string) $mailConfig['username'] !== '' ? '(set)' : '(empty)',
            'MAIL_PASSWORD' => isset($mailConfig['password']) && (string) $mailConfig['password'] !== '' ? '(set)' : '(empty)',
            'MAIL_ENCRYPTION' => $mailConfig['encryption'] ?? null,
        ]);
        // #endregion

        try {
            foreach ($notificationEmails as $email) {
                Mail::to($email)->send(new NotificationTestMail());
            }
            Log::channel('mail')->info('送信テストメール送信完了', ['to' => $notificationEmails]);

            $message = count($notificationEmails) === 1
                ? '送信テストメールを送信しました。' . $notificationEmails[0] . ' をご確認ください。'
                : '送信テストメールを送信しました。（' . count($notificationEmails) . '件）ご確認ください。';

            return redirect()
                ->back()
                ->with('success', $message);
        } catch (\Throwable $e) {
            $previous = $e->getPrevious();
            Log::channel('mail')->error('送信テストメール送信エラー', [
                'to' => $notificationEmails,
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'previous_message' => $previous ? $previous->getMessage() : null,
                'previous_class' => $previous ? get_class($previous) : null,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => '送信に失敗しました: ' . $e->getMessage()]);
        }
    }
}
