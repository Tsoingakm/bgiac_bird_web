<?php

namespace app\admin\controller;

use think\Request;
use think\Db;
use think\Collection;
use app\api\model\Device;
use app\api\model\DeviceParts;
use app\api\model\DeviceRecord;

class DeviceOverhaul extends Base{

    protected $request;
    protected $device;
    protected $parts;
    protected $record;

    public function __construct(){
        parent::__construct();
        $this->request  = Request::instance();
        $this->device   = new Device();
        $this->parts    = new DeviceParts();
        $this->record   = new DeviceRecord();
        $this->addBread('台账管理');
        $this->addBread('设备检修查询');
    }

    public function index(){
        $this->checkPowerWeb('query_device_overhaul', $this->admin['ap_codes']);

        $device_list = $this->device::all(['valid'=>1]);
        $this->assign("device_list", $device_list);

        $where      = array();
        $pageParam  = [ 'query' => [] ];

        $params = $this->request -> param();
        $start  = $params['starting_time'];
        $end    = $params['end_time'];
        $now    = date('Y-m-d', time());

        $starting_time  = $start  ? $start  : date("Y-m-d", strtotime("-1 month"));
        $end_time       = $end    ? $end    : $now;
        $this->assign("starting_time", $starting_time);
        $this->assign("end_time", $end_time);
        $this->assign("max_time", $now);

        $pageParam['query']['starting_time']  = $starting_time;
        $pageParam['query']['end_time']       = $end_time;

        $period  = [ strtotime($starting_time), strtotime($end_time) ];

        $default_device = $this->device::get(function($query){
                              $query->where('valid', 1)->order('device_id', 'asc');
                          });
        $device_id = $params['device_id'] ? $params['device_id'] : $default_device['device_id'];

        if($device_id) {
            $where['device_id'] =  $device_id;
            $this->assign('device_id',$device_id);
            $pageParam['query']['device_id'] = $device_id;
        }

        $part_id = $params['part_id'];
        if($part_id) {
            $where['part_id'] = $part_id;
            $this->assign('part_id',$part_id);
            $pageParam['query']['part_id'] = $part_id;
        }

        $list = Db::view('Device', ['device_id', 'name'=>'device_name', 'valid'=>'device_valid'])
                  -> view('Device_code', 'code', 'Device_code.device_id=Device.device_id')
                  -> view('Device_parts', ['id'=>'part_id', 'name'=>'part_name'], 'Device_parts.device_id=Device.device_id')
                  -> where('device_valid', 1)
                  -> where($where)
                  -> select();
        foreach($list as $key => $item){
            $overhaul = $this->record -> month($period) -> where([ 'device'=>$item['device_name'], 'code'=>$item['code'], 'check_item'=>$item['part_name'] ]) -> count();
            $list[$key]['overhaul'] = $overhaul > 0 ? '已检修' : '未检修';
            $list[$key]['sort'] = $overhaul;
        }
        $total  = count($list);

        $sort = array_column($list, 'sort');
        array_multisort($sort, SORT_ASC, $list);

        $this->assign('list', json_encode($list));
        $this->assign('total', $total);

        $action = $this->request -> param('act');
        if($action === "export_excel"){
          $this->export_data_handle($list);
        }

        \LogPageHelper::record('查看设备检修列表','info',$this->logOption);

        return view();
    }

    public function export_data_handle($data){
        $filename = "设备检修情况";

        $header = [
            ['device_name', '月份'],
            ['code',        '记录条数'],
            ['part_name',   '只次数'],
            ['overhaul',    '是否检修'],
        ];

        $this -> export_excel($filename, $header, $data);
    }

    public function getParts(){
        $id   = $this->request -> param('id');
        $part = $this->parts::all(function($query) use($id){
          $query  -> field('id, name')
                  -> where('valid', 1)
                  -> where('device_id', $id)
                  -> order('sort', 'asc');
        });
        return $part;
    }


}
