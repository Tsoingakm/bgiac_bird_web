<?php

namespace app\api\controller;

use think\Request;
use think\Loader;
use app\api\model\Admin;
use app\api\model\Device as Model;
use app\api\model\DeviceCode;
use app\api\model\DeviceParts;
use app\api\model\DeviceStatus;
use app\api\model\DeviceRecord;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;

class Device extends Common {

    protected $request;
    protected $worker;
    protected $model;
    protected $code;
    protected $part;
    protected $record;
    protected $status;
    protected $config;
    protected $choices;

    public function _initialize(){
        parent::_initialize();
        $this->request  = Request::instance();
        $this->worker   = new Admin();
        $this->model    = new Model();
        $this->code     = new DeviceCode();
        $this->part     = new DeviceParts();
        $this->status   = new DeviceStatus();
        $this->record   = new DeviceRecord();
        $this->config   = new TableConfig();
        $this->choices  = new TableConfigOption();
    }

    public function selectForm(){
        $options = [];

        $options[] = [
            'index_code'  =>  'worker',
            'options'     =>  $this -> get_relevant_staff('device_record_add')
        ];

        $options[] = [
            'index_code'  =>  'device_status',
            'options'     =>  $this->status -> device_status()
        ];

        $options[] = [
            'index_code'  =>  'parts_status',
            'options'     =>  $this->status -> parts_status()
        ];

        $device  =  $this->model -> device_info();

        $items = $this->config -> from_item("device");
        foreach ($items as $item) {
          $option = $this->choices -> get_options($item['index_code']);

          $arr['index_code'] = $item['column_code'];
          if(!empty($item['default_value'])){
            $arr['default_value'] = $item['default_value'];
          }
          $arr['options'] = $option;

          $options[] = $arr;
          unset($arr);
        }

        $data['form_options'] = $options;
        $data['device_info']  = $device;

        $this->return_msg( true, "获取表单成功", $data);
    }

    public function insert(){
        //$this->return_msg( false, "系统升级中，请稍后再录入数据");
        $params  = $this->request->param();

        $validate = Loader::validate('Device');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $data = array();
        $data['working_date']     = strtotime($params['working_date']);
        $data['working_time']     = strtotime( $this -> split_time($params['working_time']) );
        $data['worker1']          = $params['worker1'];
        $data['worker2']          = $params['worker2'];
        $data['worker3']          = empty($params['worker3']) ? '' : $params['worker3'];
        $data['service_provider'] = $params['service_provider'];
        $data['device']           = $params['device'];
        $data['code']             = $params['code'];
        $data['device_status']    = $params['device_status'];
        $data['aid']              = $params['aid'];

        $device_id = $this->model ->  findByName($data['device']);

        $inspection = json_decode($params['inspection'], true);
        $submission = $inspection['inspection'];

        foreach($submission as $item){
            $data['check_item']     = $item['check_item'];
            $data['process_method'] = $item['process_method'];
            $data['remarks']        = $item['remarks'];
            $result = $this->record -> insert_data($data);
        }

        // $all = $this->part -> part_name_array($device_id);
        // $section = array();
        // foreach($submission as $submission ){
        //     $section[] = $submission['check_item'];
        // }
        //
        // $remain = array_diff($all, $section);
        // foreach ($remain as $item) {
        //     $data['check_item']     = $item;
        //     $data['process_method'] = "正常";
        //     $data['remarks']        = "";
        //     $result = $this->record -> insert_data($data);
        // }

        if(!$result){
            $this->return_msg( false, "添加失败");
        }
        $this->return_msg( true, "添加成功");
    }

    public function deleteById(){
        //$this->return_msg( false, "系统升级中，请稍后再录入数据");
        $params  = $this->request -> param();

        $validate = Loader::validate('Common');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $id = $params['id'];

        $is_delete = $this->record -> delete_by_id($id);

        if(!$is_delete){
            $this->return_msg( false, "删除失败");
        }
        $this->return_msg( true, "删除成功");
    }

    public function findById(){
        $params  = $this->request -> param();

        $validate = Loader::validate('Common');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $id = $params['id'];

        $data = $this->record -> find_by_id($id);

        if(!$data){
            $this->return_msg( false, "查询失败");
        }
        $this->return_msg( true, "查询成功", $data);
    }

    public function updateById(){
        //$this->return_msg( false, "系统升级中，请稍后再录入数据");
        $params  = $this->request->param();

        $validate = Loader::validate('Common');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $id = $params['id'];

        $params['working_date'] = strtotime( $params['working_date'] );
        $params['working_time'] = strtotime( $this -> split_time($params['working_time']) );

        $is_update = $this->record -> update_by_id($id, $params);

        if($is_update !== false){
            $this->return_msg( true,  "更新成功");
        }
        $this->return_msg( false, "更新失败");
    }

    public function selectList(){
        $params  = $this->request->param();

        $validate = Loader::validate('AnyList');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $list = $this->record -> select_all_for_app($params);
        if(!$list){
            $this->return_msg(false, "查询列表失败");
        }

        $this->return_msg(true, "查询列表成功", $list);
    }

    public function selectHistory(){
        $history = $this->record -> historic_records();
        if(!$history){
            $this->return_msg(false, "查询记录失败");
        }
        $this->return_msg(true, "查询记录成功", $history);
    }

}
