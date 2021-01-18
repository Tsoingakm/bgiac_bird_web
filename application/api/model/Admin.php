<?php

namespace app\api\model;

use think\Model;

class Admin extends Base{

    protected $hidden = [
       'pwd', 'ar_id', 'last_login_time', 'login_count', 'valid',
       'addtime', 'updatetime',
       'ext1', 'ext2', 'ext3',
    ];

    protected function initialize(){
      parent::initialize();
    }

    public function worker_info($roles){
      $admins = $this::all(function($query) use($roles){
            $query -> where('valid', 1) -> where('ar_id', 'in', $roles);
      });

      $admins[] = [ 'real_name'=>'N/A' ];
      $options = $this -> process_option($admins, 'real_name', 'real_name');
      return $options;
    }

    public function entering_worker($roles){
      $admins = $this::all(function($query) use($roles){
            $query -> where('valid', 1) -> where('ar_id', 'in', $roles);
      });
      $options = $this -> process_option($admins, 'aid', 'real_name');
      return $options;
    }

    public function all_worker(){
        $admins = $this::all(function($query){
            $query -> where('valid', 1);
        });

        $options = $this -> process_option($admins, 'aid', 'real_name');
//        var_dump($options);exit;
        return $options;
    }

    public function all_worker_selects(){
        $admins = $this::all(function($query){
            $query -> where('valid', 1);
        });

        $options = $this -> process_option($admins, 'real_name', 'real_name');
//        var_dump($options);exit;
        return $options;
    }

    public function find_by_id($id){
        $admin = $this::get($id);
        if(!$admin){
            return false;
        }
        return $admin;
    }

}
