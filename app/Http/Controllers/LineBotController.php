<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\Event\MessageEvent\TextMessage;

class LineBotController extends Controller
{
    public function index()
    {
        return view('linebot.index');
    }

    public function parrot(Request $request)
    {
        //認証を行う
        $lineAccessToken = config('services.line.access_token');
        $lineChannelSecret = config('services.line.channel_secret');

        $httpClient = new CurlHTTPClient($lineAccessToken);
        $lineBot = new LINEBot($httpClient, ['channelSecret' => $lineChannelSecret]);

        $signature = $request->header('x-line-signature');

        if (!$lineBot->validateSignature($request->getContent(), $signature)) {
            //送信元に400エラーを伝える
            abort(400, 'Invalid signature');
        }

        //LINEで入力されたメッセージ情報を受け取る
        $events = $lineBot->parseEventRequest($request->getContent(), $signature);

        foreach ($events as $event) {
            if (!($event instanceof TextMessage)) {
                Log::debug('Non text message has come');
                continue;
            }

            $replyToken = $event->getReplyToken();
            $replyText = $event->getText();
            //エンコードを行う
            $replyText = mb_convert_encoding($replyText,'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');

            //Jsonを取得する
            $url = public_path() . '/data/double_weakness.json';
            $json = file_get_contents($url);
            $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
            $type_array = json_decode($json, true);

            //ポケモン情報を取得
            $url = 'https://raw.githubusercontent.com/kotofurumiya/pokemon_data/master/data/pokemon_data.json';
            $json = file_get_contents($url);
            $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
            $pokemon_array = json_decode($json, true);

            Log::debug($pokemon_array);

            //複合タイプに対応する（区切りをつけているかで判断）
            $multi_type = explode("、", $replyText);
            $type1 =  $multi_type[0];

            if(strpos($replyText, "、") !== false){
              $type2 =  $multi_type[1];
            }else{
              $type2 = $replyText;
            }

            //タイプを判定
            foreach($type_array as $key1 => $value1){
              $array_list = $value1;

              //タイプ１
              $type_match1 = array_filter($array_list, function($element) use($type1)
              {
                return $element['type'] == $type1;
              });
              //タイプ以外の入力があった場合
              if($type_match1 == null){
                $a = array(
                  array("no_much")
                );
              }else{
                $a = array_column($type_match1, 'double_weakness_type');
              }

              //タイプ２
              $type_match2 = array_filter($array_list, function($element) use($type2)
              {
                return $element['type'] == $type2;
              });
              //タイプ以外の入力があった場合
              if($type_match2 == null){
                $b = array(
                  array("no_much")
                );
              }else{
                $b = array_column($type_match2, 'double_weakness_type');
              }

              $type_match = array_merge($a[0],$b[0]);

              //重複削除
              $type_match = array_unique($type_match);
              $type_match = array_values($type_match);
            };

            //変数宣言
            $weak_text = "";

            //タイプ以外の入力がされてきた場合
            if(in_array("no_much", $type_match)){
              $weak_text = "タイプを入力してね";
            }else{
              for($i = 0; $i < count($type_match); $i++)
              {
                $weak_text = $type_match[$i].",".$weak_text;
              }
            }

            //LINEへ送信する
            $lineBot->replyText($replyToken, $weak_text);
        }
    }
}