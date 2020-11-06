<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Redis;

class WxController extends Controller
{
    public function access(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
	
        $token = env('MIX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
    
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }


    public function wxEvent(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
	
        $token = env('MIX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
    
        if( $tmpStr == $signature ){               //验证通过
            echo "";   
        }else{
            echo "";
        }
    }
    //获取access_token
    public function token(){
        $key = 'wx:access_token';
        $redis = Redis::get($key);
        if($redis){
            echo'111';
        }else{
            
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('APPID')."&secret=".env('APPSECRET');
        $response = file_get_contents($url);
        $data = json_decode($response,true);
        // echo $data;die;
        // dd($data);
        $token = $data['access_token'];
        //吧access_token保存在redis中
        
        Redis::set($key,$token);
        Redis::expire($key,3600);
        }


    }
    
}
