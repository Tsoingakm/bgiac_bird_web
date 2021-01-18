<?php

namespace app\admin\controller;

use think\Request;
use app\api\model\Device;
use app\api\model\DeviceRecord AS Record;

class StatsDeviceMaintain extends Statistics{

    protected $request;
    protected $device;
    protected $record;

    public function __construct(){
      parent::__construct();
      $this->request  = Request::instance();
      $this->device   = new Device();
      $this->record   = new Record();
      $this->addBread('设备维护台次统计');
    }

    public function index(){
      $this->checkPowerWeb('stats_device_maintain', $this->admin['ap_codes']);

      $year = $this->request -> param('year');
      $year = empty($year) ? date("Y", time()) : $year;
      $this->assign("year", $year);

      $list = array();

      $device_list = $this->device -> get_options_for_stats();
      $this->assign('device_list', $device_list);

      $month = $this -> getMonth($year);
      $sumList = [];
      foreach($month as $key => $time){
        $data = array();
        $data['month'] = $key."月";
        foreach($device_list as $item){
            if(!isset($sumList[$item['device_id'].'_device'])){
                $sumList[$item['device_id'].'_device'] = 0;
            }
          $data[$item['device_id'].'_device'] = $this->record -> month($time) -> where('device', $item['name']) -> group('working_date, device, code') -> count();
            $sumList[$item['device_id'].'_device'] += $data[$item['device_id'].'_device'];
        }
        $list[] = $data;
      }
      $this->assign("list", $list);
        $this->assign("sumList", $sumList);

      $action = $this->request -> param('act');
      if($action === "export_excel"){
        $this->export_data_handle($list);
      }

      \LogPageHelper::record('查看设备维护台次统计','info',$this->logOption);

      return view();
    }

    public function export_data_handle($data){
        $filename = "设备维护台次统计";

        $header = [
            ['month', '月份'],
        ];

        $type = $this->device -> get_options_for_stats();
        foreach($type as $item){
          $header[] = [ $item['device_id'].'_device', $item['name'] ];
        }

        $this -> export_excel($filename, $header, $data);
    }
}
