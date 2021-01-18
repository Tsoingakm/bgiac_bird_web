<?php

namespace app\api\controller;

use think\Request;
use think\Loader;
use app\api\model\Admin;
use app\api\model\WorkVector;
use app\api\model\WorkArea;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;

class Vector extends Common {

    protected $request;
    protected $model;
    protected $worker;
    protected $area;
    protected $config;
    protected $choices;

    public function _initialize(){
        parent::_initialize();
        $this->request  =   Request::instance();
        $this->model    =   new WorkVector();
        $this->area     =   new WorkArea();
        $this->worker   =   new Admin();
        $this->config   =   new TableConfig();
        $this->choices  =   new TableConfigOption();
    }

    public function selectForm(){
        $params  = $this->request->param();
        $params['aid'] = $params['aid']?$params['aid']: 0;
        $options = [];

        $options[] = [
          'index_code' => 'worker',
          'options' => $this -> get_relevant_staff('work_vector_add')
        ];

        $options[] = [
          'index_code' => 'maintain_area',
          'options' => $this->area -> area_info("work_area", $params['aid'])
        ];

        $items = $this->config -> from_item("work_vector");
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

        $extra = $this->config -> extra_item("work_vector");
        foreach ($extra as $key=>$value) {
          $option = $this->choices -> get_options($value['index_code']);
          $extra[$key]['options'] = $option;
        }

        $data['form_options'] = $options;
        if(!empty($extra)){
          $data['extra_items'] = $extra;
        }
        $this->return_msg( true, "获取表单成功", $data);
    }

    public function insert(){
        //$this->return_msg( false, "系统升级中，请稍后再录入数据");
        $params  = $this->request->param();

        //检查数据库有无guid，有guid时更新，没有则插入
        if(isset($params['guid']) && $params['guid'] != ''){
            $existData = $this->model->find_by_guid($params['guid']);
            if($existData){
                //更新操作返回结果
//            $params['id'] = $existData->id;
//            $validate = Loader::validate('Common');
//            if(!$validate->check($params)){
//                $this->return_msg( false, $validate->getError());
//            }
                $result = $this->model->update_by_id($existData->id, $params);
                if($result !== false){
                    //获取新数据返回
                    $newModel = $this->model->find_by_id($existData->id);
                    $this->return_msg( true,  "更新成功", $newModel);
                }
                $this->return_msg( false, "更新失败");
            }
        }


        $validate = Loader::validate('WorkVector');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $params['working_date'] = strtotime( $params['working_date'] );
        $params['start_time']   = strtotime( $this -> split_time($params['start_time']) );
        $params['end_time']     = strtotime( $this -> split_time($params['end_time']) );

        $data = array();
        $data['working_date']       = $params['working_date'];
        $data['work_type']          = $params['work_type'];
        $data['start_time']         = $params['start_time'];
        $data['end_time']           = $params['end_time'];
        $data['manager1']           = $params['manager1'];
        $data['manager2']           = $params['manager2'];
        $data['service_provider']   = $params['service_provider'];
        $data['aid']                = $params['aid'];
        $data['lat_gcj02']                = $params['lat_gcj02'];
        $data['lng_gcj02']                = $params['lng_gcj02'];

        $operation = json_decode($params['operation_situation'], true);

        foreach($operation as $item){
            $data['control_object'] = $item['control_object'];
            $data['maintain_area']  = $item['maintain_area'];
            $data['flight_area']    = $item['flight_area'];
            $data['remarks']        = $item['remarks'];
            $result = $this->model -> insert_data($data);
        }

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

        $is_delete = $this->model -> delete_by_id($id);

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

        $data = $this->model -> find_by_id($id);

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
        $params['start_time']   = strtotime( $this -> split_time($params['start_time']) );
        $params['end_time']     = strtotime( $this -> split_time($params['end_time']) );

        $is_update = $this->model -> update_by_id($id, $params);

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

        $list = $this->model -> select_all_for_app($params);
        if(!$list){
          $this->return_msg(false, "查询列表失败");
        }

        $this->return_msg(true, "查询列表成功", $list);
    }

    public function selectHistory(){
        $history = $this->model -> historic_records();
        if(!$history){
            $this->return_msg(false, "查询记录失败");
        }
        $this->return_msg(true, "查询记录成功", $history);
    }

    public function selectHistoryV2(){
        $history = $this->model -> historic_records();
        if(!$history){
            $this->return_msg(true, "查询记录成功", []);
        }
        $this->return_msg(true, "查询记录成功", $history);
    }

    public function statistics(){
        $params  = $this->request->param();

        $validate = Loader::validate('Time');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $start  = $params['starting_time'];
        $end    = $params['end_time'];

        $area_list = $this->area -> area_statistics('work_area');
        foreach($area_list as $key => $value){
            $records  = $this->model -> select_for_statistics($value['area_name'], $start, $end);
            if($records){
                $area_list[$key]['records'] = $records;
            }else{
                $area_list[$key]['records'] = [];
            }
        }

        if(!$area_list){
            $this->return_msg(false, "查询记录失败");
        }
        $this->return_msg(true, "查询记录成功", $area_list);
    }


}
