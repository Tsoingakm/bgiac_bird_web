<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-26
 * Time: 14:06
 */

namespace app\admin\controller;

use app\api\model\DeviceMaintenanceContent as dmc;

class DeviceMaintenanceContent extends Base
{
    protected $contentModel;

    public function __construct()
    {
        parent::__construct();
        $this->addBread('台账配置');
        $this->addBread('设备管理');
        $this->addBread('设备维护工卡内容');
        $this->contentModel = new dmc();
    }

    public function index()
    {
        $this->checkPowerWeb('device_class_config', $this->admin['ap_codes']);

        $where = array();
        $device_id = input('device_id');
        if (!$device_id) {
            $this->error('缺少device_id');
        }
        $where['device_id'] = $device_id;
        $device = db('device')->where ( $where )->find();
        $count = db('device_maintenance_content')->where ( $where )->count ();
        $this->assign('totalRows', $count);
        $this->assign('device', $device);
        $this->assign('deviceId', $device_id);

        $this->assign('dataUrl', url('DeviceMaintenanceContent/indexData', [
            'device_id'=>$device_id
        ]));

        return view();
    }

    public function indexData(){
        $where = array();
        $device_id = input('device_id');
        $where['device_id'] = $device_id;
        $orderby = 'addtime desc';

        $page = input('page', 1);
        $pageCount = input('limit', 10);

        $list = db('device_maintenance_content')->where ( $where )->order ( $orderby )
            ->limit ( ($page - 1) * $pageCount, $pageCount )->select ();
        $count = db('device_maintenance_content')->where ( $where )->order ( $orderby )->count ();
        foreach ($list as $k=>$v){
            $device = db('device')->where ( ['device_id'=>$list[$k]['device_id']] )->find();
            $list[$k]['device'] = $device['name'];
        }

        $data = [];
        $data['code']    = 0;
        $data['msg']    = "查询成功";
        $data['count']  = $count;
        $data['data']   = $list;
        return $data;
    }

    public function add(){
        $device_id = input('device_id');
        $this->assign('deviceId', $device_id);
        return view();
    }

    public function edit(){
        $id = input('id');
        $model = db('device_maintenance_content')->find ($id);
        $this->assign('model', $model);
        return view();
    }

    public function doAdd(){
        $params = input('');
        $params['addtime'] = strtotime(date('Y-m-d H:i:s'));
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $model = new \app\api\model\DeviceMaintenanceContent($params);
        $result = $model->allowField(true)->save();
        if (!$result) {
            \LogPageHelper::record('添加设备维护工作内容失败：' . $id, 'error', $this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加设备维护工作内容成功：' . $id, 'info', $this->logOption);
        $this->success('添加成功');
    }

    public function doEdit(){
        $params = input('');
        $id = input('id');
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $model = new \app\api\model\DeviceMaintenanceContent();
        $result = $model -> allowField(true) -> save($params,['id' => $id]);
        if (!$result) {
            \LogPageHelper::record('修改设备维护工作内容失败：' . $id, 'error', $this->logOption);
            $this->error('修改失败，请稍后再试');
        }
        \LogPageHelper::record('修改设备维护工作内容成功：' . $id, 'info', $this->logOption);
        $this->success('修改成功');
    }

    public function doDel(){
        $ids = input('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }
        $model = new \app\api\model\DeviceMaintenanceContent();
        $result = $model::where( 'id', 'in', $ids ) -> delete();
        if ($result <= 0) {
            \LogPageHelper::record('删除设备维护工作内容失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除设备维护工作内容成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }
}
