<?php

namespace app\admin\controller;

use think\Controller;

class DeviceParts extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->addBread('台账配置');
        $this->addBread('设备零部件管理');
    }

    /**
     * 设备零部件管理列表
     * @return \think\response\View
     */
    public function index()
    {
        $this->checkPowerWeb('device_parts_config_view',$this->admin['ap_codes']);//设备零部件管理判断
        //var_dump($pageSize);
        $keyword = input('keyword');

        $where = array();
        $pageParam = array();
        if ($keyword) {
            $where['name'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }
        $device_id=input("device_id");
        if($device_id){
            $where['device_id']=$device_id;
            $pageParam['device_id']=$device_id;
            $this->assign('device_id',$device_id);
        }
        $orderby = 'id desc';
        $list = $this->simpleGetList('device_parts', $where, $orderby, $pageParam);
        //查找设备名称
        $deviceDb=db('device');
        foreach ($list['list'] as $key=>$value){
            $deviceModel=$deviceDb->where(['device_id'=>$value['device_id']])->find();
            $list['list'][$key]['device_name']=$deviceModel['name'];
        }
        $this->assign('list',$list['list']);
        $this->getInputType();
        \LogPageHelper::record('查看设备零部件管理列表页','info',$this->logOption);
        return view();
    }

    /**
     * 添加设备零部件管理
     */
    public function add()
    {
        $this->checkPowerWeb('device_parts_config_add',$this->admin['ap_codes']);//设备零部件管理判断
        $device_id=input("device_id");
        if($device_id){
            $where['device_id']=$device_id;
            $pageParam['device_id']=$device_id;
            $this->assign('device_id',$device_id);
        }
        $this->getInputType();
        return view();
    }

    /**
     * 编辑设备零部件管理
     */
    public function edit()
    {
        $this->checkPowerWeb('device_parts_config_edit',$this->admin['ap_codes']);//设备零部件管理判断

        $id = input('id');
        if (!$id) {
            $this->error('缺少id');
        }
        $model = db('device_parts')->where(['id' => $id])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $this->assign('isEdit',1);
        $this->assign('model', $model);
        $this->getInputType();
        return view();
    }


    /**
     * 编辑
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doEdit()
    {
        $this->checkPowerWeb('device_parts_config_edit',$this->admin['ap_codes']);//设备零部件管理判断
        $data = $_POST;
        $data['valid']=input('valid',0);

        $res = db('device_parts')->data($data)->update();
        if ($res === false) {
            \LogPageHelper::record('修改设备零部件管理:'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改设备零部件管理：'.$data['column_name'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 增加
     */
    public function doAdd()
    {
        $this->checkPowerWeb('device_parts_config_add',$this->admin['ap_codes']);//设备零部件管理判断
        $data = $_POST;
        $data['valid']=input('valid',0);

        $res = db('device_parts')->data($data)->insert();
        if ($res === false) {
            \LogPageHelper::record('添加设备零部件管理：'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加设备零部件管理：'.$data['column_name'].'','info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doDel(){
        $this->checkPowerWeb('device_parts_config_del',$this->admin['ap_codes']);//设备零部件管理判断
        $ids=input('ids');
        if(!$ids){
            $this->error('参数不正确');
        }
        $where['id']=['in',$ids];
        $res=db('device_parts')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除设备零部件管理：'.$ids,'info',$this->logOption);
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
        $where['index_code'] = $index_code;
//        $where['table_name']='device_parts';
        $isExist = db('device_parts')->where($where)->count();
        if ($isExist) {
            $res = [
                'status' => 'n',
                "info" => '配置代码已经存在'
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

    /**
     * 获取配置
     */
    private function getInputType(){
        $dicM=new \app\common\model\Dictionary();
        $typdList=$dicM->getByIndexCode('table_config_type');
        $this->assign('typeList',$typdList);
        //找设备列表
        $devicesList=db('device')->order("name asc")->select();
        $this->assign('devicesList',$devicesList);
    }


}
