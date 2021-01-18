<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\api\model\Admin;
use app\api\model\BirdSeize;
use app\api\model\BirdArea;
use app\api\model\BirdName;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;

class BirdNet extends Base
{
    protected $request;
    protected $worker;
    protected $net;
    protected $area;
    protected $bird;
    protected $config;
    protected $choices;

    public function __construct()
    {
        parent::__construct();
        $this->request = Request::instance();
        $this->worker = new Admin();
        $this->net = new BirdSeize();
        $this->area = new BirdArea();
        $this->bird = new BirdName();
        $this->config = new TableConfig();
        $this->choices = new TableConfigOption();
        $this->addBread('台账管理');
        $this->addBread('捕鸟网记录管理');
    }

    public function index()
    {
        $this->checkPowerWeb('bird_net_view', $this->admin['ap_codes']);
        $hasPower = $this->checkListEditPower('bird_net_view_edit', $this->admin['ap_codes'])?1:0;
        $this->assign('hasPower', $hasPower);

        $where = array();
        $pageParam = array();
        $height = input('height');
        $aid = input('aid');
        $worker1 = input('worker1');
        $worker2 = input('worker2');
        if($height){
            $where['height'] = $height;
        }
        if($aid){
            $where['aid'] = $aid;
        }
        if($worker1){
            $where['worker1'] = $worker1;
        }
        if($worker2){
            $where['worker2'] = $worker2;
        }
        $this->assign('height', $height);
        $this->assign('aid', $aid);
        $this->assign('worker1', $worker1);
        $this->assign('worker2', $worker2);
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

        //指定区域
        $area = input("area");
        $area_list = db('bird_area')->order('area_name asc')->select();
        $pageParam['area'] = $area;
        if ($area) {
            $where['area'] = $area;
        }
        $this->assign('area', $area);
        $this->assign('area_list', $area_list);

        //鸟名
        $bird_name = input("bird_name");
        $bird_name_list = db("bird_name")->order('bird_name asc')->select();
        $pageParam['bird_name'] = $bird_name;
        if ($bird_name) {
            $where['bird_name'] = $bird_name;
        }
        $this->assign('bird_name', $bird_name);
        $this->assign('bird_name_list', $bird_name_list);

        $count = db('bird_seize')->where ( $where )->count ();
        $this->assign('totalRows', $count);

        $this->setOption();
        if (input('act') === "export_excel") {
            $this->export_data_handle($where);
        }

//        $orderby = 'patrol_date desc, patrol_time desc';

        $this->assign('dataUrl', url('BirdNet/indexData', [
            'day'=>urlencode($day),
            'bird_name'=>$bird_name,
            'area'=>$area,
            'height'=>$height,
            'aid'=>$aid,
            'worker1'=>$worker1,
            'worker2'=>$worker2
            ]));


        return view();
    }

