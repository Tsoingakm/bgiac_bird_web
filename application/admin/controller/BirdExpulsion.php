<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\api\model\Admin;
use app\api\model\BirdDrive;
use app\api\model\BirdArea;
use app\api\model\BirdName;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;

class BirdExpulsion extends Base
{
    protected $request;
    protected $worker;
    protected $expulsion;
    protected $area;
    protected $bird;
    protected $config;
    protected $choices;

    public function __construct()
    {
        parent::__construct();
        $this->request = Request::instance();
        $this->worker = new Admin();
        $this->expulsion = new BirdDrive();
        $this->area = new BirdArea();
        $this->bird = new BirdName();
        $this->config = new TableConfig();
        $this->choices = new TableConfigOption();
        $this->addBread('台账管理');
        $this->addBread('危险鸟类驱赶记录管理');
    }

    public function index()
    {
        $this->checkPowerWeb('bird_expulsion_view', $this->admin['ap_codes']);
        $hasPower = $this->checkListEditPower('bird_expulsion_view_edit', $this->admin['ap_codes'])?1:0;
        $this->assign('hasPower', $hasPower);

        $where = array();
        $pageParam = array();
        $area = input('area');
        $height = input('height');
        $aid = input('aid');
        if($area){
            $where['area'] = $area;
        }
        if($height){
            $where['height'] = $height;
        }
        if($aid){
            $where['aid'] = $aid;
        }
        $this->assign('area', $area);
        $this->assign('height', $height);
        $this->assign('aid', $aid);
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
        $where['patrol_date'] = [ 'between', [ $begin_day_int, $end_day_int ] ];

        //危险等级
        $bird_drive_level = input("bird_drive_level");
        $bird_drive_level_list = db('table_config_option')->where(['index_code' => "bird_drive_level"])->order('sort asc')->select();
        $pageParam['bird_drive_level'] = $bird_drive_level;
        if ($bird_drive_level) {
            $where['danger_level'] = $bird_drive_level;
        }
        $this->assign('bird_drive_level', $bird_drive_level);
        $this->assign('bird_drive_level_list', $bird_drive_level_list);

        //鸟名
        $bird_name = input("bird_name");
        $bird_name_list = db("bird_name")->order('bird_name asc')->select();
        $pageParam['bird_name'] = $bird_name;
        if ($bird_name) {
            $where['bird_name'] = $bird_name;
        }
        $this->assign('bird_name', $bird_name);
        $this->assign('bird_name_list', $bird_name_list);

        //指定分类
        $bird_type = input("bird_type");
        $bird_type_list = db("bird_name")->field('ecological_type as bird_type')->group('ecological_type')->order('ecological_type asc')->select();
        $pageParam['bird_type'] = $bird_type;
        if ($bird_type) {
            $where['bird_type'] = $bird_type;
        }
        $this->assign('bird_type', $bird_type);
        $this->assign('bird_type_list', $bird_type_list);

        $count = db('bird_drive')->where ( $where )->count ();
        $this->assign('totalRows', $count);

        $this->setOption();

        $this->assign('dataUrl', url('BirdExpulsion/indexData', [
            'day'=>urlencode($day),
            'bird_type'=>$bird_type,
            'bird_name'=>$bird_name,
            'bird_drive_level'=>$bird_drive_level,
            'area'=>$area,
            'height'=>$height,
            'aid'=>$aid
        ]));

        if (input('act') === "export_excel") {
            $this->export_data_handle($where);
        }

        return view();
    }

