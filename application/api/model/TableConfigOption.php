<?php

namespace app\api\model;

use think\Model;
use think\Db;
use think\Loader;

class TableConfigOption extends Base {

    protected $hidden = [
      'valid',
      'addtime', 'updatetime',
      'ext1', 'ext2', 'ext3',
    ];

    protected function initialize(){
        parent::initialize();
    }

    public function get_options($index){
        $options = $this::all(function($query) use($index){
            $query  -> field( 'key, value' )
                    -> where( 'index_code', $index )
                    -> order( 'sort desc' );
        });
        return $options;
    }

    public function get_options_for_stats($index){
        $options = $this::all(function($query) use($index){
            $query  -> field( 'id, index_code, key, value' )
                    -> where( 'index_code', $index )
                    -> order( 'sort desc' );
        });
        return $options;
    }

}
