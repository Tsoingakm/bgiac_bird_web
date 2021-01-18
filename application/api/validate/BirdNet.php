<?php
namespace app\api\validate;

use think\Validate;

class BirdNet extends Validate{

    protected $rule = [
        'worker1'       =>  ['require'],
        'worker2'       =>  ['require'],
        'patrol_date'   =>  ['require'],
        'patrol_time'   =>  ['require'],
        'bird_name'     =>  ['require'],
        'bird_num'      =>  ['require', 'number'],
        'area'          =>  ['require'],
        'height'        =>  ['require'],
        'aid'           =>  ['require'],
    ];

    protected $message = [
        'worker1.requrie'       =>  '巡视人员不能为空',
        'worker2.require'       =>  '巡视人员不能为空',
        'patrol_date.require'   =>  '巡视日期不能为空',
        'patrol_time.require'   =>  '巡视时间不能为空',
        'bird_name.require'     =>  '鸟名不能为空',
        'bird_num.require'      =>  '数量不能为空',
        'bird_num.number'       =>  '数量必须是数字',
        'area.require'          =>  '观测区域不能为空',
        'height.require'        =>  '高度不能为空',
        'aid.require'           =>  '录入人员不能为空',
    ];

}
