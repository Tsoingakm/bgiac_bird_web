<?php

namespace app\api\model;

use app\common\Util\GisHelper;
use think\Model;
use think\Loader;
use app\api\model\BirdArea;

class BirdSeize extends Base {

    protected $area;

    protected $type = [
        'patrol_date'  =>  'timestamp:Y/n/j',
        'patrol_time'  =>  'timestamp:G:i'
    ];

    protected $createTime = 'addtime';
    protected $updateTime = 'updatetime';

    protected function initialize(){
        parent::initialize();
        $this->area = new BirdArea();
    }

    protected function scopeMonth($query, $month){
        $query->whereTime('patrol_date', 'between', $month);
    }


    public function insert_data($params){
        $location = $this->area->get_lng_and_lat($params['area']);
        if($location){
          $params['coordinates_wgs84'] = $location['coordinates_wgs84'];
          $params['coordinates_gcj02'] = $location['coordinates_gcj02'];
        }

        if(isset($params['lat_gcj02']) && $params['lat_gcj02'] > 0 && isset($params['lng_gcj02']) && $params['lng_gcj02'] > 0){
            $wgs84 = GisHelper::gcj_To_wgs84($params['lat_gcj02'], $params['lng_gcj02']);
            $params['lat_wgs84'] = $wgs84['lat'];
            $params['lng_wgs84'] = $wgs84['lon'];
        }

        $model = new BirdSeize($params);
        $result = $model->allowField(true)->save();
        return $result;
    }

    public function delete_by_id($ids){
        $result = $this::where( 'id', 'in', $ids ) -> delete();
        return $result > 0?true: false;
    }

    public function find_by_id($id){
        $data = $this::get($id);
        if(!$data){
            return false;
        }
        return $data;
    }

    public function find_by_guid($guid){
        $data = $this::get(['guid'=>$guid]);
        if(!$data){
            return false;
        }
        return $data;
    }

    public function update_by_id($id,$params){
        $location = $this->area->get_lng_and_lat($params['area']);
        if($location){
          $params['coordinates_wgs84'] = $location['coordinates_wgs84'];
          $params['coordinates_gcj02'] = $location['coordinates_gcj02'];
        }

        $bird = new BirdSeize;
        $result = $bird -> allowField(true) -> save($params,['id' => $id]);
        return $result;
    }

    public function select_all($where){
        $list = $this::all(function($query) use($where){
            $query  ->  where( $where )
                    ->  order( ['patrol_date'=>'desc', 'patrol_time'=>'desc'] );
        });
        return $list;
    }

    public function select_all_for_app($params){
        $start  = $params['starting_time'];
        $end    = $params['end_time'];

        $page_size    = $params['page_size'];
        $current_page = $params['current_page'];

        $list = $this::where( 'patrol_date', 'between', [ $start, $end ] )
                      ->order( 'patrol_date desc, patrol_time desc' )
//                        ->limit($current_page - 1, $page_size)
//            ->fetchSql(true)
//            ->select();
//        var_dump($list);exit;
                      ->paginate( $page_size, false, [ 'page' => $current_page ] );
        return $list;
    }

    public function historic_records(){
        $recodes = [];
        $list = $this::all(function($query){
            $query  ->  distinct(true)
                    ->  field('patrol_date')
                    ->  whereTime('patrol_time', '-2 days')
                    ->  order(['patrol_date'=>'desc', 'patrol_time'=>'desc']);
        });
        foreach($list as $item){
            $date   = strtotime($item['patrol_date']);

            $result = $this::all(function($query) use($date){
                $query  ->  where('patrol_date', $date)
                        ->  order('patrol_time', 'desc');
            });
            $recodes[] = [
              'date'    =>  $item['patrol_date'],
              'record'  =>  $result
            ];
        }
        return $recodes;
    }


}
