<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-30
 * Time: 14:20
 */

namespace app\api\controller;


use app\api\model\ArticleSign;

class Article extends Common
{
    public function selectList(){
        $aid = input('aid');
        if(!$aid){
            $this->return_msg( false, "参数错误");
        }
        $articleList = \app\api\model\Article::with(['files'])->order('addtime DESC')->select();
        if(!$articleList){
            $this->return_msg( true, "获取成功", []);
        }
        foreach ($articleList as $k=>$v){
            $articleList[$k]['status'] = 0;
            $signData = ArticleSign::where(['article_id'=>$articleList[$k]['id'], 'aid'=>$aid])->find();
            if($signData && $signData['status'] == 2){
                $articleList[$k]['status'] = 1;
            }
        }
        $this->return_msg( true, "获取成功", $articleList);
    }

    public function findById(){
        $id = input('id');
        $aid = input('aid');
        if(!$id || !$aid){
            $this->return_msg( false, "参数错误");
        }
        $article = \app\api\model\Article::with(['files'])->find($id);
        $article['status'] = 0;
        $signData = ArticleSign::where(['article_id'=>$id, 'aid'=>$aid])->find();
        if(!$signData){
            //创建阅读记录
            $sign = [];
            $sign['article_id'] = $article['id'];
            $sign['aid'] = $aid;
            $sign['addtime'] = strtotime(date('Y-m-d H:i:s'));
            $sign['updatetime'] = strtotime(date('Y-m-d H:i:s'));
            $sign['status'] = 1;
            $signModel = new ArticleSign($sign);
            $signModel->allowField(true)->save();
        }else{
            if($signData['status'] <= 0){
                //更新阅读状态为1
                $signData = $signData->toArray();
                $signData['status'] = 1;
                $signData['updatetime'] = strtotime(date('Y-m-d H:i:s'));
                $model = new ArticleSign();
                $model->allowField(true)->save($signData, ['id'=>$signData['id']]);
            }

            if($signData['status'] == 2){
                $article['status'] = 1;
            }

        }
        if(!$article){
            $this->return_msg( false, "获取失败");
        }
        $this->return_msg( true, "获取成功", $article);
    }

    public function sign(){
        $id = input('id');
        $aid = input('aid');
        $img = input('img');
        if(!$id || !$aid){
            $this->return_msg( false, "参数错误");
        }
        if(!$img || $img == ''){
            $this->return_msg( false, "请上传签名图片");
        }
        $signData = ArticleSign::where(['article_id'=>$id, 'aid'=>$aid])->find();
        if($signData){
            $signData = $signData->toArray();
            $signData['sign_time'] = strtotime(date('Y-m-d H:i:s'));
            $signData['img'] = $img;
            $signData['updatetime'] = strtotime(date('Y-m-d H:i:s'));
            $signData['status'] = 2;
            $model = new ArticleSign();
            $result = $model->allowField(true)->save($signData, ['id'=>$signData['id']]);
            if($result <= 0){
                $this->return_msg( false, "签名失败");
            }
        }else{
            //创建阅读记录
            $sign = [];
            $sign['article_id'] = $id;
            $sign['aid'] = $aid;
            $sign['sign_time'] = strtotime(date('Y-m-d H:i:s'));
            $sign['img'] = $img;
            $sign['addtime'] = strtotime(date('Y-m-d H:i:s'));
            $sign['updatetime'] = strtotime(date('Y-m-d H:i:s'));
            $sign['status'] = 2;
            $signModel = new ArticleSign($sign);
            $result = $signModel->allowField(true)->save();
            if($result <= 0){
                $this->return_msg( false, "签名失败");
            }
        }
        $this->return_msg( true, "签名成功");
    }
}
