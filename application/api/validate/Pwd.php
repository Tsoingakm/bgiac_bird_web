<?php
namespace app\api\validate;

use think\Validate;

class Pwd extends Validate{

    protected $rule = [
        'original_pwd'    =>  ['require', 'length'=>'32'],
        'new_pwd'         =>  ['require', 'length'=>'32'],
        'new_pwd_confirm' =>  ['require', 'length'=>'32', 'confirm']
    ];

    protected $message = [
        'original_pwd.requrie'      => '原密码不能为空',
        'new_pwd.require'           => '新密码不能为空',
        'new_pwd_confirm.require'   => '新密码不能为空',
        'new_pwd_confirm.confirm'   => '两次输入的密码不一致'
    ];

}
