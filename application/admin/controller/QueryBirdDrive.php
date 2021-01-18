<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/10/25
 * Time: 3:25
 */


namespace app\admin\controller;

use think\Request;

class QueryBirdDrive  extends QueryBase
{
    public function __construct()
    {
        parent::__construct();
        $this->addBread('鸟类危险活动记录查询');
    }

    public function index()
    {
        $this->checkPowerWeb('query_bird_drive', $this->admin['ap_codes']);
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


        //危险等级
        $bird_drive_level=input("bird_drive_level");
        $bird_drive_level_list=db('table_config_option')->where(['index_code'=>"bird_drive_level"])->order('sort asc')->select();
        $pageParam['bird_drive_level']=$bird_drive_level;
        if($bird_drive_level){
            $where['danger_level']=$bird_drive_level;
        }
        $this->assign('bird_drive_level', $bird_drive_level);
        $this->assign('bird_drive_level_list', $bird_drive_level_list);

        //鸟名
        $bird_name = input("bird_name");
        $bird_name_list = db("bird_name")->order('bird_name asc')->select();
        $pageParam['bird_name'] = $bird_name;
        if ($bird_name) {
            $where['bird_name'] = $bird_name;
        }
        $this->assign('bird_name', $bird_name);
        $this->assign('bird_name_list', $bird_name_list);

        //指定分类
        $bird_type = input("bird_type");
        $bird_type_list = db("bird_name")->field('ecological_type as bird_type')->group('ecological_type')->order('ecological_type asc')->select();
        $pageParam['bird_type'] = $bird_type;
        if ($bird_type) {
            $where['bird_type'] = $bird_type;
        }
        $this->assign('bird_type', $bird_type);
        $this->assign('bird_type_list', $bird_type_list);

        $orderby = "patrol_date asc";
        $db = db('bird_drive');
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
        $filed = "patrol_date";
        $calendarList = $calendarDb->where($whereCanlendar)->order('day_int asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $where['patrol_date'] = array('between', [strtotime($calendarList[0]["day_str"]), strtotime($calendarList[count($calendarList) - 1]["day_str"])]);//统计日期范围，只统计当前页的，提高查询效率
        $list = $db->field($filed . ",from_unixtime(patrol_date, '%Y-%m-%d') as date_str,sum(bird_num) as sum_bird_num")->group($filed)->where($where)->order($orderby)->select();//指定日期的统计结果
        //拼出二维表数组
        foreach ($calendarList as $k => $v) {
            $calendarList[$k]['sum_bird_num'] = 0;//默认为0，否则导出会为空
            $calendarList[$k]['patrol_date']=strtotime($v['day_str']);
            foreach ($list as $kk => $vv) {
                if ($v['day_str'] == $vv['date_str']) {//如果当天有数据
                    $calendarList[$k]['sum_bird_num'] = $vv['sum_bird_num'];
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

        \LogPageHelper::record('查看鸟类危险活动记录查询', 'info', $this->logOption);

        return view();
    }

    public function export_data_handle($data)
    {
        $filename = "鸟类危险活动记录查询";

        $header = [
            ['day_str', '日期'],
            ['sum_bird_num', '只次数']
        ];

        $this->export_excel($filename, $header, $data);
    }
}