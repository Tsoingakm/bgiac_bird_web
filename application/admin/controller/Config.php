<?php

namespace app\admin\controller;

use think\Controller;

class Config extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 设置
     */
    public function edit() {

        $configM = new \app\admin\model\Config();
        $code = input ( "code" );
        if (empty ( $code )) {
            $this->error ( "参数不正确！" );
        }
        switch ($code) {
            case 'log' :
                $this->checkPowerWeb('log_set',$this->admin['ap_codes']);//权限判断
                $this->addBread('日志管理');
                $this->addBread('日志配置');
                break;
            case 'web' :
                $this->checkPowerWeb('sys_set',$this->admin['ap_codes']);//权限判断
                $this->addBread('系统管理');
                $this->addBread('系统设置');
                break;
            case 'app' :
                $this->checkPowerWeb('sys_app',$this->admin['ap_codes']);//权限判断
                $this->addBread('系统管理');
                $this->addBread('app管理');
                break;
            default :
                break;
        }
        $model = $configM->getSettingModel ( $code ); //获取配置实例
        if (! $model) {
            if (empty ( $code )) {
                $this->error ( "没有找到记录！" );
            }
        }
        $this->assign ( "model", $model );
        $html=$this->fetch($code);
        return $html;
    }

    /**
     * ajax更新
     */
    public function doEdit() {
        $code = input ( "code", 0 );//获取配置代码
        if (empty ( $code )) {
            $this->error ( "参数错误，请重试！" );
        }
        $data ['content'] = serialize ( $_POST );//序列化存储配置内容
        $data ['code'] = $code;
        $configM=db('config');
        $res=$configM->data($data)->update();//保存内容
        if($res === false){
            $this->error('数据写入错误！请稍后再试，如果问题持续存在，请联系开发者');
        }
        $this->success('更新成功');
    }


}
