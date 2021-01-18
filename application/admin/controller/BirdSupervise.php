<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\api\model\Admin;
use app\api\model\BirdCondition;
use app\api\model\BirdArea;
use app\api\model\BirdName;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;

class BirdSupervise extends Base
{

    protected $request;
    protected $worker;
    protected $supervise;
    protected $area;
    protected $bird;
    protected $config;
    protected $choices;

    public function __construct()
    {
        parent::__construct();
        $this->request = Request::instance();
        $this->worker = new Admin();
        $this->supervise = new BirdCondition();
        $this->area = new BirdArea();
        $this->bird = new BirdName();
        $this->config = new TableConfig();
        $this->choices = new TableConfigOption();
        $this->addBread('台账管理');
        $this->addBread('一级鸟情记录管理');
    }

    public function index()
    {
        $this->checkPowerWeb('first_level_view', $this->admin['ap_codes']);
        $hasPower = $this->checkListEditPower('first_level_view_edit', $this->admin['ap_codes'])?1:0;
        $this->assign('hasPower', $hasPower);
        $where = array();
        $pageParam = array();
//        $a = input('');
//        var_dump($a);exit;
        $realNess = input('realness');
        $area = input('area');
        $describe = input('describe');
        if($realNess){
            $where['realness'] = $realNess;
        }
        if($area){
            $where['area'] = $area;
        }
        if($describe){
            $where['describe'] = $describe;
        }
        $this->assign('realness', $realNess);
        $this->assign('area', $area);
        $this->assign('describe', $describe);
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

        $where['day_int'] = ['between', [$begin_day_int, $end_day_int]];
        //巡视序号
        $view_number = input('view_number');
        $view_number_list = db('table_config_option')->where(['index_code' => 'view_number'])->order('sort asc')->select();
        $pageParam['view_number'] = $view_number;
        if ($view_number) {
            $where['view_number'] = $view_number;
        }
        $this->assign('view_number', $view_number);
        $this->assign('view_number_list', $view_number_list);
        //鸟名
        $bird_name = input("bird_name");
        $bird_name_list = db("bird_name")->order('bird_name asc')->select();
        $pageParam['bird_name'] = $bird_name;
        if ($bird_name) {
            $where['bird_name'] = $bird_name;
        }
        $this->assign('bird_name', $bird_name);
        $this->assign('bird_name_list', $bird_name_list);

        $count = db('bird_condition')->where ( $where )->count ();
        $this->assign('totalRows', $count);

        if (input('act') === "export_excel") {
            $this->export_data_handle($where);
        }

        if (input('act') === "export_txt") {
            $this->export_txt($where);
        }

        $this->setOption();

        $this->assign('dataUrl', url('BirdSupervise/indexData', [
            'day'=>urlencode($day),
            'view_number'=>$view_number,
            'bird_name'=>$bird_name,
            'realness'=>$realNess,
            'area'=>$area,
            'describe'=>$describe
            ]));

        return view();
    }

    public function indexData(){
        $where = array();
        $pageParam = array();
        $realNess = input('realness');
        $area = input('area');
        $describe = input('describe');
        if($realNess){
            $where['realness'] = $realNess;
        }
        if($area){
            $where['area'] = $area;
        }
        if($describe){
            $where['describe'] = $describe;
        }
        $this->assign('realness', $realNess);
        $this->assign('area', $area);
        $this->assign('describe', $describe);
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

        $where['day_int'] = ['between', [$begin_day_int, $end_day_int]];
        //巡视序号
        $view_number = input('view_number');
        $view_number_list = db('table_config_option')->where(['index_code' => 'view_number'])->order('sort asc')->select();
        $pageParam['view_number'] = $view_number;
        if ($view_number) {
            $where['view_number'] = $view_number;
        }
        $this->assign('view_number', $view_number);
        $this->assign('view_number_list', $view_number_list);
        //鸟名
        $bird_name = input("bird_name");
        $bird_name_list = db("bird_name")->order('bird_name asc')->select();
        $pageParam['bird_name'] = $bird_name;
        if ($bird_name) {
            $where['bird_name'] = $bird_name;
        }
        $this->assign('bird_name', $bird_name);
        $this->assign('bird_name_list', $bird_name_list);

        $orderby = 'day_int desc, time_int desc';
        $page = input('page', 1);
        $pageCount = input('limit', 10);
        $list = db('bird_condition')->where ( $where )->order ( $orderby )
            ->limit (($page - 1) * $pageCount, $pageCount)->select ();
        $count = db('bird_condition')->where ( $where )->order ( $orderby )->count ();
        foreach ($list as $k=>$v){
            $list[$k]['day_int'] = date('Y-m-d', $list[$k]['day_int']);
            $list[$k]['time_int'] = date('H:i:s', $list[$k]['time_int']);
            $list[$k]['date_time'] = $list[$k]['day_int'] .' '. $list[$k]['time_int'];
        }
        $data = [];
        $data['code']    = 0;
        $data['msg']    = "查询成功";
        $data['count']  = $count;
        $data['data']   = $list;
        return $data;
    }

