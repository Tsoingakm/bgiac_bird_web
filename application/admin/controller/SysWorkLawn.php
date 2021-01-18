<?php

namespace app\admin\controller;

use think\Controller;

class SysWorkLawn extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->addBread('台账配置');
        $this->addBread('草坪维护配置');
    }

    /**
     * 草坪维护配置列表
     * @return \think\response\View
     */
    public function index()
    {
        $this->checkPowerWeb('work_lawn_config_view',$this->admin['ap_codes']);//草坪维护配置判断

        $where = array();
        $where['table_name']='work_lawn';
        $pageParam = array();

        $keyword = input('keyword');
        if ($keyword) {
            $where['column_name'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }

        $can_del = input( 'can_del' );
        if($can_del >= 0){
            $where['can_del'] = $can_del;
            $pageParam['can_del'] = $can_del;
            $this->assign('can_del', $can_del);
        }

        $orderby = 'sort asc,id desc';

        $list = $this->simpleGetList('table_config', $where, $orderby, $pageParam);
        $this->assign('list',$list['list']);

        $this->getInputType();
        $this->getSelectOption('is_extra');

        \LogPageHelper::record('查看草坪维护配置列表页','info',$this->logOption);
        return view();
    }

    /**
     * 添加草坪维护配置
     */
    public function add()
    {
        $this->checkPowerWeb('work_lawn_config_add',$this->admin['ap_codes']);//草坪维护配置判断

        $is_extra = input('is_extra');
        $this->assign("is_extra", $is_extra);

        $this->getSelectOption('extra_field');
        $this->getInputType();
        $this->setDefaultValue();

        return view();
    }

    /**
     * 编辑草坪维护配置
     */
    public function edit()
    {
        $this->checkPowerWeb('work_lawn_config_edit',$this->admin['ap_codes']);//草坪维护配置判断

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
        $this->checkPowerWeb('work_lawn_config_edit',$this->admin['ap_codes']);//草坪维护配置判断
        $data = $_POST;
        $data['valid']=input('valid',0);
        $data['output_valid']=input('output_valid',0);
        $data['can_del']=input('can_del',0);
        $res = db('table_config')->data($data)->update();
        if ($res === false) {
            \LogPageHelper::record('修改草坪维护配置:'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改草坪维护配置：'.$data['column_name'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 增加
     */
    public function doAdd()
    {
        $this->checkPowerWeb('work_lawn_config_add',$this->admin['ap_codes']);//草坪维护配置判断

        $data = $_POST;
        $this->isAlreadyUse($data['table_name'], $data['column_code']);

        $data['valid']=input('valid',0);
        $data['output_valid']=input('output_valid',0);
        $data['can_del']=input('can_del',0);
        $res = db('table_config')->data($data)->insert();
        if ($res === false) {
            \LogPageHelper::record('添加草坪维护配置：'.$data['column_name'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加草坪维护配置：'.$data['column_name'].'','info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doDel(){
        $this->checkPowerWeb('work_lawn_config_del',$this->admin['ap_codes']);//草坪维护配置判断
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
            \LogPageHelper::record('删除草坪维护配置：'.$ids,'info',$this->logOption);
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
//        $where['table_name']='work_lawn';
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
