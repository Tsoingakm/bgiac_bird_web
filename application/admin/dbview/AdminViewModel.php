<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/9/5
 * Time: 9:48
 */

namespace app\admin\dbview;
use think\Db;

class AdminViewModel implements ViewModel
{
    public static function getView()
    {
        // TODO: Implement getView() method.
        return Db::view("admin","*")->view("admin_role","*","admin.ar_id=admin_role.ar_id","LEFT");
    }

}