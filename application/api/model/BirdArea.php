<?php

namespace app\api\model;

use think\Model;

class BirdArea extends Base{

    const x_PI  = 52.35987755982988;
    const PI  = 3.1415926535897932384626;
    const a = 6378245.0;
    const ee = 0.00669342162296594323;

    protected function initialize(){
        parent::initialize();
    }

    public function area_info($aid){
        $list = BirdArea::where(function($query){
            $query->where(['valid'=>1]);
        })->whereOr(function($query) use ($aid){
            $query->where(['valid'=>0, 'aid'=>$aid]);
        })->select();
        $options = $this -> process_option($list, 'area_name', 'area_name');
        return $options;
    }

    public function get_lng_and_lat($area){
        $data = $this::getByAreaName($area);
        return $data;
    }

    public function find_by_id($id){
        $data = $this::get($id);
        if(!$data){
            return false;
        }
        return $data;
    }

    public function update_by_id($id, $params){
        $params['coordinates_gcj02'] = $params['points'];
        $pointWGSList = [];
        $pointStrWGSList = [];
        $pointsStrList = explode('/', trim($params['points']));
        foreach ($pointsStrList as $k=>$v){
            $point = explode(',', trim($v));
            $WGSPoint = $this->gcj02towgs84($point[0], $point[1]);
            $pointWGSList[] = $WGSPoint;
        }
        foreach ($pointWGSList as $k=>$v){
            $WGSPointStr = implode(',', $v);
            $pointStrWGSList[] = $WGSPointStr;
        }
        $params['coordinates_wgs84'] = implode('/', $pointStrWGSList);
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $model   = new BirdArea();
        $result = $model -> allowField(true)->save($params,['id' => $id]);
        return $result;
    }

    public function delete_by_id($ids){
        $result = $this::where( 'id', 'in', $ids ) -> delete();
        return $result;
    }

    public function insert_data($params){
        $params['coordinates_gcj02'] = $params['points'];
        $pointWGSList = [];
        $pointStrWGSList = [];
        $pointsStrList = explode('/', trim($params['points']));
        foreach ($pointsStrList as $k=>$v){
            $point = explode(',', trim($v));
            $WGSPoint = $this->gcj02towgs84($point[0], $point[1]);
            $pointWGSList[] = $WGSPoint;
        }
        foreach ($pointWGSList as $k=>$v){
            $WGSPointStr = implode(',', $v);
            $pointStrWGSList[] = $WGSPointStr;
        }
        $params['coordinates_wgs84'] = implode('/', $pointStrWGSList);
        $params['addtime'] = strtotime(date('Y-m-d H:i:s'));
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $model  = new BirdArea($params);
        $result = $model -> allowField(true) -> save();
        return $result;
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
