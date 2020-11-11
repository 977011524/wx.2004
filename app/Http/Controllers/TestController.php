<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;

class TestController extends Controller
{
    public function test1()
    {
        // echo __METHOD__;
        // $list = DB::table('p_users')->limit(4)->get()->toArray();
        // print_r($list);exit;
        $key = 'wx2004';
        Redis::set($key,time());
        echo Redis::get($key);
    }

    public function test2()
    {
        echo '<pre>';print_r($_GET); echo '<pre>';
    }
    public function test3()
    {
        // echo '<pre>';print_r($_POST); echo '<pre>';
        $xml_str = file_get_contents("php://input");
        
        // echo $xml_str;
        //将xml 转换为 对象或数组
        $xml = simplexml_load_string($xml_str);
        // echo '<pre>';print_r($xml); echo '<pre>';
        $sds = $xml->ToUserName;
        echo $sds;
    }
    public function guzzle1()
    {

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');
        

        //使用guzzle发起get请求
        $client = new Client(); //实列化 客户端
        $response = $client->request('GET',$url,['verify'=>false]); //发起请求并接受响应

    
        $json_str = $response->getBody();//服务器的响应数据
        echo $json_str;
    
    }
    public function guzzle2(){
        $access_token = "";
        $type = "image";
        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;
        //使用guzzle发起get请求
        $client = new Client();
        $response = $client->request('POST',$url,[
            'verify'    =>false,
            'multipart' => [
                [
                    'name' =>'media',
                'contents' => fopen('121212.png','r')

                ],   
            ]
        ]);       //发起请求并接受响应
        $data = $response->getBody();
        echo $data;

    }
    public function json(){
        $a ="";
        $url = "http://abc.com?access_token=".$a;
        var_dump($a);die;
        echo $a;die;




        $arr = [
            "name"  => "zhangsan", 
            "age"   => 20,
            'email' => "zhangsan@qq.com",
            "name_cn" => "张三"
        ];
        echo '<pre>';print_r($arr);echo '</pre>';

        echo '<hr>';
        $json = json_encode($arr,JSON_UNESCAPED_UNICODE);
        echo $json;
        echo '<hr>';
        //json转对象
        $obj = json_decode($json);
        echo '<pre>';print_r($obj);echo '</pre>';
        echo '<hr>';

        $arr = json_decode($json,true);
        echo '<pre>';print_r($arr);echo '</pre>';
    }
}