<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Model\GoodsModel;
use GuzzleHttp\Client;
use App\Http\Controllers\Controller;

Class ApiController extends Controller{

    // public function __construct()
    // {
    //     app('debugbar')->disable();
    // }
    // public function userInfo(){
    //     echo __METHOD__;
    // }
    public function test(){
        // print_r($_GET);die;
        $goods_info = [
           'goods_id' => 12345,
           'goods_name' => "IPHONE",
           'price'  =>12.13
       ];

       echo json_encode($goods_info);
    }

    public function goodslist(Request $request){
        // $goods = GoodsModel::select('goods_id','goods_name','shop_price','goods_img')->limit(10)->get()->toArray();
        $page_size = $request->get('ps');
        $goods  = GoodsModel::select('goods_id','goods_name','shop_price','goods_img')->paginate($page_size);
        // print_r($goods);die;
        $response = [
            'error' => 0,
            'msg' =>'ok',
            'data' => [
                'list' =>$goods->items()
            ]

        ];
        return $response;
    }
    public function shop_page(Request $request){
        $goods_id= $request->get('goods_id');
        // dd($goods_id);
        $where = [
            ['goods_id','=', $goods_id]
        ];
        $page = GoodsModel::where($where)->first()->toArray();
        // echo $page;
        return $page;
      }
    

}

?>