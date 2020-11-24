<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Model\LoginModel;

use GuzzleHttp\Client;
use App\Http\Controllers\Controller;

Class XcxController extends Controller{

    
    public function login(Request $request)
    {
        // print_r($_GET);
        $code = $request->get('code');
        // echo $code;
        // // printf($code);die;
        //使用code
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.env('WX_XCX_APPID').'&secret='.env('WX_XCX_SECRET').'&js_code='.$code.'&grant_type=authorization_code';
        // print_r($url);die;
        
        $data = json_decode(file_get_contents($url),true);
        // echo '<pre>';print_r($data);echo'</pre>';die;


        //自定义登录状态
        if(isset($data['errcode']))//有错误
        {
            //TODO 错误处理
            $response = [
                'error' =>  50001,
                'msg'   => '登录失败',
                
            ];
        }else{              //成功
            $token = sha1($data['openid'] . $data['session_key'].mt_rand(0,999999));

            //保持token
            $redis_key = 'xcx_toekn:'.$token;
            Redis::set($redis_key,time());
            //设置过期时间
            Redis::expire($redis_key,7200);

            $response = [
                'error' =>  0,
                'msg'   => 'ok',
                'data'  => [
                    'token' =>$token
                ]
            ];
        }
        
        LoginModel::insert(['openid'=>$data['openid']]);
        // return $response;
        
    }

    
    

}

?>