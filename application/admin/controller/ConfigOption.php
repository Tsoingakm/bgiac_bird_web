<?php

namespace app\admin\controller;

use think\Controller;

class ConfigOption extends Base
{

    private $configModel;
    public function __construct()
    {
        parent::__construct();
        $this->checkPowerWeb('sys_bird_condition_option',$this->admin['ap_codes']);
        $this->addBread('台账配置');

        $index_code=input('index_code');
        if(!$index_code){
            $this->error('参数错误');
        }

        $this->configModel=db('table_config')->where(['index_code'=>$index_code])->find();
        if(!$this->configModel){
            $this->error("未找到配置项");
        }
        $this->assign('configModel',$this->configModel);

        $this->addBread('选项设置');
        $this->addBread($this->configModel['column_name']);
    }

    /**
     * 配置选项列表
     * @return \think\response\View
     */
    public function index()
    {

        //var_dump($pageSize);
        $keyword = input('keyword');
        $where = array();
        $pageParam = array();
        if ($keyword) {
            $where['key|value|index_code'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword',$keyword);
        }
        $where['index_code']=$this->configModel['index_code'];
        $pageParam['index_code']=$this->configModel['index_code'];
        $orderby = 'sort asc,id desc';
        $list = $this->simpleGetList('table_config_option', $where, $orderby, $pageParam);

        $this->assign('list',$list['list']);

        \LogPageHelper::record('查看配置项'.$this->configModel['key'].'列表页','info',$this->logOption);
        return view();
    }

    /**
     * 添加配置选项
     */
    public function add()
    {
        $this->setDefaultValue();
        return view();
    }

    /**
     * 编辑配置选项
     */
    public function edit()
    {


        $id = input('id');
        if (!$id) {
            $this->error('缺少id');
        }
        $model = db('table_config_option')->where(['id' => $id])->find();
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

        $data = $_POST;
        $data['valid']=input('valid',0);

        $res = db('table_config_option')->data($data)->update();
        if ($res === false) {
            \LogPageHelper::record('修改配置选项:'.$data['key'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('修改配置选项：'.$data['key'],'info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 增加
     */
    public function doAdd()
    {

        $data = $_POST;
        $data['valid']=input('valid',0);

        $res = db('table_config_option')->data($data)->insert();
        if ($res === false) {
            \LogPageHelper::record('添加配置选项：'.$data['key'].' 失败','error',$this->logOption);
            $this->error('数据添加失败，请稍后重试');
        } else {
            \LogPageHelper::record('添加配置选项：'.$data['key'].'','info',$this->logOption);
            $this->success('操作成功');
        }
    }

    /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function doDel(){

        $ids=input('ids');
        if(!$ids){
            $this->error('参数不正确');
        }
        $where['id']=['in',$ids];
        $res=db('table_config_option')->where($where)->delete();
        if($res===false){
            \LogPageHelper::record('删除失败：'.$ids,'error',$this->logOption);
            $this->error('删除失败，请稍后再试');
        }else{
            \LogPageHelper::record('删除配置选项：'.$ids,'info',$this->logOption);
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
        $where['table_name']='bird_condition';
        $isExist = db('table_config_option')->where($where)->count();
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



}
