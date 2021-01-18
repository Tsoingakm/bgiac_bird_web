<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2020-11-11
 * Time: 9:21
 */

namespace app\admin\controller;

use app\api\model\Schedule as SM;
use think\Db;
use think\Request;

class Schedule extends Base
{

    protected $schedule;
    protected $request;

    public function __construct()
    {
        parent::__construct();
        $this->schedule = new SM();
        $this->request = Request::instance();
        $this->addBread('办公计划');
        $this->addBread('工作计划表');
    }

    public function index(){
        $this->checkPowerWeb('work_schedule_view',$this->admin['ap_codes']);
        $hasPower = $this->checkReadPower($this->admin['ap_codes']);
        $typeList = [
            ['value'=> '-1', 'name'=>'全部'],
            ['value'=> '0', 'name'=>'未完成'],
            ['value'=> '1', 'name'=>'已完成'],
        ];
        $is_complete = input('is_complete', -1);
        $where = [];
        $pageParam = [];
        if($is_complete >= 0){
            $where['is_complete'] = $is_complete;
            $pageParam['is_complete'] = $is_complete;
        }

        $this->assign('is_complete', $is_complete);
        $this->assign('typeList', $typeList);

        if(!$hasPower){
            $where['aid'] = $this->admin['aid'];
        }
        $orderBy = 'index DESC, addtime DESC';
//        $list = $this->simpleGetList('schedule', $where, $orderBy, $pageParam);

        $res=array();//查询结果
        $M = db ( 'schedule' );
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
        $list = $M->where ( $where )->order ( $orderBy )->limit ( $Page->firstRow . ',' . $Page->listRows )
//            ->fetchSql(true)
            ->select ();
//        var_dump($list);exit;
        foreach ($list as $k=>$v){
            if($list[$k]['deal_time']){
                $list[$k]['deal_time'] = date('Y-m-d H:i:s', $list[$k]['deal_time']);
            }
        }
// 		echo $M->_sql();
        $res['list']=$list;
        foreach($res as $key => $value){
            $this->assign($key,$value);
        }
        return view();
    }

    public function add(){
        $this->checkPowerWeb('work_schedule_add', $this->admin['ap_codes']);
        return view();
    }

    public function doAdd(){
        $this->checkPowerWeb('work_schedule_add', $this->admin['ap_codes']);

        $params = $this->request->param();
        $params['aid'] = $this->admin['aid'];
        //获取当前用户排序最后的数据
        $lastData = $this->schedule
            ->where(['aid'=>$this->admin['aid']])
            ->order('index DESC')
            ->find();
        if($lastData){
            $newIndex = intval($lastData['index']) + 1;
        }else{
            $newIndex = 1;
        }
        if(isset($params['deal_time']) && $params['deal_time'] != ''){
            $params['deal_time'] = strtotime($params['deal_time']);
        }

        $params['index'] = $newIndex;

        $result = $this->schedule->insert_data($params);
        if (!$result) {
            \LogPageHelper::record('添加工作计划表记录失败：' . $id, 'error', $this->logOption);
            $this->error('添加失败，请稍后再试');
        }
        \LogPageHelper::record('添加工作计划表记录成功：' . $id, 'info', $this->logOption);
        $this->success('添加成功');
    }

    public function doDel(){
        $this->checkPowerWeb('work_schedule_del', $this->admin['ap_codes']);
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数不正确');
        }

        $result = $this->schedule->delete_by_id($ids);
        if (!$result) {
            \LogPageHelper::record('删除工作计划表记录失败：' . $ids, 'error', $this->logOption);
            $this->error('删除失败，请稍后再试');
        }
        \LogPageHelper::record('删除工作计划表记录成功：' . $ids, 'info', $this->logOption);
        $this->success('删除成功');
    }

    public function edit(){
        $this->checkPowerWeb('work_schedule_edit', $this->admin['ap_codes']);
        $id = $this->request->param('id');
        $data = $this->schedule->find_by_id($id);
        if($data->deal_time){
            $data->deal_time = date('Y-m-d H:i:s', $data->deal_time);
        }
        $this->assign("model", $data);
        return view();
    }

    public function doEdit(){
        $this->checkPowerWeb('work_schedule_edit', $this->admin['ap_codes']);
        $id = $this->request->param('id');
        $params = $this->request->param();
        if(isset($params['deal_time']) && $params['deal_time'] != ''){
            $params['deal_time'] = strtotime($params['deal_time']);
        }
        if(!isset($params['is_complete'])){
            $params['is_complete'] = 0;
        }
//        var_dump($params);exit;
        $result = $this->schedule->update_by_id($id, $params);
        if ($result !== false) {
            \LogPageHelper::record('修改工作计划表记录成功：' . $id, 'info', $this->logOption);
            $this->success('修改成功');
        }
        \LogPageHelper::record('修改工作计划表记录失败：' . $id, 'error', $this->logOption);
        $this->error('修改失败，请稍后再试');
    }

    public function changeData(){
        $params = input('');
        $indexList = json_decode($params['list'], true);
        Db::startTrans();
        try{
            foreach ($indexList as $k=>$v){
                $this->schedule->update_by_id($indexList[$k]['id'], $indexList[$k]);
            }
        }catch (\Exception $e) {
            $this->success('修改失败：', $e->getMessage());
        }
        $this->success('修改成功');
    }
}
