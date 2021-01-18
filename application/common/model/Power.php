<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/8/11
 * Time: 0:54
 */

namespace app\common\model;


class Power
{
    /**
     * 获取父级分类（一级和二级）
     */
    public function getPids(){
        $powerDb=db('admin_power');
        $fisrtArr=$powerDb->where(['valid' => 1, 'ap_pid' => 0])->order('sort asc,ap_name asc,ap_code asc')->select();
        $returnArr=array();
        foreach ($fisrtArr as $k=>$v){
            $returnArr[]=$v;
            $secondArr=$powerDb->where(['valid' => 1, 'ap_pid' => $v['ap_id']])->order('sort asc,ap_name asc,ap_code asc')->select();
            foreach ($secondArr as $kk=>$vv){
                $vv['ap_name']="|--".$vv['ap_name'];
                $returnArr[]=$vv;
            }
        }
        return $returnArr;
    }

    /**
     * 获取权限分级数组,三维数组
     */
    public function getPowerListArr(){
        $adminPowerDb=db('admin_power');
        $fPower=$adminPowerDb->where(['valid' => 1, 'ap_pid' => 0])->order('sort asc,ap_name asc,ap_code asc')->select();
        foreach($fPower as $key=>$value){
            $fPower[$key]['list']=$adminPowerDb->where(['valid' => 1, 'ap_pid' => $value['ap_id']])->order('sort asc,ap_name asc,ap_code asc')->select();
            foreach ($fPower[$key]['list'] as $kk=>$vv){
                $fPower[$key]['list'][$kk]['list']=$adminPowerDb->where(['valid' => 1, 'ap_pid' => $vv['ap_id']])->order('sort asc,ap_name asc,ap_code asc')->select();
            }
        }
        return $fPower;
    }
}
