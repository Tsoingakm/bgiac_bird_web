<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\api\model\Admin;
use app\api\model\WorkLawn AS Work;
use app\api\model\WorkArea;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;

class WorkLawn extends Base {
    protected $request;
    protected $work;
    protected $worker;
    protected $area;
    protected $config;
    protected $choices;

    public function __construct(){
        parent::__construct();
        $this->request  = Request::instance();
        $this->work     = new Work();
        $this->worker   = new Admin();
        $this->area     = new WorkArea();
        $this->config   = new TableConfig();
        $this->choices  = new TableConfigOption();
        $this->addBread('台账管理');
        $this->addBread('草坪维护工作记录管理');
    }

    public function index(){
        $this->checkPowerWeb('work_lawn_view',$this->admin['ap_codes']);
        $hasPower = $this->checkListEditPower('work_lawn_view_edit', $this->admin['ap_codes'])?1:0;
        $this->assign('hasPower', $hasPower);

        $where = array();
        $pageParam = array();

        $manager1 = input('manager1');
        $manager2 = input('manager2');
        $manager3 = input('manager3');
        if($manager1){
            $where['manager1'] = $manager1;
        }
        if($manager2){
            $where['manager2'] = $manager2;
        }
        if($manager3){
            $where['manager3'] = $manager3;
        }
        $this->assign('manager1', $manager1);
        $this->assign('manager2', $manager2);
        $this->assign('manager3', $manager3);

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
        $where['working_date'] = [ 'between', [ $begin_day_int, $end_day_int ] ];

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
        $work_flight_list = db("table_config_option")->where(['index_code'=>'work_insect_flight'])->order('sort asc')->select();

        $pageParam['work_flight'] = $work_flight;
        if ($work_flight) {
            $where['flight_area'] = $work_flight;
        }
        $this->assign('work_flight', $work_flight);
        $this->assign('work_flight_list', $work_flight_list);
        //工作类型
        $work_type = input('work_type');
        $work_type_list = db('table_config_option')->where(['index_code' => 'work_lawn_type'])->order('sort asc')->select();
        $pageParam['work_type'] = $work_type;
        if ($work_type) {
            $where['work_type'] = $work_type;
        }
        $this->assign('work_type', $work_type);
        $this->assign('work_type_list', $work_type_list);

        $count = db('work_lawn')->where ( $where )->count ();
        $this->assign('totalRows', $count);

        if(input('act')==="export_excel"){
            $this->export_data_handle($where);
        }

        $this->setOption();

        $this->assign('dataUrl', url('WorkLawn/indexData', [
            'day'=>urlencode($day),
            'work_area'=>$work_area,
            'work_flight'=>$work_flight,
            'work_type'=>$work_type,
            'manager1'=>$manager1,
            'manager2'=>$manager2,
            'manager3'=>$manager3
        ]));

        return view();
    }

