<?php

namespace app\admin\controller;

use net\NetHelper;
use think\Controller;
use think\exception\HttpResponseException;
use think\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use think\Response;
use think\View as ViewTemplate;

class Base extends Controller
{
    public $bread = array (); // 全局面包屑变量
    public $admin;
    public $logOption;

    public function __construct()
    {
        //$url="http://219.137.228.26:30080/";
        //$this->redirect($url);
        parent::__construct();
        $this->checkAdmin();
        $configM=new \app\admin\model\Config();
        $web=$configM->getSettingModel('web');
        $this->assign('web',$web);
    }

    /**
     * 跳转到登陆页
     */
    private function heftToLogin() {
        header ( "Content-type: text/html; charset=utf-8" );
        echo "<script language='javascript'>top.location.href='" . url ( "Index/index" ) . "';</script>";
        exit ();
    }
    /**
     * 判断是否登录
     */
    public function checkAdmin(){
        $aid=0;
        if(session('?aid')){
            $aid=session('aid');
            //cookie('aid',$aid,86400*30);
        }else{
            if(cookie('aid')){
                $aid=cookie('aid');
            }else{
                session(null);
                cookie(null);
                $this->heftToLogin();
//                $this->redirect('Index/index');
//                $this->error('登录超时或未登录，请先登录！',url('Index/index'));
                exit;
//                \LogPageHelper::record('登录超时或未登录，请先登录！');
            }
        }
        $this->admin=db('admin')->where(['aid'=>$aid,'valid'=>1])->find();
        if(!$this->admin){
            session(null);
            cookie(null);
            $this->heftToLogin();
//            $this->redirect('Index/index');
            //$this->error('账号不存在或已被禁用',url('Index/index'));
            exit;
        }
        $this->admin['role']=db('admin_role')->where(['ar_id'=>$this->admin['ar_id']])->find();
        $this->admin['ap_codes']=$this->admin['role']['ap_codes'];
        $this->logOption=['aid'=>$this->admin['aid'],'admin_name'=>$this->admin['login_name']];
    }

    /**
     * 判断权限
     * @param $ap_code 权限代码
     * @param $ap_codes 权限集合
     */
    protected function checkPower($ap_code,$ap_codes){
        if(strpos($ap_codes,$ap_code) !== false){
            return true;
        }
        return false;
    }

    /**
     * 网页判断权限
     * @param $ap_code
     * @param $ap_codes
     */
    protected function checkPowerWeb($ap_code,$ap_codes){

        if(!$this->checkPower($ap_code,$ap_codes)){
            $this->error('对不起，您没有权限进行此操作');
            exit;
        }
    }

    /**
     * 列表页编辑数据权限
     * @param $ap_code
     * @param $ap_codes
     */
    protected function checkListEditPower($ap_code, $ap_codes){
        return $this->checkPower($ap_code,$ap_codes);
    }

    /**
     * 判断是否拥有读取所有员工工作计划权限
     * @param $ap_codes
     */
    protected function checkReadPower($ap_codes){
        if(!$this->checkPower('read_all_schedule',$ap_codes)){
            return false;
        }
        return true;
    }

    /**
     * 设置返回URL
     *
     * @param string $url
     */
    public function setBackUrl($url = '') {
        if (empty ( $url )) {
            cookie ( "backurl", get_url () );
        } else {
            cookie ( "backurl", $url );
        }
    }


    /**
     * 增加面包屑
     *
     * @param string $name
     * @param string $url
     */
    public function addBread($name = '', $url = '') {
        $this->bread [count ( $this->bread )] = array (
            "name" => $name,
            "url" => $url
        );
        $this->assign('bread',$this->bread);
    }

    public function setDefaultValue(){
        $model = array();
        $model['sort']  = 99;
        $model['valid'] = 1;
        $this -> assign( "model", $model );
    }


    /**
     * 获取列表并分页
     *
     * @param unknown $table
     * @param unknown $where
     * @param unknown $orderby
     * @param unknown $pageParam
     */
    public function simpleGetList($table, $where, $orderby, $pageParam) {
        $res=array();//查询结果
        $M = db ( $table );
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
        $list = $M->where ( $where )->order ( $orderby )->limit ( $Page->firstRow . ',' . $Page->listRows )->select ();
// 		echo $M->_sql();
        $res['list']=$list;
        foreach($res as $key => $value){
            $this->assign($key,$value);
        }
        return $res;
    }

    /**
     * 获取选项
     * @param  [string] $index [选项索引名]
     * @return [type]        [description]
     */
    public function getSelectOption($index){
        $dicM=new \app\common\model\Dictionary();
        $list=$dicM->getByIndexCode($index);
        $this->assign($index.'_list', $list);
    }


    /**
     * 获取相关的工作人员
     * @param  string $permission [权限吗]
     * @return array  $admins     [工作人员数组]
     */
    public function get_relevant_staff($permission){
        $role  = new \app\api\model\AdminRole();
        $roles = $role -> get_roles($permission);

        $admin  = new \app\api\model\Admin();
        $admins = $admin -> worker_info($roles);

        return $admins;
    }

