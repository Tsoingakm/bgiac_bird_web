<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/10/24
 * Time: 17:14
 */

namespace app\test\controller;


class Patch
{
    //更新经纬度信息
    public function updateAreaInfo(){
        $areaList=db('work_area')->where(['table_name'=>'work_area'])->select();
        $insectDb=db('work_insect');
        $insectList=$insectDb->select();
        foreach ($insectList as $key=>$value){
            if(!$value['coordinates_wgs84']&& !$value['coordinates_gcj02']){
                foreach ($areaList as $ak=>$av){
                    if($value['maintain_area']==$av['area_name']){
                        $value['coordinates_gcj02']=$av['coordinates_gcj02'];
                        $value['coordinates_wgs84']=$av['coordinates_wgs84'];
                        $insectDb->data($value)->update();
                        echo "\n<br/>更新id:".$value['id'];
                    }
                }
            }
        }
        $lawnDb=db('work_lawn');
        $lawnList=$lawnDb->select();
        foreach ($lawnList as $key=>$value){
            if(!$value['coordinates_wgs84']&& !$value['coordinates_gcj02']){
                foreach ($areaList as $ak=>$av){
                    if($value['maintain_area']==$av['area_name']){
                        $update['id']=$value['id'];
                        $update['coordinates_gcj02']=$av['coordinates_gcj02'];
                        $update['coordinates_wgs84']=$av['coordinates_wgs84'];
                        $lawnDb->data($update)->update();
                        echo "\n<br/>更新id:".$value['id'];
                    }
                }
            }
        }

        $vectorDb=db('work_vector');
        $vectorList=$vectorDb->select();
        foreach ($vectorList as $key=>$value){
            if(!$value['coordinates_wgs84']&& !$value['coordinates_gcj02']){
                foreach ($areaList as $ak=>$av){
                    if($value['maintain_area']==$av['area_name']){
                        $update['id']=$value['id'];
                        $update['coordinates_gcj02']=$av['coordinates_gcj02'];
                        $update['coordinates_wgs84']=$av['coordinates_wgs84'];
                        $vectorDb->data($update)->update();
                        echo "\n<br/>更新id:".$value['id'];
                    }
                }
            }
        }

    }
}