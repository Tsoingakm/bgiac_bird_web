<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-30
 * Time: 10:34
 */

namespace app\api\controller;


class DeviceCode extends Common
{
    public function selectList(){
        $deviceId = input('device_id');
        if(!$deviceId){
            $this->return_msg( false, "参数错误");
        }
        $list = \app\api\model\DeviceCode::where(['device_id'=>$deviceId])->limit(0, 5)->select();
        if(!$list){
            $this->return_msg( true, "获取成功", []);
        }
        $this->return_msg( true, "获取成功", $list);
    }

    public function insert(){
        $params = input('');
        $params['addtime'] = strtotime(date('Y-m-d H:i:s'));
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $deviceModel = new \app\api\model\DeviceCode($params);
        $result = $deviceModel->allowField(true)->save();
        if(!$result){
            $this->return_msg( false, '添加失败');
        }
        $this->return_msg( true, '添加成功');
    }

    public function findById(){
        $code = input('code');
        $deviceId = input('device_id');
        if(!$code || !$deviceId){
            $this->return_msg( false, '参数错误');
        }
        $deviceCode = \app\api\model\DeviceCode::where(['code'=>$code, 'device_id'=>$deviceId])->find();
        if($deviceCode){
            $this->return_msg( true, '获取成功', $deviceCode);
        }else{
            $this->return_msg( false, '获取失败');
        }
    }
}
