<?php

namespace app\admin\controller;

use think\Controller;

class DeviceCode extends Base
{

    private $deviceModel;
    public function __construct()
    {
        parent::__construct();
        $this->checkPowerWeb('device_class_code',$this->admin['ap_codes']);
        $this->addBread('系统管理');
        $this->addBread('设备管理');
        $device_id=input('device_id');
        if(!$device_id){
            $this->error('参数错误');
        }

        $this->deviceModel=db('device')->where(['device_id'=>$device_id])->find();
        if(!$this->deviceModel){
            $this->error("未找到设备");
        }
        $this->assign('deviceModel',$this->deviceModel);
        $this->addBread($this->deviceModel['name']);
        $this->addBread('编号管理');
    }

    /**
     * 设备编号列表
     * @return \think\response\View
     */
    public function index()
    {

        //var_dump($pageSize);
        $keyword = input('keyword');
        $where = array();
        $pageParam = array();
        if ($keyword) {
            $where['code'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }
//        $where['table_name']=$configModel['table_name'];

        $where['device_id']=$this->deviceModel['device_id'];
        $pageParam['device_id'] = $this->deviceModel['device_id'];

        $orderby = 'code asc, addtime desc';
        $list = $this->simpleGetList('device_code', $where, $orderby, $pageParam);

        $this->assign('list',$list['list']);

        \LogPageHelper::record('查看设备编号'.$this->configModel['key'].'列表页','info',$this->logOption);
        return view();
    }

    /**
     * 添加设备编号
     */
    public function add()
    {
        $this->setDefaultValue();
        return view();
    }


    /**
     * 编辑设备
     */
    public function edit()
    {


        $code= input('code');
        if (!$code) {
            $this->error('缺少code');
        }
        $model = db('device_code')->where(['code' => $code])->find();
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

        $data = $_POST;
        $data['valid']=input('valid',0);
        $res = db('device_code')->data($data)->update();
        if ($res === false) {
            \LogPageHelper::record('修改设备编号:'.$data['code'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改设备编号：'.$data['code'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }


    /**
     * 增加
     */
    public function doAdd()
    {

        $data = $_POST;

        $data['valid']=input('valid',0);
        $res = db('device_code')->data($data)->insert();
        if ($res === false) {
            \LogPageHelper::record('添加设备编号：'.$data['code'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加设备编号：'.$data['code'].'','info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doDel(){
        $ids=input('ids');

        if(!$ids){
            $this->error('参数不正确');
        }
        $where['code']=['in',explode(",", $ids)];
        $res=db('device_code')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除设备编号：'.$ids,'info',$this->logOption);
            $this->success('删除成功');
        }
    }

    /**
     * 判断code是否存在
     * @return \think\response\View
     */
    public function checkCode()
    {

        $index_code = input('param');
        $where['code'] = $index_code;

        $isExist = db('device_code')->where($where)->count();
        if ($isExist) {
            $res = [
                'status' => 'n',
                "info" => '编号已经存在'
            ];
            return json($res);
        } else {
            $res = [
                'status' => 'y',
                "info" => '编号可以使用'
            ];
            return json($res);
        }
    }



}
