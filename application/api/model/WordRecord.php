<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-11
 * Time: 15:08
 */

namespace app\api\model;


class WordRecord extends Base
{
    protected $createTime = 'addtime';
    protected $updateTime = 'updatetime';

    public function photoes(){
        return $this->hasMany(WordRecordPhoto::class, 'record_id', 'id');
    }

    

    public function insert_data($params){
        $model  = new WordRecord($params);
        $photoModel  = new WordRecordPhoto($params);
        $result = $model -> allowField(true) -> save();
        $photoDatas = [];
        if($params['imgList']){
            foreach ($params['imgList'] as $k=>$v){
                if($params['imgList'][$k]['img']){
                    $data = [];
                    $data['img'] = $params['imgList'][$k]['img'];
                    $data['record_id'] = $model->id;
                    $photoDatas[] = $data;
                }
            }
        }
//        var_dump($photoDatas);exit;
        $photoModel->saveAll($photoDatas);
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
        $data = WordRecord::get($id);
        if(!$data){
            return false;
        }
        $photoModel = new WordRecordPhoto();
        $photoList = $photoModel->where(['record_id'=>$data->id])->select();
        $data->imgList = $photoList;
        return $data;
    }

    public function update_by_id($id,$params){
        $record   = new WordRecord;
        $photoModel  = new WordRecordPhoto();
        $result = $record -> allowField(true) -> save($params,['id' => $id]);
        //删除相关照片数据
        $photoModel->where(['record_id'=>$id])->delete();
        $photoDatas = [];
        if($params['imgList']){
            foreach ($params['imgList'] as $k=>$v){
                if($params['imgList'][$k]['img']){
                    $data = [];
                    $data['img'] = $params['imgList'][$k]['img'];
                    $data['record_id'] = $id;
                    $photoDatas[] = $data;
                }
            }
        }
//        var_dump($photoDatas);exit;
        $photoModel->saveAll($photoDatas);
        return $result;
    }
}
