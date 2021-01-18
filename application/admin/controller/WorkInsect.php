<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\api\model\Admin;
use app\api\model\WorkInsect AS Work;
use app\api\model\WorkArea;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;

class WorkInsect extends Base
{
    protected $request;
    protected $worker;
    protected $work;
    protected $area;
    protected $config;
    protected $choices;

    public function __construct()
    {
        parent::__construct();
        $this->request = Request::instance();
        $this->worker = new Admin();
        $this->work = new Work();
        $this->area = new WorkArea();
        $this->config = new TableConfig();
        $this->choices = new TableConfigOption();
        $this->addBread('台账管理');
        $this->addBread('昆虫消杀工作记录管理');
    }

    public function index()
    {
        $this->checkPowerWeb('work_insect_view', $this->admin['ap_codes']);
        $hasPower = $this->checkListEditPower('work_insect_view_edit', $this->admin['ap_codes'])?1:0;
        $this->assign('hasPower', $hasPower);

        $where = array();
        $pageParam = array();

        $manager1 = input('manager1');
        $manager2 = input('manager2');
        $service_provider = input('service_provider');
        if($manager1){
            $where['manager1'] = $manager1;
        }
        if($manager2){
            $where['manager2'] = $manager2;
        }
        if($service_provider){
            $where['service_provider'] = $service_provider;
        }
        $this->assign('manager1', $manager1);
        $this->assign('manager2', $manager2);
        $this->assign('service_provider', $service_provider);
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

        $where['working_date'] = [ 'between', [$begin_day_int, $end_day_int ] ];

        //消杀区域
        $work_area = input('work_area');
        $work_area_list = db('work_area')->where(['table_name' => 'work_area'])->order('area_name asc')->select();
        $pageParam['work_area'] = $work_area;
        if ($work_area) {
            $where['maintain_area'] = $work_area;
        }

        $this->assign('work_area', $work_area);
        $this->assign('work_area_list', $work_area_list);
        //飞行区方位
        $work_flight = input("work_flight");
        $work_flight_list = db("table_config_option")->where(['index_code' => 'work_insect_flight'])->order('sort asc')->select();

        $pageParam['work_flight'] = $work_flight;
        if ($work_flight) {
            $where['flight_area'] = $work_flight;
        }
        $this->assign('work_flight', $work_flight);
        $this->assign('work_flight_list', $work_flight_list);

        $count = db('work_insect')->where ( $where )->count ();
        $this->assign('totalRows', $count);

        if (input('act') === "export_excel") {
            $this->export_data_handle($where);
        }

        $this->setOption();

        $this->assign('dataUrl', url('WorkInsect/indexData', [
            'day'=>urlencode($day),
            'work_flight'=>$work_flight,
            'work_area'=>$work_area,
            'manager1'=>$manager1,
            'manager2'=>$manager2,
            'service_provider'=>$service_provider,
        ]));

        return view();
    }

    public function indexData(){

        $where = array();
        $pageParam = array();

        $manager1 = input('manager1');
        $manager2 = input('manager2');
        $service_provider = input('service_provider');
        if($manager1){
            $where['manager1'] = $manager1;
        }
        if($manager2){
            $where['manager2'] = $manager2;
        }
        if($service_provider){
            $where['service_provider'] = $service_provider;
        }
        $this->assign('manager1', $manager1);
        $this->assign('manager2', $manager2);
        $this->assign('service_provider', $service_provider);
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

        $where['working_date'] = [ 'between', [$begin_day_int, $end_day_int ] ];

        //消杀区域
        $work_area = input('work_area');
        $work_area_list = db('work_area')->where(['table_name' => 'work_area'])->order('area_name asc')->select();
        $pageParam['work_area'] = $work_area;
        if ($work_area) {
            $where['maintain_area'] = $work_area;
        }

        $this->assign('work_area', $work_area);
        $this->assign('work_area_list', $work_area_list);
        //飞行区方位
        $work_flight = input("work_flight");
        $work_flight_list = db("table_config_option")->where(['index_code' => 'work_insect_flight'])->order('sort asc')->select();

        $pageParam['work_flight'] = $work_flight;
        if ($work_flight) {
            $where['flight_area'] = $work_flight;
        }
        $this->assign('work_flight', $work_flight);
        $this->assign('work_flight_list', $work_flight_list);

//        $this->setOption();

//        $this->assign('dataUrl', url('WorkInsect/indexData', ['day'=>urlencode($day), 'work_flight'=>$work_flight, 'work_area'=>$work_area]));


        $orderby = 'working_date desc, spary_times desc';
        $page = input('page', 1);
        $pageCount = input('limit', 10);

        $list = db('work_insect')->where ( $where )->order ( $orderby )
            ->limit (($page - 1) * $pageCount, $pageCount)
            ->select ();
        $count = db('work_insect')->where ( $where )->order ( $orderby )->count ();
        foreach ($list as $k=>$v){
            $list[$k]['date'] = date('Y-m-d', $list[$k]['working_date']);
            $list[$k]['is_compliance'] = $list[$k]['is_compliance'] == 0?'不达标':'达标';
        }
        $data = [];
        $data['code']    = 0;
        $data['msg']    = "查询成功";
        $data['count']  = $count;
        $data['data']   = $list;
        return $data;
    }

