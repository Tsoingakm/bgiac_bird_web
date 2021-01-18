<?php
namespace app\api\validate;

use think\Validate;

class WorkInsect extends Validate{

    protected $rule = [
        'working_date'        =>  ['require'],
        'time_period'         =>  ['require'],
        'spary_times'         =>  ['require'],
        'water_consumption'   =>  ['require', 'number'],
        'pharmacy_name1'      =>  ['require'],
        'dosage1'             =>  ['require', 'number'],
        'start_time'          =>  ['require'],
        'end_time'            =>  ['require'],
        'manager1'            =>  ['require'],
        'manager2'            =>  ['require'],
        'service_provider'    =>  ['require'],
        'operation_situation' =>  ['require'],
        'aid'                 =>  ['require'],
    ];

    protected $message = [
        'working_date.requrie'        =>  '日期不能为空',
        'time_period.require'         =>  '时段不能为空',
        'spary_times.require'         =>  '喷药次数代号不能为空',
        'water_consumption.require'   =>  '用水量不能为空',
        'pharmacy_name1.require'      =>  '药剂名称不能为空',
        'dosage1.require'             =>  '药剂不能为空',
        'start_time.require'          =>  '开始时间不能为空',
        'end_time.require'            =>  '结束时间不能为空',
        'manager1.require'            =>  '管理员不能为空',
        'manager2.require'            =>  '管理员不能为空',
        'service_provider.require'    =>  '服务商负责人不能为空',
        'operation_situation.require' =>  '作业情况不能为空',
        'aid.require'                 =>  '录入人员不能为空',
    ];

}
