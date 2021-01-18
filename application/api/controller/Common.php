<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use think\Validate;
use think\Db;
use think\Log;


class Common extends Controller {
  protected $request; // 用来处理参数
  protected $params; // 过滤后符合要求的参数
  protected $indate; //有效期，后期会从数据库读

  protected function _initialize() {

        parent::_initialize();
        $this->request = Request::instance();
        $this->check_token($this->request->param());
        $this->indate = 86400 * 2;

        if ($this->catchOptions()) {
            return $this->optionsReturn();
        }
  }

    protected function catchOptions()
    {

        $request = request();
//        var_dump( $request->method() == "OPTIONS");
        return $request->method() == "OPTIONS";
    }

    protected function optionsReturn()
    {
//        return Response::create(['test' => 'yckj'], 'json')->code(200);

//         return new HttpResponseException(Response::create(['test' => 'yckj'], 'json')->code(400));
        die("this is option");


    }

    protected function checkReadPower($ap_codes){
        if(!$this->checkPower('read_all_schedule',$ap_codes)){
            return false;
        }
        return true;
    }

    protected function checkPower($ap_code,$ap_codes){
        if(strpos($ap_codes,$ap_code) !== false){
            return true;
        }
        return false;
    }

/**
  * api 数据返回
   * @param  [boolval] $status [成功：success；失败：false]
   * @param  [string] $msg  [接口要返回的提示信息]
   * @param  [array]  $data [接口要返回的数据]
   * @return [string]       [最终的json数据]
  */
  public function return_msg($status, $msg = '', $data = []) {
      /*********** 组合数据  ***********/
      $return_data['status'] = $status;
      $return_data['msg']  = $msg;
      if(!empty($data)){
          $return_data['data'] = $data;
      }
      Log::record('output:'.json_encode($return_data));
      /*********** 返回信息并终止脚本  ***********/
      echo json_encode($return_data, JSON_UNESCAPED_UNICODE); die;
  }

  /**
  * 验证token(用户身份是否合法)
   * @param  [array] $arr [全部请求参数]
   * @return [json]      [token验证结果]
  */
  public function check_token($arr) {
    /*********** api传过来的token  ***********/
    if (!isset($arr['token']) || empty($arr['token'])) {
        $this->return_msg(false, 'token不能为空!');
    }
    $api_token = $arr['token']; // api传过来的token

    /*********** 数据库里的token  ***********/
    $admin = Db::name('admin')->where('token', $api_token)->find();
    if(!$admin){
      $this->return_msg(false, "该token值无效");
    }

    $service_token = $admin['token'];

    /*********** 对比token ***********/
    if ($api_token !== $service_token) {
        $this->return_msg(false, 'token值不正确!');
    }

    $is_indate = ( time() - $admin['token_time'] > 86400 * 2 ) ? 0 : 1;
    if(!$is_indate){
      $this->return_msg(false, 'token已过期!');
    }
  }

  /**
  * 验证参数 参数过滤
   * @param  [array] $arr [除time和token外的所有参数]
   * @return [return]      [合格的参数数组]
  */
  public function check_params($arr) {
      /*********** 获取参数的验证规则  ***********/
      $rule = $this->rules[$this->request->controller()][$this->request->action()];
      /*********** 验证参数并返回错误  ***********/
      $this->validater = new Validate($rule);
      if (!$this->validater->check($arr)) {
          $this->return_msg(false, $this->validater->getError());
      }
      /*********** 如果正常,通过验证  ***********/
      return $arr;
  }


  public function get_admin_id(){
      $token = $this->request->param('token');
      $admin = Db::name('admin')->where('token', $token)->find();
      return $admin['aid'];
  }


  /**
   * 处理选项
 * @param  [array] $arr     [原始数组]
   * @param  string $key   [键名]
   * @param  string $value [值]
   * @return [array]        [处理后的数组]
   */
  public function process_option($arr, $key = 'key', $value = 'value'){
      $options = [];
      foreach($arr as $k=>$v){
         $options[] = [
           'key'    => $v[$key],
           'value'  => $v[$value]
         ];
      }
      return $options;
  }

  /**
     * 在查询记录详情时把扩展项也加进去
     * @param  [string] $table [表名]
     * @param  [array] $data  [该记录的数据]
     * @return [array]        [扩展项的数据]
     */
    public function extension($table, $data){
        $config = new \app\api\model\TableConfig();
        $extra = $config -> extra_item($table);
        foreach ($extra as $key=>$value) {
            $data[$value['column_name']]  =  $data[$value['column_code']];
        }
        return $data;
    }

    /**
     * 提升鸟名的权重
     * @param  [string] $bird [鸟名]
     * @return [type]       [description]
     */
    public function increase_weights($bird){
        Db::name('bird_name') -> where('bird_name', $bird) -> setInc('weights');
    }

    /**
     * 将app端传过来的时间转为正常格式
     * @param  [string] $time [app传过来的时间] 1830
     * @return [string]       [返回的时间]      18:30
     */
    public function split_time($time){
        if(strlen($time) < 4){
          $time = "0".$time;
        }
        $time = str_split($time, 2);
        $time = implode(":", $time);
        return $time;
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

    public function get_all_staff_selects(){

        $admin  = new \app\api\model\Admin();
        $admins = $admin -> all_worker_selects();

        return $admins;
    }

}
