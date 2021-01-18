<?php

namespace app\api\model;

use think\Model;
use think\Db;
use think\Loader;

class TableConfig extends Base {

    protected $hidden = [
      'table_name', 'index_code', 'valid', 'output_valid', 'sort',
      'addtime', 'updatetime', 'section_code', 'section_sort', 'can_del',
      'ext1', 'ext2', 'ext3',
    ];

    protected function initialize(){
        parent::initialize();
    }

    public function from_item($table){
        $list = TableConfig::where(['table_name'=>$table, 'can_del'=>'0', 'valid'=>'1'])->column('id, column_code, index_code, default_value');
        return $list;
    }

    public function extra_item($table){
        $list = $this::all(function($query) use($table){
            $query  ->  where( 'table_name', $table )
                    ->  where( 'can_del', 1 )
                    ->  where( 'valid', 1)
                    ->  order( 'sort asc' );
        });
        return $list;
    }

    public function item_options($index){
        $options = TableConfigOption::all(function($query) use($index){
            $query  -> field( 'id, index_code, key, value' )
                -> where( 'index_code', $index )
                -> order( 'sort desc' );
        });
        return $options;
    }

}
