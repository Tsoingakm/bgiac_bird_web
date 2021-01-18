<?php

namespace app\api\model;

use think\Model;

class Device extends Base{

    protected $hidden = [
        'valid', 'addtime', 'updatetime',
        'ext1', 'ext2', 'ext3',
    ];

    protected function initialize(){
        parent::initialize();
    }

    public function parts(){
        return $this -> hasMany('DeviceParts') -> field( 'device_id, name' );
    }

    public function codes(){
        return $this -> hasMany('DeviceCode') -> field( 'device_id, code' )->order("code asc");
    }

    public function device_info(){
        $Device = new Device();
        $data = $Device -> with([ 'codes', 'parts' ])  -> where('valid', 1)  -> select();
        return $data;
    }

    public function device_list(){
        $info = $this::all(function($query){
          $query -> where( "valid", 1 )
                 -> order( "addtime desc");
        });
        $options = $this -> process_option($info, 'name', 'name');
        return $options;
    }

    public function get_options_for_stats(){
        $options = $this::all(function($query){
          $query  -> field( 'device_id, name')
                  -> where( "valid", 1 )
                  -> order( "addtime desc");
        });
        return $options;
    }

    public function findByName($name){
        $info = $this::getByName($name);
        return $info->device_id;
    }
}
