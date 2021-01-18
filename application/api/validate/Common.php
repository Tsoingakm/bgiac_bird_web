<?php
namespace app\api\validate;

use think\Validate;

class Common extends Validate{

    protected $rule = [
        'id'  =>  ['require'],
    ];

    protected $message = [
        'id.requrie' => '用户名不能为空',
    ];

}