    public function changeData(){
        $this->checkPowerWeb('work_insect_view_edit', $this->admin['ap_codes']);
        $data = input('');
        $data['working_date'] = strtotime($data['date']);
        $data['is_compliance'] = $data['is_compliance'] == '达标'? 1: 0;
        $result = $this->work->update_by_id($data['id'], $data);
        if ($result !== false) {
            \LogPageHelper::record('修改一级鸟情记录成功：' . $id, 'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改一级鸟情记录失败：' . $id, 'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function add()
    {
        $this->checkPowerWeb('work_insect_add', $this->admin['ap_codes']);

        $model['working_date'] = date("Y/n/j", time());
        $this->assign("model", $model);

        $this->setOption();
        $records = $this->work->historic_records();
        $this->assign("records", $records);

        $this->extra('work_insect');

        return view();
    }

    public function doAdd()
    {
        $this->checkPowerWeb('work_insect_add', $this->admin['ap_codes']);
        $params = $this->request->param();

        if (empty($params['operation_situation'])) {
            $this->error("作业情况为空！");
        }

        $params['working_date'] = strtotime($params['working_date']);
        $params['start_time'] = strtotime($params['start_time']);
        $params['end_time'] = strtotime($params['end_time']);

        $data = array();
        $data['working_date'] = $params['working_date'];
        $data['time_period'] = $params['time_period'];
        $data['spary_times'] = $params['spary_times'];
        $data['water_consumption'] = $params['water_consumption'];
        $data['pharmacy_name1'] = $params['pharmacy_name1'];
        $data['dosage1'] = $params['dosage1'];
        $data['pharmacy_name2'] = $params['pharmacy_name2'];
        $data['dosage2'] = $params['dosage2'];
        $data['pharmacy_name3'] = $params['pharmacy_name3'];
        $data['dosage3'] = $params['dosage3'];
        $data['start_time'] = $params['start_time'];
        $data['end_time'] = $params['end_time'];
        $data['manager1'] = $params['manager1'];
        $data['manager2'] = $params['manager2'];
        $data['service_provider'] = $params['service_provider'];
        $data['avg_water'] = $params['avg_water'];
        $data['is_compliance'] = $params['is_compliance'];
        $data['aid'] = $params['aid'];

        $operation = json_decode($params['operation_situation'], true);

        foreach ($operation as $item) {
            if($item){
                $data['maintain_area'] = $item['maintain_area'];
                $data['flight_area'] = $item['flight_area'];
                $data['work_area'] = $item['work_area'];
                $data['area_unit'] = $item['area_unit'];
                $data['remarks'] = $item['remarks'];
                $result = $this->work->insert_data($data);
            }
        }

        if (!$result) {
            \LogPageHelper::record('添加昆虫消杀工作记录记录失败：' . $id, 'error', $this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加昆虫消杀工作记录记录成功：' . $id, 'info', $this->logOption);
        $this->success('添加成功');
    }

    public function edit()
    {
        $this->checkPowerWeb('work_insect_update', $this->admin['ap_codes']);

        $this->setOption();

        $records = $this->work->historic_records();
        $this->assign("records", $records);

        $id = $this->request->param('id');
        $data = $this->work->find_by_id($id);
        $this->assign("model", $data);

        $this->extra('work_insect', $data);

        return view();
    }

    public function detail()
    {
//        $this->checkPowerWeb('first_level_update', $this->admin['ap_codes']);

        $this->setOption();

        $id = $this->request->param('id');
        $data = $this->work->find_by_id($id);
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

        $this->extraDetail('work_insect', $data);

        return view();
    }

    public function map(){
        $id = $this->request->param('id');
        $data = $this->work->find_by_id($id);
        $this->assign("lng", $data['lng_gcj02']);
        $this->assign("lat", $data['lat_gcj02']);
        $this->assign("diff", $data['diff']);
        return view();
    }

    public function doEdit()
    {
        $this->checkPowerWeb('work_insect_update', $this->admin['ap_codes']);

        $id = $this->request->param('id');
        $params = $this->request->param();

        $params['working_date'] = strtotime($params['working_date']);
        $params['start_time'] = strtotime($params['start_time']);
        $params['end_time'] = strtotime($params['end_time']);

        $result = $this->work->update_by_id($id, $params);
        if ($result !== false) {
            \LogPageHelper::record('修改昆虫消杀工作记录记录成功：' . $id, 'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改昆虫消杀工作记录记录失败：' . $id, 'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function doDel()
    {
        $this->checkPowerWeb('work_insect_delete', $this->admin['ap_codes']);
        $ids = $this->request->param('ids');

        if (empty($ids)) {
            $this->error('参数不正确');
        }

        $result = $this->work->delete_by_id($ids);
        if (!$result) {
            \LogPageHelper::record('删除昆虫消杀工作记录记录失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除昆虫消杀工作记录记录成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }

    public function add_table()
    {

        $this->setOption();

        return view();
    }

    public function setOption()
    {
        $items = $this->config->from_item("work_insect");
        foreach ($items as $item) {
            $option = $this->choices->get_options($item['index_code']);
            $options[] = [
                'index_code' => $item['index_code'],
                'options' => $option
            ];
        }

        foreach ($options as $key => $value) {
            $this->assign($value['index_code'] . "_list", $value['options']);
//            var_dump($value['options']);
        }

        $worker_list = $this -> get_relevant_staff('work_insect_add');
        $this->assign("worker_list", $worker_list);

        $entering_list = $this -> get_entering_staff('work_insect_add');
        $this->assign("entering_list", $entering_list);

        $area_list = $this->area->area_info('work_area', $this->admin['aid']);
        $this->assign("area_list", $area_list);
    }

    public function findAll()
    {
        $id = $this->request->param('id');
        $data = $this->work->find_by_id($id);

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
        $filename = "昆虫消杀工作记录";

        $header = [
            ['id', '编号'],
            ['working_date', '日期'],
            ['time_period', '时段'],
            ['spary_times', '喷药次数代号'],
            ['water_consumption', '用水量（吨）'],
            ['pharmacy_name1', '药剂名称1'],
            ['dosage1', '药剂1(ml)'],
            ['pharmacy_name2', '药剂名称2'],
            ['dosage2', '药剂2(ml)'],
            ['pharmacy_name3', '药剂名称3'],
            ['dosage3', '药剂3(ml)'],
            ['start_time', '开始时间'],
            ['end_time', '结束时间'],
            ['manager1', '管理员1'],
            ['manager2', '管理员2'],
            ['service_provider', '服务商负责人'],
            ['maintain_area', '消杀区域'],
            ['flight_area', '飞行区方位'],
            ['work_area', '消杀面积(m²)'],
            ['remarks', '备注'],
            ['dilutability1', '稀释浓度1'],
            ['dilutability2', '稀释浓度2'],
            ['dilutability3', '稀释浓度3'],
            ['avg_dosage1', '每亩地用药量1(ml/亩)'],
            ['avg_dosage2', '每亩地用药量2(ml/亩)'],
            ['avg_dosage3', '每亩地用药量3(ml/亩)'],
            ['avg_water', '每亩地用水量（kg/亩）'],
            ['is_compliance', '是否达标'],
        ];

        $extra = $this->config->extra_item('work_insect');
        foreach ($extra as $extra) {
            $arr = [$extra['column_code'], $extra['column_name']];
            $header[] = $arr;
        }

        $data = $this->work->select_all($where);

        $this->export_excel($filename, $header, $data);
    }

}
