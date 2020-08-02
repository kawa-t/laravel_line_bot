<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\Event\MessageEvent\TextMessage;

class LineBotPokenameController extends Controller
{
    public function input_pokemon(Request $request)
    {
        //トークンの取得
        $lineAccessToken = config('services.line.access_token');
        $lineChannelSecret = config('services.line.channel_secret');
        //認証を行う
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

            //入力チェック
            if(in_array($replyText, array_column($pokemon_array, 'name'), true))
            {
              //該当するポケモンがいる場合
              $pokemon_data = $pokemon_array[array_search($replyText, array_column($pokemon_array, 'name'))];

              //タイプを入れる
              $type1 = $pokemon_data['types'][0];

              //単タイプかどうか
              if(isset($pokemon_data['types'][1])){
                $type2 = $pokemon_data['types'][1];
              }else{
                $type2 = 'mono_type';
                $sp_weak = "";
              }

              //タイプ判定
              foreach($type_array as $key1 => $value1){
                $array_list = $value1;

                //タイプ１
                $type_match1 = array_filter($array_list, function($element) use($type1)
                {
                  //必ずある前提なので返すだけ
                  return $element['type'] == $type1;
                });
                //該当するものを取得
                $type_a = array_column($type_match1, 'double_weakness_type');

                //タイプ２
                if ($type2 === 'mono_type'){
                  $type_match = $type_a[0];
                }else{
                  $type_match2 = array_filter($array_list, function($element) use($type2)
                  {
                    return $element['type'] == $type2;
                  });
                  //該当するものを取得
                  $type_b = array_column($type_match2, 'double_weakness_type');

                  //複合弱点をマージ
                  $type_match = array_merge($type_a[0],$type_b[0]);

                  //4倍弱点
                  $sp_weak = array_intersect($type_a[0],$type_b[0]);

                  //2倍弱点
                  $type_match = array_diff($type_match,$sp_weak);

                }

                //重複削除
                $type_match = array_unique($type_match);
                $type_match = array_values($type_match);
              };
            }else{
              //ポケモン以外の入力の場合
                $type_match = array("no_much");
            }

            //変数宣言
            $weak_text = "";
            $spweak_text = "";

            //タイプ以外の入力がされてきた場合
            if(in_array("no_much", $type_match)){
              $weak_text = "ポケモンのなまえを入力してね";
            }else{
              for($i = 0; $i < count($type_match); $i++)
              {
                $weak_text = $type_match[$i].",".$weak_text;
              }
              if($sp_weak){
                $weak_text = $weak_text. "\n \n4倍弱点だよ \n".$sp_weak[0];
              }
            }

            //LINEへ送信する
            $lineBot->replyText($replyToken, $weak_text);
        }
    }
}
