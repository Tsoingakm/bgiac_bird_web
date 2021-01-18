<?php

namespace app\admin\controller;

use think\Controller;

class Power extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->addBread('账号管理');
        $this->addBread('权限管理');
    }

    /**
     * 权限列表
     * @return \think\response\View
     */
    public function index()
    {
        $this->checkPowerWeb('power_view',$this->admin['ap_codes']);//权限判断
        //var_dump($pageSize);
        $keyword = input('keyword');
        $where = array();
        $pageParam = array();
        if ($keyword) {
            $where['ap_name'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }
        $orderby = 'ap_id desc';
        $list = $this->simpleGetList('admin_power', $where, $orderby, $pageParam);
        $adminPowerDB=db('admin_power');
        foreach ($list['list'] as $key => $value) {
            if($value['ap_pid']>0){
                $pModel=$adminPowerDB->field('ap_name')->where(['ap_id'=>$value['ap_pid']])->find();
                $list['list'][$key]['ap_pname']=$pModel['ap_name'];
            }

        }
        $this->assign('list',$list['list']);
        \LogPageHelper::record('查看权限配置列表页','info',$this->logOption);
        return view();
    }

    /**
     * 添加权限
     */
    public function add()
    {
        $this->checkPowerWeb('power_add',$this->admin['ap_codes']);//权限判断
        //上级权限
        $powerModel=new \app\common\model\Power();
        $adPid = $powerModel->getPids();
        $this->assign('apPid', $adPid);

        $this->setDefaultValue();

        return view();
    }

    /**
     * 编辑权限
     */
    public function edit()
    {
        $this->checkPowerWeb('power_edit',$this->admin['ap_codes']);//权限判断
        $ap_id = input('ap_id');
        if (!$ap_id) {
            $this->error('缺少ap_id');
        }
        $model = db('admin_power')->where(['ap_id' => $ap_id])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $powerModel=new \app\common\model\Power();
        $adPid = $powerModel->getPids();
        $this->assign('isEdit',1);
        $this->assign('apPid', $adPid);
        $this->assign('model', $model);
        return view();
    }


    /**
     * 判断code是否存在
     * @return \think\response\View
     */
    public function checkCode()
    {
        $ap_code = input('param');
        $where['ap_code'] = $ap_code;
        $isExist = db('admin_power')->where($where)->count();
        if ($isExist) {
            $res = [
                'status' => 'n',
                "info" => '权限代码已经存在'
            ];
            return json($res);
        } else {
            $res = [
                'status' => 'y',
                "info" => '可以使用'
            ];
            return json($res);
        }
    }

    public function doEdit()
    {
        $this->checkPowerWeb('power_edit',$this->admin['ap_codes']);//权限判断
        $data = $_POST;
        $data['valid'] = input('valid', 0);
        $res = db('admin_power')->data($data)->update();
        if ($res === false) {
            \LogPageHelper::record('修改权限配置:'.$data['ap_code'].' 失败','error',$this->logOption);
            $this->error('数据修改失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改权限配置:'.$data['ap_code'].' 成功','info',$this->logOption);
            $this->success('操作成功');
        }
    }


    public function doAdd()
    {
        $this->checkPowerWeb('power_add',$this->admin['ap_codes']);//权限判断
        $data = $_POST;
        $data['valid'] = input('valid', 0);
        $res = db('admin_power')->data($data)->insert();
        if ($res === false) {
            \LogPageHelper::record('添加权限配置:'.$data['ap_code'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加权限配置:'.$data['ap_code'].' 成功','info',$this->logOption);
            $this->success('操作成功');
        }
    }

    public function doDel(){
        $this->checkPowerWeb('power_del',$this->admin['ap_codes']);//权限判断
        $ids=input('ids');
        if(!$ids){
            $this->error('参数不正确');
        }
        $where['ap_id']=['in',$ids];
        $res=db('admin_power')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除权限配置失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除权限配置:'.$ids.' 成功','info',$this->logOption);
            $this->success('删除成功');
        }
    }

}
