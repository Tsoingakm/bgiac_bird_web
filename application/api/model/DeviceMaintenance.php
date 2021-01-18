<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-25
 * Time: 10:25
 */

namespace app\api\model;


class DeviceMaintenance extends Base
{
    protected $createTime = 'addtime';
    protected $updateTime = 'updatetime';

    public function device(){
        return $this->hasOne(Device::class, 'device_id', 'device_id');
    }
}
