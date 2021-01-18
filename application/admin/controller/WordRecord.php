<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-11
 * Time: 15:09
 */

namespace app\admin\controller;

use app\api\model\WordRecord as wr;
use app\api\model\WordRecordPhoto;
use think\Request;

class WordRecord extends Base
{
    protected $record;
    protected $recordPhoto;
    protected $request;

    public function __construct()
    {
        parent::__construct();
        $this->record = new wr();
        $this->recordPhoto = new WordRecordPhoto();
        $this->request = Request::instance();
        $this->addBread('办公计划');
        $this->addBread('文字性综合记录');
    }

    public function index(){
        $this->checkPowerWeb('word_record_view',$this->admin['ap_codes']);
        $where = array();
        $pageParam = array();
        $end_day_int = 0;//时间戳格式的结束日期
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
        $where['record_date'] = ['between', [$begin_day_int, $end_day_int]];

        $orderby = 'record_date desc, deal_time desc';

        $res=array();//查询结果
        $M = db ( 'word_record' );
        $pagekey=config('paginate.var_page') ? config('paginate.var_page'):'p'; //设置分页参数名称
        $p = input ($pagekey, 1 ); // 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取

        $totalRows = $M->where ( $where )->count ();
        $res['totalRows']=$totalRows;
        // 查询满足要求的总记录数
        $pageSize = config('page_size') ? config('page_size') : 15;
        $res['pageSize']=$pageSize;
        $Page = new \PageHelper( $totalRows, $pageSize, $pageParam); // 实例化分页类 传入总记录数和每页显示的记录数
        // 分页跳转的时候保证查询条件
        //$Page->parameter = $pageParam;
        $show = $Page->show (); // 分页显示输出
        $res['pageShow']=$show;
        $res['page']=$show;
        $list = $M->where ( $where )->order ( $orderby )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
//            ->fetchSql(true)
            ->select ();
//        var_dump($list);exit;
// 		echo $M->_sql();
        foreach ($list as $k=>$v){
            $staff = \app\api\model\Admin::find($list[$k]['aid']);
            $list[$k]['staff'] = $staff->real_name;
            if($list[$k]['deal_time'] > 0){
                $list[$k]['deal_time'] = date('Y-m-d H:i:s', $list[$k]['deal_time']);
            }

            if($list[$k]['record_date'] > 0){
                $list[$k]['record_date'] = date('Y-m-d', $list[$k]['record_date']);
            }
        }
        $res['list']=$list;
        foreach($res as $key => $value){
            $this->assign($key,$value);
        }

        return view();
    }

    public function add(){
        $this->checkPowerWeb('word_record_add', $this->admin['ap_codes']);
        $workerList = $this->get_all_staff();
        $this->assign('worker_list', $workerList);
        $configM = new \app\admin\model\Config();
        $model = $configM->getSettingModel ( 'web' ); //获取配置实例
        if (!$model) {
            $optionList = [];
        }else{
            $optionList = explode('|', $model['options']);
        }
        $options = [];
//        var_dump($optionList);
        foreach ($optionList as $k=>$v){
//            var_dump($v);
            $option = [];
            $option['key'] = $v;
            $option['value'] = $v;
            $options[] = $option;
        }
        $this->assign('option_list', $options);
        $this->assign("imgList", []);
//        var_dump($options);exit;
        return view();
    }

    public function doAdd(){
        $this->checkPowerWeb('word_record_add', $this->admin['ap_codes']);

        $params = $this->request->param();

        $params['imgList'] = json_decode($params['imgList'], JSON_UNESCAPED_UNICODE);
        $params['record_date'] = strtotime($params['record_date']);
        if(isset($params['deal_time']) && $params['deal_time'] != ''){
            $params['deal_time'] = strtotime($params['deal_time']);
        }

        $result = $this->record->insert_data($params);
        if (!$result) {
            \LogPageHelper::record('添加文字性综合记录失败：' . $id, 'error', $this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加文字性综合记录成功：' . $id, 'info', $this->logOption);
        $this->success('添加成功');
    }

    public function doDel(){
        $this->checkPowerWeb('word_record_del', $this->admin['ap_codes']);
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }

        $result = $this->record->delete_by_id($ids);
        if (!$result) {
            \LogPageHelper::record('删除工作计划表记录失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除工作计划表记录成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }

    public function edit(){
        $this->checkPowerWeb('word_record_edit', $this->admin['ap_codes']);

        $workerList = $this->get_all_staff();
        $this->assign('worker_list', $workerList);
        $configM = new \app\admin\model\Config();
        $model = $configM->getSettingModel ( 'web' ); //获取配置实例
        if (!$model) {
            $optionList = [];
        }else{
            $optionList = explode('|', $model['options']);
        }
        $options = [];
//        var_dump($optionList);
        foreach ($optionList as $k=>$v){
//            var_dump($v);
            $option = [];
            $option['key'] = $v;
            $option['value'] = $v;
            $options[] = $option;
        }
        $this->assign('option_list', $options);

        $id = $this->request->param('id');
        $data = $this->record->find_by_id($id);
        if($data->deal_time){
            $data->deal_time = date('Y-m-d H:i:s', $data->deal_time);
        }
        $data->record_date = date('Y-m-d', $data->record_date);
//        var_dump($data->imgList);exit;
        $imgList = $data->imgList;
        $photoList = [];
        foreach ($imgList as $k=>$v){
            $photo = [];
            $photo['img'] = $imgList[$k]['img'];
            $photoList[] = $photo;
        }
//        var_dump(json_encode($photoList));
//        exit;
        $this->assign("model", $data);
        $this->assign("imgList", json_encode($photoList));
        return view();
    }

    public function doEdit(){
        $this->checkPowerWeb('word_record_edit', $this->admin['ap_codes']);

        $params = $this->request->param();
        $id = $this->request->param('id');
        $params['imgList'] = json_decode($params['imgList'], JSON_UNESCAPED_UNICODE);
        $params['record_date'] = strtotime($params['record_date']);
        if(isset($params['deal_time']) && $params['deal_time'] != ''){
            $params['deal_time'] = strtotime($params['deal_time']);
        }
//        var_dump($params['imgList']);exit;

        $result = $this->record->update_by_id($id, $params);
        if (!$result) {
            \LogPageHelper::record('修改文字性综合记录失败：' . $id, 'error', $this->logOption);
            $this->error('修改失败，请稍后再试');
        }
        \LogPageHelper::record('修改文字性综合记录成功：' . $id, 'info', $this->logOption);
        $this->success('修改成功');
    }
}
