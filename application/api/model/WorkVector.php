<?php

namespace app\api\model;

use app\common\Util\GisHelper;
use think\Model;
use think\Loader;
use app\api\model\WorkArea;

class WorkVector extends Base {

    protected $area;

    protected $hidden = [
      'addtime', 'updatetime',
      'coordinates_wgs84', 'coordinates_gcj02',
    ];

    protected $type = [
        'working_date'  =>  'timestamp:Y/n/j',
        'start_time'    =>  'timestamp:G:i',
        'end_time'      =>  'timestamp:G:i'
    ];

    protected $createTime = 'addtime';
    protected $updateTime = 'updatetime';

    protected function initialize(){
        parent::initialize();
        $this->area = new WorkArea();
    }

    protected function scopeMonth($query, $month){
        $query->whereTime('working_date', 'between', $month);
    }

    public function insert_data($params){
        $location = $this->area->get_lng_and_lat($params['maintain_area']);
        if($location){
          $params['coordinates_wgs84'] = $location['coordinates_wgs84'];
          $params['coordinates_gcj02'] = $location['coordinates_gcj02'];
        }

        if(isset($params['lat_gcj02']) && $params['lat_gcj02'] > 0 && isset($params['lng_gcj02']) && $params['lng_gcj02'] > 0){
            $wgs84 = GisHelper::gcj_To_wgs84($params['lat_gcj02'], $params['lng_gcj02']);
            $params['lat_wgs84'] = $wgs84['lat'];
            $params['lng_wgs84'] = $wgs84['lon'];
        }

        $model = new WorkVector($params);
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

    public function find_by_guid($guid){
        $data = $this::get(['guid'=>$guid]);
        if(!$data){
            return false;
        }
        return $data;
    }

    public function update_by_id($id,$params){
        $location = $this->area->get_lng_and_lat($params['maintain_area']);
        if($location){
          $params['coordinates_wgs84'] = $location['coordinates_wgs84'];
          $params['coordinates_gcj02'] = $location['coordinates_gcj02'];
        }

        $work = new WorkVector;
        $result = $work -> allowField(true) -> save($params,['id' => $id]);
        return $result;
    }

    public function select_all($where){
        $list = $this::all(function($query) use($where){
            $query  ->  where( $where )
                    ->  order( ['working_date'=>'desc'] );
        });
        return $list;
    }


    public function select_all_for_app($params){
        $start  = $params['starting_time'];
        $end    = $params['end_time'];

        $page_size     = $params['page_size'];
        $current_page = $params['current_page'];

        $list = $this::where( 'working_date', 'between', [ $start, $end ] )
                      ->  order( 'working_date desc, start_time desc' )
                      ->  paginate( $page_size, false, [ 'page' => $current_page ] );
        return $list;
    }

    public function select_for_statistics($area, $start, $end){
        $list = $this::all(function($query) use($area, $start, $end){
            $query  ->  where( 'maintain_area', $area )
                    ->  where( 'working_date', 'between', [ $start, $end ] )
                    ->  order( ['working_date'=>'desc'] );
        });
        return $list;
    }

    public function historic_records(){
        $recodes = [];
        $list = $this::all(function($query){
            $query  ->  distinct(true)
                    ->  field('working_date')
                    ->  whereTime('working_date', '>', '-2 days')
                    ->  order(['working_date'=>'desc']);
        });
        foreach($list as $item){
            $date   = strtotime($item['working_date']);

            $result = $this::all(function($query) use($date){
                $query  ->  where( 'working_date', $date )
                        ->  order( 'start_time', 'desc' );
            });
            $recodes[] = [
              'date'    =>  $item['working_date'],
              'record'  =>  $result
            ];
        }
        return $recodes;
    }

}
