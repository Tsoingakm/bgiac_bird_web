<?php
namespace app\api\validate;

use think\Validate;

class AnyList extends Validate{

    protected $rule = [
        'starting_time' => ['require'],
        'end_time'      => ['require'],
        'current_page'  => ['require'],
    ];

    protected $message = [
        'starting_time.requrie' => '开始时间不能为空',
        'end_time.requrie'      => '结束时间不能为空',
        'current_page'          => '要查询的页数不能为空'
    ];

}
