<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/9/4
 * Time: 16:49
 */

namespace app\admin\controller;

use app\admin\dbview\WorkInsectGropViewModel;
use think\Db;

class StaticWorkInsectRecord extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->addBread('数据统计');
        $this->addBread('昆虫消杀记录统计');

    }


    public function index()
    {
        $this->checkPowerWeb('static_work_insect_list',$this->admin['ap_codes']);//权限判断

        $keyword = input('keyword');
        $where = array();
        $pageParam = array();
        if ($keyword) {
            $where['spary_times'] = ['like', '%' . $keyword . '%'];
            $pageParam['keyword'] = $keyword;
            $this->assign('keyword', $keyword);
        }

        $begin_day = input ( 'begin_day', date('Y-m-d', time() - 86400 * 30));
        $begin_day_int=0;
        $end_day = input ( 'end_day',date('Y-m-d') );
        $end_day_int=strtotime("2037-01-01");
        $day = urldecode(input('day'));
        $dayList = explode('~', $day);
        $begin_day = $dayList[0]? $dayList[0]: date("Y-m-d", strtotime("-1 month"));
        $end_day = $dayList[1]? $dayList[1]: date('Y-m-d');
        $begin_day = trim($begin_day);
        $end_day = trim($end_day);
        if (! empty ( $end_day )) {
            $end_day_int = strtotime($end_day . ' 23:59:59');
        }
        if (! empty ( $begin_day )) {
            $begin_day_int = strtotime ( $begin_day );
            $pageParam ['begin_day'] = $begin_day;
            $this->assign ( 'begin_day', $begin_day );
        }

        if (! empty ( $end_day )) {
            $end_day_int = strtotime ( $end_day . ' 23:59:59' );
            $pageParam ['end_day'] = $end_day;
            $this->assign ( 'end_day', $end_day );
        }

        $where ['working_date'] = array (
            'between',
            [$begin_day_int,$end_day_int]
        );

        $orderby = 'working_date desc,start_time desc';

        $pagekey=config('paginate.var_page') ? config('paginate.var_page'):'p'; //设置分页参数名称
        $p = input ($pagekey, 1 ); // 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取

        $totalRows = WorkInsectGropViewModel::getView()->where ( $where )->count ();
        $res['totalRows']=$totalRows;
        // 查询满足要求的总记录数
        $pageSize = config ( 'page_size' )?config ( 'page_size' ):15;
        $res['pageSize']=$pageSize;
        $Page = new \PageHelper( $totalRows, $pageSize, $pageParam); // 实例化分页类 传入总记录数和每页显示的记录数
        // 分页跳转的时候保证查询条件
        //$Page->parameter = $pageParam;
        $show = $Page->show (); // 分页显示输出
        $res['pageShow']=$show;
        $res['page']=$show;
        $list = WorkInsectGropViewModel::getView()->where ( $where )->order ( $orderby )->limit ( $Page->firstRow . ',' . $Page->listRows )->select ();

        $res['list']=$list;
        $totalCount = 0;
        foreach ($list as $k=>$v){
            $totalCount += $list[$k]['water_consumption'];
        }
        $this->assign("totalCount",$totalCount);
        foreach($res as $key => $value){
            $this->assign($key,$value);
        }
        $pageParam ['day'] = $begin_day.' ~ '.$end_day;
        $this->assign('day', $begin_day.' ~ '.$end_day);
        \LogPageHelper::record('查看昆虫消杀记录统计列表页','info',$this->logOption);
        return view();
    }

    public function detail(){
        $this->checkPowerWeb('static_work_insect_detail',$this->admin['ap_codes']);//权限判断

        $spary_times=input('spary_times');
        if(!$spary_times){
            $this->error('参数错误');
        }
        $where['spary_times']=$spary_times;

        $working_date=input('working_date');
        if(!$working_date){
            $this->error('参数错误');
        }
        $where['working_date']=$working_date;

        $model=WorkInsectGropViewModel::getView()->where($where)->find();
        if(!$model){
            $this->error('没有找到记录');
        }
        $orderby = 'working_date desc,start_time desc';
        $list=db('work_insect')->where($where)->order($orderby)->select();
        $this->assign('model',$model);
        $this->assign("list",$list);
        \LogPageHelper::record('查看昆虫消杀记录统计详情页，喷药次数代号：'.$spary_times,'info',$this->logOption);
        return view();
    }

}
