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
//        Log::debug($request->header());
//        Log::debug($request->input());

        $lineAccessToken = config('services.line.access_token');
        $lineChannelSecret = config('services.line.channel_secret');

        $httpClient = new CurlHTTPClient($lineAccessToken);
        $lineBot = new LINEBot($httpClient, ['channelSecret' => $lineChannelSecret]);

        $signature = $request->header('x-line-signature');

        if (!$lineBot->validateSignature($request->getContent(), $signature)) {
            abort(400, 'Invalid signature');
        }

        $events = $lineBot->parseEventRequest($request->getContent(), $signature);

        foreach ($events as $event) {
            if (!($event instanceof TextMessage)) {
                Log::debug('Non text message has come');
                continue;
            }

            $replyToken = $event->getReplyToken();
            $replyText = $event->getText();

            //Jsonを取得する
            $url = public_path() . '/data/double_weakness.json';
            $json = file_get_contents($url);
            $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
            $type_array = json_decode($json, true);

            //タイプを判定
            foreach($type_array as $key1 => $value1){
              $array_list = $value1;
              $type_match = array_filter($array_list, function($element) use($replyText)
              {
                return $element['type'] == $replyText;
              });
            };

          $weak_text = "";

          if($type_match == null){
            $weak_text = "タイプを入力してね";
          }else{
            $results = array_column($type_match, 'double_weakness_type');
            if(count($results[0]) === 1){
              $weak_text = $results[0][0];
            }else{
              for($i = 0; $i < count($results[0]); $i++)
              {
                $weak_text = $results[0][$i].",".$weak_text;
              }
            }
          }

            $lineBot->replyText($replyToken, $weak_text);
        }
    }
}