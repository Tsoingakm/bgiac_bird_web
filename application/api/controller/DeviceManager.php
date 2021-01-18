<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-30
 * Time: 10:19
 */

namespace app\api\controller;


class DeviceManager extends Common
{
    public function selectList(){
        $list = \app\api\model\Device::select();
        if(!$list){
            $this->return_msg( true, "获取成功", []);
        }
        foreach ($list as $k=>$v){
            $list[$k]['value'] = $list[$k]['name'];
        }
        $this->return_msg( true, "获取成功", $list);
    }

    public function insert(){
        $params = input('');
        $params['addtime'] = strtotime(date('Y-m-d H:i:s'));
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $deviceModel = new \app\api\model\Device($params);
        $result = $deviceModel->allowField(true)->save();
        if(!$result){
            $this->return_msg( false, '添加失败');
        }
        $this->return_msg( true, '添加成功');
    }

    public function findById(){
        $id = input('id');
        if(!$id){
            $this->return_msg( false, '参数错误');
        }
        $device = \app\api\model\Device::find($id);
        if($device){
            $this->return_msg( true, '获取成功', $device);
        }else{
            $this->return_msg( false, '获取失败');
        }
    }

    public function updateById(){
        $params = input('');
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $deviceModel = new \app\api\model\Device();
        $result = $deviceModel->allowField(true)->save($params, ['id'=>$params['id']]);
        if(!$result){
            $this->return_msg( false, '修改失败');
        }
        $this->return_msg( true, '修改成功');
    }

    public function deleteById(){
        $id = input('id');
        if(!$id){
            $this->return_msg( false, '参数错误');
        }
        $result = \app\api\model\Device::where( 'id', 'in', $id ) -> delete();
        if($result <= 0){
            $this->return_msg( false, "删除失败");
        }
        $this->return_msg( true, "删除成功");
    }
}
