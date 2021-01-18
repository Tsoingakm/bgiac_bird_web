<?php

namespace app\api\controller;

use think\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use app\api\model\Config;

class Email extends Controller {

  public function data_handle(){
      $start  = date("Y-m-d", strtotime('-7 day'));
      $end    = date("Y-m-d", time());

      $filename = "日志";

      $filepath = "email/".$start."_".$end."_log.xlsx";

      $header = [
          ['log_id',      '编号'],
          ['content',     '日志内容'],
          ['admin_name',  '操作人'],
          ['addtime',     '时间'],
      ];

      $data = db('log_page') -> field( 'log_id, content, admin_name, addtime' ) -> whereTime('addtime', 'between', [$start, $end]) -> select();
      foreach($data as $key=>$value){
          $data[$key]['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
      }

      $spreadsheet = new Spreadsheet();
      $worksheet = $spreadsheet->getActiveSheet();
      $spreadsheet->getDefaultStyle()->getFont()->setName('宋体');
      $spreadsheet->getDefaultStyle()->getFont()->setSize(11);

      $spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(12);

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

      $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
      $writer->save($filepath);

      return $filepath;
  }

  public function send(){
      $config = Config::get('log');
      $item = unserialize($config['content']);


      $filepath = $this->data_handle();

      $transport = (new \Swift_SmtpTransport('ssl://smtp.163.com', 465))
                    ->setUsername($item['sender'])
                    ->setPassword($item['smtp_code']);

      $mailer = new \Swift_Mailer($transport);

      $message = (new \Swift_Message('鸟情和设备综合操作平台日志'))
                    ->setFrom([$item['sender'] => 'yc'])
                    ->setTo($item['recipient'])
                    ->setBody('日志文件在附件中');

      $attachment = \Swift_Attachment::fromPath($filepath);
      $message->attach($attachment);

      $mailer->send($message);
  }

    public function export_excel($filename, $filepath, $header, $data){
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('宋体');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(11);

        $spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(12);

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

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filepath);
    }

}
