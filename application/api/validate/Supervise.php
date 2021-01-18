<?php
namespace app\api\validate;

use think\Validate;

class Supervise extends Validate{

    protected $rule = [
        'worker1'         => ['require'],
        'worker2'         => ['require'],
        'day_str'         => ['require'],
        'time_str'        => ['require'],
        'view_number'     => ['require', 'number'],
        'bird_name'       => ['require'],
        'realness'        => ['require'],
        'bird_num'        => ['require', 'number'],
        'area'            => ['require'],
        'describe'        => ['require'],
        'height'          => ['require'],
        'temperature'     => ['require'],
        'humidity'        => ['require'],
        'pressure'        => ['require'],
        'wind_direction'  => ['require'],
        'wind_power'      => ['require'],
        'weather1'        => ['require'],
        'weather2'        => ['require'],
        'aid'             => ['require'],
    ];

    protected $message = [
        'worker1.requrie'         => '巡视人员不能为空',
        'worker2.require'         => '巡视人员不能为空',
        'day_str.require'         => '日期不能为空',
        'time_str.require'        => '时间不能为空',
        'view_number.require'     => '巡视序号不能为空',
        'view_number.number'      => '巡视序号必须是数字',
        'bird_name.require'       => '鸟名不能为空',
        'realness.require'        => '置信度不能为空',
        'bird_num.require'        => '数量不能为空',
        'bird_num.number'         => '数量必须是数字',
        'area.require'            => '观测区域不能为空',
        'describe.require'        => '鸟情描述不能为空',
        'height.require'          => '活动高度不能为空',
        'temperature.require'     => '温度不能为空',
        'humidity.require'        => '湿度不能为空',
        'pressure.require'        => '气压不能为空',
        'wind_direction.require'  => '风向不能为空',
        'wind_power.require'      => '风力不能为空',
        'weather1.require'        => '天气情况不能为空',
        'weather2.require'        => '天气情况不能为空',
        'aid.require'             => '录入人员不能为空',
    ];

}