    public function indexData(){
        $where = array();
        $pageParam = array();
        $area = input('area');
        $height = input('height');
        $aid = input('aid');
        if($area){
            $where['area'] = $area;
        }
        if($height){
            $where['height'] = $height;
        }
        if($aid){
            $where['aid'] = $aid;
        }
        $this->assign('area', $area);
        $this->assign('height', $height);
        $this->assign('aid', $aid);
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
        $where['patrol_date'] = [ 'between', [ $begin_day_int, $end_day_int ] ];

        //危险等级
        $bird_drive_level = input("bird_drive_level");
        $bird_drive_level_list = db('table_config_option')->where(['index_code' => "bird_drive_level"])->order('sort asc')->select();
        $pageParam['bird_drive_level'] = $bird_drive_level;
        if ($bird_drive_level) {
            $where['danger_level'] = $bird_drive_level;
        }
        $this->assign('bird_drive_level', $bird_drive_level);
        $this->assign('bird_drive_level_list', $bird_drive_level_list);

        //鸟名
        $bird_name = input("bird_name");
        $bird_name_list = db("bird_name")->order('bird_name asc')->select();
        $pageParam['bird_name'] = $bird_name;
        if ($bird_name) {
            $where['bird_name'] = $bird_name;
        }
        $this->assign('bird_name', $bird_name);
        $this->assign('bird_name_list', $bird_name_list);

        //指定分类
        $bird_type = input("bird_type");
        $bird_type_list = db("bird_name")->field('ecological_type as bird_type')->group('ecological_type')->order('ecological_type asc')->select();
        $pageParam['bird_type'] = $bird_type;
        if ($bird_type) {
            $where['bird_type'] = $bird_type;
        }
        $this->assign('bird_type', $bird_type);
        $this->assign('bird_type_list', $bird_type_list);
        $page = input('page', 1);
        $pageCount = input('limit', 10);
        $orderby = 'patrol_date desc, patrol_time desc';

        $list = db('bird_drive')->where ( $where )->order ( $orderby )
            ->limit ( ($page - 1) * $pageCount , $pageCount )->select ();
        $count = db('bird_drive')->where ( $where )->order ( $orderby )->count ();

        foreach ($list as $k=>$v){
            $list[$k]['date_time'] = date('Y-m-d', $list[$k]['patrol_date']).' '. date('H:i:s', $list[$k]['patrol_time']);
        }
        $data = [];
        $data['code']    = 0;
        $data['msg']    = "查询成功";
        $data['count']  = $count;
        $data['data']   = $list;
        return $data;

    }