    /**
     * 获取所有有效的工作人员
     * @return array  $admins     [工作人员数组]
     */
    public function get_all_staff(){

        $admin  = new \app\api\model\Admin();
        $admins = $admin -> all_worker();

        return $admins;
    }

    /**
     * 获取所有有效的工作人员下拉框选择
     * @return array  $admins     [工作人员数组]
     */
    public function get_all_staff_selects(){

        $admin  = new \app\api\model\Admin();
        $admins = $admin -> all_worker_selects();

        return $admins;
    }

    /**
     * 获取相关的录入人员
     * @param  string $permission [权限吗]
     * @return array  $admins     [工作人员数组]
     */
    public function get_entering_staff($permission){
        $role  = new \app\api\model\AdminRole();
        $roles = $role -> get_roles($permission);

        $admin  = new \app\api\model\Admin();
        $admins = $admin -> entering_worker($roles);

        return $admins;
    }


    /**
     * 获取对应表的扩展项
     * @param  [string] $table_name [表名]
     * @param  string $row_data   [数据的值]
     * @return [type]             [description]
     */
    public function extra($table_name, $row_data = ''){
        $list = $this->config -> extra_item($table_name);
        $renderHTML = array();
        foreach($list as $item){
          $data = array();
          $data['item_name']    = $item['column_name'];
          $data['item_field']   = $item['column_code'];
          $data['item_value']   = empty($row_data) ? $item['default_value'] : $row_data[$item['column_code']];
          $data['item_options'] = $this->config -> item_options($item['index_code']);
          $renderHTML[] = $this->renderHtml($item['type'], $data);
        }
        $this -> assign( "renderHTML", $renderHTML );
    }

    /**
     * 获取对应表的扩展项展示详情使用
     * @param  [string] $table_name [表名]
     * @param  string $row_data   [数据的值]
     * @return [type]             [description]
     */
    public function extraDetail($table_name, $row_data = ''){
        $list = $this->config -> extra_item($table_name);
        $renderHTML = array();
        foreach($list as $item){
            $data = array();
            $data['item_name']    = $item['column_name'];
            $data['item_value']   = empty($row_data) ? $item['default_value'] : $row_data[$item['column_code']];
        }
        $this -> assign( "extraDetail", $renderHTML );
    }

    /**
     * 渲染扩展项的HTML
     * @param  [string] $type [扩展项的类型]
     * @param  [array] $data [数据]
     * @return [string]       [description]
     */
    public function renderHTML($type, $data){
        $this->assign("data", $data);
        switch ($type) {
          case 'input':
            $html = $this->fetch('extra/input');
            break;
        case 'remarkInput':
            $html = $this->fetch('extra/remark_input');
            break;

          case 'text':
            $html = $this->fetch('extra/textarea');
            break;

          case 'select':
            $html = $this->fetch('extra/select');
            break;

          case 'hidden':
            $html = $this->fetch('extra/hidden');
            break;

          default:
            break;
        }
        return $html;
    }

    /**
     * 判断该扩展字段是否已经使用
     * @param  string  $table [表名]
     * @param  string  $field [字段名]
     * @return boolean        [description]
     */
    public function isAlreadyUse($table, $field){
        $where = [];
        $where['table_name']  = $table;
        $where['column_code'] = $field;

        $isExist = db('table_config') -> where($where) -> find();
        if($isExist){
            $this->error("该字段已被使用");
        }
    }

    /**
     * 根据时间划分时间段
     * @param  int $time [时间]
     * @return string       [时间段]
     */
    public function dividing_time_period($time){
        $period = "";
        if( $time <= 859 ){
            $period = "早晨";
        }
        elseif( $time <= 1259 ){
            $period = "上午";
        }
        elseif( $time <= 1659 ){
            $period = "下午";
        }
        else{
            $period = "傍晚";
        }

        return $period;
    }

    /**
     * 将数据导出为excel表
     * @param  [string] $filename [导出的文件名]
     * @param  [array]  $header   [表头数组]
     * @param  [array]  $data     [导出的数据]
     * @return [string]           [description]
     */
    public function export_excel($filename, $header, $data){
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('宋体');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(11);

        $spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(9);

        $worksheet->setTitle($filename);

        foreach($header as $key => $value){
          $index = $key + 1;
          $worksheet->setCellValueByColumnAndRow($index,  1,  $value[1]);
        }

        foreach($data as $row => $data){
          $row += 2;
          foreach ($header as $column => $item) {
            $column += 1;
            $worksheet->setCellValueByColumnAndRow($column, $row, $data[$item[0]]);
          }
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    protected function succ($msg = '', $data = '', $wait = 3, array $header = [])
    {


        $type = $this->getResponseType();
        $result = [
            'code' => 1,
            'msg'  => $msg,
            'data' => $data,
            'wait' => $wait,
        ];

        if ('html' == strtolower($type)) {
            $template = Config::get('template');
            $view = Config::get('view_replace_str');

            $result = ViewTemplate::instance($template, $view)
                ->fetch(Config::get('dispatch_success_tmpl'), $result);
        }

        $response = Response::create($result, $type)->header($header);

        throw new HttpResponseException($response);
    }

}
