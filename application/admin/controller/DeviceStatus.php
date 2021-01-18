<?php

namespace app\admin\controller;

use Think\Controller;
use think\Request;
use app\api\model\DeviceStatus AS Status;

class DeviceStatus extends Base {

    protected $request;
    protected $status;

    public function __construct(){
        parent::__construct();
        $this->request  = Request::instance();
        $this->status  = new Status();
        $this->addBread('台账配置');
        $this->addBread('设备状态管理');
    }

    public function index(){
        $this->checkPowerWeb('device_status_view',$this->admin['ap_codes']);

        $where = array();
        $pageParam = array();

        $keyword = $this->request -> param("keyword");
        if ($keyword) {
            $where['name'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }

        $where['type'] = 1;

        $orderby = 'addtime desc';
        $list = $this->simpleGetList('device_status', $where, $orderby, $pageParam);

        return view();
    }

    public function add(){
        $this->checkPowerWeb('device_status_add',$this->admin['ap_codes']);
        $this->setDefaultValue();
        return view();
    }

    public function doAdd(){
        $this->checkPowerWeb('device_status_add',$this->admin['ap_codes']);

        $params = $this->request -> param();
        $params['type'] = 1;

        $result = $this->status-> insert_data($params);

        if(!$result){
            \LogPageHelper::record('添加设备状态失败：'.$id,'error',$this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加设备状态成功：'.$id,'info',$this->logOption);
        $this->success('添加成功');
    }

    public function edit(){
        $this->checkPowerWeb('device_status_update',$this->admin['ap_codes']);

        $id   = $this->request -> param('id');

        $data = $this->status-> find_by_id($id);
        $this -> assign("model", $data);

        return view();
    }

    public function doEdit(){
        $this->checkPowerWeb('device_status_update',$this->admin['ap_codes']);

        $id     = $this->request -> param('id');
        $params = $this->request -> param();
        $params['type'] = 1;

        $result = $this->status-> update_by_id($id, $params);

        if($result !== false){
            \LogPageHelper::record('修改设备状态成功：'.$id,'info',$this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改设备状态失败：'.$id,'error',$this->logOption);
          $this->error('修改失败，请稍后再试');
    }

    public function doDel(){
        $this->checkPowerWeb('device_status_delete',$this->admin['ap_codes']);

        $ids   = $this->request -> param('ids');
        if (empty($ids)) { $this->error('参数不正确'); }

        $result = $this->status-> delete_by_id($ids);
        if(!$result){
            \LogPageHelper::record('删除设备状态失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除设备状态成功：'.$ids,'info',$this->logOption);
        $this->success('删除成功');
    }

}
