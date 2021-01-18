<?php

namespace app\api\controller;

use think\Request;
use think\Db;
use think\Loader;
use think\Validate;

class User {

    public function login(){
        $request  = Request::instance();
        $params   = $request -> param();

        $validate = Loader::validate('User');
        if(!$validate->check($params)){
            $this->return_msg( false, $validate->getError());
        }

        $name = $params['login_name'];
        $pwd  = $params['pwd'];

        $is_valid = Db::name('admin')
                      ->  where('login_name', $name)
                      ->  where('pwd', $pwd)
                      ->  find();



        if(!$is_valid){
            $this->return_msg( false, "登录失败，账号或密码错误");
        }

        if($is_valid['valid'] != 1){
            $this->return_msg( false, "该账号已被禁用");
        }

        $tokenArr = $this -> refreshToken($is_valid);

        $this->return_msg( true, "登录成功", $tokenArr);

    }


    public function logout(){
        $reqest = Request::instance();
        $params = $reqest -> param();

        $api_token = $params['token'];

        $admin = Db::name('admin')
                  ->where('token', $api_token)
                  ->find();

        $tokenArr = [ 'token' => '', 'token_time' => '' ];

        $is_delete = Db::name('admin')
                      ->where('aid', $admin['aid'])
                      ->update($tokenArr);

        if($is_delete){
            $this->return_msg( true, "退出成功");
        }
        else{
            $this->return_msg( flase, "退出失败");
        }
    }

    /**
      * 登陆成功后刷新token和token_time
      * @param [array] $user 需要判断的用户信息
      * @return [json] 返回有效的token信息
     */
    protected function refreshToken($user){
        $tokenArr    = [];
        $indate      = 86400*2;
        $currentTime = time();

        $tokenValue = $user['login_name'].$user['pwd'].time();
        $tokenTime  = $currentTime + $indate;

        $tokenArr = [
          'token'      => md5($tokenValue),
          'token_time' => $tokenTime
        ];

        $renewal = Db::name('admin')
                    ->where('aid', $user['aid'])
                    ->update($tokenArr);

        return $tokenArr;
    }

    /**
    * api 数据返回
     * @param  [boolval] $status [成功：success；失败：false]
     * @param  [string] $msg  [接口要返回的提示信息]
     * @param  [array]  $data [接口要返回的数据]
     * @return [string]       [最终的json数据]
    */
    public function return_msg($status, $msg = '', $data = []) {
        /*********** 组合数据  ***********/
        $return_data['status'] = $status;
        $return_data['msg']  = $msg;
        if(!empty($data)){
            $return_data['data'] = $data;
        }
        /*********** 返回信息并终止脚本  ***********/
        echo json_encode($return_data, JSON_UNESCAPED_UNICODE); die;
    }




}
