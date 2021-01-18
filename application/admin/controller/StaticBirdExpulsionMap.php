<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-20
 * Time: 9:42
 */

namespace app\admin\controller;

use think\Request;
use app\api\model\Admin;
use app\api\model\BirdDrive;

class StaticBirdExpulsionMap extends Base
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->addBread('区域统计');
        $this->addBread('鸟类危险活动记录统计');
        $this->model = new BirdDrive();
    }

    public function amap(){
        $this->checkPowerWeb('static_bird_expulsion_map_view',$this->admin['ap_codes']);//权限判断

        $day = urldecode(input('day'));
        $dayList = explode('~', $day);
        $begin_day = $dayList[0];
        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
        $begin_day = trim($begin_day);
        $end_day = trim($end_day);
        if (! empty ( $begin_day )) {
            $begin_day_int = strtotime ( $begin_day );
        }else{
            $begin_day=date('Y-m-d',time()-86400*90);
            $begin_day_int = strtotime ( $begin_day );

        }
        $begin_day_str = date('Y年m月d日',$begin_day_int);
        $pageParam ['begin_day'] = $begin_day;
        $this->assign ( 'begin_day_str', $begin_day_str );
        $this->assign ( 'begin_day', $begin_day );
        if (! empty ( $end_day )) {
            $end_day_int = strtotime ( $end_day . ' 23:59:59' );
            $pageParam ['end_day'] = $end_day;
        }
        $end_day_str = date('Y年m月d日',$end_day_int);
        $this->assign ( 'end_day', $end_day );
        $this->assign ( 'end_day_str', $end_day_str );
        $pageParam ['day'] = $begin_day.' ~ '.$end_day;
        $this->assign('day', $begin_day.' ~ '.$end_day);
        $this->assign('today',date('Y-m-d'));
        $where=array();
        $where['patrol_date']=array('between',[$begin_day_int,$end_day_int]);
        $recordList = $this->model->select_all($where);
        $lineList = [];
        $totalBirdNum = 0;
        foreach ($recordList as $k=>$v){
            $totalBirdNum += $recordList[$k]['bird_num'];
            if($recordList[$k]['activity_line_gcj02']){
                $line = [];
                $line['stamp'] = strtotime($recordList[$k]['patrol_date'].' '.$recordList[$k]['patrol_time']);
                $line['line'] = [];
                $lineStrList = explode('/', trim($recordList[$k]['activity_line_gcj02']));
                foreach ($lineStrList as $key=>$val){
                    $linePoint = explode(',', trim($val));
                    $line['line'][] = $linePoint;
                }
                $lineList[] = $line;
            }
        }
        $this->assign ( 'lineList', json_encode($lineList));
        $this->assign ( 'totalBirdNum', $totalBirdNum );
        $this->assign ( 'recordCount', count($recordList) );
        \LogPageHelper::record('查看危险鸟类活动记录统计页','info',$this->logOption);
        return view();
    }
}
