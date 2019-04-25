<?php
/**
 * Created by PhpStorm.
 * User: Joql
 * Date: 2019/4/9
 * Time: 13:53
 */
namespace Joql;


use think\Db;
use think\Validate;

class Api extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->getWebConf();
    }

    /**
     * use for:获取系统配置
     * auth: Joql
     * @return array|bool
     * date:2019-04-23 21:30
     */
    public function getWebConf(){

        $this->templage->assign([
            'web_config' => $web_config = Db::name('system')->where('id','1')->find()
        ]);
    }

    public function index_index(){
        $this->templage->fetch('index');
    }
    public function checkKey(){
        $data = $_POST;
        if(empty($data)){
            json(['code' => -1, 'msg' => '非法提交']);
        }
        $validate= Validate::make([
            'key'=>'require',
        ]);
        if(!$validate->check($data)){
            json(['code' => -2, 'msg' => '参数异常']);
        }
        $key_info =  Db::name('card')->where('state','in', '1,2')
            ->where('key', $data['key'])->find();
        if(empty($key_info)){
            json(['code' => -3, 'msg' => '卡密不存在']);//卡密不存在
        }
        if($key_info['state'] == 1){
            //未使用
            $result = Db::name('card')->where('id', $key_info['id'])
                ->update([
                    'state' => 2,
                    'utime' => time()
                ]);
            if($result){
                $key_info['state'] = 2;
                $key_info['utime'] = time();
                session('card', $key_info);
                json(['code' => 1, 'msg' => 'success']);
            }
            json(['code' => -4, 'msg' => '使用失败']);
        }
        //使用中
        if($key_info['time'] - (time()-$key_info['utime']) > 0 ){
            session('card', $key_info);
            json(['code' => 1, 'msg' => 'success']);
        }
        json(['code' => -5, 'msg' => '卡密不存在']);
    }

    public function lists_index(){
        $key_info = session('card');
        if(empty($key_info)){
            header("Location: http://".$_SERVER['SERVER_NAME'].'/index.php');
            exit();
        }
        //查询订单
        $orders = Db::name('order')
            ->where('state', '<>', -1)
            ->where('key', $key_info['key'])->select();
        foreach ($orders as &$v){
            //检测更新订单状态
            try{
                $order_info = json_decode(httpRequest('http://120.79.132.39:7101/userapi/orders/get',
                    'post',
                    json_encode([
                        'token' => 'testtoken',
                        'type' => 3,
                        'id' => $v['out_no']
                    ]),
                    [
                        "content-type: application/json",
                    ]
                ), true);
                if(empty($order_info['order'])){
                    continue;
                }
                Db::name('order')->where('id', $v['id'])->update([
                    'start_time' => strtotime($order_info['order']['create_time']),
                    'end_time' => strtotime($order_info['order']['end_time']),
                    'refresh_time' => strtotime($order_info['order']['update_time']),
                    'info' => $order_info['order']['info'],
                    'state' => orderStatus($order_info['order']['status'])
                ]);
                $v['state'] = orderStatus($order_info['order']['status']);
                if($order_info['order']['info'] == '无法登录'){
                    $order_info['order']['helloid'] = '获取失败';
                }
                $v['detail'] = $order_info['order'];
            }catch (\Exception $e){
                continue;
            }
        }
        $this->templage->assign([
            'key' => $key_info,
            'last_time' => parseSecond($key_info['time']),
            'order' => $orders,
        ]);
        $this->templage->fetch('lists');
    }

    public function logoOut(){
        session('card', null);
        json(['code' => 1, 'msg' => '添加成功']);
    }

    public function save(){
        $key_info = session('card');
        if(empty($key_info)){
            json(['code' => -3, 'msg' => '未登录']);
        }
        $data = $_POST;
        if(empty($data)){
            json(['code' => -1, 'msg' => '非法提交']);
        }
        if($key_info['time'] - ( time() - $key_info['utime']) <= 0){
            json(['code' => -6, 'msg' => '已到期']);
        }
        $validate= Validate::make([
            'account'=>'require',
            'pwd'=>'require',
            'login_type'=>'in:1,2',
            'answer-1'=>'require',
            'answer-2'=>'require',
            'answer-3'=>'require',
            'id'=>'number',
        ]);
        if(!$validate->check($data)){
            json(['code' => -2, 'msg' => $validate->getError()]);
        }

        if(empty($data['id'])){
            //添加
            $order_has_count = Db::name('order')
                ->where('key',$key_info['key'])
                ->where('state', '<>', -1)
                ->count();
            if($order_has_count >= $key_info['num']){
                json(['code' => -4, 'msg' => '添加数量已达上限']);
            }
            $insert = [
                'key' => $key_info['key'],
                'account' => $data['account'],
                'pwd' => $data['pwd'],
                'start_time' => time(),
                'login_type' => $data['login_type'],
                'answer_1' => $data['answer-1'],
                'answer_2' => $data['answer-2'],
                'answer_3' => $data['answer-3'],
            ];
            $result = Db::name('order')->insertGetId($insert);
            if(empty($result)){
                json(['code' => -5, 'msg' => '添加失败']);
            }
            //添加成功，提交订单
            try{
                $order_commit = [
                    'token' => 'testtoken',
                    'type' => cardPrice($key_info['time'])['type'],
                    'order' => [
                        'user' => $data['account'],
                        'type' => $data['login_type'] -1 ,
                        'pass' => $data['pwd'],
                        'safe_pass' => $data['answer-1'].'|'.$data['answer-2'].'|'.$data['answer-3'],
                    ]
                ];
                $commit_result = json_decode(httpRequest('http://120.79.132.39:7101/userapi/orders/add',
                    'post',
                    json_encode($order_commit),
                    [
                        "content-type: application/json",
                    ]
                ), true);
                if(empty($commit_result['id'])){
                    json(['code' => -7, 'msg' => '订单提交失败']);
                }
                $order_info = json_decode(httpRequest('http://120.79.132.39:7101/userapi/orders/get',
                    'post',
                    json_encode([
                        'token' => 'testtoken',
                        'type' => 3,
                        'id' => $commit_result['id']
                    ]),
                    [
                        "content-type: application/json",
                    ]
                ), true);
                if(empty($order_info['order'])){
                    json(['code' => -8, 'msg' => '订单获取失败']);
                }
                Db::name('order')->where('id', $result)->update([
                    'out_no' => $commit_result['id'],
                    'start_time' => strtotime($order_info['order']['create_time']),
                    'end_time' => strtotime($order_info['order']['end_time']),
                    'refresh_time' => strtotime($order_info['order']['update_time']),
                    'info' => $order_info['order']['info'],
                    'state' => orderStatus($order_info['order']['status'])
                ]);
            }catch (\Exception $e){
                json(['code' => -6, 'msg' => $e->getMessage()]);
            }
            json(['code' => 1, 'msg' => '添加成功']);
        }else{
            //修改
            $info = Db::name('order')->where('id', $data['id'])
                ->where('key', $key_info['key'])
                ->find();
            if(empty($info)){
                json(['code' => -8, 'msg' => '订单不存在']);
            }
            $update = [
                'account' => $data['account'],
                'pwd' => $data['pwd'],
                'login_type' => $data['login_type'],
                'answer_1' => $data['answer-1'],
                'answer_2' => $data['answer-2'],
                'answer_3' => $data['answer-3'],
            ];
            $result = Db::name('order')->where('id', $data['id'])
                ->where('key', $key_info['key'])
                ->update($update);
            if(empty($result)){
                json(['code' => -6, 'msg' => '保存失败']);
            }

            try{
                $order_info = json_decode(httpRequest('http://120.79.132.39:7101/userapi/orders/modify',
                    'post',
                    json_encode([
                        'token' => 'testtoken',
                        'type' => 3,
                        'id' => $info['out_no'],
                        'order' => [
                            'user' => $data['account'],
                            'type' => $data['login_type'] - 1,
                            'pass' => $data['pwd'],
                            'safe_pass' => $data['answer_1'].'|'.$data['answer_2'].'|'.$data['answer_3'],
                            'status' => $info['status'] == 5 ? 3 : 0,
                        ]
                    ]),
                    [
                        "content-type: application/json",
                    ]
                ), true);
            }catch (\Exception $exception){

            }
            json(['code' => 1, 'msg' => '保存成功']);
        }
    }

    public function orderInfo(){
        $key_info = session('card');
        if(empty($key_info)){
            json(['code' => -3, 'msg' => '未登录']);
        }
        $data = $_POST;
        if(empty($data)){
            json(['code' => -1, 'msg' => '非法提交']);
        }
        if($key_info['time'] - ( time() - $key_info['utime']) <= 0){
            json(['code' => -6, 'msg' => '已到期']);
        }
        $validate= Validate::make([
            'id'=>'>:0',
        ]);
        if(!$validate->check($data)){
            json(['code' => -2, 'msg' => $validate->getError()]);
        }

        $info = Db::name('order')->where('id', $data['id'])->where('key', $key_info['key'])
            ->find();
        if(empty($info)){
            json(['code' => -4, 'msg' => '未查到数据']);
        }
        json(['code' => 1, 'msg' => 'success', 'data' => $info]);
    }

    public function orderStatus(){
        $key_info = session('card');
        if(empty($key_info)){
            json(['code' => -3, 'msg' => '未登录']);
        }
        $data = $_POST;
        if(empty($data)){
            json(['code' => -1, 'msg' => '非法提交']);
        }
        if($key_info['time'] - ( time() - $key_info['utime']) <= 0){
            json(['code' => -6, 'msg' => '已到期']);
        }

        $validate= Validate::make([
            'id'=>'>:0',
            's'=>'in:1,5',
        ]);
        if(!$validate->check($data)){
            json(['code' => -2, 'msg' => $validate->getError()]);
        }
        $info = Db::name('order')->where('id', $data['id'])
            ->where('key', $key_info['key'])->find();
        if(empty($info)){
            json(['code' => -4, 'msg' => '订单不存在']);
        }

        //订单暂停
        try{
            $order_info = json_decode(httpRequest('http://120.79.132.39:7101/userapi/orders/modify',
                'post',
                json_encode([
                    'token' => 'testtoken',
                    'type' => 3,
                    'id' => $info['out_no'],
                    'order' => [
                        'user' => $info['account'],
                        'type' => $info['login_type'] - 1,
                        'pass' => $info['pwd'],
                        'safe_pass' => $info['answer_1'].'|'.$info['answer_2'].'|'.$info['answer_3'],
                        'status' => $data['s'] == 5 ? 3 : 0,
                    ]
                ]),
                [
                    "content-type: application/json",
                ]
            ), true);
        }catch (\Exception $e){
            json(['code' => -5, 'msg' => $e->getMessage()]);
        }
        Db::name('order')->where('id', $data['id'])
            ->where('key', $key_info['key'])->update([
                'state' => $data['s']
            ]);
        json(['code' =>1, 'msg' => 'success']);
    }

    public function updateStatus(){
        $key_info = session('card');
        if(empty($key_info)){
            json(['code' => -3, 'msg' => '未登录']);
        }
//        $data = $_POST;
//        if(empty($data)){
//            json(['code' => -1, 'msg' => '非法提交']);
//        }
        if($key_info['time'] - ( time() - $key_info['utime']) <= 0){
            json(['code' => -6, 'msg' => '已到期']);
        }

        $order = Db::name('order')
            ->where('key',$key_info['key'])
            ->where('state', '<>', -1)
            ->field('id, out_no')->select();
        foreach ($order as $v){
            $order_info = json_decode(httpRequest('http://120.79.132.39:7101/userapi/orders/get',
                'post',
                json_encode([
                    'token' => 'testtoken',
                    'type' => 3,
                    'id' => $v['out_no']
                ]),
                [
                    "content-type: application/json",
                ]
            ), true);
            if(empty($order_info['order'])){
                continue;
            }
            $result = Db::name('order')->where('id', $v['id'])->update([
                'end_time' => strtotime($order_info['order']['end_time']),
                'refresh_time' => strtotime($order_info['order']['update_time']),
                'info' => $order_info['order']['info'],
                'state' => orderStatus($order_info['order']['status'])
            ]);
            if(empty($result)){
                continue;
            }
            json(['code' => 1, 'msg' => 'success']);
        }
        json(['code' => -7, 'msg' => '无变化']);
    }
}