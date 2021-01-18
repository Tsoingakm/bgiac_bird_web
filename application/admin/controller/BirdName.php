<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\api\model\BirdName AS Name;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class BirdName extends Base {
    protected $request;
    protected $name;

    public function __construct(){
        parent::__construct();
        $this->request  = Request::instance();
        $this->name     = new Name();
        $this->addBread('台账配置');
        $this->addBread('鸟类信息管理');
    }

    public function index(){
        $this->checkPowerWeb('bird_name_view',$this->admin['ap_codes']);

        $where = array();
        $pageParam = array();

        $keyword = input('keyword');
        if ($keyword) {
            $where['bird_name'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword', $keyword);
        }

        $orderby = 'id desc';
        $list = $this->simpleGetList('bird_name', $where, $orderby, $pageParam);

        if(input('act')==="export_excel"){
          $this->export_data_handle($where);
        }

        return view();
    }

    public function import(){
        $params = $this->request -> param();

        $filepath = $params['path'];

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');

        $spreadsheet  = $reader->load($_SERVER['DOCUMENT_ROOT'].$params['path']);
        $worksheet    = $spreadsheet->getActiveSheet();

        $highestRow     = $worksheet->getHighestRow();
        $highestColumn  = $worksheet->getHighestColumn();

        $lines = $highestRow - 1;
        if ($lines <= 0) {
            exit('Excel表格中没有数据');
        }

        for ($row = 2; $row <= $highestRow; $row++) {
            $data = array();
            $data['bird_name']        = $worksheet->getCellByColumnAndRow(2,  $row)->getValue();
            $data['manual_number']    = $worksheet->getCellByColumnAndRow(3,  $row)->getValue();
            $data['order']            = $worksheet->getCellByColumnAndRow(4,  $row)->getValue();
            $data['family']           = $worksheet->getCellByColumnAndRow(5,  $row)->getValue();
            $data['residence_type']   = $worksheet->getCellByColumnAndRow(6,  $row)->getValue();
            $data['ecological_type']  = $worksheet->getCellByColumnAndRow(7,  $row)->getValue();
            $data['body_length']      = $worksheet->getCellByColumnAndRow(8,  $row)->getValue();
            $data['body_type']        = $worksheet->getCellByColumnAndRow(9,  $row)->getValue();
            $data['risk']             = $worksheet->getCellByColumnAndRow(10, $row)->getValue();

            $is_exist = $this->name -> find_by_name($data['bird_name']);
            if($is_exist){
                $result = $this->name -> update_by_id($is_exist['id'], $data);
            }
            else{
                $result = $this->name -> insert_data($data);
            }
        }

        $res['status']  = 1;
        $res['msg']     = "导入成功！";

        return $res;
    }

    public function doDel(){
      $this->checkPowerWeb('bird_name_delete',$this->admin['ap_codes']);
      $ids   = $this->request -> param('ids');
      if (empty($ids)) { $this->error('参数不正确'); }

      $result = $this->name -> delete_by_id($ids);
      if(!$result){
          \LogPageHelper::record('删除鸟类信息记录失败：'.$ids,'error',$this->logOption);
          $this->error('删除失败，请稍后再试');
      }
      \LogPageHelper::record('删除鸟类信息记录成功：'.$ids,'info',$this->logOption);
      $this->success('删除成功');
    }

}