    public function indexData(){
        $where = array();
        $pageParam = array();
        $pageParam = array();

        $height = input('height');
        $aid = input('aid');
        $worker1 = input('worker1');
        $worker2 = input('worker2');
        if($height){
            $where['height'] = $height;
        }
        if($aid){
            $where['aid'] = $aid;
        }
        if($worker1){
            $where['worker1'] = $worker1;
        }
        if($worker2){
            $where['worker2'] = $worker2;
        }
        $this->assign('height', $height);
        $this->assign('aid', $aid);
        $this->assign('worker1', $worker1);
        $this->assign('worker2', $worker2);
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

        //指定区域
        $area = input("area");
        $area_list = db('bird_area')->order('area_name asc')->select();
        $pageParam['area'] = $area;
        if ($area) {
            $where['area'] = $area;
        }
        $this->assign('area', $area);
        $this->assign('area_list', $area_list);

        //鸟名
        $bird_name = input("bird_name");
        $bird_name_list = db("bird_name")->order('bird_name asc')->select();
        $pageParam['bird_name'] = $bird_name;
        if ($bird_name) {
            $where['bird_name'] = $bird_name;
        }
        $this->assign('bird_name', $bird_name);
        $this->assign('bird_name_list', $bird_name_list);

        $orderby = 'patrol_date desc, patrol_time desc';

        $page = input('page', 1);
        $pageCount = input('limit', 10);

        $list = db('bird_seize')->where ( $where )->order ( $orderby )
            ->limit ( ($page - 1) * $pageCount, $pageCount )->select ();
        $count = db('bird_seize')->where ( $where )->order ( $orderby )->count ();

//        var_dump($list);exit;
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
        $this->checkPowerWeb('bird_net_view_edit', $this->admin['ap_codes']);
        $data = input('');
        $dayList = explode(' ', $data['date_time']);
        $data['patrol_date'] = strtotime($dayList[0]);
//        $data['day_str'] = date('Y/m/d', strtotime($dayList[0]));

        $data['patrol_time'] = strtotime($data['date_time']);
//        $data['time_str'] = date('H:i', strtotime($data['date_time']));
        $result = $this->net->update_by_id($data['id'], $data);
        if ($result !== false) {
            \LogPageHelper::record('修改捕鸟网记录成功：' . $id, 'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改捕鸟网情记录失败：' . $id, 'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function add()
    {
        $this->checkPowerWeb('bird_net_add', $this->admin['ap_codes']);

        $model['patrol_date'] = date("Y/n/j", time());
        $model['patrol_time'] = date("H:i", time());
        $this->assign("model", $model);

        $this->setOption();

        $records = $this->net->historic_records();
        $this->assign("records", $records);

        $this->extra('bird_seize');

        return view();
    }

    public function doAdd()
    {
        $this->checkPowerWeb('bird_net_add', $this->admin['ap_codes']);
        $params = $this->request->param();

        $params['patrol_time'] = strtotime($params['patrol_date'] . ' ' . $params['patrol_time']);
        $params['patrol_date'] = strtotime($params['patrol_date']);

        $result = $this->net->insert_data($params);
        if (!$result) {
            \LogPageHelper::record('添加捕鸟网记录记录失败：' . $id, 'error', $this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加捕鸟网记录记录成功：' . $id, 'info', $this->logOption);
        $this->success('添加成功');
    }

    public function edit()
    {
        $this->checkPowerWeb('bird_net_update', $this->admin['ap_codes']);

        $this->setOption();

        $records = $this->net->historic_records();
        $this->assign("records", $records);

        $id = $this->request->param('id');
        $data = $this->net->find_by_id($id);
        $this->assign("model", $data);

        $this->extra('bird_seize', $data);

        return view();
    }

    public function detail()
    {
//        $this->checkPowerWeb('first_level_update', $this->admin['ap_codes']);

        $this->setOption();

        $id = $this->request->param('id');
        $data = $this->net->find_by_id($id);
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

        $this->extraDetail('bird_seize', $data);

        return view();
    }

    public function map(){
        $id = $this->request->param('id');
        $data = $this->net->find_by_id($id);
        $this->assign("lng", $data['lng_gcj02']);
        $this->assign("lat", $data['lat_gcj02']);
        $this->assign("diff", $data['diff']);
        return view();
    }

    public function doEdit()
    {
        $this->checkPowerWeb('bird_net_update', $this->admin['ap_codes']);
        $id = $this->request->param('id');
        $params = $this->request->param();
        $result = $this->net->update_by_id($id, $params);
        if ($result !== false) {
            \LogPageHelper::record('修改捕鸟网记录记录成功：' . $id, 'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改捕鸟网记录记录失败：' . $id, 'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function doDel()
    {
        $this->checkPowerWeb('bird_net_delete', $this->admin['ap_codes']);
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }

        $result = $this->net->delete_by_id($ids);
        if (!$result) {
            \LogPageHelper::record('删除捕鸟网记录记录失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除捕鸟网记录记录成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }

    public function setOption()
    {
        $items = $this->config->from_item("bird_seize");
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

        $worker_list = $this -> get_relevant_staff('bird_net_add');
        $this->assign("worker_list", $worker_list);

        $entering_list = $this -> get_entering_staff('bird_net_add');
        $this->assign("entering_list", $entering_list);

        $area_list = $this->area->area_info($this->admin['aid']);
        $this->assign("area_list", $area_list);

        $bird_name_list = $this->bird->bird_info();
        $this->assign("bird_name_list", $bird_name_list);
    }

    public function findAll()
    {
        $id = $this->request->param('id');
        $data = $this->net->find_by_id($id);

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

    public function export_data_handle($where)
    {
        $header = [
            ['id', '编号'],
            ['patrol_date', '日期'],
            ['time_slot', '时段'],
            ['worker1', '巡视人员一'],
            ['worker2', '巡视人员二'],
            ['patrol_time', '时间'],
            ['area', '区域'],
            ['bird_name', '鸟名'],
            ['bird_num', '数量'],
            ['height', '高度'],
            ['manual_number', '手册编号'],
            ['order', '目'],
            ['family', '科'],
            ['residence_type', '居留类型'],
            ['ecological_type', '生态类型'],
            ['body_length', '体长(cm)'],
            ['body_type', '体型'],
            ['risk', '危险性'],
        ];

        $extra = $this->config->extra_item('bird_seize');
        foreach ($extra as $extra) {
            $arr = [$extra['column_code'], $extra['column_name']];
            $header[] = $arr;
        }

        $data = $this->net->select_all($where);
        foreach ($data as $item) {
            $time = str_replace(':', '', $item['patrol_time']);
            $item['time_slot'] = $this->dividing_time_period($time);

            $propertys = $this->bird->find_by_name($item['bird_name']);
            $item['manual_number'] = $propertys['manual_number'];
            $item['order'] = $propertys['order'];
            $item['family'] = $propertys['family'];
            $item['residence_type'] = $propertys['residence_type'];
            $item['ecological_type'] = $propertys['ecological_type'];
            $item['body_length'] = $propertys['body_length'];
            $item['body_type'] = $propertys['body_type'];
            $item['risk'] = $propertys['risk'];
        }

        $filename = "捕鸟网记录";

        $this->export_excel($filename, $header, $data);
    }

}
