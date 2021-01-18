<?php

namespace app\admin\controller;

use app\api\model\BirdCondition;

class Statistics extends Base{

    public function __construct(){
      parent::__construct();
      $this->addBread('数据统计');
    }

    /**
     * 获取某年中每个月的第一天和最后一天
     * @param  int $year [年份]
     * @return array       [日期的数组]
     */
    public function getMonth($year=''){
      $year = empty($year) ? 2018 : $year;

      $month = [];

      for( $i = 1; $i <= 12; $i++ ){
//        $days   = cal_days_in_month(CAL_GREGORIAN, $i, $year);
        $days = date("t",strtotime("$year-$i"));;
        $start  = mktime(0, 0, 0, $i, 1, $year);
        $end    = mktime(23, 59, 59, $i, $days, $year);
        $month[$i] = [ $start, $end ];
      }

      return $month;
    }
}
