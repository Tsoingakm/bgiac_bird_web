<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-30
 * Time: 16:03
 */

namespace app\admin\controller;


class Unit extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->addBread('台账配置');
        $this->addBread('维护单位管理');
    }

    public function index(){
        $this->checkPowerWeb('unit_class_view',$this->admin['ap_codes']);//设备判断
        //var_dump($pageSize);
        $keyword = input('keyword');
        $where = array();
        $pageParam = array();
        if ($keyword) {
            $where['name'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }
        $orderby = 'addtime desc';
        $list = $this->simpleGetList('unit', $where, $orderby, $pageParam);
        $this->assign('list', $list['list']);

        \LogPageHelper::record('查看维护单位列表页','info',$this->logOption);
        return view();
    }

    public function indexData(){

    }

    public function add(){
        $this->checkPowerWeb('unit_class_add',$this->admin['ap_codes']);//设备判断
        return view();
    }

    public function edit(){
        $this->checkPowerWeb('unit_class_edit',$this->admin['ap_codes']);//设备判断

        $id = input('id');
        if (!$id) {
            $this->error('缺少id');
        }
        $model = db('unit')->find($id);
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }
        $this->assign('model', $model);

        return view();
    }

    public function doAdd(){
        $this->checkPowerWeb('unit_class_add',$this->admin['ap_codes']);//设备判断
        $data = $_POST;
        $data['valid']=input('valid',0);
        $res = db('unit')->data($data)->insert();
        if ($res === false) {
            \LogPageHelper::record('添加维护单位：'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加维护单位：'.$data['column_name'].'','info',$this->logOption);
            $this->success('操作成功');
        }
    }

    public function doEdit(){
        $this->checkPowerWeb('unit_class_edit',$this->admin['ap_codes']);//设备判断
        $data = $_POST;
        $data['valid']=input('valid',0);
        $res = db('unit')->data($data)->update();
        if ($res === false) {
            \LogPageHelper::record('修改维护单位:'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改维护单位：'.$data['column_name'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    public function doDel(){
        $this->checkPowerWeb('unit_class_del',$this->admin['ap_codes']);//设备判断
        $ids=input('ids');
        if(!$ids){
            $this->error('参数不正确');
        }
        $where['id']=['in',$ids];
        $res=db('unit')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除单位：'.$ids,'info',$this->logOption);
            $this->success('删除成功');
        }
    }
}
