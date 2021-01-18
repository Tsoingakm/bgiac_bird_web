<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/8/13
 * Time: 11:37
 */

namespace app\common\Util;


class KmlHelper
{
    public function  testKml($path){
        $root = dirname ( $_SERVER ['DOCUMENT_ROOT'] . "/aa" ) . "/";
        $file=$root.$path;
    }

    /**
     * 获取KML的区域
     * @param $kmlContent
     * @return array
     */
    public static function getKmlPolygon($kmlContent){
        $pattern = '/<Placemark.*?>([\s\S]*?)<\/Placemark>/';
        preg_match_all($pattern, $kmlContent, $match);
        $polygonArr=[];
        foreach ($match[0] as $k=>$v){
            $item=[];
//            print_r($v);
            $pattern = '/<name.*?>([\s\S]*?)<\/name>/';
            preg_match_all($pattern, $v, $name);
//            print_r($name);
            $item['name']=$name[1][0];
            $pattern = '/<Polygon.*?>([\s\S]*?)<\/Polygon>/';
            preg_match_all($pattern, $v, $polygon);
//            print_r($polygon);
//            echo "\n----\n";
            foreach($polygon[1] as $k1=>$v1){
                $pattern = '/<coordinates.*?>([\s\S]*?)<\/coordinates>/';
                preg_match_all($pattern, $v, $coordinates);
//                print_r($coordinates);
                $item['coordinates_wgs84']=trim($coordinates[1][0]);
                $item['coordinates_gcj02']='';
                //经纬度转移
                $item['coordinates_arr']=explode(" ",trim($item['coordinates_wgs84']));
                $item['coordinates_gcj02_arr']=[];
                $item['coordinates_wgs84_arr']=[];

                $dot='';
                foreach ($item['coordinates_arr'] as $caK=>$caV){
                    $item['coordinates_wgs84_arr'][$caK]=explode(",",trim($caV));
                    $item['coordinates_gcj02_arr'][$caK]=GisHelper::wgs84_To_Gcj02($item['coordinates_wgs84_arr'][$caK][1],$item['coordinates_wgs84_arr'][$caK][0]);
                    $item['coordinates_gcj02'].= $dot.$item['coordinates_gcj02_arr'][$caK]['lon'].','.$item['coordinates_gcj02_arr'][$caK]['lat'];
                    $dot=' ';
                }
                $polygonArr[]=$item;
            }

//            echo "\n====\n";
        }
        return $polygonArr;
    }

}