<?php

namespace app\admin\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        //$url="http://219.137.228.26:30080/";
        //$this->redirect($url);

        $url = 'http://ctyun.ycxxkj.com:38081/birdSupervise/web/public/admin.php';
        $curUrl=get_url();
        if(strpos($curUrl,'ctyun.ycxxkj.com') !==false &&strpos($curUrl,'ctyun.ycxxkj.com:38081') ===false ){
            //如果是测试环境且端口不是38081
            $this->redirect($url);
        }

        $this->checkAdmin();
        $configM=new \app\admin\model\Config();
        $web=$configM->getSettingModel('web');
        $this->assign('web',$web);

        return view();
    }

    /**
     * 处理登录
     */
    public function doLogin(){
        $login_name=input('login_name');
        $pwd=input('pwd');
        if(empty($login_name) || empty($pwd)){
            $this->error('用户名或密码不能为空！');
        }
        $adminDB=db('admin');
        $admin=$adminDB->where(['login_name'=>$login_name,'pwd'=>md5($pwd)])->find();
        if(!$admin){
            $this->error('用户名或密码不正确！');
        }
        $logOption=['aid'=>$admin['aid'],'admin_name'=>$admin['login_name']];
        session('aid',$admin['aid']);
        \LogPageHelper::record(''.$admin['login_name'] .' 登录！','info',$logOption);
        //更新登录信息
        $updateAdmin['last_login_time']=time();
        $updateAdmin['login_count']=$admin['login_count']+1;
        $updateAdmin['aid']=$admin['aid'];
        $adminDB->data($updateAdmin)->update();
//        var_dump(url('Main/index'));exit;
        $this->success('登录成功！',url('Main/index'));

    }




    /**
     * 判断是否登录
     */
    public function checkAdmin(){
        $aid=0;
        if(session('?aid')){
            $aid=session('aid');
            $this->redirect(url('Main/index'));

            exit;
            //cookie('aid',$aid,86400*30);
        }else{
            if(cookie('aid')){
                $aid=cookie('aid');
                $this->redirect(url('Main/index'));
                session('aid',$aid);
                exit;
            }

        }

    }

}
