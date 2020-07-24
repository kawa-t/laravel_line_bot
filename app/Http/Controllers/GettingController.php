<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GettingController extends Controller
{
    public function gettoeken()
    {
        //リクエスト先URL
        $API_URL = "https://api.line.me/v2/oauth/accessToken";

        $data = array(
            'grant_type' => 'client_credentials',
            'client_id' => '1654467172',
            'client_secret' => '6b9d35aa5582dddf2e4ba3e491c12fdf',
        );
        $header = array(
            "Content-Type: application/x-www-form-urlencoded",
        );
        $options = array('http' => array(
            'method' => 'POST',
            'header'  => implode("\r\n", $header),
            'content' => http_build_query($data)
        ));

        $response = file_get_contents(
            $API_URL,
            false,
            stream_context_create($options)
        );

        //レスポンスのjsonからtokenを取得
        $access_token = json_decode($response)->access_token;
        return response()->json(['results' => $access_token]);
    }
}
