<?php

namespace app\api\model;

use think\Model;

class Base extends Model{

    protected function initialize(){
      parent::initialize();
    }

    /**
     * 处理选项
     * @param  [array] $arr     [原始数组]
     * @param  string $key   [键名]
     * @param  string $value [值]
     * @return [array]        [处理后的数组]
     */
    public function process_option($arr, $key = 'key', $value = 'value'){
        $options = [];
        foreach($arr as $k=>$v){
           $options[] = [
             'key'    => $v[$key],
             'value'  => $v[$value]
           ];
        }
        return $options;
    }

}
