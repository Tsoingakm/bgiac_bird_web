<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/8/8
 * Time: 17:41
 */

class LogPageHelper
{
    public static function record($content,$level='info',$options=[]){
        $logData['level']=$level;
        $logData['content']=$content;

        $logData['url']=get_url();
        $logData['method']=$_SERVER['REQUEST_METHOD'];
        $logData['reference']=$_SERVER['HTTP_REFERER'];
        $request=\think\Request::instance();
        $logData['group_name']=$request->module();
        $logData['controller']=$request->controller();
        $logData['action']=$request->action();
        $logData['ip']=$request->ip(1);
        $logData['user_agent']=$_SERVER['HTTP_USER_AGENT'];
        $logData['addtime']=$logData['updatetime']=time();
        $logData['post']=json_encode($_POST);
        $logData['get']=json_encode($_GET);
        $logData['cookie']=json_encode($_COOKIE);
        foreach($options as $key=>$value){
            $logData[$key]=$value;
        }
        $res=db('log_page')->data($logData)->insert();

        return $res;
    }

}
