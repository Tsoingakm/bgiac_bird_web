<?php

namespace app\admin\controller;

use think\Request;
use app\api\model\BirdSeize;

class StatsBirdNet extends Statistics{

    protected $request;
    protected $net;

    public function __construct(){
      parent::__construct();
      $this->request  = Request::instance();
      $this->net      = new BirdSeize();
      $this->addBread('捕鸟网统计');
    }

    public function index(){
      $this->checkPowerWeb('stats_bird_net',$this->admin['ap_codes']);

      $year = $this->request -> param('year');
      $year = empty($year) ? date("Y", time()) : $year;
      $this->assign("year", $year);

      $list = array();

      $month = $this -> getMonth($year);
      $totalCount = 0;
      foreach($month as $key => $time){
        $data = array();
        $data['month']        = $key."月";
        $data['total_times']  = $this->net -> month($time) -> sum('bird_num');
        $list[] = $data;

          $totalCount += $data['total_times'];
      }

      $this->assign("list", $list);
        $this->assign("totalCount", $totalCount);

      $action = $this->request -> param('act');
      if($action === "export_excel"){
        $this->export_data_handle($list);
      }

      \LogPageHelper::record('查看捕鸟网统计','info',$this->logOption);

      return view();
    }

    public function export_data_handle($data){
        $filename = "捕鸟网统计";

        $header = [
            ['month',       '月份'],
            ['total_times', '只次数'],
        ];

        $this -> export_excel($filename, $header, $data);
    }
}
