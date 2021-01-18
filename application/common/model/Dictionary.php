<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/8/11
 * Time: 0:54
 */

namespace app\common\model;


class Dictionary
{
    public  $model='';


    public function __construct(){
        $this->model=db('dictionary');

    }

    public function getByIndexCode($indexCode,$status=1){
        $where['index_code']=$indexCode;
        if($status!=''){
            $where['status']=$status;
        }
        $list=$this->model->where($where)->order("sort asc,dic_id asc")->select();
        $arr=array();
        foreach($list as $k=>$v){
            $arr[$k]=array('key'=>$v['key'],'value'=>$v['value']);
        }
        return $arr;
    }
}