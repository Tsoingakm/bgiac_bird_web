<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-12-01
 * Time: 15:20
 */

namespace app\api\controller;


use app\api\model\WordRecordPhoto;
use think\Request;

class WordRecord extends Common
{
    protected $record;
    protected $recordPhoto;

    public function __construct()
    {
        parent::__construct();
        $this->record = new \app\api\model\WordRecord();
        $this->recordPhoto = new WordRecordPhoto();
    }

    public function selectList(){
        $list = \app\api\model\WordRecord::with(['photoes'])->select();
        if(!$list){
            $this->return_msg( true, "获取成功", []);
        }
        $this->return_msg( true, "获取成功", $list);
    }

    public function insert(){
        $params = input('');

        $result = $this->record->insert_data($params);
        if(!$result){
            $this->return_msg( false, "添加失败");
        }
        $this->return_msg( true, "添加成功");
    }

    public function findById(){
        $id = input('id');
        if(!$id){
            $this->return_msg( false, "参数错误");
        }
        $data = $this->record->find_by_id($id);
        if(!$data){
            $this->return_msg( false, "获取失败");
        }
        $this->return_msg( true, "获取成功", $data);
    }

    public function updateById(){
        $params = input('');

        //$params['imgList'] = json_encode($params['imgList'], JSON_UNESCAPED_UNICODE);
        $result = $this->record->update_by_id($params['id'], $params);
        if(!$result){
            $this->return_msg( false, "修改失败");
        }
        $this->return_msg( true, "修改成功");
    }

    public function deleteById(){
        $id = input('id');
        if(!$id){
            $this->return_msg( false, "参数错误");
        }
        $result = $this->record->delete_by_id($id);
        if(!$result){
            $this->return_msg( false, "删除失败");
        }
        $this->return_msg( true, "删除成功");
    }

    public function getOptions(){
        $configM = new \app\admin\model\Config();
        $model = $configM->getSettingModel ( 'web' ); //获取配置实例
        if (!$model) {
            $optionList = [];
        }else{
            $optionList = explode('|', $model['options']);
        }
        $options = [];
        foreach ($optionList as $k=>$v){
//            var_dump($v);
            $option = [];
            $option['key'] = $v;
            $option['value'] = $v;
            $options[] = $option;
        }
        $res['work_type']=$options;
        $workerList = $this->get_all_staff();
        $res['worker_list']=$workerList;

        $this->return_msg( true, "获取成功", $res);
    }

    /**
     * 获取所有有效的工作人员
     * @return array  $admins     [工作人员数组]
     */
    public function get_all_staff(){

        $admin  = new \app\api\model\Admin();
        $admins = $admin -> all_worker();

        return $admins;
    }
}