    public function indexData(){
        $this->checkPowerWeb('work_lawn_view',$this->admin['ap_codes']);
        $hasPower = $this->checkListEditPower('work_insect_view_edit', $this->admin['ap_codes'])?1:0;
        $this->assign('hasPower', $hasPower);

        $where = array();
        $pageParam = array();

        $manager1 = input('manager1');
        $manager2 = input('manager2');
        $manager3 = input('manager3');
        if($manager1){
            $where['manager1'] = $manager1;
        }
        if($manager2){
            $where['manager2'] = $manager2;
        }
        if($manager3){
            $where['manager3'] = $manager3;
        }
        $this->assign('manager1', $manager1);
        $this->assign('manager2', $manager2);
        $this->assign('manager3', $manager3);

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
        $where['working_date'] = [ 'between', [ $begin_day_int, $end_day_int ] ];

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
        $work_flight_list = db("table_config_option")->where(['index_code'=>'work_insect_flight'])->order('sort asc')->select();

        $pageParam['work_flight'] = $work_flight;
        if ($work_flight) {
            $where['flight_area'] = $work_flight;
        }
        $this->assign('work_flight', $work_flight);
        $this->assign('work_flight_list', $work_flight_list);
        //工作类型
        $work_type = input('work_type');
        $work_type_list = db('table_config_option')->where(['index_code' => 'work_lawn_type'])->order('sort asc')->select();
        $pageParam['work_type'] = $work_type;
        if ($work_type) {
            $where['work_type'] = $work_type;
        }
        $this->assign('work_type', $work_type);
        $this->assign('work_type_list', $work_type_list);

        $orderby = 'working_date desc';
        $page = input('page', 1);
        $pageCount = input('limit', 10);

        $list = db('work_lawn')->where ( $where )->order ( $orderby )
            ->limit (($page - 1) * $pageCount, $pageCount)->select ();
        $count = db('work_lawn')->where ( $where )->order ( $orderby )->count ();
        foreach ($list as $k=>$v){
            $list[$k]['date'] = date('Y-m-d', $list[$k]['working_date']);
            $list[$k]['is_compliance'] = $list[$k]['is_compliance'] == 1?'达标':'不达标';
        }
        $data = [];
        $data['code']    = 0;
        $data['msg']    = "查询成功";
        $data['count']  = $count;
        $data['data']   = $list;
        return $data;
    }

