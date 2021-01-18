<?php

namespace app\admin\controller;
use ROL\Baksql\Baksql;
use think\Config;
use think\Request;

class DataBackup extends Base{

    private $savePath;
    private $saveName;
    private $config;
    private $obj;
    protected $request;

    public function __construct(){
        parent::__construct();
        $this->savePath = ROOT_PATH."databak/";
        $this->saveName = date('YmdHis', time()).".sql";
        $this->config   = $this->setConfig();
        $this->obj      = new Baksql($this->config);
        $this->request  = Request::instance();
        $this->addBread('系统管理');
        $this->addBread('数据库管理');
    }

    public function index(){
        $this->checkPowerWeb('data_backup_view',  $this->admin['ap_codes']);
        \LogPageHelper::record("查看数据库备份", 'INFO', $this->logOption);
        return view();
    }

    public function index_data(){
        $data         = [];
        $data['code'] = 0;

        $list = $this->obj -> getFileList(true);
        foreach($list as $index => $item){
            $url = get_domain().dirname($_SERVER['SCRIPT_NAME']). "/../databak/" . $item['name'];
            $list[$index]['download'] = '<a href="'.$url.'" download>'. $item['name'].'</a>';
        }

        if($list){
            $data['msg']    = "查询成功";
            $data['count']  = count($list);
            $data['data']   = $list;
        }

        return $data;
    }

    public function add(){
        ini_set('memory_limit','512M');    // 临时设置最大内存占用为512M
        $this->checkPowerWeb('data_backup_add',  $this->admin['ap_codes']);
        $data = [];

        $res = $this->obj -> backup();

        if(!$res){
            $data['code'] = 0;
            $data['msg']    = "添加备份失败";
        }

        $data['code'] = 1;
        $data['msg']    = "添加备份成功";

        return $data;
    }

    public function delete(){
        $this->checkPowerWeb('data_backup_delete',  $this->admin['ap_codes']);

        $fileName   = $this->request -> param('fileName');
        $res = $this->obj -> delFileName($fileName);

        if(!$res){
            $data['code'] = 0;
            $data['msg']    = "删除备份失败";
        }
        $data['code'] = 1;
        $data['msg']    = "删除备份成功";

        return $data;
    }

    public function deleteAll(){
        $this->checkPowerWeb('data_backup_delete',  $this->admin['ap_codes']);

        $fileName = $this->request -> param('name');
        $fileName = explode(',', $fileName);

        foreach($fileName as $name){
            $res = $this->obj -> delFileName($name);
            if(!$res){
                $data['code'] = 0;
                $data['msg']    = "删除备份失败";
            }
        }

        $data['code'] = 1;
        $data['msg']    = "删除备份成功";

        return $data;
    }

    public function download(){
        $this->checkPermission("data_backup_download");

        $fileName   = $this->request -> param('fileName');
        $this->obj ->downloadFile($fileName);
    }

    public function restore(){
        $this->checkPermission("data_backup_restore");

        $fileName   = $this->request -> param('fileName');
        $res = $this->obj -> restore($fileName);

        if(!$res){
            $data['code'] = 0;
            $data['msg']    = "恢复失败";
        }
        $data['code'] = 1;
        $data['msg']    = "恢复成功";

        return $data;
    }

    private function setConfig(){
        $arr['savePath']    = $this->savePath;
        $arr['sqlBakName']  = $this->saveName;
        $config = Config::get("database");
        $config = array_merge($config, $arr);
        return $config;
    }
}
