<?php

namespace app\api\model;

use think\Model;

class BirdName extends Base{

    protected $hidden = [
      'addtime', 'updatetime',
      'ext1', 'ext2', 'ext3'
    ];

    protected function initialize(){
      parent::initialize();
    }

    public function bird_info(){
      $info = BirdName::all(function($query){
        $query -> order( "weights desc");
      });
      $options = $this -> process_option($info, 'bird_name', 'bird_name');
      return $options;
    }

    public function insert_data($params){
        $model  = new BirdName($params);
        $result = $model -> allowField(true) -> save();
        return $result;
    }

    public function delete_by_id($ids){
        $result = $this::where( 'id', 'in', $ids ) -> delete();
        return $result;
    }

    public function find_by_name($name){
        $result = $this::get([ 'bird_name'=>$name ]);
        if(!$result){
            return false;
        }
        return $result;
    }

    public function update_by_id($id,$params){
        $bird   = new BirdName;
        $result = $bird -> allowField(true) -> save($params,['id' => $id]);
        return $result;
    }

}