    public function changeData(){
        $this->checkPowerWeb('work_lawn_view_edit', $this->admin['ap_codes']);
        $data = input('');
        $data['working_date'] = strtotime($data['date']);
        $data['is_compliance'] = $data['is_compliance'] == '达标'? 1: 0;
        $result = $this->work->update_by_id($data['id'], $data);
        if ($result !== false) {
            \LogPageHelper::record('修改草坪维护记录成功：' . $id, 'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改草坪维护记录失败：' . $id, 'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function add(){
        $this->checkPowerWeb('work_lawn_add',$this->admin['ap_codes']);

        $model['working_date']   = date("Y/n/j", time());
        $this->assign("model", $model);

        $this->setOption();

        $records = $this->work ->historic_records();
        $this->assign("records", $records);

        $this->extra('work_lawn');

        return view();
    }

    public function doAdd(){
        $this->checkPowerWeb('work_lawn_add',$this->admin['ap_codes']);
        $params = $this->request -> param();

        $params['working_date'] = strtotime($params['working_date']);
        $params['start_time']   = strtotime($params['start_time']);
        $params['end_time']     = strtotime($params['end_time']);

        $data = array();
        $data['working_date']       = $params['working_date'];
        $data['work_type']          = $params['work_type'];
        $data['start_time']         = $params['start_time'];
        $data['end_time']           = $params['end_time'];
        $data['manager1']           = $params['manager1'];
        $data['manager2']           = $params['manager2'];
        $data['manager3']           = $params['manager3'];
        $data['service_provider']   = $params['service_provider'];
        $data['aid']                = $params['aid'];

        $operation = json_decode($params['operation_situation'], true);

        foreach($operation as $item){
            if($item){
                $data['maintain_area']  = $item['maintain_area'];
                $data['flight_area']    = $item['flight_area'];
                $data['work_area']      = $item['work_area'];
                $data['area_unit']      = $item['area_unit'];
                $data['harvest_area']      = $item['harvest_area'];
                $data['remarks']        = $item['remarks'];
//                var_dump($data);
                $result = $this->work -> insert_data($data);
            }
        }
//        exit;
//        var_dump($data);exit;
        if(!$result){
              \LogPageHelper::record('添加草坪维护工作记录记录失败：'.$id,'error',$this->logOption);
              $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加草坪维护工作记录记录成功：'.$id,'info',$this->logOption);
        $this->success('添加成功');
    }

    public function edit(){
        $this->checkPowerWeb('work_lawn_update',$this->admin['ap_codes']);

        $this->setOption();

        $records = $this->work ->historic_records();
        $this->assign("records", $records);

        $id   = $this->request -> param('id');
        $data = $this->work -> find_by_id($id);
        $this -> assign("model", $data);

        $this->extra('work_lawn', $data);

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
        switch($data['area_unit']){
            case 0:
                $data['area_unit'] = '平方米';
                break;
            case 1:
                $data['area_unit'] = '立方米';
                break;
            case 2:
                $data['area_unit'] = '平方米';
                break;
        }
        $this->assign("hasAddress", $hasAddress);
        $this->assign("model", $data);

        $this->extraDetail('work_lawn', $data);

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

    public function doEdit(){
        $this->checkPowerWeb('work_lawn_update',$this->admin['ap_codes']);

        $id     = $this->request -> param('id');
        $params = $this->request -> param();

        $params['working_date'] = strtotime($params['working_date']);
        $params['start_time']   = strtotime($params['start_time']);
        $params['end_time']     = strtotime($params['end_time']);

        $result = $this->work -> update_by_id($id, $params);
        if($result !== false){
              \LogPageHelper::record('修改草坪维护工作记录记录成功：'.$id,'info', $this->logOption);
              $this->success('修改成功');
        }
        \LogPageHelper::record('修改草坪维护工作记录记录失败：'.$id,'error', $this->logOption);
          $this->error('修改失败，请稍后再试');
    }

    public function doDel(){
        $this->checkPowerWeb('work_lawn_delete',$this->admin['ap_codes']);
        $ids   = $this->request -> param('ids');
        if (empty($ids)) { $this->error('参数不正确'); }

        $result = $this->work -> delete_by_id($ids);
        if(!$result){
              \LogPageHelper::record('删除草坪维护工作记录记录失败：'.$ids,'error', $this->logOption);
              $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除草坪维护工作记录记录成功：'.$ids,'info', $this->logOption);
        $this->success('删除成功');
    }

    public function add_table(){
        $this->setOption();

        return view();
    }

    public function setOption(){
        $items = $this->config -> from_item("work_lawn");
        foreach ($items as $item) {
          $option = $this->choices -> get_options($item['index_code']);
          $options[] = [
            'index_code' => $item['index_code'],
            'options' => $option
          ];
        }

//        var_dump($options);

        foreach($options as $key=>$value){
          $this->assign($value['index_code']."_list", $value['options']);
        }

        $worker_list = $this -> get_relevant_staff('work_lawn_add');
        $this->assign("worker_list", $worker_list);

        $entering_list = $this -> get_entering_staff('work_lawn_add');
        $this->assign("entering_list", $entering_list);

        $area_list = $this->area -> area_info('work_area', $this->admin['aid']);
        $this->assign("area_list", $area_list);
    }

    public function findAll(){
        $id = $this->request -> param('id');
        $data = $this->work -> find_by_id($id);

        $return_data['status']  = true;
        $return_data['info']    = "查询成功";
        $return_data['data']    = $data;

        if(!$data){
          $return_data['status']  = false;
          $return_data['info']    = "复制失败";
          unset($return_data['data']);
        }

        return $return_data;
    }

    public function export_data_handle($where){
        $filename = "草坪维护工作记录";

        $header = [
            ['id',                '编号'],
            ['working_date',      '日期'],
            ['work_type',         '维护类型'],
            ['maintain_area',     '维护区域'],
            ['flight_area',       '飞行区方位'],
            ['start_time',        '开始时间'],
            ['end_time',          '结束时间'],
            ['work_area',         '作业面积'],
            ['manager1',          '管理员1'],
            ['manager2',          '管理员2'],
            ['manager3',          '管理员3'],
            ['service_provider',  '服务商负责人'],
            ['remarks',           '备注'],
        ];

        $extra = $this->config -> extra_item('work_lawn');
        foreach($extra as $extra) {
           $arr = [ $extra['column_code'], $extra['column_name' ]];
           $header[] = $arr;
        }

        $data = $this->work -> select_all($where);

        $this -> export_excel($filename, $header, $data);
    }

}
