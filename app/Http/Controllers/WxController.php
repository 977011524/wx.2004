<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Redis;
use App\Model\UserModel;
use GuzzleHttp\Client;
class WxController extends Controller
{
    

    //处理推送事件
    public function wxEvent(){
        // echo __METHOD__;die;
        echo __LINE__;die;
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
	
        $token = env('MIX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
    
        if( $tmpStr == $signature ){               //验证通过
            //使用guzzle发起get请求
            

            //接收信息
          $xml_str =   file_get_contents("php://input") . "\n\n";

          //记录日志
          file_put_contents('wx_event.log',$xml_str,FILE_APPEND);
            //把xml的文本转换为对象或数组
            $obj = simplexml_load_string($xml_str); //将文件转换成 对象
            
        
           if($obj->MsgType=="event"){
               if($obj->Event=="subscribe"){    //处理扫码关注
                   $content = '关注成功';
                   $resule =  $this->xiaoxi($obj,$content);

                   //用户信息
                   $FromUserName = $obj->FromUserName;
                   $access_token = $this->token();
                   $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$FromUserName.'&lang=zh_CN';
                   $user_json = file_get_contents($url);    //发送地址  接回来json 字符串
                   $user_data = json_decode($user_json,true);    //转换成数组


                   $data = [
                       'nickname'=>$user_data['nickname'],
                       'sex'=>$user_data['sex'],
                       'country'=>$user_data['country'],
                       'headimgurl'=>$user_data['headimgurl'],
                       'add_time'=>$user_data['subscribe_time'],
                       'openid'=>$user_data['openid'],
                   ];
                   $userModel = new UserModel;
                   $userModel::inserGetId($data);
                   return $resule;   //关注成功   返回值
                
                }
               
           
          //TODO 处理业务逻辑
         

        }else if($obj->MsgType=='text'){
            switch($obj->Content){
                case '天气';
                $count_str = $this->weather();    //天气 返回参数
                $weather = $this->attention($obj,$count_str);   //xml  返回微信
                echo $weather;
            break;
            case'你好';
            }
        }
            
            
        }
    }



    //获取access_token
    public function token(){
        $key = 'wx:access_token';
        if(empty(Redis::get($key))){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');
            // echo $url;die;
            $ken = file_get_contents($url);
            $data = json_decode($ken,true);
            // dd($data);
            Redis::set($key,$data['access_token']);
            
            Redis::expire($key,3600);
        }
        return Redis::get($key);
    }
    //天气
    public function weather(){
        $url = 'https://devapi.qweather.com/v7/weather/now?location=101010100&key=ef14d67e99d74715b691c012e9ff4285&gzip=n';
        $weather_url = file_get_contents($url);
        // dd($weather_url);
        $weather_url = json_decode($weather_url,true);
        $weather_data = $weather_url['now'];
        
        $count_str = '日期：'.date('Y-m-d H:i:s',time()+8).'天气：'.$weather_data['text'].';风向：'.$weather_data['windDir'].';风力等级：'.$weather_data['windScale'];
        return $count_str;
        
    
    }


    //回复关注消息
    public function xiaoxi($obj,$content){
        $content = "欢迎关注11111";
        $ToUserName = $obj->FromUserName;
        $FromUserName = $obj->ToUserName;


        $xml="<xml>
                <ToUserName><![CDATA[".$ToUserName."]]></ToUserName>
                <FromUserName><![CDATA[".$FromUserName."]]></FromUserName>
                <CreateTime>time()</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[".$content."]]></Content>
                <MsgId>%s<MsgId>
                </xml>";

        $xml_info = sprintf($xml,$ToUserName,$FromUserName,time(),'text',$content);
        return $xml_info;
    }
    //上次素材
    public function guzzle2(){
        $access_token = $this->token();
        // echo $access_token;die;
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

    //创建自定义菜单
    public function createMenu(){
        $access_token = $this->token();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        // echo $url;die;
        $menu = [
            'button'  =>[
            [
                'type'  => 'click',
                'name'    =>'李建博',
                'key'     => 'V1001_TODAY_MUSIC'
            ],
            [
                'type'  => 'view',
                'name'    => '百度',
                'url'     => 'https://www.baidu.com'
            ],
            [
                'type'  => 'view',
                'name'    => '百度',
                'url'     => 'https://www.baidu.com'
            ],
        ]
    ];
    // print_r($menu);die;


        //使用guzzle发起POST请求
        $client = new Client();

        $response = $client->request('POST',$url,[
            'verify' => false,
            'body'   => json_encode($menu,JSON_UNESCAPED_UNICODE),  
        ]);
        $json_data = $response->getBody();
        echo $json_data;
    }
    
}
