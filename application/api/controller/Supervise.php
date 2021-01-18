<?php

namespace app\api\controller;

use think\Request;
use think\Db;
use think\Loader;
use app\api\model\Admin;
use app\api\model\BirdCondition;
use app\api\model\BirdArea;
use app\api\model\BirdName;
use app\api\model\TableConfig;
use app\api\model\TableConfigOption;

class Supervise extends Common {

    protected $request;
    protected $model;
    protected $worker;
    protected $area;
    protected $bird;
    protected $config;
    protected $choices;

    public function _initialize(){
        parent::_initialize();
        $this->request  = Request::instance();
        $this->worker   = new Admin();
        $this->model    = new BirdCondition();
        $this->area     = new BirdArea();
        $this->bird     = new BirdName();
        $this->config   = new TableConfig();
        $this->choices  = new TableConfigOption();
    }

    public function selectForm(){

        $params  = $this->request->param();

        $params['aid'] = $params['aid']?$params['aid']: 0;

        $options = [];

        $options[] = [
          'index_code' => 'worker',
          'options' => $this -> get_relevant_staff('first_level_add')
        ];

        $options[] = [
          'index_code' => 'area',
          'options' => $this->area -> area_info($params['aid'])
        ];

        $options[] = [
          'index_code' => 'bird_name',
          'options' => $this->bird -> bird_info()
        ];

        $items = $this->config -> from_item("bird_condition");
        foreach ($items as $item) {
          $option = $this->choices -> get_options($item['index_code']);

          $arr['index_code'] = $item['index_code'];
          if(!empty($item['default_value'])){
            $arr['default_value'] = $item['default_value'];
          }
          $arr['options'] = $option;

          $options[] = $arr;
          unset($arr);
        }

        $extra = $this->config -> extra_item("bird_condition");
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
                /*$validate = Loader::validate('Common');
                if(!$validate->check($params)){
                    $this->return_msg( false, $validate->getError());
                }*/
                //lzj:上面的验证会影响下方的验证
                $result = $this->model->update_by_id($existData->id, $params);
                if($result !== false){
                    //获取新数据返回
                    $newModel = $this->model->find_by_id($existData->id);
                    $this->return_msg( true,  "更新成功", $newModel);
                }
                $this->return_msg( false, "更新失败");
            }
        }
        $validate = Loader::validate('Supervise');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $this -> increase_weights($params['bird_name']);

        $params['humidity'] = $params['humidity']."%";

        $params['time_str'] = $this -> split_time($params['time_str']);

        $result = $this->model -> insert_data($params);

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

        $data = $this -> extension("bird_condition", $data);

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

        $params['time_str'] = $this -> split_time($params['time_str']);
        $params['humidity'] = $params['humidity']."%";

        $is_update = $this->model -> update_by_id($id, $params);

        if($is_update !== false){
            $this->return_msg( true,  "更新成功");
        }
        $this->return_msg( false, "更新失败");
    }

    public function selectList(){
        $params  = $this->request->param();

        $validate = Loader::validate('SuperviseList');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $list = $this->model -> select_all_for_app($params);

        if(!$list){
          $this->return_msg(false, "查询列表失败");
        }

        $data['list'] = $list;

        $this->return_msg(true, "查询列表成功", $data);
    }

    public function selectListV2(){
        $params  = $this->request->param();

        $validate = Loader::validate('SuperviseList');
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

}
