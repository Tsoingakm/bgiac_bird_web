<?php

namespace app\api\controller;

use think\Request;
use think\Loader;
use app\api\model\Admin as AdminModel;
use app\api\model\AdminRole;

class Admin extends Common {
    protected $request;
    protected $admin;

    public function _initialize(){
        parent::_initialize();
        $this->request = Request::instance();
        $this->admin   = new AdminModel();
    }

    public function changePwd(){
        $params = $this->request -> param();

        $validate = Loader::validate('Pwd');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $original_pwd = $params['original_pwd'];
        $new_pwd      = $params['new_pwd'];

        $where = [];
        $where['token'] = $params['token'];
        $where['pwd'] = $original_pwd;

        $is_original = $this->admin -> where($where) -> find();
        if(!$is_original){
            $this->return_msg( false, "原密码错误");
        }

        $is_change = $this->admin -> where('aid', $is_original['aid']) -> update(['pwd'=>$new_pwd]);
        if(!$is_change){
            $this->return_msg( false, "修改密码失败" );
        }

        $this->return_msg( true, "修改密码成功" );
    }

    public function findByToken(){
        $token = $this->request -> param('token');

        $data = $this->admin -> where('token', $token ) -> find();

        $host = $this->request->scheme().'://'. $this->request->host();
        $data['headimg'] = $host.$data['headimg'];

        $data['permission'] = AdminRole::get($data['ar_id']) -> ap_codes;

        if(!$data){
            $this->return_msg( false, "查找信息失败" );
        }

        $this->return_msg( true, "查找信息成功", $data);
    }

}
