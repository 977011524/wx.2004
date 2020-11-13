<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Redis;
use App\Model\UserModel;
use GuzzleHttp\Client;
class WxController extends Controller
{
    protected $obj;

    //处理推送事件
    public function checkwx(){
        //测试微信服务器仅限于线上
        //以下三个参数是由微信服务器发送到本地服务器上的参数，不可以直接访问本地服务器
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        
        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
   
        if( $tmpStr == $signature ){
            
            //接受数据
            $xml = file_get_contents('php://input');
            
 
            //记录日志
            
            file_put_contents('wx_event.log',$xml,FILE_APPEND);
            $obj = simplexml_load_string($xml);         //将xml文件转换成对象
            $ToUserName = $obj->FromUserName;
            
            //判断
            if($obj->MsgType=='event'){
                //关注
                if($obj->Event=='subscribe'){
                     
                    $wx_user = UserModel::where(['openid'=>$obj->FromUserName])->first();
                   
                    if($wx_user){
                        $content = '谢谢再次关注';
                    }else{
                        //关注 方法
                        $content = '关注成功';
                        
                        //用户信息
                        $access_token = $this->token();             //获取access_token
                        // dd($access_token);
                        // $fromusername = $obj->FromUserName;
                        
                        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$obj->FromUserName.'&lang=zh_CN';
                        
                        $user_json = file_get_contents($url);                 //发送地址  接回来 json 字符串
                        $user_data = json_decode($user_json,true);          //转换成数组

                        $data = [
                            'nickname'=>$user_data['nickname'],
                            'sex'=>$user_data['sex'],
                            'country'=>$user_data['country'],
                            'headimgurl'=>$user_data['headimgurl'],
                            'add_time'=>$user_data['subscribe_time'],
                            'openid'=>$user_data['openid'],
                        ];

                        UserModel::insertGetId($data);  //添加用户    
                    }
                    
                    
                    $resule = $this->xiaoxi($obj,$content);          //调用回复文本
                   
                    return $resule;     //关注成功  返回值
                }
                //自定义 菜单回复
                if($obj->Event=='CLICK'){
                    switch($obj->EventKey){
                        case'V1001_TODAY_MUSIC';
                            $count_str = $this->weather();          //天气 返回参数
                            $weather = $this->xiaoxi($obj,$count_str);           //xml  返回微信
                            echo $weather;
                        break;
                        
                    }
                }
                // if($obj->Event=='CLICK'){
                //     if($obj->Event=='SING_IN'){
                //         $key = 'USER_SIGN_'.date('y-m-d',time());
                //         $content = "签到成功";
                //         $user_sign_in = Redis::zrange($key,0,-1);
                //         if(in_array((string)$ToUserName,$user_sign_in)){
                //             $countent = '已经签了，明天再来呗';
                //         }else{
                //             Redis::zadd($key,time(),(string)$ToUserName);
                //         }
                //         $result = $this->xiaoxi($obj,$content);
                //     }
                // }
                
            }else if($obj->MsgType=='text'){
                //信息 回复
                switch($obj->content){
                    case'天气:';
                        $count_str = $this->weather();          //天气 返回参数
                        $weather = $this->xiaoxi($obj,$count_str);           //xml  返回微信
                        return $weather;
                    break; 
                    case'你好';
                        $content = '您好系统维护中，请稍后再试';
                        $weather = $this->xiaoxi($obj,$content);           //xml  返回微信
                        echo $weather;
                    break;
                    case'时间';
                        $time = date('Y-m-d H:i:s',time());
                        $weather = $this->xiaoxi($obj,$time);           //xml  返回微信
                        echo $weather;
                    break; 
                }
            }
        }
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

    //获取access_token
    public function token(){
        $key = 'wx:access_token';
        if(empty(Redis::get($key))){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');
            
            // echo $url;die;
            $ken = file_get_contents($url);
            $data = json_decode($ken,true);
            // dd($data);die;
            Redis::set($key,$data['access_token']);
            
            Redis::expire($key,3600);
        }
        return Redis::get($key);
        
    }
    
    
    

    //回复关注消息
    public function xiaoxi($obj,$content){
        $ToUserName = $obj->FromUserName;
        $FromUserName = $obj->ToUserName;
        $xml="<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[%s]]></MsgType>
                <Content><![CDATA[%s]]></Content>
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
            "button"=>[
                [
                "type"=>"click",
                "name"=>"天气",
                "key"=>"V1001_TODAY_MUSIC"
            ],[
                "name"=>"菜单",
                "sub_button"=>[
                    [	
                        "type"=>"view",
                        "name"=>"搜索",
                        "url"=>"http://www.baidu.com/"
                    ],
                    [
                        "type"=>"click",
                        "name"=>"赞一下我们",
                        "key"=>"V1001_GOOD"
                    ],
                    // [
                    //     "type"=>"click",
                    //     "name"=>"签到",
                    //     "key"=>"SING_IN"
                    // ]
            
                ]
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
        echo __LINE__;die;
    }
    
}
