<?php

namespace app\admin\controller;

use think\Controller;

class DeviceProvider extends Base{

    public function __construct(){
        parent::__construct();
        $this->addBread('台账配置');
        $this->addBread('设备相关配置');
        $this->addBread('服务商管理');
    }

    public function index(){
        $this->checkPowerWeb('device_provider_view',$this->admin['ap_codes']);
        $where = array();
        $where['table_name']='device';
        $pageParam = array();

        $keyword = input('keyword');
        if ($keyword) {
            $where['column_name'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }

        $can_del = input( 'can_del' );
        if($can_del >= 0){
            $where['can_del'] = $can_del;
            $pageParam['can_del'] = $can_del;
            $this->assign('can_del', $can_del);
        }

        $orderby = 'sort asc,id desc';

        $list = $this->simpleGetList('table_config', $where, $orderby, $pageParam);
        $this->assign('list',$list['list']);

        $this->getInputType();
        $this->getSelectOption('is_extra');

        \LogPageHelper::record('查看服务商列表页','info',$this->logOption);
        return view();
    }

    public function add(){
        $this->checkPowerWeb('device_provider_add',$this->admin['ap_codes']);//服务商管理判断

        $is_extra = input('is_extra');
        $this->assign("is_extra", $is_extra);

        $this->getSelectOption('extra_field');
        $this->getInputType();
        $this->setDefaultValue();

        return view();
    }

    public function doAdd(){
        $this->checkPowerWeb('device_provider_add',$this->admin['ap_codes']);//服务商管理判断
        $data = $_POST;
        $data['valid']=input('valid',0);
        $data['output_valid']=input('output_valid',0);
        $data['can_del']=input('can_del',0);
        $res = db('table_config')->data($data)->insert();
        if ($res === false) {
            \LogPageHelper::record('添加服务商：'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加服务商：'.$data['column_name'].'','info',$this->logOption);
            $this->success('操作成功');
        }
    }

    public function edit(){
        $this->checkPowerWeb('device_provider_update',$this->admin['ap_codes']);//服务商管理判断

        $is_extra = input('is_extra');
        $this->assign("is_extra", $is_extra);

        $id = input('id');
        if (!$id) {
            $this->error('缺少id');
        }
        $model = db('table_config')->where(['id' => $id])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $this->assign('isEdit',1);
        $this->assign('model', $model);

        $this->getSelectOption('extra_field');
        $this->getInputType();

        return view();
    }

    public function doEdit(){
        $this->checkPowerWeb('device_provider_update',$this->admin['ap_codes']);//服务商管理判断

        $data = $_POST;
        $this->isAlreadyUse($data['table_name'], $data['column_code']);

        $data['valid']=input('valid',0);
        $data['output_valid']=input('output_valid',0);
        $data['can_del']=input('can_del',0);
        $res = db('table_config')->data($data)->update();

        if ($res === false) {
            \LogPageHelper::record('修改服务商:'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改服务商：'.$data['column_name'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    public function doDel(){
        $this->checkPowerWeb('device_provider_delete',$this->admin['ap_codes']);//服务商管理判断
        $ids=input('ids');
        if(!$ids){
            $this->error('参数不正确');
        }
        $where['id']=['in',$ids];
        $res=db('table_config')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除服务商：'.$ids,'info',$this->logOption);
            $this->success('删除成功');
        }
    }

    /**
     * 判断code是否存在
     * @return \think\response\View
     */
    public function checkCode(){
        $index_code = input('param');
        $where['index_code'] = $index_code;
        $isExist = db('table_config')->where($where)->count();
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

    private function getInputType(){
        $dicM=new \app\common\model\Dictionary();
        $typdList=$dicM->getByIndexCode('table_config_type');
        $this->assign('typeList',$typdList);
    }


}
