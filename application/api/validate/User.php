<?php
namespace app\api\validate;

use think\Validate;

class User extends Validate{
  
    protected $rule = [
        'login_name'  =>  ['require'],
        'pwd' =>  ['require', 'length'=>'32'],
    ];

    protected $message = [
        'name.requrie' => '用户名不能为空',
        'pwd.require'  => '密码不能为空',
    ];

}
