<?php

namespace app\admin\controller;

use think\Request;
use app\api\model\BirdCondition;

class QueryDevice extends QueryBase
{


    public function __construct()
    {
        parent::__construct();
        $this->addBread('设备维护查询');
    }

    public function index()
    {
        $this->checkPowerWeb('query_device_check_item', $this->admin['ap_codes']);
        $action = input('act');
        $where = array();
        $pageParam = array();
        $begin_day = input('begin_day');
        $begin_day_int = 0;//时间戳格式的开始日期
        $end_day_int = 0;//时间戳格式的结束日期
        $end_day = input('end_day', date('Y-m-d'));
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
        $this->assign('end_day', $end_day);
        $this->assign('today', date('Y-m-d'));
        //处理方式
        $parts_status_list=db("device_status")->where(['type'=>2])->select();

        $this->assign('parts_status_list',$parts_status_list);
        //联动菜单

        //设备
        $device = input('device');
        $pageParam['device'] = $device;
        if ($device) {
            $where['device'] = $device;
        }
        $this->assign('device', $device);
        $deviceList=db("device")->order("name asc")->select();
        $this->assign('device_list',$deviceList);
        //编号
        $code = input('code');
        $pageParam['code'] = $code;
        if ($code) {
            $where['code'] = $code;
        }
        $this->assign('code', $code);

        //检查项目
        $check_item = input('check_item');
        $pageParam['check_item'] = $check_item;
        if ($check_item) {
            $where['check_item'] = $check_item;
        }
        $this->assign('check_item', $check_item);

        $orderby = "working_date asc";
        $db = db('device_record');
        $calendarDb = db('calendar');
        $pagekey = config('paginate.var_page') ? config('paginate.var_page') : 'p'; //设置分页参数名称
        $p = input($pagekey, 1); // 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
        //天数统计
        $whereCanlendar['day_int'] = array('between', [date('Ymd', $begin_day_int), date('Ymd', $end_day_int)]);//日历查询范围，从开始到结束

        $totalRows = $calendarDb->where($whereCanlendar)->count();

        $res['totalRows'] = $totalRows;
        // 查询满足要求的总记录数
        $pageSize = config('page_size') ? config('page_size') : 15;

        if ($action === "export_excel") {
            $pageSize = 10000;
        }
        $res['pageSize'] = $pageSize;
        $Page = new \PageHelper($totalRows, $pageSize, $pageParam); // 实例化分页类 传入总记录数和每页显示的记录数
        // 分页跳转的时候保证查询条件
        //$Page->parameter = $pageParam;
        $show = $Page->show(); // 分页显示输出
        $res['pageShow'] = $show;
        $res['page'] = $show;
        $filed = "working_date,process_method";
        $calendarList = $calendarDb->where($whereCanlendar)->order('day_int asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $where['working_date'] = array('between', [strtotime($calendarList[0]["day_str"]), strtotime($calendarList[count($calendarList) - 1]["day_str"])]);//统计日期范围，只统计当前页的，提高查询效率

        $list = $db->field($filed . ",from_unixtime(working_date, '%Y-%m-%d') as date_str,count(check_item) as count_check_item")->group($filed)->where($where)->order($orderby)->select();//指定日期的统计结果
//        print_r($list);
//        echo $db->getLastSql();
        //拼出二维表数组
        foreach ($calendarList as $k => $v) {
            foreach ($parts_status_list as $key=>$value){
                $calendarList[$k][$value['type'].'_'.$value['id']]=0;//[$value['value'],$value['key']];
                foreach ($list as $kk => $vv) {
                    if ($v['day_str'] == $vv['date_str'] && $value['name']==$vv['process_method']) {//如果当天有数据
                        $calendarList[$k][$value['type'].'_'.$value['id']] = $vv['count_check_item'];
                    }
                }
            }

        }
        $this->assign('list', $calendarList);
//        print_r($calendarList);
        foreach ($res as $key => $value) {
            $this->assign($key, $value);
        }
        if ($action === "export_excel") {
            $this->export_data_handle($calendarList);
        }

        \LogPageHelper::record('查看设备维护查询', 'info', $this->logOption);

        return view();
    }

    //获取级联菜单
    public function getSearchSelect()
    {
        //设备
        $device = input('device');
        $code = input('code');
        $check_item = input('check_item');
        if($device){
            $deviceModel=db("device")->where(['name'=>$device])->find();

            $whereCode['device_id']=$deviceModel['device_id'];
            $codeList=db('device_code')->field(["code"=>"value"])->where($whereCode)->order('code asc')->select();
//            print_r($codeList);
            $this->assign('list',$codeList);
            $this->assign('value',$code);
            $res['code_html']=$this->fetch("fetch_select");

            $partsList=db("device_parts")->field(["name"=>"value"])->where($whereCode)->order("sort asc")->select();
            $this->assign('list',$partsList);
            $this->assign('value',$check_item);
            $res['parts_html']=$this->fetch("fetch_select");
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
//            json($res);
        }

    }

    public function export_data_handle($data)
    {
        $filename = "设备维护查询";
        //处理方式
        $parts_status_list=db("device_status")->where(['type'=>2])->select();
        $header = [
            ['day_str', '日期']
        ];
        foreach ($parts_status_list as $key=>$value){
            $header[]=[$value['type'].'_'.$value['id'],$value['key']];
        }

        $this->export_excel($filename, $header, $data);
    }
}
