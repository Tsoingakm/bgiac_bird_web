<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-25
 * Time: 14:18
 */

namespace app\admin\controller;



class DeviceMaintenanceConfig extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->addBread('台账配置');
        $this->addBread('设备管理');
        $this->addBread('设备维护工卡');
        $this->addBread('工作内容选项');
    }

    public function index()
    {

        $where = array();
        $content_id = input('content_id');
        if (!$content_id) {
            $this->error('缺少content_id');
        }
        $where['content_id'] = $content_id;
        $count = db('device_maintenance_config')->where ( $where )->count ();
        $this->assign('totalRows', $count);
        $this->assign('contentId', $content_id);

        $this->assign('dataUrl', url('DeviceMaintenanceConfig/indexData', [
            'content_id'=>$content_id
        ]));

        return view();
    }

    public function indexData(){
        $where = array();
        $content_id = input('content_id');
        $where['content_id'] = $content_id;
        $orderby = 'addtime desc';

        $page = input('page', 1);
        $pageCount = input('limit', 10);

        $list = db('device_maintenance_config')->where ( $where )->order ( $orderby )
            ->limit ( ($page - 1) * $pageCount, $pageCount )->select ();
        $count = db('device_maintenance_config')->where ( $where )->order ( $orderby )->count ();
        foreach ($list as $k=>$v){
            switch($list[$k]['type']){
                case 0:
                    $list[$k]['typeStr'] = '单行文本框';
                    break;
                case 1:
                    $list[$k]['typeStr'] = '多行文本框';
                    break;
                case 2:
                    $list[$k]['typeStr'] = '普通下拉框';
                    break;
                case 3:
                    $list[$k]['typeStr'] = '人员下拉框';
                    break;
                default:
                    break;
            }
        }

        $data = [];
        $data['code']    = 0;
        $data['msg']    = "查询成功";
        $data['count']  = $count;
        $data['data']   = $list;
        return $data;
    }

    public function add(){
        $content_id = input('content_id');
        if (!$content_id) {
            $this->error('缺少content_id');
        }
        $this->assign('contentId', $content_id);
        return view();
    }

    public function edit(){
        $id = input('id');
        if (!$id) {
            $this->error('缺少id');
        }
        $model = db('device_maintenance_config')->find ($id);
        $this->assign('model', $model);
        return view();
    }

    public function doAdd(){
        $params = input('');
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $params['addtime'] = strtotime(date('Y-m-d H:i:s'));
        $model = new \app\api\model\DeviceMaintenanceConfig($params);
        $result = $model->allowField(true)->save();
        if (!$result) {
            \LogPageHelper::record('添加工作内容选项失败：' . $id, 'error', $this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加工作内容选项成功：' . $id, 'info', $this->logOption);
        $this->success('添加成功');
    }

    public function doEdit(){
        $params = input('');
        $id = input('id');
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $model = new \app\api\model\DeviceMaintenanceConfig();
        $result = $model -> allowField(true) -> save($params,['id' => $id]);
        if (!$result) {
            \LogPageHelper::record('修改工作内容选项失败：' . $id, 'error', $this->logOption);
            $this->error('修改失败，请稍后再试');
        }
        \LogPageHelper::record('修改工作内容选项成功：' . $id, 'info', $this->logOption);
        $this->success('修改成功');
    }

    public function doDel(){
        $ids = input('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }
        $model = new \app\api\model\DeviceMaintenanceConfig();
        $result = $model::where( 'id', 'in', $ids ) -> delete();
        if ($result <= 0) {
            \LogPageHelper::record('删除工作内容选项失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除工作内容选项成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }
}
