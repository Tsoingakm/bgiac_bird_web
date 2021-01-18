<?php

namespace app\api\model;

use app\common\Util\GisHelper;
use think\Model;
use think\Loader;
use app\api\model\BirdArea;

class BirdCondition extends Base {

    protected $area;

    protected $createTime = 'addtime';
    protected $updateTime = 'updatetime';

    protected function initialize(){
        parent::initialize();
        $this->area = new BirdArea();
    }

    protected function scopeMonth($query, $month){
        $query->whereTime('day_int', 'between', $month);
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

        $params['day_int']    = strtotime($params['day_str']);
        $params['time_int']   = strtotime($params['day_str'].' '.$params['time_str']);
        $params['view_point'] = "N/A";
        $params['step1']      = empty($params['stpe1']) ? "无" : $params['stpe1'];
        $params['step2']      = empty($params['stpe2']) ? "无" : $params['stpe2'];
        $params['result']     = empty($params['result']) ? "无需评定" : $params['result'];
        $model = new BirdCondition($params);
        $result = $model->allowField(true)->save();
        return $result;
    }

    public function delete_by_id($ids){
        $result = BirdCondition::where( 'id', 'in', $ids ) -> delete();
        return $result;
    }

    public function find_by_id($id){
        $data = BirdCondition::get($id);
        if(!$data){
            return false;
        }
        return $data;
    }

    public function find_by_guid($guid){
        $data = BirdCondition::get(['guid'=>$guid]);
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

        $params['day_int']    = strtotime($params['day_str']);
        $params['time_int']   = strtotime($params['day_str'].' '.$params['time_str']);
        $params['view_point'] = "N/A";
        $params['step1']      = empty($params['stpe1']) ? "无" : $params['stpe1'];
        $params['step2']      = empty($params['stpe2']) ? "无" : $params['stpe2'];
        $params['result']     = empty($params['result']) ? "无需评定" : $params['result'];
        $bird = new BirdCondition;
        $result = $bird -> allowField(true) -> save($params,['id' => $id]);
        return $result;
    }

    public function historic_records(){
        $recodes = [];
        $list = BirdCondition::all(function($query){
            $query  -> distinct(true)
                    -> field('day_int, view_number')
                    -> whereTime('day_int', '-2 days')
                    -> order(['day_int'=>'desc', 'view_number'=>'desc', 'time_int'=>'desc']);
        });
        foreach($list as $item){
            $day  = $item['day_int'];
            $no   = $item['view_number'];
            $result = BirdCondition::all(function($query) use($day,$no){
                $query  -> where('day_int', $day)
                        -> where('view_number', $no)
                        -> order('time_int', 'desc');
            });
            $recodes[] = [
              'date'          => date("Y/m/d", $item['day_int']),
              'view_number'   => $item['view_number'],
              'record'        => $result
            ];
        }
        return $recodes;
    }

    public function find_by_No($params){
        $where = [
            'day_str'     =>  $params['day'],
            'view_number' =>  $params['No']
         ];
        $data = BirdCondition::get($where);
        return $data;
    }

    public function select_all($where){
      $list = BirdCondition::all(function($query) use($where){
          $query  ->  where( $where )
                  ->  order(['day_int'=>'desc', 'view_number'=>'desc', 'time_int'=>'desc']);
      });
      return $list;
    }

    public function select_all_for_app($params){
      $start  = $params['starting_time'];
      $end    = $params['end_time'];

      $pageSize     = $params['page_size'];
      $current_page = $params['current_page'];

      $list = $this::where( 'day_int', 'between', [ $start, $end ] )
                    ->order( 'day_int desc, view_number desc, time_int desc' )
                    ->paginate( $pageSize, false, [ 'page' => $current_page ] );
      return $list;
    }

}
