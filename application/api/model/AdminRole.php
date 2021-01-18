<?php

namespace app\api\model;

use think\Model;

class AdminRole extends Model{

    protected $hidden = [
        'sort',
        'addtime', 'updatetime',
        'ext1', 'ext2', 'ext3',
    ];

    public function get_roles($permission){
        $roles = $this::all(function($query) use($permission){
            $query -> where('ap_codes', 'like', "%$permission%");
        });

        $ids = [];
        foreach($roles as $role){
            $ids[] = $role->ar_id;
        }
        $ids = implode(',', $ids);

        return $ids;
    }

}
