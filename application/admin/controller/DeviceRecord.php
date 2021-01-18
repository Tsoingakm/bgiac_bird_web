<?php

namespace app\admin\controller;

use think\Request;
use app\api\model\Admin;
use app\api\model\Device;
use app\api\model\DeviceCode;
use app\api\model\DeviceParts;
use app\api\model\DeviceStatus;
use app\api\model\DeviceRecord AS Record;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;

class DeviceRecord extends Base {

    protected $request;
    protected $worker;
    protected $device;
    protected $code;
    protected $part;
    protected $status;
    protected $record;
    protected $config;
    protected $choices;

    public function __construct(){
        parent::__construct();
        $this->request  = Request::instance();
        $this->worker   = new Admin();
        $this->device   = new Device();
        $this->code     = new DeviceCode();
        $this->part     = new DeviceParts();
        $this->status   = new DeviceStatus();
        $this->record   = new Record();
        $this->config   = new TableConfig();
        $this->choices  = new TableConfigOption();
        $this->addBread('台账管理');
        $this->addBread('设备维护工作记录管理');
    }

    public function index(){
        $this->checkPowerWeb('device_record_view',$this->admin['ap_codes']);

        $devices = $this->device -> device_list();
        $this->assign("device_list", $devices);

        $where = array();
        $pageParam = array();
/*
        $starting_time  = input('starting_time') ? input('starting_time') : date("Y-m-d", strtotime("-1 month"));
        $end_time       = input('end_time') ? input('end_time') : date('Y-m-d', time());
        $this->assign("starting_time", $starting_time);
        $this->assign("end_time", $end_time);
        $this->assign("max_time", date('Y-m-d', time()));

        $where['working_date'] = [ 'between', [ strtotime($starting_time), strtotime($end_time) ] ];
        $pageParam['starting_time'] = $starting_time;
        $pageParam['end_time']      = $end_time;

        $device = input( 'device' );
        if($device){
            $where['device'] = $device;
            $pageParam['device'] = $device;
            $this->assign('device', $device);
        }

        $keyword = input('keyword');
        if ($keyword) {
            $where['check_item'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword', $keyword);
        }*/

        $begin_day = input('begin_day');
        $begin_day_int = 0;//时间戳格式的开始日期
        $end_day_int = 0;//时间戳格式的结束日期
        $end_day = input('end_day', date('Y-m-d'));
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
        $this->assign('end_day', $end_day);
        $this->assign('today', date('Y-m-d'));

        $where['working_date'] = [ 'between', [ strtotime($begin_day), strtotime($end_day) ] ];
        //处理方式
        $parts_status_list=db("device_status")->where(['type'=>2])->select();

        $this->assign('parts_status_list',$parts_status_list);
        //联动菜单

        //设备
        $device = input('device');
        $pageParam['device'] = $device;
        if ($device) {
            $where['device'] = $device;
        }
        $this->assign('device', $device);
        $deviceList=db("device")->order("name asc")->select();
        $this->assign('device_list',$deviceList);
        //编号
        $code = input('code');
        $pageParam['code'] = $code;
        if ($code) {
            $where['code'] = $code;
        }
        $this->assign('code', $code);

        //检查项目
        $check_item = input('check_item');
        $pageParam['check_item'] = $check_item;
        if ($check_item) {
            $where['check_item'] = $check_item;
        }
        $this->assign('check_item', $check_item);

        $orderby = 'working_date desc,id desc';
        $list = $this->simpleGetList('device_record', $where, $orderby, $pageParam);

        if(input('act')==="export_excel"){
          $this->export_data_handle($where);
        }

        return view();
    }

    public function add(){
        $this->checkPowerWeb('device_record_add',$this->admin['ap_codes']);

        $model['working_date']   = date("Y/n/j", time());
        $model['working_time']   = date("G:i", time());
        $this->assign("model", $model);

        $this->setOption();
        $records = $this->record->historic_records();
        $this->assign("records", $records);

        return view();
    }

