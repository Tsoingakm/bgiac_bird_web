<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-27
 * Time: 15:35
 */

namespace app\api\model;


class ArticleSign extends Base
{
    public function staff(){
        return $this->hasOne(Admin::class, 'aid', 'aid');
    }
}
