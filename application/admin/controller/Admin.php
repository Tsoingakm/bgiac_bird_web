<?php

namespace app\admin\controller;

use think\Controller;

class Admin extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->addBread('账号管理');
        $this->addBread('账号列表');
    }

    /**
     * 列表
     * @return \think\response\View
     */
    public function index()
    {
        $this->checkPowerWeb('admin_view',$this->admin['ap_codes']);//权限判断
        //var_dump($pageSize);
        $where = array();
        $pageParam = array();

        $keyword = input('keyword');
        if ($keyword) {
            $where['login_name|real_name|tel'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword', $keyword);
        }

        $ar_id = input( 'ar_id' );
        if($ar_id){
            $where['ar_id'] = $ar_id;
            $pageParam['keyword'] = $ar_id;
            $this->assign('ar_id', $ar_id);
        }

        $orderby = 'aid desc';
        $list = $this->simpleGetList('admin', $where, $orderby, $pageParam);
        $adminRoleDB = db('admin_role');
        foreach ($list['list'] as $key => $value) {
            $pModel = $adminRoleDB->field('ar_name')->where(['ar_id' => $value['ar_id']])->find();
            $list['list'][$key]['ar_name'] = $pModel['ar_name'];
        }
        $this->assign('list', $list['list']);
        \LogPageHelper::record('查看账号列表页','info',$this->logOption);
        $this->getRoleList();
        return view();
    }

    /**
     * 添加账号
     */
    public function add()
    {
        $this->checkPowerWeb('admin_add',$this->admin['ap_codes']);//权限判断
        //角色列表
        $arList = db('admin_role')->order('ar_name asc')->select();
        $this->assign('arList', $arList);

        $this->setDefaultValue();

        return view();
    }

    /**
     * 编辑账号
     */
    public function edit()
    {
        $this->checkPowerWeb('admin_edit',$this->admin['ap_codes']);//权限判断
        $aid = input('aid');
        if (!$aid) {
            $this->error('缺少aid');
        }
        $model = db('admin')->where(['aid' => $aid])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        //角色列表
        $arList = db('admin_role')->order('ar_name asc')->select();
        $this->assign('arList', $arList);
        $this->assign('isEdit', 1);
        $this->assign('model', $model);
        return view();
    }

    /**
     * 重设置密码
     */
    public function resetPwd(){
        $aid = input('aid');
        if($this->admin['aid']!=$aid){
            $this->checkPowerWeb('admin_reset_pwd',$this->admin['ap_codes']);//权限判断
        }


        if (!$aid) {
            $this->error('缺少aid');
        }
        $model = db('admin')->where(['aid' => $aid])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $this->assign('model', $model);
        return view();
    }

    /**
     * 判断登录名是否存在
     * @return \think\response\View
     */
    public function checkLoginName()
    {
        $loginName = input('param');
        $where['login_name'] = $loginName;
        $isExist = db('admin')->where($where)->count();
        if ($isExist) {
            $res = [
                'status' => 'n',
                "info" => '账号已经存在，请更换账号'
            ];
            return json($res);
        } else {
            $res = [
                'status' => 'y',
                "info" => '账号可以使用'
            ];
            return json($res);
        }
    }

    public function doEdit()
    {
        $this->checkPowerWeb('admin_edit',$this->admin['ap_codes']);//权限判断
        $data = $_POST;
        $data['valid'] = input('valid', 0);
        $adminDb=db('admin');
        $aid = input('aid');
        $model = $adminDb->where(['aid' => $aid])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $res = db('admin')->data($data)->update();
        if ($res === false) {
            $this->error('数据添加失败，请稍后重试');
            \LogPageHelper::record('修改账号，操作失败','error',$this->logOption);
        } else {
            \LogPageHelper::record('修改账号：'.$model['login_name'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 重置密码
     */
    public  function doResetPwd(){
        $data = $_POST;
        $aid=$data['aid'];
        if($this->admin['aid']!=$aid){
            $this->checkPowerWeb('admin_reset_pwd',$this->admin['ap_codes']);//权限判断
        }
        if($data['pwd']!==$data['pwd1']){
            $this->error('两次输入的密码不一致');
        }
        $data['pwd']=md5($data['pwd']);
        $adminDb=db('admin');
        unset($data['pwd1']);
        $aid = input('aid');
        if (!$aid) {
            $this->error('缺少aid');
        }
        $model = $adminDb->where(['aid' => $aid])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $res = $adminDb->data($data)->update();
        if ($res === false) {
            $this->error('数据添加失败，请稍后重试');
            \LogPageHelper::record('重置账号'.$model['login_name'].'密码，操作失败','error',$this->logOption);
        } else {
            \LogPageHelper::record('重置账号 '.$model['login_name'].' 密码','info',$this->logOption);
            $this->success('操作成功');
        }
    }


    public function doAdd()
    {
        $this->checkPowerWeb('admin_add',$this->admin['ap_codes']);//权限判断
        $data = $_POST;
        $data['valid'] = input('valid', 0);
        if($data['pwd']!==$data['pwd1']){
            $this->error('两次输入的密码不一致');
        }
        $data['pwd']=md5($data['pwd']);
        unset($data['pwd1']);
        $res = db('admin')->data($data)->insert();
        if ($res === false) {
            \LogPageHelper::record('添加账号：'.$data['login_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加账号：'.$data['login_name'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    public function doDel()
    {
        $this->checkPowerWeb('admin_del',$this->admin['ap_codes']);//权限判断
        $ids = input('ids');
        if (!$ids) {
            $this->error('参数不正确');
        }
        $where['aid'] = ['in', $ids];
        $res = db('admin')->where($where)->delete();
        if ($res === false) {
            \LogPageHelper::record('删除账号失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        } else {
            \LogPageHelper::record('删除账号：'.$ids,'info',$this->logOption);
            $this->success('删除成功');
        }
    }

    private function getRoleList(){
        $list = db('admin_role') -> field( 'ar_id, ar_name' ) -> select();
        $arr=array();
        foreach($list as $k=>$v){
            $arr[$k]=array( 'option_name'=>$v['ar_name'], 'option_value'=>$v['ar_id'] );
        }
        $this->assign( "role_list", $arr);
    }

}
