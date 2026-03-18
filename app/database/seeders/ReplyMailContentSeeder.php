<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class ReplyMailContentSeeder extends Seeder
{
    /**
     * 返信メールの上部・下部文章をサイト設定に登録する。
     * 実行: php artisan db:seed --class=ReplyMailContentSeeder
     */
    public function run(): void
    {
        $header = <<<'TEXT'
このたびは、DSチャットボットのご利用申込をいただき、誠にありがとうございます。
以下の内容で申込を承りました。内容をご確認ください。
TEXT;

        $footer = <<<'TEXT'
ご不明な点がございましたら、お気軽にお問い合わせください。

【お問い合わせ】
・お問い合わせ・資料請求：https://dschatbot.ai/inquiry/
・サービスサイト：https://dschatbot.ai/

【運営会社】
株式会社ディーエスブランド
〒852-8003　長崎県長崎市旭町6-1 タワーシティ長崎タワーコート1F
TEL：095-862-4891　FAX：095-862-4855
https://ds-b.jp/

今後ともDSチャットボットをどうぞよろしくお願いいたします。
株式会社ディーエスブランド
TEXT;

        SiteSetting::setTextValue(
            'reply_mail_header',
            $header,
            '申込者への返信メールの上部文章'
        );

        SiteSetting::setTextValue(
            'reply_mail_footer',
            $footer,
            '申込者への返信メールの下部文章'
        );
    }
}
