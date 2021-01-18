<?php

namespace app\admin\controller;

use think\Request;
use app\api\model\BirdCondition;

class StatsBirdSupervise extends Statistics{

    protected $request;
    protected $supervise;

    public function __construct(){
      parent::__construct();
      $this->request    = Request::instance();
      $this->supervise  = new BirdCondition();
      $this->addBread('一级鸟情统计');
    }

    public function index(){

        $this->checkPowerWeb('stats_bird_supervise',$this->admin['ap_codes']);

        $year = $this->request -> param('year');
        $year = empty($year) ? date("Y", time()) : $year;
        $this->assign("year", $year);

        $list = array();

        $month = $this -> getMonth($year);

        $sumDay = 0;
        $sumSerial = 0;
        $sumSerial_1 = 0;
        $sumSerial_2 = 0;
        $sumSerial_3 = 0;
        $sumSerial_4 = 0;
        $sumRecords = 0;
        $sumTimes = 0;

        foreach($month as $key => $time){
            $data = array();
            $data['month'] = $key."月";
            $data['days'] = $this->supervise -> month($time) -> field('day_int') -> group('day_int') -> count('day_int');
            $data['serial_1'] = $this->supervise -> month($time) -> where('view_number', '01') -> count();
            $data['serial_2'] = $this->supervise -> month($time) -> where('view_number', '02') -> count();
            $data['serial_3'] = $this->supervise -> month($time) -> where('view_number', '03') -> count();
            $data['serial_4'] = $this->supervise -> month($time) -> where('view_number', '04') -> count();
            $data['total_serial']   = $data['serial_1'] + $data['serial_2'] + $data['serial_3'] + $data['serial_4'];
            $data['total_records']  = $this->supervise -> month($time) -> count();
            $data['total_times']    = $this->supervise -> month($time) -> sum('bird_num');
            $list[] = $data;

            $sumDay += $data['days'];
            $sumSerial += $data['total_serial'];
            $sumSerial_1 += $data['serial_1'];
            $sumSerial_2 += $data['serial_2'];
            $sumSerial_3 += $data['serial_3'];
            $sumSerial_4 += $data['serial_4'];
            $sumRecords += $data['total_records'];
            $sumTimes += $data['total_times'];
        }

        $this->assign("list", $list);

        $this->assign("sumDay", $sumDay);
        $this->assign("sumSerial", $sumSerial);
        $this->assign("sumSerial_1", $sumSerial_1);
        $this->assign("sumSerial_2", $sumSerial_2);
        $this->assign("sumSerial_3", $sumSerial_3);
        $this->assign("sumSerial_4", $sumSerial_4);
        $this->assign("sumRecords", $sumRecords);
        $this->assign("sumTimes", $sumTimes);

        $action = $this->request -> param('act');
        if($action === "export_excel"){
            $this->export_data_handle($list);
        }

        \LogPageHelper::record('查看一级鸟情统计','info',$this->logOption);

        return view();
    }

    public function export_data_handle($data){
        $filename = "一级鸟情统计";

        $header = [
            ['month',         '月份'],
            ['days',          '记录日数'],
            ['total_serial',  '总巡视次数'],
            ['serial_1',      '01次巡视'],
            ['serial_2',      '02次巡视'],
            ['serial_3',      '03次巡视'],
            ['serial_4',      '04次巡视'],
            ['total_records', '总记录条数'],
            ['total_times',   '总只次数'],
        ];

        $this -> export_excel($filename, $header, $data);
    }
}
