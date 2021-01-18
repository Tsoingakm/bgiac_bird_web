<?php

namespace app\api\model;

use app\common\Util\GisHelper;
use think\Model;
use think\Loader;
use app\api\model\BirdArea;

class BirdDrive extends Base {

    const x_PI  = 52.35987755982988;
    const PI  = 3.1415926535897932384626;
    const a = 6378245.0;
    const ee = 0.00669342162296594323;

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
        if($params['activity_line']){
            $params['activity_line_gcj02'] = $params['activity_line'];
            $pointWGSList = [];
            $pointStrWGSList = [];
            $pointsStrList = explode('/', trim($params['activity_line']));
            foreach ($pointsStrList as $k=>$v){
                $point = explode(',', trim($v));
                $WGSPoint = $this->gcj02towgs84($point[0], $point[1]);
                $pointWGSList[] = $WGSPoint;
            }
            foreach ($pointWGSList as $k=>$v){
                $WGSPointStr = implode(',', $v);
                $pointStrWGSList[] = $WGSPointStr;
            }
            $params['activity_line_wgs84'] = implode('/', $pointStrWGSList);
        }

        if(isset($params['lat_gcj02']) && $params['lat_gcj02'] > 0 && isset($params['lng_gcj02']) && $params['lng_gcj02'] > 0){
            $wgs84 = GisHelper::gcj_To_wgs84($params['lat_gcj02'], $params['lng_gcj02']);
            $params['lat_wgs84'] = $wgs84['lat'];
            $params['lng_wgs84'] = $wgs84['lon'];
        }
        $model  = new BirdDrive($params);
        $result = $model -> allowField(true) -> save();
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
        $location = $this->area->get_lng_and_lat($params['area']);
        if($location){
          $params['coordinates_wgs84'] = $location['coordinates_wgs84'];
          $params['coordinates_gcj02'] = $location['coordinates_gcj02'];
        }



        if($params['activity_line']){
            $params['activity_line_gcj02'] = $params['activity_line'];
            $pointWGSList = [];
            $pointStrWGSList = [];
            $pointsStrList = explode('/', trim($params['activity_line']));
            foreach ($pointsStrList as $k=>$v){
                $point = explode(',', trim($v));
                $WGSPoint = $this->gcj02towgs84($point[0], $point[1]);
                $pointWGSList[] = $WGSPoint;
            }
            foreach ($pointWGSList as $k=>$v){
                $WGSPointStr = implode(',', $v);
                $pointStrWGSList[] = $WGSPointStr;
            }
            $params['activity_line_wgs84'] = implode('/', $pointStrWGSList);
        }else{
            $params['activity_line_gcj02'] = '';
            $params['activity_line_wgs84'] = '';
        }

        $bird   = new BirdDrive;
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

        $page_size     = $params['page_size'];
        $current_page = $params['current_page'];

        $list = $this::where( 'patrol_date', 'between', [ $start, $end ] )
                      ->order( 'patrol_date desc, patrol_time desc' )
                      ->paginate( $page_size, false, [ 'page' => $current_page ] );
        return $list;
    }

    public function historic_records(){
        $recodes = [];
        $list = $this::all(function($query){
            $query  ->  distinct(true)
                    ->  field('patrol_date')
                    ->  whereTime('patrol_date', '-2 days')
                    ->  order(['patrol_date'=>'desc', 'patrol_time'=>'desc']);
        });
        foreach($list as $item){
            $date   = strtotime($item['patrol_date']);

            $result = $this::all(function($query) use($date, $times){
                $query  ->  where( 'patrol_date', $date )
                        ->  order( 'patrol_time', 'desc' );
            });
            $recodes[] = [
              'date'    =>  $item['patrol_date'],
              'record'  =>  $result
            ];
        }
        return $recodes;
    }

    public function gcj02towgs84($lng, $lat) {
        $dlat = $this->transformlat($lng - 105.0, $lat - 35.0);
        $dlng = $this->transformlng($lng - 105.0, $lat - 35.0);
        $radlat = $lat / 180.0 * self::PI;
        $magic = sin($radlat);
        $magic = 1 - self::ee * $magic * $magic;
        $sqrtmagic = sqrt($magic);
        $dlat = ($dlat * 180.0) / ((self::a * (1 - self::ee)) / ($magic * $sqrtmagic) * self::PI);
        $dlng = ($dlng * 180.0) / (self::a / $sqrtmagic * cos($radlat) * self::PI);
        $mglat = $lat + $dlat;
        $mglng = $lng + $dlng;
        return array($lng * 2 - $mglng, $lat * 2 - $mglat);
    }

    private function transformlat($lng, $lat) {
        $ret = -100.0 + 2.0 * $lng + 3.0 * $lat + 0.2 * $lat * $lat + 0.1 * $lng * $lat + 0.2 * sqrt(abs($lng));
        $ret += (20.0 * sin(6.0 * $lng * self::PI) + 20.0 * sin(2.0 * $lng * self::PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($lat * self::PI) + 40.0 * sin($lat / 3.0 * self::PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($lat / 12.0 * self::PI) + 320 * sin($lat * self::PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }
    private function transformlng($lng, $lat) {
        $ret = 300.0 + $lng + 2.0 * $lat + 0.1 * $lng * $lng + 0.1 * $lng * $lat + 0.1 * sqrt(abs($lng));
        $ret += (20.0 * sin(6.0 * $lng * self::PI) + 20.0 * sin(2.0 * $lng * self::PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($lng * self::PI) + 40.0 * sin($lng / 3.0 * self::PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($lng / 12.0 * self::PI) + 300.0 * sin($lng / 30.0 * self::PI)) * 2.0 / 3.0;
        return $ret;
    }

}
