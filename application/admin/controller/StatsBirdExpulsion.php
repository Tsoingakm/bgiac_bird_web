<?php

namespace app\admin\controller;

use think\Request;
use app\api\model\BirdDrive;

class StatsBirdExpulsion extends Statistics{

    protected $request;
    protected $expulsion;

    public function __construct(){
      parent::__construct();
      $this->request    = Request::instance();
      $this->expulsion  = new BirdDrive();
      $this->addBread('危险鸟类活动统计');
    }

    public function index(){
      $this->checkPowerWeb('stats_bird_expulsion', $this->admin['ap_codes']);

      $year = $this->request -> param('year');
      $year = empty($year) ? date("Y", time()) : $year;
      $this->assign("year", $year);

      $list = array();

      $month = $this -> getMonth($year);
      $sumRecord = 0;
      $sumTimes = 0;
      foreach($month as $key => $time){
        $data = array();
        $data['month']          = $key."月";
        $data['total_records']  = $this->expulsion -> month($time) -> count();
        $data['total_times']    = $this->expulsion -> month($time) -> sum('bird_num');
        $list[] = $data;

          $sumRecord += $data['total_records'];
          $sumTimes += $data['total_times'];
      }

      $this->assign("list", $list);
        $this->assign("sumRecords", $sumRecord);
        $this->assign("sumTimes", $sumTimes);

      $action = $this->request -> param('act');
      if($action === "export_excel"){
        $this->export_data_handle($list);
      }

      \LogPageHelper::record('查看危险鸟类活动统计','info',$this->logOption);

      return view();
    }

    public function export_data_handle($data){
        $filename = "危险鸟类活动统计";

        $header = [
            ['month',         '月份'],
            ['total_records', '记录条数'],
            ['total_times',   '只次数'],
        ];

        $this -> export_excel($filename, $header, $data);
    }
}
