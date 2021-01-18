<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-25
 * Time: 10:38
 */

namespace app\admin\controller;


use app\api\model\DeviceCodeIndex;
use app\api\model\DeviceMaintenanceConfigValue;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;
use app\api\model\Unit;
use think\Db;

class DeviceMaintenance extends Base
{

    protected $config;
    protected $choices;

    public function __construct()
    {
        parent::__construct();
        $this->addBread('台账配置');
        $this->addBread('设备管理');
        $this->addBread('设备维护工卡');
        $this->config = new TableConfig();
        $this->choices = new TableConfigOption();

    }

    public function index(){
        $this->checkPowerWeb('device_maintenance_view', $this->admin['ap_codes']);

        $where = array();
        $pageParam = array();
        $deviceId = input('deviceId');
        if(is_numeric($deviceId)){
            $pageParam ['deviceId'] = $deviceId;
            $where['device_id'] = $deviceId;
        }

        $day = urldecode(input('day'));
        $dayList = explode('~', $day);
        $begin_day = $dayList[0];
        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
        $begin_day = trim($begin_day);
        $end_day = trim($end_day);
        if (!empty ($begin_day)) {
            $begin_day_int = strtotime($begin_day);
        } else {
            $begin_day = date('Y-m-d', time() - 86400 * 30);
            $begin_day_int = strtotime($begin_day);
        }
        $pageParam ['begin_day'] = $begin_day;
        $this->assign('begin_day', $begin_day);
        if (!empty ($end_day)) {
            $end_day_int = strtotime($end_day . ' 23:59:59');
        }
        $pageParam ['end_day'] = $end_day;
        $pageParam ['day'] = $begin_day.' ~ '.$end_day;
        $this->assign('day', $begin_day.' ~ '.$end_day);
        $this->assign('end_day', $end_day);
        $this->assign('today', date('Y-m-d'));
        $where['maintenance_time'] = [ 'between', [ $begin_day_int, $end_day_int ] ];

        $count = db('device_maintenance')->where ( $where )->count ();
        $this->assign('totalRows', $count);

        $this->assign('dataUrl', url('DeviceMaintenance/indexData', [
            'deviceId'=>$deviceId,
            'day'=>urlencode($day)
        ]));

        return view();
    }

    public function indexData(){
        $where = array();
        $deviceId = input('deviceId');
        if(is_numeric($deviceId)){
            $where['device_id'] = $deviceId;
        }

        $day = urldecode(input('day'));
        $dayList = explode('~', $day);
        $begin_day = $dayList[0];
        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
        $begin_day = trim($begin_day);
        $end_day = trim($end_day);
        if (!empty ($begin_day)) {
            $begin_day_int = strtotime($begin_day);
        } else {
            $begin_day = date('Y-m-d', time() - 86400 * 30);
            $begin_day_int = strtotime($begin_day);
        }
        if (!empty ($end_day)) {
            $end_day_int = strtotime($end_day . ' 23:59:59');
        }
        $where['maintenance_time'] = [ 'between', [ $begin_day_int, $end_day_int ] ];

        $orderby = 'maintenance_time desc';

        $page = input('page', 1);
        $pageCount = input('limit', 10);

        $list = db('device_maintenance')->where ( $where )->order ( $orderby )
            ->limit ( ($page - 1) * $pageCount, $pageCount )->select ();
        $count = db('device_maintenance')->where ( $where )->count ();

        foreach ($list as $k=>$v){
            $device = db('device')->where(['device_id'=>$list[$k]['device_id']])->find();
            $list[$k]['device'] = $device['name'];
            $list[$k]['maintenance_time'] = date('Y-m-d H:i:s',$list[$k]['maintenance_time']);
        }
        $data = [];
        $data['code']    = 0;
        $data['msg']    = "查询成功";
        $data['count']  = $count;
        $data['data']   = $list;
        return $data;
    }


