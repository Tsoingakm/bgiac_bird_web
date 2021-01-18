<?php

namespace app\admin\controller;

use think\Controller;

class Main extends Base
{
    public function index()
    {
        \LogPageHelper::record('进入系统后台','info',$this->logOption);
        $this->assign('admin',$this->admin);

        return $this->fetch();
    }

    public function loginOut(){
        \LogPageHelper::record('退出登录','info',$this->logOption);
        session('aid',null);
        cookie('aid', null);
        $this->redirect(url('Index/index'));
    }

    public function wellcome(){
        //获取所有台账的记录数

        $aid = $this->admin['aid'];

        //构建本年、本月
        $today = date('Y-m-d H:i:s');
        $endTimeStamp = strtotime($today);
        $beginMonth = date('Y-m').'-01 00:00:00';
        $beginMonthStamp = strtotime($beginMonth);
        $beginYear = date('Y').'-01-01 00:00:00';
        $beginYearStamp = strtotime($beginYear);



        //获取台账记录数
        $recordDataList = [];
        $tableList = [
            'bird_condition'=>'一级鸟情记录',
            'bird_seize'=>'捕鸟网记录',
            'bird_drive'=>'危险鸟类驱赶记录',
            'work_insect'=>'草坪维护工作记录',
            'work_lawn'=>'病媒防治记录',
            'work_vector'=>'昆虫消杀记录',
            'device_maintenance'=>'设备维护记录'
        ];
        foreach ($tableList as $k=>$v){
            $whereMonth = [];
            $whereYear = [];
            switch($k){
                case 'bird_condition':
                    $whereMonth['day_int'] = ['between', [$beginMonthStamp, $endTimeStamp]];
                    $whereYear['day_int'] = ['between', [$beginYearStamp, $endTimeStamp]];
                    break;
                case 'bird_seize':
                case 'bird_drive':
                    $whereMonth['patrol_date'] = ['between', [$beginMonthStamp, $endTimeStamp]];
                    $whereYear['patrol_date'] = ['between', [$beginYearStamp, $endTimeStamp]];
                    break;
                case 'work_insect':
                case 'work_lawn':
                case 'work_vector':
                    $whereMonth['working_date'] = ['between', [$beginMonthStamp, $endTimeStamp]];
                    $whereYear['working_date'] = ['between', [$beginYearStamp, $endTimeStamp]];
                    break;
                case 'device_maintenance':
                    $whereMonth['maintenance_time'] = ['between', [$beginMonthStamp, $endTimeStamp]];
                    $whereYear['maintenance_time'] = ['between', [$beginYearStamp, $endTimeStamp]];
                    break;
                default:
                    break;
            }
            $records = [];
            $records['monthCount'] = db($k)->where($whereMonth)->count();
            $records['yearCount'] = db($k)->where($whereYear)->count();
            $records['totalCount'] = db($k)->count();
            $records['type'] = $v;
            $recordDataList[] = $records;
        }
        $this->assign('recordDataList', $recordDataList);

        //获取工作计划表列表
        $scheduleList = db('schedule')->where ( ['aid'=>$aid] )->order ( 'index desc, addtime desc' )->limit ( 0, 10 )
            ->select ();
        foreach ($scheduleList as $k=>$v){
            if($scheduleList[$k]['deal_time']){
                $scheduleList[$k]['deal_time'] = date('Y-m-d', $scheduleList[$k]['deal_time']);
            }
        }
        $this->assign('scheduleList', $scheduleList);

        //获取文件通知记录列表
        $articleList = db('article')->order ( 'addtime desc' )->limit(0, 8)->select();
        foreach ($articleList as $k=>$v){
            $articleList[$k]['addtime'] = date('Y-m-d', $articleList[$k]['addtime']);
            $signData = db('article_sign')->where(['article_id'=>$articleList[$k]['id'], 'aid'=>$aid, 'status'=>2])->find();
            $articleList[$k]['status'] = 0;
            if($signData){
                $articleList[$k]['status'] = 1;
            }
        }
        $this->assign('articleList', $articleList);

        //获取过去30天台账记录统计数据
        $dayList = [];
        $labelList = [];
        $dataList = [];

        $past30Day = date('Y-m-d', strtotime('-30 days'));
        $past30DayStamp = strtotime($past30Day);


        foreach ($tableList as $k=>$v){
            $labelList[] = $v;
            $recordList = [];
            $i = $past30DayStamp;
            while($i <= $endTimeStamp ){
                $where = [];
                $currentDate = date('Y-m-d', $i);
                $dayList[] = $currentDate;
                $beginStamp = strtotime($currentDate.' 00:00:00');
                $endStamp = strtotime($currentDate.' 23:59:59');
                switch($k){
                    case 'bird_condition':
                        $where['day_int'] = ['between', [$beginStamp, $endStamp]];
                        break;
                    case 'bird_seize':
                    case 'bird_drive':
                        $where['patrol_date'] = ['between', [$beginStamp, $endStamp]];
                        break;
                    case 'work_insect':
                    case 'work_lawn':
                    case 'work_vector':
                        $where['working_date'] = ['between', [$beginStamp, $endStamp]];
                        break;
                    case 'device_maintenance':
                        $where['maintenance_time'] = ['between', [$beginStamp, $endStamp]];
                        break;
                    default:
                        break;
                }
                $recordList[] = db($k)->where($where)->count();
                $i += (3600 * 24);
            }
            $data = [];
            $data['name'] = $v;
            $data['type'] = 'line';
            $data['data'] = $recordList;
            $dataList[] = $data;
        }

        $dayList = array_unique($dayList);

        $this->assign('dayList', json_encode($dayList, JSON_UNESCAPED_UNICODE));
        $this->assign('labelList', json_encode($labelList, JSON_UNESCAPED_UNICODE));
        $this->assign('dataList', json_encode($dataList, JSON_UNESCAPED_UNICODE));

//        var_dump($dayList);exit;

        return view();
    }
}
