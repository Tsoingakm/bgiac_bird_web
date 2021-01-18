<?php

namespace app\admin\controller;

use think\Request;
use app\api\model\WorkVector AS Work;
use app\api\model\TableConfigOption;

class StatsWorkVector extends Statistics{

    protected $request;
    protected $work;
    protected $choices;

    public function __construct(){
      parent::__construct();
      $this->request  = Request::instance();
      $this->work     = new Work();
      $this->choices  = new TableConfigOption();
      $this->addBread('病媒防控次数统计');
    }

    public function index(){
      $this->checkPowerWeb('stats_work_vector',$this->admin['ap_codes']);

      $year = $this->request -> param('year');
      $year = empty($year) ? date("Y", time()) : $year;
      $this->assign("year", $year);

      $list = array();

      $type = $this->choices -> get_options_for_stats('work_vector_type');
      $this->assign('work_type', $type);

      $month = $this -> getMonth($year);
      $sumList = [];
      foreach($month as $key => $time){
        $data = array();
        $data['month'] = $key."月";
        foreach($type as $item){
            if(!isset($sumList[$item['id'].'_'.$item['index_code']])){
                $sumList[$item['id'].'_'.$item['index_code']] = 0;
            }
          $data[$item['id'].'_'.$item['index_code']] = $this->work -> month($time) -> where('work_type', $item['value']) -> group('working_date, start_time, end_time') -> count();
            $sumList[$item['id'].'_'.$item['index_code']] += $data[$item['id'].'_'.$item['index_code']];
        }
        $list[] = $data;
      }
      $this->assign("list", $list);
        $this->assign("sumList", $sumList);

      $action = $this->request -> param('act');
      if($action === "export_excel"){
        $this->export_data_handle($list);
      }

      \LogPageHelper::record('查看病媒防控次数统计','info',$this->logOption);

      return view();
    }

    public function export_data_handle($data){
        $filename = "病媒防控次数统计";

        $header = [
            ['month', '月份'],
        ];

        $type = $this->choices -> get_options_for_stats('work_vector_type');
        foreach($type as $item){
          $header[] = [ $item['id'].'_'.$item['index_code'], $item['value'] ];
        }

        $this -> export_excel($filename, $header, $data);
    }
}