    /**
     * 添加维护保养工卡记录
     */
    public function add(){

        $this->checkPowerWeb('device_maintenance_add', $this->admin['ap_codes']);

        $deviceId = input('device_id');
        if(!$deviceId){
            $this->error('缺少参数');
        }
        $this->assign('deviceId', $deviceId);
        $contentList = \app\api\model\DeviceMaintenanceContent::with(['configs.options'])->where(['device_id'=>$deviceId])->select();
        foreach ($contentList as $k=>$v){
            $renderHTML = array();
            foreach ($contentList[$k]['configs'] as $key=>$val){
                $data = array();
                $data['item_options'] = [];
                $type = '';
                switch($contentList[$k]['configs'][$key]['type']){
                    case 0:
                        $type = 'input';
                        break;
                    case 1:
                        $type = 'text';
                        break;
                    case 2:
                        $type = 'select';
                        $data['item_options'] = $contentList[$k]['configs'][$key]['options'];
                        break;
                    case 3:
                        //人员下拉框
                        $type = 'select';
                        $data['item_options'] = $this -> get_all_staff_selects();
                        break;
                    default:
                        break;
                }
                if($type == 'input' && $contentList[$k]['configs'][$key]['name'] == '备注'){
                    $type = 'remarkInput';
                }
                $data['item_name']    = $contentList[$k]['configs'][$key]['name'];
                $data['type']    = $contentList[$k]['configs'][$key]['type'];
                $data['item_field']   = $contentList[$k]['configs'][$key]['id'].'$'.$contentList[$k]['configs'][$key]['index_code'];
                $data['item_value']   = '';
                $renderHTML[] = $this->renderHtml($type, $data);
            }
            $contentList[$k]['extras'] = $renderHTML;
        }

        $this -> assign( "contentList", $contentList );
        $codeList = \app\api\model\DeviceCode::where(['device_id'=>$deviceId, 'valid'=>1])->select();
        $this -> assign( "code_list", $codeList );
        $unitList = Unit::where(['valid'=>1])->select();
        $this -> assign( "unit_list", $unitList );

        $items = $this->config->from_item("bird_condition");

        foreach ($items as $item) {
            $option = $this->choices->get_options($item['index_code']);
            $options[] = [
                'index_code' => $item['index_code'],
                'options' => $option
            ];
        }
        foreach ($options as $key => $value) {
            $this->assign($value['index_code'] . "_list", $value['options']);
        }

        $this->extra('device_maintenance');

        return view();
    }

    public function edit(){
        $this->checkPowerWeb('device_maintenance_edit', $this->admin['ap_codes']);

        $id = input('id');
        if(!$id){
            $this->error('缺少参数');
        }
        $maintenance = \app\api\model\DeviceMaintenance::find($id);
        $maintenance['maintenance_time'] = date('Y-m-d H:i:s', $maintenance['maintenance_time']);
//        var_dump($maintenance);exit;
        $this->assign('model', $maintenance);
        $contentList = \app\api\model\DeviceMaintenanceContent::with(['configs.options'])->where(['device_id'=>$maintenance['device_id']])->select();
        foreach ($contentList as $k=>$v){
            $renderHTML = array();
            foreach ($contentList[$k]['configs'] as $key=>$val){
                $data = array();
                $data['item_options'] = [];
                $type = '';
                switch($contentList[$k]['configs'][$key]['type']){
                    case 0:
                        $type = 'input';
                        break;
                    case 1:
                        $type = 'text';
                        break;
                    case 2:
                        $type = 'select';
                        $data['item_options'] = $contentList[$k]['configs'][$key]['options'];
                        break;
                    case 3:
                        //人员下拉框
                        $type = 'select';
                        $data['item_options'] = $this -> get_all_staff_selects();
                        break;
                    default:
                        break;
                }
                if($type == 'input' && $contentList[$k]['configs'][$key]['name'] == '备注'){
                    $type = 'remarkInput';
                }
                $data['type']    = $contentList[$k]['configs'][$key]['type'];
                $data['item_name']    = $contentList[$k]['configs'][$key]['name'];
                $data['item_field']   = $contentList[$k]['configs'][$key]['id'].'$'.$contentList[$k]['configs'][$key]['index_code'];
                $valueData = \app\api\model\DeviceMaintenanceConfigValue::where([
                    'maintenance_id'=>$id, 'config_id'=>$contentList[$k]['configs'][$key]['id'], 'index_code'=>$contentList[$k]['configs'][$key]['index_code']
                ])->find();

                $data['item_value']   = $valueData?$valueData['value']:'';
                $renderHTML[] = $this->renderHtml($type, $data);
            }
            $contentList[$k]['extras'] = $renderHTML;
        }
        $this->assign('deviceId', $maintenance['device_id']);
        $this -> assign( "contentList", $contentList );
        $codeList = \app\api\model\DeviceCode::where(['device_id'=>$maintenance['device_id'], 'valid'=>1])->select();
        $this -> assign( "code_list", $codeList );
        $unitList = Unit::where(['valid'=>1])->select();
        $this -> assign( "unit_list", $unitList );

        $items = $this->config->from_item("bird_condition");

        foreach ($items as $item) {
            $option = $this->choices->get_options($item['index_code']);
            $options[] = [
                'index_code' => $item['index_code'],
                'options' => $option
            ];
        }
        foreach ($options as $key => $value) {
            $this->assign($value['index_code'] . "_list", $value['options']);
        }

        $this->extra('device_maintenance');

        $this->extra('device_maintenance', $maintenance);

        return view();
    }

