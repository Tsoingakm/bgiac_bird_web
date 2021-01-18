<?php

namespace app\admin\controller;

use think\Controller;

class DeviceClass extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->addBread('台账配置');
        $this->addBread('设备管理');
    }

    /**
     * 设备列表
     * @return \think\response\View
     */
    public function index()
    {
        $this->checkPowerWeb('device_class_view',$this->admin['ap_codes']);//设备判断
        //var_dump($pageSize);
        $keyword = input('keyword');
        $where = array();
        $pageParam = array();
        if ($keyword) {
            $where['column_name|column_code|index_code'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }
        $orderby = 'class_id desc';
        $list = $this->simpleGetList('device_class', $where, $orderby, $pageParam);
        $deviceCodeM = db('device_code');
        foreach ($list['list'] as $key => $value) {
            $sum = $deviceCodeM->where(['class_id' => $value['class_id']])->count();
            $list['list'][$key]['num'] = $sum;
        }
        $this->assign('list', $list['list']);

        \LogPageHelper::record('查看设备列表页','info',$this->logOption);
        return view();
    }

    /**
     * 添加设备
     */
    public function add()
    {
        $this->checkPowerWeb('device_class_add',$this->admin['ap_codes']);//设备判断

        return view();
    }

    /**
     * 编辑设备
     */
    public function edit()
    {
        $this->checkPowerWeb('device_class_edit',$this->admin['ap_codes']);//设备判断

        $class_id = input('class_id');
        if (!$class_id) {
            $this->error('缺少class_id');
        }
        $model = db('device_class')->where(['class_id' => $class_id])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $this->assign('isEdit',1);
        $this->assign('model', $model);

        return view();
    }


    /**
     * 编辑
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doEdit()
    {
        $this->checkPowerWeb('device_class_edit',$this->admin['ap_codes']);//设备判断
        $data = $_POST;
        $data['class_valid']=input('class_valid',0);
        $res = db('device_class')->data($data)->update();
        if ($res === false) {
            \LogPageHelper::record('修改设备:'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改设备：'.$data['column_name'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 增加
     */
    public function doAdd()
    {
        $this->checkPowerWeb('device_class_add',$this->admin['ap_codes']);//设备判断
        $data = $_POST;
        $data['class_valid']=input('class_valid',0);
        $res = db('device_class')->data($data)->insert();
        if ($res === false) {
            \LogPageHelper::record('添加设备：'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加设备：'.$data['column_name'].'','info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doDel(){
        $this->checkPowerWeb('device_class_del',$this->admin['ap_codes']);//设备判断
        $class_ids=input('ids');
        if(!$class_ids){
            $this->error('参数不正确');
        }
        $where['class_id']=['in',$class_ids];
        $res=db('device_class')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除失败：'.$class_ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除设备：'.$class_ids,'info',$this->logOption);
            $this->success('删除成功');
        }
    }



    /**
     * 获取配置
     */
    private function getInputType(){
        $dicM=new \app\common\model\Dictionary();
        $typdList=$dicM->getByIndexCode('table_config_type');
        $this->assign('typeList',$typdList);
    }


}
