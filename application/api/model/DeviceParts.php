<?php

namespace app\api\model;

use think\Model;

class DeviceParts extends Base{

    protected $hidden = [
        'sort', 'type', 'valid', 'export_valid',
        'addtime', 'updatetime',
        'ext1', 'ext2', 'ext3',
    ];

    protected function initialize(){
        parent::initialize();
    }

    public function part_info($id){
        $info = $this::all(function($query) use($id){
          $query  -> where( 'device_id', $id)
                  -> where( 'valid', 1)
                  -> order( "sort desc, addtime desc");
        });
        $options = $this -> process_option($info, 'name', 'name');
        return $options;
    }

    public function part_name_array($id){
        $data = $this::where( 'device_id', $id ) -> column( 'name' );
        return $data;
    }

    public function part_option($id){
        $data = $this::where([ 'device_id'=>$id, 'valid'=>1 ]) -> column('name');
        return $data;
    }

}
