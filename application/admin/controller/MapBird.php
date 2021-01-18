<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;

class MapBird extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->addBread('鸟情管理');
        $this->addBread('区域地图');
    }

    /**
     * 地图管理
     * @return \think\response\View
     */
    public function index()
    {

        //找当前的KML文件
        $kmlFileDb=db('kml_file');
        $kmlModel=$kmlFileDb->order('kml_id desc')->find();
        $this->assign('kmlModel',$kmlModel);
        //找区域
        $areaBirdDb=db('bird_area');
        $areaList=$areaBirdDb->select();//找到所有区域
        $this->assign('areaList',$areaList);
        //print_r($areaList);
        return view();
    }




}
