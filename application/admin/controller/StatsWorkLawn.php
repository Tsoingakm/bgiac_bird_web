<?php

namespace app\admin\controller;

use think\Request;
use app\api\model\WorkLawn AS Work;
use app\api\model\TableConfigOption;

class StatsWorkLawn extends Statistics{

    protected $request;
    protected $work;
    protected $choices;

    public function __construct(){
      parent::__construct();
      $this->request  = Request::instance();
      $this->work     = new Work();
      $this->choices  = new TableConfigOption();
      $this->addBread('草坪维护面积统计');
    }

    public function index(){
      $this->checkPowerWeb('stats_work_lawn',$this->admin['ap_codes']);

      $year = $this->request -> param('year');
      $year = empty($year) ? date("Y", time()) : $year;
      $this->assign("year", $year);

      $list = array();

      $type = $this->choices -> get_options_for_stats('work_lawn_type');
      $this->assign('work_type', $type);

      $month = $this -> getMonth($year);
      $sumList = [];
//      $typeList = [];
      foreach($month as $key => $time){
        $data = array();
        $data['month'] = $key."月";
        foreach($type as $item){
            if(!isset($sumList[$item['id'].'_'.$item['index_code']])){
                $sumList[$item['id'].'_'.$item['index_code']] = 0;
            }

            $data[$item['id'].'_'.$item['index_code']] = $this->work -> month($time) -> where('work_type', $item['value']) -> sum('work_area');
            $sumList[$item['id'].'_'.$item['index_code']] += $data[$item['id'].'_'.$item['index_code']];
        }

        $list[] = $data;
      }
//      var_dump($sumList);
      $this->assign("list", $list);
        $this->assign("sumList", $sumList);

      $action = $this->request -> param('act');
      if($action === "export_excel"){
        $this->export_data_handle($list);
      }

      \LogPageHelper::record('查看草坪维护面积统计','info',$this->logOption);

      return view();
    }

    public function export_data_handle($data){
        $filename = "草坪维护面积统计";

        $header = [
            ['month', '月份'],
        ];

        $type = $this->choices -> get_options_for_stats('work_lawn_type');
        foreach($type as $item){
          $header[] = [ $item['id'].'_'.$item['index_code'], $item['value'] ];
        }

        $this -> export_excel($filename, $header, $data);
    }
}
