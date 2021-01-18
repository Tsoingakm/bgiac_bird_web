<?php

namespace app\admin\controller;

use think\Controller;

class LogPage extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->addBread('日志管理');
        $this->addBread('日志列表');
    }

    /**
     * 权限列表
     * @return \think\response\View
     */
    public function index()
    {
        $this->checkPowerWeb('log_view',$this->admin['ap_codes']);//权限判断
        //var_dump($pageSize);
        $keyword = input('keyword');
        $where = array();
        $pageParam = array();
        if ($keyword) {
            $where['content|admin_name'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }
        $begin_day = input ( 'begin_day' );
        $end_day = input ( 'end_day' );
//        $day = urldecode(input('day'));
//        $dayList = explode('~', $day);
//        $begin_day = $dayList[0]? $dayList[0]: date("Y-m-d", strtotime("-1 month"));
//        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
//        $begin_day = trim($begin_day);
//        $end_day = trim($end_day);
        if (! empty ( $begin_day )) {
            $begin_day_int = strtotime ( $begin_day );
            $where ['addtime'] = array (
                'gt',
                $begin_day_int
            );
            $pageParam ['begin_day'] = $begin_day;
            $this->assign ( 'begin_day', $begin_day );
        }

        if (! empty ( $end_day )) {
            $end_day_int = strtotime ( $end_day . ' 23:59:59' );
            $where ['addtime'] = array (
                'lt',
                $end_day_int
            );
            $pageParam ['end_day'] = $end_day;
            $this->assign ( 'end_day', $end_day );
        }
//        var_dump($where);exit;
//        $pageParam ['day'] = $begin_day.' ~ '.$end_day;
//        $this->assign('day', $begin_day.' ~ '.$end_day);
        $this->assign('today', date('Y-m-d'));
        $orderby = 'log_id desc';
        $list = $this->simpleGetList('log_page', $where, $orderby, $pageParam);

        $this->assign('list',$list['list']);
        \LogPageHelper::record('查看日志列表页','info',$this->logOption);
        return view();
    }



    /**
     * 详情
     */
    public function detail()
    {
        $this->checkPowerWeb('log_detail',$this->admin['ap_codes']);//权限判断
        $log_id = input('log_id');
        if (!$log_id) {
            $this->error('缺少log_id');
        }
        $model = db('log_page')->where(['log_id' => $log_id])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $this->assign('isEdit',1);
        $this->assign('model', $model);
        $this->getPowers();
        \LogPageHelper::record('查看日志详情：'.$log_id,'info',$this->logOption);
        return view();
    }


    /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doDel(){
        $this->checkPowerWeb('log_del',$this->admin['ap_codes']);//权限判断
        $ids=input('ids');
        if(!$ids){
            $this->error('参数不正确');
        }
        $where['log_id']=['in',$ids];
        $res=db('log_page')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除日志失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除日志:'.$ids.' 成功','info',$this->logOption);
            $this->success('删除成功');

        }
    }



}
