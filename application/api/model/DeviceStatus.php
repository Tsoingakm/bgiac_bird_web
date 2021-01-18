<?php

namespace app\api\model;

use think\Model;

class DeviceStatus extends Base{

    protected function initialize(){
      parent::initialize();
    }

    public function insert_data($params){
        $model = new DeviceStatus($params);
        $result = $model->allowField(true)->save();
        return $result;
    }

    public function delete_by_id($ids){
        $result = $this::where( 'id', 'in', $ids ) -> delete();
        return $result;
    }

    public function update_by_id($id,$params){
        $model  = new DeviceStatus;
        $result = $model -> allowField(true) -> save($params,['id' => $id]);
        return $result;
    }

    public function find_by_id($id){
        $data = $this::get($id);
        if(!$data){
            return false;
        }
        return $data;
    }

    public function device_status(){
        $info = $this::all(function($query){
          $query  -> where( 'type', 1)
                  -> where( "valid", 1 )
                  -> order( "addtime desc");
        });
        $options = $this -> process_option($info, 'name', 'name');
        return $options;
    }

    public function parts_status(){
      $info = $this::all(function($query){
          $query  -> where( 'type', 2)
                  -> where( "valid", 1 )
                  -> order( "addtime desc");
        });
        $options = $this -> process_option($info, 'name', 'name');
        return $options;
    }

}
