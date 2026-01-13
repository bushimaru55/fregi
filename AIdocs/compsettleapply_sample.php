<?php

/*

F-REGI：compsettleapply.cgi
接続サンプルプログラム (PHP版)

本プログラムはF-REGI WEBAPIに連携し、決済処理を行うためのサンプルプログラムです。
お客様のウェブサイト、ショッピングカートからの接続にご利用下さい。

注：SSLサポートが有効なPHPでないと通信できません。

*/

# F-REGI接続先
# テスト環境
$api = "/connecttest/compsettleapply.cgi";
$url = "https://pay.f-regi.com/usertest/";
# 本番環境
# $api = "/connect/compsettleapply.cgi";
# $url = "https://pay.f-regi.com/user/";

$fp = fsockopen("ssl://ssl.f-regi.com", 443, $errno, $errstr, 30);
if (!$fp) die("error: $errstr ($errno)\n");

$SHOPID   = '*****';         # 店舗ID（設定完了後にお知らせします）
$PASSWORD = '********';      # 店舗パスワード（設定完了後にお知らせします）
$ID       = '0123456789-1';  # 伝票番号（半角英数字、半角ハイフン、一意となる値）

# 引数の用意
$param =
    'SHOPID=' . $SHOPID .                # 店舗ID(必須)
    '&ID=' . $ID .                       # 伝票番号(必須)
    '&PAY=10000' .                       # 金額（カンマ区切り不可、2〜9999999）(必須)
    '&USERNAME1=山田' .                  # お客様名：姓(最大１６文字,全角文字）
    '&USERNAME2=太郎' .                  # お客様名：名(最大１６文字,全角文字）
    '&USERNAMEKANA1=ﾔﾏﾀﾞ' .              # お客様名カナ：姓(最大３２文字,全角文字）
    '&USERNAMEKANA2=ﾀﾛｳ' .               # お客様名カナ：名(最大３２文字,全角文字）
    '&USERTEL=012-345-6789' .            # お客様電話番号(最大１３文字、半角英数字、半角ハイフン)
    '&ITEMTITLE=辞書' .                  # 商品名(最大３２文字、全角文字)
    '&ITEMNAME=辞書' .                   # pay-easy用商品名(最大１２文字、全角文字)
    '&ITEMNAMEKANA=ｼﾞｼﾖ' .               # pay-easy用商品名カナ(最大２４文字、全角文字)
    '&EXPIRE=0' .                        # 有効期限(半角英数字、0〜30)
    '&CHARCODE=euc' .                    # 文字エンコーディング形式
    '&QUIET=0';                          # メール送信フラグ(0:通知する、1:通知しない)

# GET
fwrite(
    $fp,
    "GET $api?$param HTTP/1.1\r\n" .
    "Host: ssl.f-regi.com\r\n" .
    "Connection: Close\r\n\r\n"
);

/*
# POST
fwrite(
    $fp,
    "POST $api HTTP/1.1\r\n" .
    "Host: ssl.f-regi.com\r\n" .
    "Connection: Close\r\n" .
    "Content-Length: " . strlen($param) . "\r\n\r\n" .
    $param
);
*/



# レスポンスをパーズしながら、結果をテキスト出力する。
header('Content-Type: text/plain; charset=EUC-JP');
while (!feof($fp)) {
    $line = fgets($fp);
    if (substr($line,0,2) == 'OK') {
        
        $RESULT   = $line;
        $SETTLENO = chop(fgets($fp, 128));
        
        # 支払画面のURLを作成
        $md5_seed = $SHOPID . "\t" . $PASSWORD . "\t" . $SETTLENO . "\t" . $ID;
        $CHECKSUM = md5($md5_seed);
        $orderurl = $url."?SETTLENO=".$SETTLENO."&CHECKSUM=".$CHECKSUM;
        
        # 戻り値を表示
        echo    "結果: "             . $RESULT .
                "発行番号: "         . $SETTLENO . 
                "支払画面URL: "      . $orderurl;

    } else if (substr($line,0,2) == 'NG') {
        # 失敗理由を表示
        echo "結果: "                . $line .
             "エラーコード: "        . fgets($fp, 128) .
             "エラーメッセージ: "    . fgets($fp, 128);

    }
}
fclose($fp);

?>