    public function doAdd(){
        $this->checkPowerWeb('device_record_add',$this->admin['ap_codes']);
        $params = $this->request -> param();

        if(empty($params['inspection'])){
            $this->error("检查情况为空！");
        }

        $params['working_date'] = strtotime($params['working_date']);

        $data = array();
        $data['working_date']     = $params['working_date'];
        $data['worker1']          = $params['worker1'];
        $data['worker2']          = $params['worker2'];
        $data['worker3']          = empty($params['worker3']) ? '' : $params['worker3'];
        $data['service_provider'] = $params['service_provider'];
        $data['device']           = $params['device'];
        $data['code']             = $params['code'];
        $data['device_status']    = $params['device_status'];
        $data['aid']              = $params['aid'];

        $device_id = $this->device ->  findByName($data['device']);

        $inspection = json_decode($params['inspection'], true);

        foreach($inspection as $item){
            $data['check_item']     = $item['check_item'];
            $data['process_method'] = $item['process_method'];
            $data['remarks']        = $item['remarks'];
            $result = $this->record -> insert_data($data);
        }

        // $all = $this->part -> part_name_array($device_id);
        // $section = array();
        // foreach($inspection as $inspection ){
        //     $section[] = $inspection['check_item'];
        // }
        //
        // $remain = array_diff($all, $section);
        // foreach ($remain as $item) {
        //     $data['check_item']     = $item;
        //     $data['process_method'] = "正常";
        //     $data['remarks']        = "";
        //     $result = $this->record -> insert_data($data);
        // }

        if(!$result){
            \LogPageHelper::record('添加设备维护工作记录记录失败：'.$id,'error',$this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加设备维护工作记录记录成功：'.$id,'info',$this->logOption);
        $this->success('添加成功');
    }

    public function edit(){
        $this->checkPowerWeb('device_record_update',$this->admin['ap_codes']);

        $this->setOption();

        $records = $this->record->historic_records();
        $this->assign("records", $records);

        $id   = $this->request -> param('id');
        $data = $this->record-> find_by_id($id);

        $device_id = $this->device -> findByName($data['device']);

        $code_list =  $this->code -> code_info($device_id);
        $this->assign("code_list", $code_list);

        $part_list  = $this->part -> part_info($device_id);
        $this->assign("part_list", $part_list);

        $this -> assign("model", $data);
        return view();
    }

    public function doEdit(){
        $this->checkPowerWeb('device_record_update',$this->admin['ap_codes']);

        $id     = $this->request -> param('id');
        $params = $this->request -> param();

        $params['working_date'] = strtotime($params['working_date']);

        $result = $this->record-> update_by_id($id, $params);
        if($result !== false){
            \LogPageHelper::record('修改设备维护工作记录记录成功：'.$id,'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改设备维护工作记录记录失败：'.$id,'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function doDel(){
        $this->checkPowerWeb('device_record_delete',$this->admin['ap_codes']);
        $ids   = $this->request -> param('ids');
        if (empty($ids)) { $this->error('参数不正确'); }

        $result = $this->record-> delete_by_id($ids);
        if(!$result){
            \LogPageHelper::record('删除设备维护工作记录记录失败：'.$ids,'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除设备维护工作记录记录成功：'.$ids,'info', $this->logOption);
        $this->success('删除成功');
    }

    public function setOption(){
        $worker_list = $this -> get_relevant_staff('device_record_add');
        $this->assign("worker_list", $worker_list);

        $entering_list = $this -> get_entering_staff('device_record_add');
        $this->assign("entering_list", $entering_list);

        $device_list = $this->device -> device_list();
        $this->assign("device_list", $device_list);

        $device_status = $this->status -> device_status();
        $this->assign("device_status_list", $device_status);

        $parts_status = $this->status -> parts_status();
        $this->assign("parts_status_list", $parts_status);

        $items = $this->config -> from_item("device");
        foreach ($items as $item) {
          $option = $this->choices -> get_options($item['index_code']);
          $options[] = [
            'index_code' => $item['index_code'],
            'options' => $option
          ];
        }
        foreach($options as $key=>$value){
          $this->assign($value['index_code']."_list", $value['options']);
        }
    }

    public function getCode(){
        $device_name  = $this->request  ->  param('device');
        $device_id    = $this->device   ->  findByName($device_name);

        $code_list  = $this->code -> code_option($device_id);
        return $code_list;
    }

    public function getParts(){
        $device_name  = $this->request  ->  param('device');
        $device_id    = $this->device   ->  findByName($device_name);

        $part_list  = $this->part -> part_option($device_id);
        return $part_list;
    }

    public function add_table(){
        $device_name  = $this->request  ->  param('device');
        $device_id    = $this->device   ->  findByName($device_name);

        $part_list  = $this->part -> part_option($device_id);
        $this -> assign( "part_list", $part_list );

        $parts_status = $this->status -> parts_status();
        $this->assign("parts_status_list", $parts_status);

        return view();
    }

    public function findAll(){
        $id   = $this->request -> param('id');
        $data = $this->record-> find_by_id($id);

        $return_data['status']  = true;
        $return_data['info']    = "查询成功";
        $return_data['data']    = $data;

        if(!$data){
          $return_data['status']  = false;
          $return_data['info']    = "复制失败";
          unset($return_data['data']);
        }

        return $return_data;
    }

    public function export_data_handle($where){
        $filename = "设备维护工作记录";

        $header = [
            ['id',              '编号'],
            ['working_date',    '日期'],
            ['worker1',         '维修人员1'],
            ['worker2',         '维修人员2'],
            ['worker3',         '维修人员3'],
            ['device',          '设备'],
            ['code',            '编号'],
            ['check_item',      '检查项目'],
            ['process_method',  '处理方式'],
            ['remarks',         '备注'],
        ];

        $data = $this->record -> select_all($where);

        $this -> export_excel($filename, $header, $data);
    }

    public function DeleteDuplicatData(){
        $content = $this->record::all(function($query){
            $query   -> field('id,working_date,working_time,device,code AS device_code,check_item,process_method,remarks, COUNT(check_item) AS partCount')
                     -> group('working_date,working_time,device,device_code,check_item,process_method')
                     -> having('COUNT(check_item)>1')
                     -> order('working_date,working_time,device,device_code');
         });

        $total = 0;

        foreach($content as $origin){
            $lists = $this->record::all(function($query) use($origin){
                $query  -> where('working_date',    $origin->getData('working_date'))
                        -> where('working_time',    $origin->getData('working_time'))
                        -> where('device',          $origin->device)
                        -> where('code',            $origin->device_code )
                        -> where('check_item',      $origin->check_item)
                        -> where('process_method',  $origin->process_method);
            });
            foreach($lists as $single){
                if($single->id != $origin->id){
                    $this->record::destroy($single->id);
                }
            }
        }

    }
}
