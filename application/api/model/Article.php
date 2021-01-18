<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-27
 * Time: 15:35
 */

namespace app\api\model;


class Article extends Base
{
    public function files(){
        return $this->hasMany(ArticleFile::class, 'article_id', 'id');
    }
}
