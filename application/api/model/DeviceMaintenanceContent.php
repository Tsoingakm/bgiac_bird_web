<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-25
 * Time: 10:25
 */

namespace app\api\model;


class DeviceMaintenanceContent extends Base
{
    protected $createTime = 'addtime';
    protected $updateTime = 'updatetime';

    public function configs(){
        return $this->hasMany(DeviceMaintenanceConfig::class, 'content_id', 'id');
    }
}
