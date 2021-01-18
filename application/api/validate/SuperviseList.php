<?php
namespace app\api\validate;

use think\Validate;

class SuperviseList extends Validate{

    protected $rule = [
        'starting_time' => ['require', 'number'],
        'end_time'      => ['require', 'number'],
        'current_page'  => ['require'],
    ];

    protected $message = [
        'starting_time.requrie' => '开始时间不能为空',
        'staring_time.number'   => '开始时间有误',
        'end_time.requrie'      => '结束时间不能为空',
        'end_time.number'       => '结束时间有误',
        'current_page'          => '要查询的页数不能为空'
    ];

}
