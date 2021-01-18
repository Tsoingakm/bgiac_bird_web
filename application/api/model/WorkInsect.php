<?php

namespace app\api\model;

use app\common\Util\GisHelper;
use think\Model;
use think\Loader;
use app\api\model\WorkArea;

class WorkInsect extends Base {

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

    public function getIsComplianceAttr($value)
    {
        $status = [ 0=>'不达标', 1=>'达标' ];
        return $status[$value];
    }

    public function insert_data($params){
        $date   = $params['working_date'];
        $times  = $params['spary_times'];

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

        $is_display = $this -> is_display_on_map($date, $params['maintain_area']);
        $params['is_display_on_map'] = $is_display;

        $model  = new WorkInsect($params);
        $result = $model->allowField(true)->save();

        if($result){
            $this -> data_processing($date, $times, $params);
        }

        return $result;
    }

    public function delete_by_id($ids){
        $idsArr = explode(',', $ids);
        $batchsArr = [];
        foreach($idsArr as $id){
            $data = WorkInsect::get(['id'=>$id]);
            $batchsArr[] = [
              'date' => strtotime($data->working_date),
              'times' => $data->spary_times
            ];
        }

        $result = $this::where( 'id', 'in', $ids ) -> delete();

        if($result){
            foreach($batchsArr as $batch){
                $sameBatch = WorkInsect::get([
                    'working_date' => $batch['date'],
                    'spary_times'  => $batch['times']
                ]);

                if($sameBatch){
                    $this->data_processing(strtotime($sameBatch['working_date']), $sameBatch['spary_times'], $sameBatch->getData());
                }
            }
        }

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
        $date   = $params['working_date'];
        $times  = $params['spary_times'];

        $location = $this->area->get_lng_and_lat($params['maintain_area']);

        if($location){
          $params['coordinates_wgs84'] = $location['coordinates_wgs84'];
          $params['coordinates_gcj02'] = $location['coordinates_gcj02'];
        }

        $work = new WorkInsect;
        $result = $work -> allowField(true) -> save($params,['id' => $id]);

        if($result){
            $this -> data_processing($date, $times, $params);
        }

        return $result;
    }

    public function select_all($where){
        $list = $this::all(function($query) use($where){
            $query  ->  where( $where )
                    ->  order( ['working_date'=>'desc', 'spary_times'=>'desc'] );
        });
        return $list;
    }

    public function select_all_for_app($params){
        $start  = $params['starting_time'];
        $end    = $params['end_time'];

        $page_size    = $params['page_size'];
        $current_page = $params['current_page'];

        $list = $this::where( 'working_date', 'between', [ $start, $end ] )
                      ->  order( 'working_date desc, spary_times desc, start_time desc' )
                      ->  paginate( $page_size, false, [ 'page' => $current_page ] );
        return $list;
    }

    public function historic_records(){
        $recodes = [];
        $list = $this::all(function($query){
            $query  ->  distinct(true)
                    ->  field('working_date, spary_times')
                    ->  whereTime('working_date', '>', '-2 days')
                    ->  order(['working_date'=>'desc', 'spary_times'=>'desc']);
        });
        foreach($list as $item){
            $date   = strtotime($item['working_date']);
            $times  = $item['spary_times'];

            $result = $this::all(function($query) use($date, $times){
                $query  ->  where( 'working_date',  $date)
                        ->  where( 'spary_times',   $times )
                        ->  order( 'start_time', 'desc' );
            });
            $recodes[] = [
              'date'    =>  $item['working_date'],
              'times'   =>  $item['spary_times'],
              'record'  =>  $result
            ];
        }

        return $recodes;
    }

    public function select_for_statistics($area, $start, $end){
        $list = $this::all(function($query) use($area, $start, $end){
            $query  ->  where( 'maintain_area', $area )
                    ->  where( 'working_date', 'between', [ $start, $end ] )
                    ->  order( ['working_date'=>'desc'] );
        });
        return $list;
    }

    /**
     * 处理同一车的各种计算值
     * @param  int $date  [日期的时间戳]
     * @param  int $times [喷药次数代号]
     * @return [type]        [description]
     */
    public function data_processing($date, $times, $params){
        $total_area = $this::where(['working_date'=>$date, 'spary_times'=>$times])->sum('work_area');
        if($total_area == 0){
          return ;
        }

        $data['dilutability1']  = $params['water_consumption'] * 1000000 / $params['dosage1'];
        $data['avg_dosage1']    = $params['dosage1'] * 666 / $total_area;

        $data['dilutability2']  = $params['dosage2'] == 0      ? 0 : $params['water_consumption'] * 1000000 / $params['dosage2'];
        $data['avg_dosage2']    = empty($params['dosage2'] )   ? 0 : $params['dosage2'] * 666 / $total_area;

        $data['dilutability3']  = $params['dosage3'] == 0      ? 0 : $params['water_consumption'] * 1000000 / $params['dosage3'];
        $data['avg_dosage3']    = empty($params['dosage3'] )   ? 0 : $params['dosage3'] * 666 / $total_area;

        $avg_water              = $params['water_consumption'] * 1000 * 666 / $total_area;
        $data['avg_water']      = round($avg_water, 1);

        $data['is_compliance']  = $data['avg_water'] > 60 ? 1 : 0;

        $this::where( [ 'working_date' => $date, 'spary_times' => $times ] )
            -> update([
                  'dilutability1' => $data['dilutability1'],
                  'avg_dosage1'   => $data['avg_dosage1'],
                  'dilutability2' => $data['dilutability2'],
                  'avg_dosage2'   => $data['avg_dosage2'],
                  'dilutability3' => $data['dilutability3'],
                  'avg_dosage3'   => $data['avg_dosage3'],
                  'avg_water'     => $data['avg_water'],
                  'is_compliance' => $data['is_compliance'],
            ]);
    }

    /**
     * 判断该条记录是否需要在区域统计地图上显示
     * 判断条件：同一区域 7天 内的最新记录
     * @param  int $date  [日期的时间戳]
     * @return boolean    [description]
     */
    public function is_display_on_map($date, $area){
        $is_display = 1;
        $records = \app\api\model\WorkInsect::all(function($query) use($area){
            $query -> where('maintain_area', $area)
                   -> whereTime('addtime', 'week');
        });

        if(!empty($records)){
            foreach($records as $record){
                if($record->addtime > time()){
                    $record->is_display_on_map = 1;
                    $record->save();
                    $is_display = 0;
                }
                else{
                    $record->is_display_on_map = 0;
                    $record->save();
                    $is_display = 1;
                }

            }
        }
        return $is_display;
    }




}
