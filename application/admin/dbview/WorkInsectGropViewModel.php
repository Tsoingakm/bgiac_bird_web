<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/9/5
 * Time: 15:42
 */

namespace app\admin\dbview;


use think\Db;

class WorkInsectGropViewModel implements ViewModel
{
    public static function getView()
    {
        return Db::name('work_insect')
            ->field("working_date,time_period,spary_times,water_consumption,pharmacy_name1,dosage1,pharmacy_name2,dosage2,pharmacy_name3,dosage3,start_time,end_time,manager1,manager2,service_provider,avg_dosage1,avg_dosage2,avg_dosage3,is_compliance,sum(work_area) as area_all,avg_water, count(spary_times) as recored_count")
            // ->group("working_date,spary_times,water_consumption,pharmacy_name1,dosage1,pharmacy_name2,dosage2,pharmacy_name3,dosage3,start_time,end_time,manager1,manager2,service_provider,avg_dosage1,avg_dosage2,avg_dosage3,avg_water");
            ->group("working_date,spary_times");

    }
}
