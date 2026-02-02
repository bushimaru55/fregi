<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
        'terms_of_service', // HTMLコンテンツなのでトリムしない
        'reply_mail_header', // 上部・下部の先頭/末尾改行を保持するためトリムしない
        'reply_mail_footer',
    ];
}
