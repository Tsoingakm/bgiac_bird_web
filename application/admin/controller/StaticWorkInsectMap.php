<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/9/5
 * Time: 17:35
 */

namespace app\admin\controller;


class StaticWorkInsectMap extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->addBread('区域统计');
        $this->addBread('昆虫消杀区域统计');

    }

    //准备弃用
    public function index(){
        $this->checkPowerWeb('static_work_insect_map_view',$this->admin['ap_codes']);//权限判断
        $begin_day = input ( 'begin_day' );
        $begin_day_int=0;
        $end_day_int=0;
        $end_day = input ( 'end_day',date('Y-m-d') );
        $day = urldecode(input('day'));
        $dayList = explode('~', $day);
        $begin_day = $dayList[0];
        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
        $begin_day = trim($begin_day);
        $end_day = trim($end_day);
        if (! empty ( $begin_day )) {
            $begin_day_int = strtotime ( $begin_day );
        }else{
            $begin_day=date('Y-m-d',time()-86400*90);
            $begin_day_int = strtotime ( $begin_day );
        }
        $pageParam ['begin_day'] = $begin_day;
        $this->assign ( 'begin_day', $begin_day );
        if (! empty ( $end_day )) {
            $end_day_int = strtotime ( $end_day . ' 23:59:59' );
            $pageParam ['end_day'] = $end_day;
        }
        $this->assign ( 'end_day', $end_day );
        $pageParam ['day'] = $begin_day.' ~ '.$end_day;
        $this->assign('day', $begin_day.' ~ '.$end_day);
        $this->assign('today', date('Y-m-d'));
        //找当前的KML文件
        $kmlFileDb=db('kml_file');
        $where['kml_table']="work_insect";
        $kmlModel=$kmlFileDb->where($where)->order('kml_id desc')->find();
        $this->assign('kmlModel',$kmlModel);
        //找区域
        $areaBirdDb=db('work_area');
        $whereArea['table_name']="work_insect";
        // $whereArea['maintain_area'] =  array(array('<>', '待登记'),array('<>', '其它'));
        $areaList=$areaBirdDb->where($whereArea)->order("area_name asc")->select();//找到所有区域
        $workInsectDb=db('work_insect');

        //作业记录数
        foreach($areaList as $k=>$v){
            $where=array();
            $where['working_date']=array('between',[$begin_day_int,$end_day_int]);
            $where['maintain_area']=$v['area_name'];
            $areaList[$k]['count']=$workInsectDb->where($where)->count();
        }

        $this->assign('areaList',$areaList);
        \LogPageHelper::record('查看昆虫消杀记录区域统计页','info',$this->logOption);
        return view();
    }


    public function amap(){
        $this->checkPowerWeb('static_work_insect_map_view',$this->admin['ap_codes']);//权限判断
        $begin_day = input ( 'begin_day' );
        $begin_day_int=0;
        $end_day_int=0;
        $end_day = input ( 'end_day',date('Y-m-d') );
        $day = urldecode(input('day'));
        $dayList = explode('~', $day);
        $begin_day = $dayList[0];
        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
        $begin_day = trim($begin_day);
        $end_day = trim($end_day);
        if (! empty ( $begin_day )) {
            $begin_day_int = strtotime ( $begin_day );
        }else{
            $begin_day=date('Y-m-d',time()-86400*90);
            $begin_day_int = strtotime ( $begin_day );

        }
        $pageParam ['begin_day'] = $begin_day;
        $this->assign ( 'begin_day', $begin_day );
        if (! empty ( $end_day )) {
            $end_day_int = strtotime ( $end_day . ' 23:59:59' );
            $pageParam ['end_day'] = $end_day;
        }
        $this->assign ( 'end_day', $end_day );
        $pageParam ['day'] = $begin_day.' ~ '.$end_day;
        $this->assign('day', $begin_day.' ~ '.$end_day);
        $this->assign('today',date('Y-m-d'));
        //work_insect_spary
        $work_insect_spary=input("work_insect_spary");
        $whereTableConfig['index_code']="work_insect_spary";
        $work_insect_spary_list=db('table_config_option')->where($whereTableConfig)->order('sort asc')->select();
//        echo db()->getLastSql();

        $this->assign('work_insect_spary',$work_insect_spary);
        $this->assign('work_insect_spary_list',$work_insect_spary_list);

        //找当前的KML文件
        $kmlFileDb=db('kml_file');
        $where['kml_table']="work_area";
        $kmlModel=$kmlFileDb->where($where)->order('kml_id desc')->find();
        $this->assign('kmlModel',$kmlModel);
        //找区域
        $areaBirdDb=db('work_area');
        $whereArea['table_name']="work_area";
        $areaList=$areaBirdDb->where($whereArea)->order("area_name asc")->select();//找到所有区域

        $workInsectDb=db('work_insect');
        //作业记录
        $where=array();
        $where['working_date']=array('between',[$begin_day_int,$end_day_int]);
        $where['is_display_on_map']=1;
        if($work_insect_spary){
            $where['spary_times']=$work_insect_spary;
        }
        $insectList=$workInsectDb->where($where)->order('start_time desc,id desc')->select();
        foreach ($insectList as $k=>$v){
            $insectList[$k]['remarks']=str_replace("\n","",$v['remarks']);
        }
        $this->assign('insectList',$insectList);
//        $where['maintain_area']=$v['area_name'];

        $this->assign('areaList',$areaList);
//        print_r($areaList);
        \LogPageHelper::record('查看昆虫消杀记录区域统计页','info',$this->logOption);
        return view();
    }

    //导出KML
    public function kml(){
        $this->checkPowerWeb('static_work_insect_map_view',$this->admin['ap_codes']);//权限判断
        $begin_day = input ( 'begin_day' );
        $begin_day_int=0;
        $end_day_int=0;
        $end_day = input ( 'end_day',date('Y-m-d') );
        if (! empty ( $begin_day )) {
            $begin_day_int = strtotime ( $begin_day );
        }else{
            $begin_day=date('Y-m-d',time()-86400*90);
            $begin_day_int = strtotime ( $begin_day );

        }
        $pageParam ['begin_day'] = $begin_day;
        $this->assign ( 'begin_day', $begin_day );
        if (! empty ( $end_day )) {
            $end_day_int = strtotime ( $end_day . ' 23:59:59' );
            $pageParam ['end_day'] = $end_day;
        }
        $this->assign ( 'end_day', $end_day );
        $this->assign('today',date('Y-m-d'));
        //work_insect_spary
        $work_insect_spary=input("work_insect_spary");

        $this->assign('work_insect_spary',$work_insect_spary);


        $workInsectDb=db('work_insect');
        //作业记录
        $where=array();
        $where['working_date']=array('between',[$begin_day_int,$end_day_int]);
        $where['is_display_on_map']=1;
        if($work_insect_spary){
            $where['spary_times']=$work_insect_spary;
        }
        $insectList=$workInsectDb->where($where)->order('start_time desc,id desc')->select();
        $kmlList=array();
        foreach ($insectList as $key=>$value){
            $kmlList[$value['spary_times']][]=$value;
        }
        $this->assign('kmlList',$kmlList);
        $this->assign('insectList',$insectList);


        //找区域
        $areaBirdDb=db('work_area');
        $whereArea['table_name']="work_area";
        $areaList=$areaBirdDb->where($whereArea)->order("area_name asc")->select();//找到所有区域
        $this->assign('areaList',$areaList);

        \LogPageHelper::record('下载昆虫消杀区域统计KML文件','info',$this->logOption);

        $fileName=urlencode("昆虫消杀区域统计").".kml";
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=".$fileName);

        echo $this->fetch();
//        return view();
    }

    /**
     * 作业记录
     */
    public function record(){
        $area_name=input('area_name');

        $begin_day = input ( 'begin_day' );
        $begin_day_int=0;
        $end_day_int=0;
        $end_day = input ( 'end_day',date('Y-m-d') );
        if (! empty ( $begin_day )) {
            $begin_day_int = strtotime ( $begin_day );
        }else{
            $begin_day=date('Y-m-d',time()-86400*90);
            $begin_day_int = strtotime ( $begin_day );

        }
        $pageParam ['begin_day'] = $begin_day;
        $this->assign ( 'begin_day', $begin_day );
        if (! empty ( $end_day )) {
            $end_day_int = strtotime ( $end_day . ' 23:59:59' );
            $pageParam ['end_day'] = $end_day;
        }
        $this->assign ( 'end_day', $end_day );

        $orderby = 'working_date desc, spary_times desc';
        $where ['working_date'] = array (
            'between',
            [$begin_day_int,$end_day_int]
        );
        $where['maintain_area']=$area_name;
        $pageParam['area_name']=$area_name;
        $list = $this->simpleGetList('work_insect', $where, $orderby, $pageParam);
//        print_r(db()->getLastSql());

        return view();
    }


}
