<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-25
 * Time: 16:14
 */

namespace app\admin\controller;


class DeviceMaintenanceConfigOption extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->addBread('台账配置');
        $this->addBread('设备管理');
        $this->addBread('设备维护工卡');
        $this->addBread('工作内容选项');
        $this->addBread('工作内容选项下拉值');
    }

    public function index()
    {

        $where = array();
        $config_id = input('config_id');
        if (!$config_id) {
            $this->error('缺少config_id');
        }
        $where['config_id'] = $config_id;
        $count = db('device_maintenance_config_option')->where ( $where )->count ();
        $this->assign('totalRows', $count);
        $this->assign('configId', $config_id);

        $this->assign('dataUrl', url('DeviceMaintenanceConfigOption/indexData', [
            'config_id'=>$config_id
        ]));

        return view();
    }

    public function indexData(){
        $where = array();
        $config_id = input('config_id');
        $where['config_id'] = $config_id;
        $orderby = 'sort desc';

        $page = input('page', 1);
        $pageCount = input('limit', 10);

        $list = db('device_maintenance_config_option')->where ( $where )->order ( $orderby )
            ->limit ( ($page - 1) * $pageCount, $pageCount )->select ();
        $count = db('device_maintenance_config_option')->where ( $where )->order ( $orderby )->count ();
        foreach ($list as $k=>$v){
            $config = db('device_maintenance_config')->where ( ['id'=>$list[$k]['config_id']] )->find();
            $list[$k]['config_code'] = $config['index_code'];
        }

        $data = [];
        $data['code']    = 0;
        $data['msg']    = "查询成功";
        $data['count']  = $count;
        $data['data']   = $list;
        return $data;
    }

    public function add(){
        $config_id = input('config_id');
        if (!$config_id) {
            $this->error('缺少config_id');
        }
        $config = db('device_maintenance_config')->where ( ['id'=>$config_id] )->find();
        $this->assign('configId', $config_id);
        $this->assign('configModel', $config);
        $model = [];
        $model['sort'] = 99;
        $model['valid'] = 1;
        $this->assign('model', $model);
        return view();
    }

    public function edit(){
        $id = input('id');
        if (!$id) {
            $this->error('缺少id');
        }
        $model = db('device_maintenance_config_option')->find ($id);
        $config = db('device_maintenance_config')->where ( ['id'=>$model['config_id']] )->find();
        $this->assign('configModel', $config);
        $this->assign('model', $model);
        return view();
    }

    public function doAdd(){
        $params = input('');
        $config = db('device_maintenance_config')->where ( ['id'=>$params['config_id']] )->find();
        $params['index_code'] = $config['index_code'];
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $params['addtime'] = strtotime(date('Y-m-d H:i:s'));
        $model = new \app\api\model\DeviceMaintenanceConfigOption($params);
        $result = $model->allowField(true)->save();
        if (!$result) {
            \LogPageHelper::record('添加选项配置下拉值失败：' . $id, 'error', $this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加选项配置下拉值成功：' . $id, 'info', $this->logOption);
        $this->success('添加成功');
    }

    public function doEdit(){
        $params = input('');
        $id = input('id');
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $model = new \app\api\model\DeviceMaintenanceConfigOption();
        $result = $model -> allowField(true) -> save($params,['id' => $id]);
        if (!$result) {
            \LogPageHelper::record('修改选项配置下拉值失败：' . $id, 'error', $this->logOption);
            $this->error('修改失败，请稍后再试');
        }
        \LogPageHelper::record('修改选项配置下拉值成功：' . $id, 'info', $this->logOption);
        $this->success('修改成功');
    }

    public function doDel(){
        $ids = input('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }
        $model = new \app\api\model\DeviceMaintenanceConfigOption();
        $result = $model::where( 'id', 'in', $ids ) -> delete();
        if ($result <= 0) {
            \LogPageHelper::record('删除选项配置下拉值失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除选项配置下拉值成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }
}
