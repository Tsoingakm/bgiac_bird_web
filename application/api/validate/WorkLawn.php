<?php
namespace app\api\validate;

use think\Validate;

class WorkLawn extends Validate{

    protected $rule = [
        'working_date'        =>  ['require'],
        'work_type'           =>  ['require'],
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
        'work_type.require'           =>  '维护类型不能为空',
        'start_time.require'          =>  '开始时间不能为空',
        'end_time.require'            =>  '结束时间不能为空',
        'manager1.require'            =>  '管理员不能为空',
        'manager2.require'            =>  '管理员不能为空',
        'service_provider.require'    =>  '服务商负责人不能为空',
        'operation_situation.require' =>  '作业情况不能为空',
        'aid.require'                 =>  '录入人员不能为空',
    ];

}
