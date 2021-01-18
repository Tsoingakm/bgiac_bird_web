<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');

define('BIND_MODULE','api');
define('APP_DEBUG', true);

header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Origin:*");//注意修改这里填写你的前端的域名
header("Access-Control-Max-Age:3600");
header("Access-Control-Allow-Headers:*");
//        header("Access-Control-Allow-Headers:DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Authorization,SessionToken,X-Token");

header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS, POIST');

//定义运行时目录
define('RUNTIME_PATH',__DIR__.'/../runtime/api/');

// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
