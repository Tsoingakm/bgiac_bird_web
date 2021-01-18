<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-26
 * Time: 15:39
 */

namespace app\api\controller;


use app\api\model\DeviceCodeIndex;
use app\api\model\DeviceMaintenanceConfigValue;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;
use app\api\model\Unit;
use think\Db;

class DeviceMaintenance extends Common
{

    protected $config;
    protected $choices;

    public function _initialize(){
        parent::_initialize();
        $this->config   = new TableConfig();
        $this->choices  = new TableConfigOption();
    }

    public function insert(){
        $params = input('');
        $params['maintenance_date'] = strtotime(date('Y-m-d', $params['maintenance_time']));
        $codeList = explode('-', $params['worker_code']);
        $index = $codeList[count($codeList) - 1];
        $params['addtime'] = strtotime(date('Y-m-d H:i:s'));
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
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
            $this->return_msg( false, "添加失败");
        }
        $this->return_msg( true, "添加成功");
    }

    public function deleteById(){
        $id  = $this->request -> param('id');

        if(!$id){
            $this->return_msg( false, "缺少参数");
        }

        $result = \app\api\model\DeviceMaintenance::where( 'id', 'in', $id ) -> delete();

        if($result <= 0){
            $this->return_msg( false, "删除失败");
        }
        $this->return_msg( true, "删除成功");
    }

    public function updateById(){
        //$this->return_msg( false, "系统升级中，请稍后再录入数据");
        $params  = $this->request->param();
        $params['maintenance_date'] = strtotime(date('Y-m-d', $params['maintenance_time']));
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
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
                        $params['addtime'] = strtotime(date('Y-m-d H:i:s'));
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
            $this->return_msg( false, "更新失败");
        }
        $this->return_msg( true,  "更新成功");

    }

    public function findById(){
        $id  = $this->request -> param('id');

        if(!$id){
            $this->return_msg( false, "缺少参数");
        }

        $maintenance = \app\api\model\DeviceMaintenance::find($id);
        $contentList = \app\api\model\DeviceMaintenanceContent::with(['configs.options'])->where(['device_id'=>$maintenance['device_id']])->select();
        foreach ($contentList as $k=>$v){
            foreach ($contentList[$k]['configs'] as $key=>$val){
                $valueData = \app\api\model\DeviceMaintenanceConfigValue::where([
                    'maintenance_id'=>$id, 'config_id'=>$contentList[$k]['configs'][$key]['id'], 'index_code'=>$contentList[$k]['configs'][$key]['index_code']
                ])->find();
                $maintenance[$contentList[$k]['configs'][$key]['id'].'$'.$contentList[$k]['configs'][$key]['index_code']] = $valueData?$valueData['value']:'';

            }
        }

        $maintenance = $this -> extension("device_maintenance", $maintenance);

        if(!$maintenance){
            $this->return_msg( false, "查询失败");
        }
        $this->return_msg( true, "查询成功", $maintenance);
    }

    public function selectForm(){
        $device_id  = $this->request -> param('device_id');
        if(!$device_id){
            $this->return_msg( false, "缺少参数");
        }
        $options = [];

        $contentList = \app\api\model\DeviceMaintenanceContent::with(['configs.options'])->where(['device_id'=>$device_id])->select();

        foreach ($contentList as $k=>$v){
            $singleContent = [];
            $singleContent['content'] = $contentList[$k]['content'];
            $singleContent['details'] = $contentList[$k]['details'];
            $singleContent['config'] = [];
            foreach ($contentList[$k]['configs'] as $key=>$val){
                $singleData = array();
                $singleData['index_name']    = $contentList[$k]['configs'][$key]['name'];
                $singleData['type']    = $contentList[$k]['configs'][$key]['type'];
                $singleData['index_code']   = $contentList[$k]['configs'][$key]['id'].'$'.$contentList[$k]['configs'][$key]['index_code'];
                $singleData['value'] = '';
                $singleData['options'] = [];
                switch($contentList[$k]['configs'][$key]['type']){
                    case 2:
                        $singleData['options'] = $contentList[$k]['configs'][$key]['options'];
                        break;
                    case 3:
                        //人员下拉框
                        $singleData['options'] = $this -> get_all_staff_selects();
                        break;
                    default:
                        break;
                }
                $singleContent['config'][] = $singleData;

            }
            $options[] = $singleContent;
        }

        $codeList = \app\api\model\DeviceCode::where(['device_id'=>$device_id, 'valid'=>1])->select();
        $selectCodes = [];
        foreach ($codeList as $k=>$v){
            $selectCode = [];
            $selectCode['key'] = $codeList[$k]['code'];
            $selectCode['value'] = $codeList[$k]['code'];
            $selectCodes[] = $selectCode;
        }
        $selectUnits = [];
        $unitList = Unit::where(['valid'=>1])->select();
        foreach ($unitList as $k=>$v){
            $selectUnit = [];
            $selectUnit['key'] = $unitList[$k]['name'];
            $selectUnit['value'] = $unitList[$k]['name'];
            $selectUnits[] = $selectUnit;
        }

        $items = $this->config -> from_item("device_maintenance");
        foreach ($items as $item) {
            $option = $this->choices -> get_options($item['index_code']);

            $arr['index_code'] = $item['index_code'];
            if(!empty($item['default_value'])){
                $arr['default_value'] = $item['default_value'];
            }
            $arr['options'] = $option;

            $options[] = $arr;
            unset($arr);
        }

        $data['form_options'] = $options;

        $extra = $this->config -> extra_item("device_maintenance");
        foreach ($extra as $key=>$value) {
            $option = $this->choices -> get_options($value['index_code']);
            $extra[$key]['options'] = $option;
        }

        if(!empty($extra)){
            $data['extra_items'] = $extra;
        }

        $data['codeList'] =$selectCodes;
        $data['unitList'] =$selectUnits;
        $data['form_options'] = $options;
        $this->return_msg( true, "获取表单成功", $data);
    }

    public function selectList(){
        $where = array();
        $deviceId = $this->request -> param('device_id');
        if(is_numeric($deviceId)){
            $where['device_id'] = $deviceId;
        }

        $start  = $this->request -> param('starting_time');
        $end    = $this->request -> param('end_time');

        $page_size    = $this->request -> param('page_size');
        $current_page = $this->request -> param('current_page');
        $current_page = $current_page < 1 ? 1 : $current_page;
        $page_size = $page_size?$page_size: 10;
        $where['maintenance_time'] = [ 'between', [ $start, $end ] ];

        $orderby = 'maintenance_time desc';

        $list = db('device_maintenance')->where ( $where )->order ( $orderby )
            ->limit(($current_page - 1) * $page_size, $page_size)->select();

        foreach ($list as $k=>$v){
            $device = \app\api\model\Device::where(['device_id'=>$list[$k]['device_id']])->find();
            $list[$k]['device'] = $device;
        }

        $returnList = [];
        $returnList['total'] = count($list);
        $returnList['per_page'] = $page_size;
        $returnList['current_page'] = $current_page;
        $returnList['data'] = $list;

        if(!$list){
            $returnList['data'] = [];
            $this->return_msg(true, "查询列表成功", $returnList);
        }

        $this->return_msg(true, "查询列表成功", $returnList);
    }

    public function generateWorkerCode(){
        $code = input('code');
        $device_id = input('device_id');
        if(!$code || !$device_id){
            $this->return_msg(false, "参数错误");
        }

        $lastMaintenance = \app\api\model\DeviceCodeIndex::where(['device_id'=>$device_id, 'code'=>$code])
            ->order('index desc')
            ->find();
        if($lastMaintenance){
            $index = $lastMaintenance['index'];
            $index += 1;
            $newCode = $code.'-'.$index;
        }else{
            $newCode = $code.'-1';
        }

        $this->return_msg(true, "获取成功", $newCode);
    }
}
