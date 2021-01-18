<?php

namespace app\admin\controller;

use think\Request;
use app\api\model\BirdCondition;

class QueryWorkInsect extends QueryBase
{


    public function __construct()
    {
        parent::__construct();
        $this->addBread('消杀记录查询');
    }

    public function index()
    {
        $this->checkPowerWeb('query_work_insect', $this->admin['ap_codes']);

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
        //消杀区域
        $work_area = input('work_area');
        $work_area_list = db('work_area')->where(['table_name' => 'work_area'])->order('area_name asc')->select();
        $pageParam['work_area'] = $work_area;
        if ($work_area) {
            $where['maintain_area'] = $work_area;
        }

        $this->assign('work_area', $work_area);
        $this->assign('work_area_list', $work_area_list);
        //飞行区方位
        $work_flight = input("work_flight");
        $work_flight_list = db("table_config_option")->where(['index_code'=>'work_insect_flight'])->order('sort asc')->select();

        $pageParam['work_flight'] = $work_flight;
        if ($work_flight) {
            $where['flight_area'] = $work_flight;
        }
        $this->assign('work_flight', $work_flight);
        $this->assign('work_flight_list', $work_flight_list);


        $orderby = "working_date asc";
        $db = db('work_insect');
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
        $filed = "working_date";
        $calendarList = $calendarDb->where($whereCanlendar)->order('day_int asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $where['working_date'] = array('between', [strtotime($calendarList[0]["day_str"]), strtotime($calendarList[count($calendarList) - 1]["day_str"])]);//统计日期范围，只统计当前页的，提高查询效率
        $list = $db->field($filed . ",from_unixtime(working_date, '%Y-%m-%d') as date_str,sum(work_area) as sum_work_area")->group($filed)->where($where)->order($orderby)->select();//指定日期的统计结果
        //拼出二维表数组
        foreach ($calendarList as $k => $v) {
            $calendarList[$k]['sum_work_area'] = 0;//默认为0，否则导出会为空
            foreach ($list as $kk => $vv) {
                if ($v['day_str'] == $vv['date_str']) {//如果当天有数据
                    $calendarList[$k]['sum_work_area'] = $vv['sum_work_area'];
                }
            }
        }
        $this->assign('list', $calendarList);
        foreach ($res as $key => $value) {
            $this->assign($key, $value);
        }
        if ($action === "export_excel") {
            $this->export_data_handle($calendarList);
        }

        \LogPageHelper::record('查看消杀记录查询', 'info', $this->logOption);

        return view();
    }

    public function export_data_handle($data)
    {
        $filename = "消杀记录查询";

        $header = [
            ['day_str', '日期'],
            ['sum_work_area', '消杀面积']
        ];

        $this->export_excel($filename, $header, $data);
    }
}