    public function doAdd(){
        $this->checkPowerWeb('device_maintenance_add', $this->admin['ap_codes']);
        $params = input('');
        $params['maintenance_time'] = strtotime($params['maintenance_time']);
        $params['maintenance_date'] = strtotime(date('Y-m-d', $params['maintenance_time']));
        $codeList = explode('-', $params['worker_code']);
        $index = $codeList[count($codeList) - 1];
        $maintenanceModel = new \app\api\model\DeviceMaintenance($params);
        Db::startTrans();
        try{
            $maintenanceModel->allowField(true)->save();
            $codeData = [];
            $codeData['device_id'] = $params['device_id'];
            $codeData['code'] = $params['device_code'];
            $codeData['index'] = $index;
            $codeModel = new DeviceCodeIndex($codeData);
            $codeModel->allowField(true)->save();
            foreach ($params as $k=>$v){
                $paramList = explode('$', $k);
                if(count($paramList) > 1){
                    $valueData = [];
                    $valueData['maintenance_id'] = $maintenanceModel->id;
                    $valueData['value'] = $v;
                    $valueData['addtime'] = $params['addtime'];
                    $valueData['updatetime'] = $params['updatetime'];
                    $valueData['config_id'] = $paramList[0];
                    $valueData['index_code'] = $paramList[1];
                    $valueModel = new DeviceMaintenanceConfigValue($valueData);
                    $valueModel->allowField(true)->save();
                }
            }
            Db::commit();
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            \LogPageHelper::record('添加设备维护保养记录失败：' . $id, 'error', $this->logOption);
            $this->error('数据库操作失败，请稍后再试'.$e->getMessage());
        }
        \LogPageHelper::record('添加设备维护保养记录成功：' . $id, 'error', $this->logOption);
        $this->success('添加成功');
    }

    public function doEdit(){
        $this->checkPowerWeb('device_maintenance_edit', $this->admin['ap_codes']);
        $params = input('');
        $params['maintenance_time'] = strtotime($params['maintenance_time']);
        $params['maintenance_date'] = strtotime(date('Y-m-d', $params['maintenance_time']));
        Db::startTrans();
        try{
            $maintenanceModel = new \app\api\model\DeviceMaintenance();
            $maintenanceModel->allowField(true)->save($params, ['id' => $params['id']]);
            foreach ($params as $k=>$v){
                $paramList = explode('$', $k);
                if(count($paramList) > 1){
                    $valueData = \app\api\model\DeviceMaintenanceConfigValue::where([
                        'maintenance_id'=>$params['id'], 'config_id'=>$paramList[0], 'index_code'=>$paramList[1]
                    ])->find();
                    if($valueData){
                        //更新
                        $valueData = $valueData->toArray();
                        $valueData['value'] = $v;

                        $valueModel = new DeviceMaintenanceConfigValue();
                        $valueModel->allowField(true)->save($valueData, ['id' => $valueData['id']]);
                    }else{
                        //插入
                        $newData = [];
                        $newData['maintenance_id'] = $maintenanceModel->id;
                        $newData['value'] = $v;
                        $newData['addtime'] = $params['addtime'];
                        $newData['updatetime'] = $params['updatetime'];
                        $newData['config_id'] = $paramList[0];
                        $newData['index_code'] = $paramList[1];
                        $valueModel = new DeviceMaintenanceConfigValue($newData);
                        $valueModel->allowField(true)->save();
                    }
                }
            }
            Db::commit();
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            \LogPageHelper::record('修改设备维护保养记录失败：' . $id, 'error', $this->logOption);
            $this->error('数据库操作失败，请稍后再试');
        }
        \LogPageHelper::record('修改设备维护保养记录成功：' . $id, 'error', $this->logOption);
        $this->success('修改成功');
    }

