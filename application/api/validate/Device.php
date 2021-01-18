<?php
namespace app\api\validate;

use think\Validate;

class Device extends Validate{

    protected $rule = [
        'working_date'  =>  ['require'],
        'working_time'  =>  ['require'],
        'worker1'       =>  ['require'],
        'worker2'       =>  ['require'],
        'device'        =>  ['require'],
        'code'          =>  ['require'],
        'inspection'    =>  ['require'],
        'device_status' =>  ['require'],
        'aid'           =>  ['require'],
    ];

    protected $message = [
        'working_date.require'  =>  '日期不能为空',
        'working_time.require'  =>  '时间不能为空',
        'worker1.requrie'       =>  '维修人员不能为空',
        'worker2.require'       =>  '维修人员不能为空',
        'device.require'        =>  '设备名不能为空',
        'code.require'          =>  '设备编号不能为空',
        'inspection.require'    =>  '检查情况不能为空',
        'device_status.require' =>  '设备状态不能为空',
        'aid.require'           =>  '录入人员不能为空',
    ];

}
