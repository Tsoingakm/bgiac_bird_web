<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-12-01
 * Time: 14:58
 */

namespace app\api\controller;


use app\api\model\AdminRole;
use think\Request;

class Schedule extends Common
{

    protected $schedule;

    public function __construct()
    {
        parent::__construct();
        $this->schedule = new \app\api\model\Schedule();
    }

    public function selectList(){
        $aid = input('aid');
        if(!$aid){
            $this->return_msg( false, "参数错误");
        }
        $where = [];
        $admin = \app\api\model\Admin::where(['aid'=>$aid])->find();
        $role = AdminRole::where(['ar_id'=>$admin['ar_id']])->find();
        $hasPower = $this->checkReadPower($role['ap_codes']);
        if(!$hasPower){
            $where['aid'] = $aid;
        }
        $list = \app\api\model\Schedule::where($where)->order('index desc, deal_time desc')->select();
        if(!$list){
            $this->return_msg( true, "获取成功", []);
        }
        $this->return_msg( true, "获取成功", $list);
    }

    public function insert(){
        $params = input('');
        //获取当前用户排序最后的数据
        $lastData = \app\api\model\Schedule::where(['aid'=>$params['aid']])
            ->order('index DESC')
            ->find();
        if($lastData){
            $newIndex = intval($lastData->index) + 1;
        }else{
            $newIndex = 1;
        }

        $params['index'] = $newIndex;

        $result = $this->schedule->insert_data($params);
        if(!$result){
            $this->return_msg( false, "添加失败");
        }
        $this->return_msg( true, "添加成功");
    }

    public function findById(){
        $id = input('id');
        if(!$id){
            $this->return_msg( false, "参数错误");
        }
        $data = $this->schedule->find_by_id($id);
        if(!$data){
            $this->return_msg( false, "获取失败");
        }
        $this->return_msg( true, "获取成功", $data);
    }

    public function updateById(){
        $params = input('');
        $result = $this->schedule->update_by_id($params['id'], $params);
        if(!$result){
            $this->return_msg( false, "修改失败");
        }
        $this->return_msg( true, "修改成功");
    }

    public function deleteById(){
        $id = input('id');
        if(!$id){
            $this->return_msg( false, "参数错误");
        }
        $result = $this->schedule->delete_by_id($id);
        if(!$result){
            $this->return_msg( false, "删除失败");
        }
        $this->return_msg( true, "删除成功");
    }
}
