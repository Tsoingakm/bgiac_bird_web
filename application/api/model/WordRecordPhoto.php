<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-11
 * Time: 15:08
 */

namespace app\api\model;


class WordRecordPhoto extends Base
{
    protected $createTime = 'addtime';
    protected $updateTime = 'updatetime';

    public function insert_data($params){
        $model  = new WordRecordPhoto($params);
        $result = $model -> allowField(true) -> save();
        return $result;
    }

    public function delete_by_id($ids){
        $result = $this::where( 'id', 'in', $ids ) -> delete();
        return $result;
    }

    public function find_by_name($name){
        $result = $this::get([ 'bird_name'=>$name ]);
        if(!$result){
            return false;
        }
        return $result;
    }

    public function find_by_id($id){
        $data = WordRecordPhoto::get($id);
        if(!$data){
            return false;
        }
        return $data;
    }

    public function update_by_id($id,$params){
        $bird   = new WordRecordPhoto;
        $result = $bird -> allowField(true) -> save($params,['id' => $id]);
        return $result;
    }
}
