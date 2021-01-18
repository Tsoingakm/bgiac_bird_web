<?php

namespace app\admin\controller;

use think\Controller;

class Role extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->addBread('账号管理');
        $this->addBread('角色管理');
    }

    /**
     * 权限列表
     * @return \think\response\View
     */
    public function index()
    {
        $this->checkPowerWeb('role_view',$this->admin['ap_codes']);//权限判断
        //var_dump($pageSize);
        $keyword = input('keyword');
        $where = array();
        $pageParam = array();
        if ($keyword) {
            $where['ar_name'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }
        $orderby = 'ar_id desc';
        $list = $this->simpleGetList('admin_role', $where, $orderby, $pageParam);

        $this->assign('list',$list['list']);
        \LogPageHelper::record('查看角色列表页','info',$this->logOption);
        return view();
    }

    /**
     * 添加权限
     */
    public function add()
    {
        $this->checkPowerWeb('role_add',$this->admin['ap_codes']);//权限判断
        $this->getPowers();
        return view();
    }

    /**
     * 编辑权限
     */
    public function edit()
    {
        $this->checkPowerWeb('role_edit',$this->admin['ap_codes']);//权限判断
        $ar_id = input('ar_id');
        if (!$ar_id) {
            $this->error('缺少ar_id');
        }
        $model = db('admin_role')->where(['ar_id' => $ar_id])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $this->assign('isEdit',1);
        $this->assign('model', $model);
        $this->getPowers();
        return view();
    }


    /**
     * 编辑
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doEdit()
    {
        $this->checkPowerWeb('role_edit',$this->admin['ap_codes']);//权限判断
        $data = $_POST;
        $data['ap_codes'] =implode(",",$data['ap_codes']);
        $res = db('admin_role')->data($data)->update();
        if ($res === false) {
            \LogPageHelper::record('修改角色:'.$data['ar_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改角色：'.$data['ar_name'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 增加
     */
    public function doAdd()
    {
        $this->checkPowerWeb('role_add',$this->admin['ap_codes']);//权限判断
        $data = $_POST;
        $data['ap_codes'] =implode(",",$data['ap_codes']);
        $res = db('admin_role')->data($data)->insert();
        if ($res === false) {
            \LogPageHelper::record('添加角色：'.$data['ar_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加角色：'.$data['ar_name'].'','info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doDel(){
        $this->checkPowerWeb('role_del',$this->admin['ap_codes']);//权限判断
        $ids=input('ids');
        if(!$ids){
            $this->error('参数不正确');
        }
        $where['ar_id']=['in',$ids];
        $res=db('admin_role')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除角色：'.$ids,'info',$this->logOption);
            $this->success('删除成功');
        }
    }

    /**
     * 获取权限数据
     */
    private function getPowers(){
//        $adminPowerDb=db('admin_power');
//        $fPower=$adminPowerDb->where(['valid' => 1, 'ap_pid' => 0])->order('sort asc,ap_name asc,ap_code asc')->select();
//        foreach($fPower as $key=>$value){
//            $fPower[$key]['list']=$adminPowerDb->where(['valid' => 1, 'ap_pid' => $value['ap_id']])->order('sort asc,ap_name asc,ap_code asc')->select();
//        }
        $powerModel=new \app\common\model\Power();
        $fPower=$powerModel->getPowerListArr();
//        print_r($fPower);

//        print_r($fPower);
        $this->assign('powerList',$fPower);
    }

}
