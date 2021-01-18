<?php

namespace app\api\model;

use think\Model;

class DeviceCode extends Base{

    protected function initialize(){
      parent::initialize();
    }

    public function code_info($id){
        $info = $this::all(function($query) use($id){
          $query  -> where( 'device_id', $id)
                  -> where( 'valid', 1)
                  -> order( "code asc,addtime desc");
        });
        $options = $this -> process_option($info, 'code', 'code');
        return $options;
    }

    public function code_option($id){
        $data = $this::where([ 'device_id'=>$id, 'valid'=>1 ]) -> column('code');
        return $data;
    }

}
