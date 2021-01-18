<?php

namespace app\admin\controller;

use think\Request;
use app\api\model\WorkInsect AS Work;

class StatsWorkInsect extends Statistics{

    protected $request;
    protected $work;

    public function __construct(){
      parent::__construct();
      $this->request  = Request::instance();
      $this->work     = new Work();
      $this->addBread('消杀次数、面积统计');
    }

    public function index(){
      $this->checkPowerWeb('stats_work_insect',$this->admin['ap_codes']);

      $year = $this->request -> param('year');
      $year = empty($year) ? date("Y", time()) : $year;
      $this->assign("year", $year);

      $list = array();

      $month = $this -> getMonth($year);
        $sumTime = 0;
        $sumArea = 0;
      foreach($month as $key => $time){
        $data = array();
        $data['month']      = $key."月";
        $data['train_time'] = $this->work -> month($time) -> field('working_date, spary_times') -> group('working_date, spary_times') -> count();
        $data['total_area'] = $this->work -> month($time) -> field('working_date, spary_times, work_area') -> sum('work_area');
        $list[] = $data;

          $sumTime += $data['train_time'];
          $sumArea += $data['total_area'];
      }

      $this->assign("list", $list);
        $this->assign("sumTime", $sumTime);
        $this->assign("sumArea", $sumArea);

      $action = $this->request -> param('act');
      if($action === "export_excel"){
        $this->export_data_handle($list);
      }

      \LogPageHelper::record('查看消杀次数、面积统计','info',$this->logOption);

      return view();
    }

    public function export_data_handle($data){
        $filename = "消杀次数、面积统计";

        $header = [
            ['month',       '月份'],
            ['train_time',  '出车次数'],
            ['total_area',  '消杀面积(m²)'],
        ];

        $this -> export_excel($filename, $header, $data);
    }
}
