<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-27
 * Time: 15:34
 */

namespace app\admin\controller;


use app\api\model\ArticleFile;
use app\api\model\ArticleSign;
use think\Db;

class Article extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->addBread('办公计划');
        $this->addBread('文件通知');
    }

    public function index(){
        $this->checkPowerWeb('article_view', $this->admin['ap_codes']);

        $where = array();
        $pageParam = array();

        $day = urldecode(input('day'));
        $dayList = explode('~', $day);
        $begin_day = $dayList[0];
        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
        $begin_day = trim($begin_day);
        $end_day = trim($end_day);
        if (!empty ($begin_day)) {
            $begin_day_int = strtotime($begin_day);
        } else {
            $begin_day = date('Y-m-d', time() - 86400 * 30);
            $begin_day_int = strtotime($begin_day);
        }
        $pageParam ['begin_day'] = $begin_day;
        $this->assign('begin_day', $begin_day);
        if (!empty ($end_day)) {
            $end_day_int = strtotime($end_day . ' 23:59:59');
        }
        $pageParam ['end_day'] = $end_day;
        $pageParam ['day'] = $begin_day.' ~ '.$end_day;
        $this->assign('day', $begin_day.' ~ '.$end_day);
        $this->assign('end_day', $end_day);
        $this->assign('today', date('Y-m-d'));
        $where['addtime'] = [ 'between', [ $begin_day_int, $end_day_int ] ];

        $count = db('article')->where ( $where )->count ();
        $this->assign('totalRows', $count);

//        $orderby = 'patrol_date desc, patrol_time desc';

        $this->assign('dataUrl', url('Article/indexData', [
            'day'=>urlencode($day)
        ]));
        return view();
    }

    public function indexData(){
        $where = array();

        $day = urldecode(input('day'));
        $dayList = explode('~', $day);
        $begin_day = $dayList[0];
        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
        $begin_day = trim($begin_day);
        $end_day = trim($end_day);
        if (!empty ($begin_day)) {
            $begin_day_int = strtotime($begin_day);
        } else {
            $begin_day = date('Y-m-d', time() - 86400 * 30);
            $begin_day_int = strtotime($begin_day);
        }
        if (!empty ($end_day)) {
            $end_day_int = strtotime($end_day . ' 23:59:59');
        }
        $where['addtime'] = [ 'between', [ $begin_day_int, $end_day_int ] ];

        $orderby = 'addtime desc';

        $page = input('page', 1);
        $pageCount = input('limit', 10);

        $list = db('article')
            ->where ( $where )
            ->order ( $orderby )
            ->limit ( ($page - 1) * $pageCount, $pageCount )
//            ->fetchSql(true)
            ->select ();
//        var_dump($list);exit;
        $count = db('article')->where ( $where )->count ();
        foreach ($list as $k=>$v){
            $list[$k]['addtime'] = date('Y-m-d H:i:s', $list[$k]['addtime']);
            $staff = db('admin')->where(['aid'=>$list[$k]['aid']])->find();
            $list[$k]['staff'] = $staff['real_name'];
            $signCount = ArticleSign::where(['article_id'=>$list[$k]['id'], 'status'=>2])->count();
            $list[$k]['hasSign'] = 0;
            if($signCount > 0){
                $list[$k]['hasSign'] = 1;
            }
        }
        $data = [];
        $data['code']    = 0;
        $data['msg']    = "查询成功";
        $data['count']  = $count;
        $data['data']   = $list;
        return $data;
    }

    public function add(){
        $this->checkPowerWeb('article_add', $this->admin['ap_codes']);
        return view();
    }

    public function edit(){
        $this->checkPowerWeb('article_edit', $this->admin['ap_codes']);
        $id = input('id');
        if(!$id){
            $this->error('参数错误');
        }
        $model = \app\api\model\Article::with(['files'])->find($id);
        $this->assign('model', $model);
//        var_dump($model['files']);exit;
        $this->assign('fileList', json_encode($model['files'], JSON_UNESCAPED_UNICODE));
//        var_dump($model);exit;
        return view();
    }

    public function doAdd(){
        $this->checkPowerWeb('article_add', $this->admin['ap_codes']);
        $params = input('');
//        var_dump($params);exit;
        $params['aid'] = $this->admin['aid'];
        $params['addtime'] = strtotime(date('Y-m-d H:i:s'));
        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $fileList = json_decode($params['fileList'], true);
//        var_dump($fileList);exit;
        $domain = request()->domain();
        Db::startTrans();
        try{
            $articleModel = new \app\api\model\Article($params);
            $articleModel->allowField(true)->save();
            $allFileData = [];
            foreach ($fileList as $k=>$v){
                $fileData = [];
                $fileData['name'] = $fileList[$k]['name'].'.'.$fileList[$k]['ext'];
                $fileData['url'] = $fileList[$k]['url'];
                $fileData['full_url'] = $domain.'/'.$fileList[$k]['url'];
                $fileData['size'] = $fileList[$k]['size'];
                $fileData['article_id'] = $articleModel->id;
                $fileData['addtime'] = strtotime(date('Y-m-d H:i:s'));
                $fileData['updatetime'] = strtotime(date('Y-m-d H:i:s'));
                $allFileData[] = $fileData;
            }
            $fileModel = new ArticleFile();
            $fileModel->allowField(true)->saveAll($allFileData);
            Db::commit();
        }catch (\Exception $e) {
//            print_r($e);
            \LogPageHelper::record('添加文件消息通知记录'.$data['kml_name'].' 失败','error',$this->logOption);
            Db::rollback();
            $this->error('数据添加失败，请稍后重试!'.$e->getMessage());

        }
        \LogPageHelper::record('添加文件消息通知记录成功：' . $id, 'error', $this->logOption);
        $this->success('添加成功');
    }

    public function doEdit(){
        $this->checkPowerWeb('article_edit', $this->admin['ap_codes']);
        $params = input('');

        $params['updatetime'] = strtotime(date('Y-m-d H:i:s'));
        $fileList = json_decode($params['fileList'], true);
//        var_dump($fileList);exit;
//        var_dump(request()->domain());exit;
        $domain = request()->domain();
        Db::startTrans();
        try{
            $articleModel = new \app\api\model\Article();
            $articleModel->allowField(true)->save($params, $params['id']);
            ArticleFile::where(['article_id'=>$params['id']])->delete();
            $allFileData = [];
            foreach ($fileList as $k=>$v){
                if($fileList[$k]['name'] && $fileList[$k]['url']){
                    $fileData = [];
                    $fileData['name'] = $fileList[$k]['name'].'.'.$fileList[$k]['ext'];
                    $fileData['url'] = $fileList[$k]['url'];
                    $fullUrl = $domain.'/'.$fileList[$k]['url'];
                    $fileData['full_url'] = $fullUrl;
                    $fileData['size'] = $fileList[$k]['size'];
                    $fileData['article_id'] = $articleModel->id;
                    $fileData['addtime'] = strtotime(date('Y-m-d H:i:s'));
                    $fileData['updatetime'] = strtotime(date('Y-m-d H:i:s'));
                    $allFileData[] = $fileData;
                }
            }
            $fileModel = new ArticleFile();
            $fileModel->allowField(true)->saveAll($allFileData);
            Db::commit();
        }catch (\Exception $e) {
//            print_r($e);
            \LogPageHelper::record('修改文件消息通知记录'.$data['kml_name'].' 失败','error',$this->logOption);
            Db::rollback();
            $this->error('数据添加失败，请稍后重试!'.$e->getMessage());

        }
        \LogPageHelper::record('修改文件消息通知记录成功：' . $id, 'error', $this->logOption);
        $this->success('编辑成功');
    }

    public function doDel(){
        $ids = input('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }
        $result = \app\api\model\Article::where('id', 'in', $ids)->delete();
        if ($result <= 0) {
            \LogPageHelper::record('删除文件消息通知记录失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除文件消息通知记录成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }

    public function check(){
        $this->checkPowerWeb('article_check', $this->admin['ap_codes']);
        $id = input('id');
        if(!$id){
            $this->error('参数错误');
        }
        $model = \app\api\model\Article::with(['files'])->find($id);
        $this->assign('model', $model);
        $this->assign('fileList', $model['files']);
        $signList = ArticleSign::with(['staff'])->where(['article_id'=>$id, 'status'=>2])->select();
        $this->assign('signList', $signList);
        return view();
    }
}
