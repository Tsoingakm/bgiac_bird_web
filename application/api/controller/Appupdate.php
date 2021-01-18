<?php

namespace app\api\controller;

use think\Request;
use think\Db;
use think\Loader;
use think\Validate;

class Appupdate {

    public function index(){
        $configM=new \app\common\model\ConfigModel();
        $app=$configM->getSettingModel('app');
        $app['status']=true;
        unset($app['file']);
        unset($app['code']);
        return json($app);
    }


}
