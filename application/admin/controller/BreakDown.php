<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-12-21
 * Time: 9:38
 */

namespace app\admin\controller;


class BreakDown extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->addBread('台账配置');
        $this->addBread('设备维护工卡配置');
    }

    /**
     * 一级鸟情记录配置列表
     * @return \think\response\View
     */
    public function index()
    {
//        $this->checkPowerWeb('sys_bird_condition_view',$this->admin['ap_codes']);

        $where = array();
        $where['table_name']='device_maintenance';

        $keyword = input('keyword');
        $pageParam = array();
        if ($keyword) {
            $where['column_name'] = ['like', '%'. $keyword .'%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }

//        $can_del = input( 'can_del' );
//        if($can_del >= 0){
//            $where['can_del'] = $can_del;
//            $pageParam['can_del'] = $can_del;
//            $this->assign('can_del', $can_del);
//        }

        $orderby = 'sort asc,id desc';

        $list = $this->simpleGetList('table_config', $where, $orderby, $pageParam);
        $this->assign('list',$list['list']);

        $this->getInputType();
        $this->getSelectOption('is_extra');

        \LogPageHelper::record('查看故障及处理配置列表页','info',$this->logOption);
        return view();
    }

    /**
     * 添加一级鸟情记录配置
     */
    public function add()
    {
//        $this->checkPowerWeb('sys_bird_condition_add',$this->admin['ap_codes']);//一级鸟情记录配置判断

        $is_extra = input('is_extra');
        $this->assign("is_extra", $is_extra);

        $this->getSelectOption('device_extra');
        $this->getInputType();
        $model = array();
        $model['sort']  = 99;
        $model['valid'] = 1;
        $model['column_code'] = 'breakdown';
        $this -> assign( "model", $model );

        return view();
    }

    /**
     * 编辑一级鸟情记录配置
     */
    public function edit()
    {
//        $this->checkPowerWeb('sys_bird_condition_edit',$this->admin['ap_codes']);//一级鸟情记录配置判断

        $is_extra = input('is_extra');
        $this->assign("is_extra", $is_extra);

        $id = input('id');
        if (!$id) {
            $this->error('缺少id');
        }

        $model = db('table_config')->where(['id' => $id])->find();
        if (!$model) {
            $this->error('记录不存在或已被删除');
        }

        $this->assign('isEdit',1);
        $this->assign('model', $model);
        $this->getSelectOption('extra_field');
        $this->getInputType();
        return view();
    }


    /**
     * 编辑
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doEdit()
    {
//        $this->checkPowerWeb('sys_bird_condition_edit',$this->admin['ap_codes']);//一级鸟情记录配置判断
        $data = $_POST;
//        var_dump($data);exit;
        $data['valid']=input('valid',0);
//        $data['output_valid']=input('output_valid',0);
        $data['can_del']=1;
        $res = db('table_config')->data($data)->update();
        if ($res === false) {
            \LogPageHelper::record('修改一级鸟情记录配置:'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改一级鸟情记录配置：'.$data['column_name'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 增加
     */
    public function doAdd()
    {
//        $this->checkPowerWeb('sys_bird_condition_add',$this->admin['ap_codes']);//一级鸟情记录配置判断


        $data = $_POST;
//        var_dump($data);exit;
        $this->isAlreadyUse($data['table_name'], $data['column_code']);

        $data['valid']=input('valid',0);
//        $data['output_valid']=input('output_valid',0);
        $data['can_del']=1;
        $res = db('table_config')->data($data)->insert();

        if ($res === false) {
            \LogPageHelper::record('添加一级鸟情记录配置：'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加一级鸟情记录配置：'.$data['column_name'].'','info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doDel(){
//        $this->checkPowerWeb('sys_bird_condition_del',$this->admin['ap_codes']);//一级鸟情记录配置判断
        $ids=input('ids');
        if(!$ids){
            $this->error('参数不正确');
        }
        $where['id']=['in',$ids];
        $res=db('table_config')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除一级鸟情记录配置：'.$ids,'info',$this->logOption);
            $this->success('删除成功');
        }
    }

    /**
     * 判断code是否存在
     * @return \think\response\View
     */
    public function checkCode()
    {

        $index_code = input('param');
        $where['index_code'] = $index_code;
        $isExist = db('table_config')->where($where)->count();
        if ($isExist) {
            $res = [
                'status' => 'n',
                "info" => '配置代码已经存在'
            ];
            return json($res);
        } else {
            $res = [
                'status' => 'y',
                "info" => '可以使用'
            ];
            return json($res);
        }
    }

    /**
     * 获取配置
     */
    private function getInputType(){
        $dicM=new \app\common\model\Dictionary();
        $typdList=$dicM->getByIndexCode('table_config_type');
        $this->assign('typeList',$typdList);
    }
}