    public function add()
    {
        $this->checkPowerWeb('first_level_add', $this->admin['ap_codes']);

        $model['day_str'] = date("Y/n/j", time());
        $model['time_str'] = date("H:i", time());
        $this->assign("model", $model);

        $this->setOption();

        $records = $this->supervise->historic_records();
        $this->assign("records", $records);

        $this->extra('bird_condition');

        return view();
    }

    public function doAdd()
    {
        $this->checkPowerWeb('first_level_add', $this->admin['ap_codes']);
        $params = $this->request->param();
        $result = $this->supervise->insert_data($params);
        if (!$result) {
            \LogPageHelper::record('添加一级鸟情记录失败：' . $id, 'error', $this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加一级鸟情记录成功：' . $id, 'info', $this->logOption);
        $this->success('添加成功');
    }

    public function edit()
    {
        $this->checkPowerWeb('first_level_update', $this->admin['ap_codes']);

        $this->setOption();

        $records = $this->supervise->historic_records($this->admin['aid']);
        $this->assign("records", $records);

        $id = $this->request->param('id');
        $data = $this->supervise->find_by_id($id);
        $this->assign("model", $data);

        $this->extra('bird_condition', $data);

        return view();
    }

    public function detail()
    {
//        $this->checkPowerWeb('first_level_update', $this->admin['ap_codes']);

        $this->setOption();

        $id = $this->request->param('id');
        $data = $this->supervise->find_by_id($id);
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

        $this->extraDetail('bird_condition', $data);

        return view();
    }

    public function map(){
        $id = $this->request->param('id');
        $data = $this->supervise->find_by_id($id);
        $this->assign("lng", $data['lng_gcj02']);
        $this->assign("lat", $data['lat_gcj02']);
        $this->assign("diff", $data['diff']);
        return view();
    }

    public function doEdit()
    {
        $this->checkPowerWeb('first_level_update', $this->admin['ap_codes']);
        $id = $this->request->param('id');
        $params = $this->request->param();
        $result = $this->supervise->update_by_id($id, $params);
        if ($result !== false) {
            \LogPageHelper::record('修改一级鸟情记录成功：' . $id, 'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改一级鸟情记录失败：' . $id, 'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function doDel()
    {
        $this->checkPowerWeb('first_level_delete', $this->admin['ap_codes']);
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }

        $result = $this->supervise->delete_by_id($ids);
        if (!$result) {
            \LogPageHelper::record('删除一级鸟情记录失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除一级鸟情记录成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }

    public function setOption()
    {
        $items = $this->config->from_item("bird_condition");

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

        $worker_list = $this -> get_relevant_staff('first_level_add');
        $this->assign("worker_list", $worker_list);

        $entering_list = $this -> get_entering_staff('first_level_add');
        $this->assign("entering_list", $entering_list);

        $area_list = $this->area->area_info($this->admin['aid']);
        $this->assign("area_list", $area_list);

        $bird_name_list = $this->bird->bird_info();
        $this->assign("bird_name_list", $bird_name_list);
    }

    public function findPublic()
    {
        $params = $this->request->param();
        $data = $this->supervise->find_by_No($params);

        $return_data['status'] = true;
        $return_data['info'] = "查询成功";
        $return_data['data'] = $data;

        if (!$data) {
            $return_data['status'] = false;
            $return_data['info'] = "没有可复制的公共部分";
            unset($return_data['data']);
        }

        return $return_data;
    }

    public function findAll()
    {
        $id = $this->request->param('id');
        $data = $this->supervise->find_by_id($id);

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

    public function changeData(){
        $this->checkPowerWeb('first_level_update', $this->admin['ap_codes']);
        $data = input('');
        $dayList = explode(' ', $data['date_time']);
        $data['day_int'] = strtotime($dayList[0]);
        $data['day_str'] = date('Y/m/d', strtotime($dayList[0]));

        $data['time_int'] = strtotime($data['date_time']);
        $data['time_str'] = date('H:i', strtotime($data['date_time']));
        $result = $this->supervise->update_by_id($data['id'], $data);
        if ($result !== false) {
            \LogPageHelper::record('修改一级鸟情记录成功：' . $id, 'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改一级鸟情记录失败：' . $id, 'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function export_data_handle($where)
    {
        $filename = "一级鸟情记录";

        $header = [
            ['id', '编号'],
            ['worker1', '巡视人员一'],
            ['worker2', '巡视人员二'],
            ['day_int', '日期'],
            ['time_int', '时间'],
            ['view_number', '巡视序号'],
            ['bird_name', '鸟名'],
            ['realness', '置信度'],
            ['bird_num', '数量'],
            ['area', '观测区域'],
            ['view_point', '观测点'],
            ['describe', '鸟情描述'],
            ['height', '活动高度'],
            ['temperature', '温度'],
            ['humidity', '湿度'],
            ['pressure', '气压（百帕）'],
            ['wind_direction', '风向'],
            ['wind_power', '风力'],
            ['weather1', '天气状况一'],
            ['weather2', '天气状况二'],
            ['step1', '采取措施一'],
            ['step2', '采取措施二'],
            ['result', '效果评价'],
        ];
        $extra = $this->config->extra_item('bird_condition');
        foreach ($extra as $extra) {
            $arr = [$extra['column_code'], $extra['column_name']];
            $header[] = $arr;
        }

        $data = $this->supervise->select_all($where);
        foreach ($data as $item) {
            $item['realness'] = $item['realness'] / 100;
            $item['humidity'] = $item['humidity'] / 100;
            $item['day_int'] = date("Y/n/j", $item['day_int']);
            $item['time_int'] = date("H:i", $item['time_int']);
        }

        $this->export_excel($filename, $header, $data);
    }

    public function export_txt($where)
    {
        $filename = '一级鸟情记录';
        header('Content-type:text/plain;charset=utf-8');
        header('Content-Disposition:attachment;filename="' . $filename . '.txt"');

        $data = $this->supervise->select_all($where);
        foreach ($data as $item) {
            $realness = number_format($item['realness'] / 100, 2);
            $item['realness'] = $realness;
            $is_one = substr($item['realness'], 0, 1);
            if (!$is_one) {
                $item['realness'] = substr(strval($realness), 1);
            }
            $humidity = number_format($item['humidity'] / 100, 2);
            $item['humidity'] = substr(strval($humidity), 1);

            echo $item['id'] . ',';
            echo "\"" . $item['worker1'] . '",';
            echo "\"" . $item['worker2'] . '",';
            echo date("Y-m-d G:i:s", $item['day_int']) . ',';
            echo date("Y-m-d G:i:s", $item['time_int']) . ',';
            echo "\"" . $item['view_number'] . '",';
            echo "\"" . $item['bird_name'] . '",';
            echo $item['realness'] . ',';
            echo $item['bird_num'] . ',';
            echo "\"" . $item['area'] . '",';
            echo "\"N/A\"" . ',';
            echo "\"" . $item['describe'] . '",';
            echo "\"" . $item['height'] . '",';
            echo $item['temperature'] . ',';
            echo $item['humidity'] . ',';
            echo $item['pressure'] . '.00,';
            echo "\"" . $item['wind_direction'] . '",';
            echo "\"" . $item['wind_power'] . '",';
            echo "\"" . $item['weather1'] . '",';
            echo "\"" . $item['weather2'] . '",';
            echo "\"" . $item['step1'] . '",';
            echo "\"" . $item['step2'] . '",';
            echo "\"" . $item['result'] . "\"\r\n";
        }
        exit;
    }

}
