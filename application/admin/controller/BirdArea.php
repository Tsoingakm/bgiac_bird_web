<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-19
 * Time: 9:31
 */

namespace app\admin\controller;

use think\Request;
use app\api\model\BirdArea as ba;
use app\api\model\Admin;

class BirdArea extends Base
{
    protected $request;
    protected $model;
    protected $worker;

    public function __construct()
    {
        parent::__construct();
        $this->request = Request::instance();
        $this->model = new ba();
        $this->worker = new Admin();
        $this->addBread('区域管理');
        $this->addBread('维护鸟情区域');
    }

    public function index(){
        $this->checkPowerWeb('bird_area_view', $this->admin['ap_codes']);
        $where = array();
        $pageParam = array();

        $day = urldecode(input('day'));
        $dayList = explode('~', $day);
        $begin_day = $dayList[0];
        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
        $begin_day = trim($begin_day);
        $end_day = trim($end_day);
        if (!empty ($begin_day)) {
            $begin_day_int = strtotime($begin_day);
        } else {
            $begin_day = date('Y-m-d', time() - 86400 * 30);
            $begin_day_int = strtotime($begin_day);
        }
        $pageParam ['begin_day'] = $begin_day;
        $this->assign('begin_day', $begin_day);
        if (!empty ($end_day)) {
            $end_day_int = strtotime($end_day . ' 23:59:59');
        }
        $pageParam ['end_day'] = $end_day;
        $pageParam ['day'] = $begin_day.' ~ '.$end_day;
        $this->assign('day', $begin_day.' ~ '.$end_day);
        $this->assign('end_day', $end_day);
        $this->assign('today', date('Y-m-d'));
        $where['addtime'] = [ 'between', [ $begin_day_int, $end_day_int ] ];

        $aid = input('aid');
        if($aid){
            $where['aid'] = $aid;
            $pageParam ['aid'] = $aid;
        }
        $this->assign('aid', $aid);

        $valid = input('valid');
        if(is_numeric($valid)){
            $where['valid'] = $valid;
            $pageParam ['valid'] = $valid;
        }
        $this->assign('valid', $valid);
        $count = db('bird_area')->where ( $where )->count ();
        $this->assign('totalRows', $count);
        $staffList = $this->get_all_staff();
        $this->assign('worker_list', $staffList);

        $this->assign('dataUrl', url('BirdArea/indexData', [
            'day'=>urlencode($day),
            'valid'=>$valid,
            'aid'=>$aid
        ]));

        return view();
    }

    public function indexData(){

        $where = array();

        $day = urldecode(input('day'));
        $dayList = explode('~', $day);
        $begin_day = $dayList[0];
        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
        $begin_day = trim($begin_day);
        $end_day = trim($end_day);
        if (!empty ($begin_day)) {
            $begin_day_int = strtotime($begin_day);
        } else {
            $begin_day = date('Y-m-d', time() - 86400 * 30);
            $begin_day_int = strtotime($begin_day);
        }
        if (!empty ($end_day)) {
            $end_day_int = strtotime($end_day . ' 23:59:59');
        }
        $where['addtime'] = [ 'between', [ $begin_day_int, $end_day_int ] ];

        $aid = input('aid');
        if($aid){
            $where['aid'] = $aid;
        }

        $valid = input('valid');
        if(is_numeric($valid)){
            $where['valid'] = $valid;
        }
        $count = db('bird_area')->where ( $where )->count ();
        $list = db('bird_area')->where ( $where )->select ();
        foreach ($list as $k=>$v){
            $list[$k]['date'] = date('Y-m-d H:i:s', $list[$k]['addtime']);
            $admin = $this->worker->find_by_id($list[$k]['aid']);
            if($admin){
                $list[$k]['staff'] = $admin->real_name;
            }
        }
        $data = [];
        $data['code']    = 0;
        $data['msg']    = "查询成功";
        $data['count']  = $count;
        $data['data']   = $list;
        return $data;
    }

    public function add(){
        $this->checkPowerWeb('bird_area_add', $this->admin['ap_codes']);
        return view();
    }

    public function doAdd(){
        $this->checkPowerWeb('bird_area_add', $this->admin['ap_codes']);
        $params = $this->request->param();
        if(!isset($params['points'])){
            $this->error('请绘制多边形区域');
        }
        $pointList = explode(' ', $params['points']);
        if(count($pointList) < 3){
            $this->error('请绘制封闭的多边形区域');
        }
        $params['aid'] = $this->admin['aid'];

        $result = $this->model->insert_data($params);
        if (!$result) {
            \LogPageHelper::record('添加鸟情区域失败：' . $id, 'error', $this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加鸟情区域成功：' . $id, 'info', $this->logOption);
        $this->success('添加成功');
    }

    public function edit(){
        $this->checkPowerWeb('bird_area_edit', $this->admin['ap_codes']);
        $id = $this->request->param('id');
        $data = $this->model->find_by_id($id);
        if(!$data){
            $this->error('未找到记录');
        }
        $this->assign("model", $data);

        return view();
    }

    public function doEdit(){
        $this->checkPowerWeb('bird_area_edit', $this->admin['ap_codes']);
        $params = $this->request->param();
        if(!isset($params['points'])){
            $this->error('请绘制多边形区域');
        }
        $pointList = explode(' ', $params['points']);
        if(count($pointList) < 3){
            $this->error('请绘制封闭的多边形区域');
        }
        if(!isset($params['valid'])){
            $params['valid'] = -1;
        }
        $result = $this->model->update_by_id($params['id'], $params);
        if (!$result) {
            \LogPageHelper::record('编辑鸟情区域失败：' . $id, 'error', $this->logOption);
            $this->error('编辑失败，请稍后再试');
        }
        \LogPageHelper::record('编辑鸟情区域成功：' . $id, 'info', $this->logOption);
        $this->success('编辑成功');
    }

    public function doDel()
    {
        $this->checkPowerWeb('bird_area_del', $this->admin['ap_codes']);
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }

        $result = $this->model->delete_by_id($ids);
        if (!$result) {
            \LogPageHelper::record('删除鸟情区域失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除鸟情区域成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }
}
