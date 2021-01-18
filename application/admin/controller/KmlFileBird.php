<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;

class KmlFileBird extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->addBread('区域管理');
        $this->addBread('鸟情KML管理');
    }

    /**
     * 鸟情KML列表
     * @return \think\response\View
     */
    public function index()
    {
        $this->checkPowerWeb('kml_file_bird_view',$this->admin['ap_codes']);//鸟情KML判断
        //var_dump($pageSize);
        $keyword = input('keyword');
        $where = array();
        $pageParam = array();
        if ($keyword) {
            $where['kml_name'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }
        $where['kml_table']="bird_area";
        $orderby = 'kml_id desc';
        $list = $this->simpleGetList('kml_file', $where, $orderby, $pageParam);

        $this->assign('list',$list['list']);
        \LogPageHelper::record('查看鸟情KML列表页','info',$this->logOption);
        return view();
    }

    /**
     * 添加鸟情KML
     */
    public function add()
    {
        $this->checkPowerWeb('kml_file_bird_add',$this->admin['ap_codes']);//鸟情KML判断

        return view();
    }

    /**
     * 编辑鸟情KML
     */
    public function edit()
    {
        $this->checkPowerWeb('kml_file_bird_edit',$this->admin['ap_codes']);//鸟情KML判断
        $kml_id = input('kml_id');
        if (!$kml_id) {
            $this->error('缺少kml_id');
        }
        $model = db('kml_file')->where(['kml_id' => $kml_id])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $this->assign('isEdit',1);
        $this->assign('model', $model);

        return view();
    }


    /**
     * 编辑
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doEdit()
    {
        $this->checkPowerWeb('kml_file_bird_edit',$this->admin['ap_codes']);//鸟情KML判断
        $data = $_POST;

        $res = db('kml_file')->data($data)->update();
        if ($res === false) {
            \LogPageHelper::record('修改鸟情KML:'.$data['kml_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改鸟情KML：'.$data['kml_name'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 增加
     */
    public function doAdd()
    {
        $this->checkPowerWeb('kml_file_bird_add',$this->admin['ap_codes']);//鸟情KML判断
        $data = $_POST;
        // 启动事务

        Db::startTrans();
        try{
            $res = db('kml_file')->data($data)->insert();

            $root = dirname ( $_SERVER ['DOCUMENT_ROOT'] . "/aa" ) . "/";
            $kmlFile=$root.$data['kml_path'];
            $myfile = fopen($kmlFile, "r") or die("Unable to open file!");
            $xml=fread($myfile,filesize($kmlFile));
            fclose($myfile);

            $polygonArr=\app\common\Util\KmlHelper::getKmlPolygon($xml);
            $dbArea=db('bird_area');

            $dbArea->where('id>0')->delete();//删除所有记录

            foreach ($polygonArr as $key=>$value){
                $areaData=array();
                $areaData['area_name']=htmlspecialchars_decode($value['name']);
                $areaData['ext1']=$data['kml_name'];
                $areaData['ext2']=$data['kml_path'];

                $areaModel=$dbArea->where($areaData)->find();

                if($areaModel){
                    //如果记录存在
                    $areaData['id']=$areaModel['id'];
                }
                $areaData['coordinates_wgs84']=$value['coordinates_wgs84'];
                $areaData['coordinates_gcj02']=$value['coordinates_gcj02'];
                $areaData['addtime']=$areaData['updatetime']=time();
                if($areaModel){
                    $dbArea->data($areaData)->update();
                }else{
                    $dbArea->data($areaData)->insert();
                }

            }
            $count=$dbArea->count();
            \LogPageHelper::record('添加鸟情区域'.$count.' 个','info',$this->logOption);
            //区域数据管理
            if ($res === false) {
                \LogPageHelper::record('添加鸟情KML：'.$data['kml_name'].' 失败','error',$this->logOption);
                $this->error('数据添加失败，请稍后重试');
            } else {
                \LogPageHelper::record('添加鸟情KML：'.$data['kml_name'].'','info',$this->logOption);
                //$this->success('操作成功');
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
//            print_r($e);
            \LogPageHelper::record('添加鸟情KML：'.$data['kml_name'].' 失败','error',$this->logOption);
            Db::rollback();
            $this->error('数据添加失败，请稍后重试!'.$e->getMessage());
            // 回滚事务

        }
        $this->success('操作成功');

    }

    /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doDel(){
        $this->checkPowerWeb('kml_file_bird_del',$this->admin['ap_codes']);//鸟情KML判断
        $ids=input('ids');
        if(!$ids){
            $this->error('参数不正确');
        }
        $where['kml_id']=['in',$ids];
        $res=db('kml_file')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除鸟情KML：'.$ids,'info',$this->logOption);
            $this->success('删除成功');
        }
    }



}
