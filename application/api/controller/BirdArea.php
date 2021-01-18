<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-19
 * Time: 15:01
 */

namespace app\api\controller;

use think\Request;
use think\Db;
use app\api\model\Admin;
use app\api\model\BirdArea as ba;

class BirdArea extends Common
{
    protected $request;
    protected $model;
    protected $worker;

    public function _initialize(){
        parent::_initialize();
        $this->request  = Request::instance();
        $this->worker   = new Admin();
        $this->model     = new ba();
    }

    public function insert(){
        $params  = $this->request->param();
        if(!isset($params['pointList']) || !$params['pointList']){
            $this->return_msg( false, "请绘制区域");
        }
        $pointList = json_decode($params['pointList']);
        if(count($pointList) < 3){
            $this->return_msg( false, "请绘制封闭的多边形区域");
        }
        if(!isset($params['aid']) || !$params['aid']){
            $this->return_msg( false, "录入人员不能为空");
        }

        if(!isset($params['area_name']) || !$params['area_name']){
            $this->return_msg( false, "请填写区域名字");
        }
        $pointStrList = [];
        foreach ($pointList as $k=>$v){
            $point = implode(',', $v);
            $pointStrList[] = $point;
        }
        $pointStr = implode(' ', $pointStrList);
        $params['points'] = $pointStr;
        $result = $this->model -> insert_data($params);

        if(!$result){
            $this->return_msg( false, "添加失败");
        }
        $this->return_msg( true, "添加成功");
    }
}