    public function changeData(){
        $this->checkPowerWeb('bird_expulsion_view_edit', $this->admin['ap_codes']);
        $data = input('');
        $dayList = explode(' ', $data['date_time']);
        $data['patrol_date'] = strtotime($dayList[0]);
        $data['patrol_time'] = strtotime($data['date_time']);
        $result = $this->expulsion->update_by_id($data['id'], $data);
        if ($result !== false) {
            \LogPageHelper::record('修改一级鸟情记录成功：' . $id, 'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改一级鸟情记录失败：' . $id, 'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function add()
    {
        $this->checkPowerWeb('bird_expulsion_add', $this->admin['ap_codes']);

        $model['patrol_date'] = date("Y/n/j", time());
        $model['patrol_time'] = date("H:i", time());
        $this->assign("model", $model);

        $this->setOption();

        $records = $this->expulsion->historic_records();
        $this->assign("records", $records);

        $this->extra('bird_drive');

        return view();
    }

    public function addLine()
    {
        $points = input('points');
//        var_dump($points);exit;
//        $pointList = [];
//        if($points && $points != ''){
//            $pointStrList = explode('/', trim($points));
//            foreach ($pointStrList as $k=>$v){
//                $point = explode(',', $v);
//                $pointList[] = $point;
//            }
//        }
        $this->assign("points", $points);
        return view();
    }

    public function doAdd()
    {
        $this->checkPowerWeb('bird_expulsion_add', $this->admin['ap_codes']);
        $params = $this->request->param();


        $params['patrol_time'] = strtotime($params['patrol_date'] . ' ' . $params['patrol_time']);
        $params['patrol_date'] = strtotime($params['patrol_date']);

        $result = $this->expulsion->insert_data($params);
        if (!$result) {
            \LogPageHelper::record('添加危险鸟类驱赶记录记录失败：' . $id, 'error', $this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加危险鸟类驱赶记录记录成功：' . $id, 'info', $this->logOption);
        $this->success('添加成功');
    }

    public function edit()
    {
        $this->checkPowerWeb('bird_expulsion_update', $this->admin['ap_codes']);

        $this->setOption();

        $records = $this->expulsion->historic_records();
        $this->assign("records", $records);

        $id = $this->request->param('id');
        $data = $this->expulsion->find_by_id($id);
        if($data->activity_line_gcj02 && $data->activity_line_gcj02 != ''){
            $this->assign("hasDraw", 1);
        }else{
            $this->assign("hasDraw", 0);
        }
        $this->assign("model", $data);

        $this->extra('bird_drive', $data);

        return view();
    }

    public function detail()
    {
//        $this->checkPowerWeb('first_level_update', $this->admin['ap_codes']);

        $this->setOption();

        $id = $this->request->param('id');
        $data = $this->expulsion->find_by_id($id);
        $staff = Admin::where(['aid'=>$data['aid']])->find();
        $data['staff'] = $staff['real_name'];
        $hasAddress = false;
        if(($data['lat_gcj02'] && $data['lng_gcj02']) && ($data['lat_gcj02'] >0 && $data['lng_gcj02'] > 0)){
            $hasAddress = true;
        }else{
            $data['lat_gcj02'] = 0;
            $data['lng_gcj02'] = 0;
        }
        $this->assign("hasAddress", $hasAddress);
        $this->assign("model", $data);

        $this->extraDetail('bird_drive', $data);

        return view();
    }

    public function map(){
        $id = $this->request->param('id');
        $data = $this->expulsion->find_by_id($id);
        $this->assign("lng", $data['lng_gcj02']);
        $this->assign("lat", $data['lat_gcj02']);
        $this->assign("diff", $data['diff']);
        return view();
    }

    public function doEdit()
    {
        $this->checkPowerWeb('bird_expulsion_update', $this->admin['ap_codes']);
        $id = $this->request->param('id');
        $params = $this->request->param();
        $result = $this->expulsion->update_by_id($id, $params);
        if ($result !== false) {
            \LogPageHelper::record('修改危险鸟类驱赶记录记录成功：' . $id, 'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改危险鸟类驱赶记录记录失败：' . $id, 'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function doDel()
    {
        $this->checkPowerWeb('bird_expulsion_delete', $this->admin['ap_codes']);
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }

        $result = $this->expulsion->delete_by_id($ids);
        if (!$result) {
            \LogPageHelper::record('删除危险鸟类驱赶记录记录失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除危险鸟类驱赶记录记录成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }

    public function setOption()
    {
        $items = $this->config->from_item("bird_drive");
        foreach ($items as $item) {
            $option = $this->choices->get_options($item['index_code']);
            $options[] = [
                'index_code' => $item['index_code'],
                'options' => $option
            ];
        }

        foreach ($options as $key => $value) {
            $this->assign($value['index_code'] . "_list", $value['options']);
        }

        $worker_list = $this -> get_relevant_staff('bird_expulsion_add');
        $this->assign("worker_list", $worker_list);

        $entering_list = $this -> get_entering_staff('bird_expulsion_add');
        $this->assign("entering_list", $entering_list);

        $area_list = $this->area->area_info($this->admin['aid']);
        $this->assign("area_list", $area_list);

        $bird_name_list = $this->bird->bird_info();
        $this->assign("bird_name_list", $bird_name_list);
    }

    public function findAll()
    {
        $id = $this->request->param('id');
        $data = $this->expulsion->find_by_id($id);

        $return_data['status'] = true;
        $return_data['info'] = "查询成功";
        $return_data['data'] = $data;

        if (!$data) {
            $return_data['status'] = false;
            $return_data['info'] = "复制失败";
            unset($return_data['data']);
        }

        return $return_data;
    }

    public function export_data_handle($where){
        $filename = "危险鸟类驱赶记录";

        $header = [
            ['id',              '编号'],
            ['worker1',         '巡视人员一'],
            ['worker2',         '巡视人员二'],
            ['patrol_date',     '日期'],
            ['patrol_time',     '时间'],
            ['time_slot',       '时段'],
            ['bird_name',       '鸟名'],
            ['bird_num',        '数量'],
            ['area',            '区域'],
            ['height',          '高度'],
            ['drive_method1',   '驱赶措施1'],
            ['bullet_num1',     '弹药数量1'],
            ['drive_result1',   '驱赶效果1'],
            ['drive_method2',   '驱赶措施2'],
            ['bullet_num2',     '弹药数量2'],
            ['drive_result2',   '驱赶效果2'],
            ['danger_level',    '危险等级'],
            ['manual_number',   '手册编号'],
            ['order',           '目'],
            ['family',          '科'],
            ['residence_type',  '居留类型'],
            ['ecological_type', '生态类型'],
            ['body_length',     '体长(cm)'],
            ['body_type',       '体型'],
            ['risk',            '危险性'],
        ];

        $extra = $this->config->extra_item('bird_drive');
        foreach ($extra as $extra) {
            $arr = [$extra['column_code'], $extra['column_name']];
            $header[] = $arr;
        }

        $data = $this->expulsion->select_all($where);

        foreach ($data as $item) {
            $time = str_replace(':', '', $item['patrol_time']);
            $item['time_slot'] = $this->dividing_time_period($time);

            $propertys = $this->bird->find_by_name($item['bird_name']);
            $item['manual_number']    = $propertys['manual_number'];
            $item['order']            = $propertys['order'];
            $item['family']           = $propertys['family'];
            $item['residence_type']   = $propertys['residence_type'];
            $item['ecological_type']  = $propertys['ecological_type'];
            $item['body_length']      = $propertys['body_length'];
            $item['body_type']        = $propertys['body_type'];
            $item['risk']             = $propertys['risk'];
        }

        $this->export_excel($filename, $header, $data);
    }

}
