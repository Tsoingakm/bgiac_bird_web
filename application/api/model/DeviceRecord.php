<?php

namespace app\api\model;

use think\Model;
use think\Loader;

class DeviceRecord extends Base {

    protected $hidden = [
      'addtime', 'updatetime',
      'ext1', 'ext2', 'ext3'
    ];

    protected $type = [
        'working_date'  =>  'timestamp:Y/n/j',
        'working_time'  =>  'timestamp:G:i'
    ];

    protected $createTime = 'addtime';
    protected $updateTime = 'updatetime';

    protected function initialize(){
        parent::initialize();
    }

    protected function scopeMonth($query, $month){
        $query->whereTime('working_date', 'between', $month);
    }

    public function insert_data($params){
        $model = new DeviceRecord($params);
        $result = $model->allowField(true)->save();
        return $result;
    }

    public function delete_by_id($ids){
        $result = $this::where( 'id', 'in', $ids ) -> delete();
        return $result;
    }

    public function find_by_id($id){
        $data = $this::get($id);
        if(!$data){
            return false;
        }
        return $data;
    }

    public function update_by_id($id,$params){
        $bird = new DeviceRecord;
        $result = $bird -> allowField(true) -> save($params,['id' => $id]);
        return $result;
    }

    public function select_all($where){
        $list = $this::all(function($query) use($where){
            $query  ->  where( $where )
                    ->  order( ['working_date'=>'desc', 'working_time'=>'desc'] );
        });
        return $list;
    }

    public function select_all_for_app($params){
        $start  = $params['starting_time'];
        $end    = $params['end_time'];

        $page_size    = $params['page_size'];
        $current_page = $params['current_page'];

        $list = $this::where( 'working_date', 'between', [ $start, $end ] )
                      ->order( 'working_date desc, working_time desc' )
                      ->paginate( $page_size, false, [ 'page' => $current_page ] );
        return $list;
    }

    public function historic_records(){
        $recodes = [];
        $list = $this::all(function($query){
            $query  ->  distinct(true)
                    ->  field('working_date')
                    ->  whereTime('working_date', '-2 days')
                    ->  order(['working_date'=>'desc', 'working_time'=>'desc']);
        });
        foreach($list as $item){
            $date   = strtotime($item['working_date']);

            $result = $this::all(function($query) use($date){
                $query  ->  where( 'working_date', $date )
                        ->  order( 'working_time', 'desc' );
            });
            $recodes[] = [
              'date'    =>  $item['working_date'],
              'record'  =>  $result
            ];
        }
        return $recodes;
    }


}