    public function doDel(){
        $this->checkPowerWeb('device_maintenance_del', $this->admin['ap_codes']);
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }
        $result = \app\api\model\DeviceMaintenance::where( 'id', 'in', $ids ) -> delete();
        if ($result <= 0) {
            \LogPageHelper::record('删除捕鸟网记录记录失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除捕鸟网记录记录成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }

    public function generateWorkerCode(){
        $device_id = input('device_id');
        $code = input('code');
        if(!$device_id || !$code){
            $this->error('缺少参数');
        }

        $lastMaintenance = \app\api\model\DeviceCodeIndex::where(['device_id'=>$device_id, 'code'=>$code])
            ->order('index desc')
//            ->fetchSql(true)
            ->find();
//        var_dump($lastMaintenance);exit;
        if($lastMaintenance){
            $index = $lastMaintenance['index'];
            $index += 1;
            $newCode = $code.'-'.$index;
        }else{
            $newCode = $code.'-1';
        }

        $this->succ('获取成功', $newCode);

    }

//    public function historyRecords(){
//        $recodes = [];
//        $list = \app\api\model\DeviceMaintenance::all(function($query){
//            $query
//                ->  field('maintenance_date')
//                ->  whereTime('maintenance_date', '-2 days')
//                ->  order(['maintenance_date'=>'desc', 'maintenance_time'=>'desc']);
//        });
//
//        foreach($list as $item){
//
//            $date = date('Y-m-d', $item['maintenance_date']);
//            $dateStamp   = $item['maintenance_date'];
//
//            $result = \app\api\model\DeviceMaintenance::all(function($query) use($dateStamp){
//                $query  ->  where('maintenance_date', $dateStamp)
//                    ->  order('maintenance_time', 'desc');
//            });
//            $recodes[] = [
//                'date'    =>  $date,
//                'record'  =>  $result
//            ];
//        }
////        var_dump($recodes);
////        exit;
//        return $recodes;
//    }
//
//    public function findAll(){
//        $id = input('id');
//        if(!$id){
//            $this->error('参数错误');
//        }
//
//        $maintenance = \app\api\model\DeviceMaintenance::find($id);
//        $maintenance['maintenance_time'] = date('Y-m-d H:i:s', $maintenance['maintenance_time']);
//        $contentList = \app\api\model\DeviceMaintenanceContent::with(['configs.options'])->where(['device_id'=>$maintenance['device_id']])->select();
//        foreach ($contentList as $k=>$v){
//            foreach ($contentList[$k]['configs'] as $key=>$val){
//                $valueData = \app\api\model\DeviceMaintenanceConfigValue::where([
//                    'maintenance_id'=>$id, 'config_id'=>$contentList[$k]['configs'][$key]['id'], 'index_code'=>$contentList[$k]['configs'][$key]['index_code']
//                ])->find();
//                $maintenance[$contentList[$k]['configs'][$key]['id'].'$'.$contentList[$k]['configs'][$key]['index_code']] = $valueData?$valueData['value']:'';
//
//            }
//        }
//        if($maintenance) {
//            $return_data['status'] = true;
//            $return_data['info'] = "查询成功";
//            $return_data['data'] = $maintenance;
//        }else{
//            $return_data['status'] = false;
//            $return_data['info'] = "复制失败";
//            unset($return_data['data']);
//        }
//        return $return_data;
//    }
}
