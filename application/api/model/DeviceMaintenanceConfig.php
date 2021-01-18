<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-25
 * Time: 10:25
 */

namespace app\api\model;


class DeviceMaintenanceConfig extends Base
{
    protected $createTime = 'addtime';
    protected $updateTime = 'updatetime';

    public function options(){
        return $this->hasMany(DeviceMaintenanceConfigOption::class, 'config_id', 'id');
    }
}
