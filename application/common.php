<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

error_reporting(E_ERROR | E_WARNING | E_PARSE);
/**
 * 判断是否SSL协议
 * @return boolean
 */
function is_ssl() {
    if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){
        return true;
    }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
        return true;
    }
    return false;
}
/**
 * 获取当前完整URL
 *
 * @return string
 */
function get_url() {
    $sys_protocal = isset ( $_SERVER ['SERVER_PORT'] ) && $_SERVER ['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER ['PHP_SELF'] ? $_SERVER ['PHP_SELF'] : $_SERVER ['SCRIPT_NAME'];
    $path_info = isset ( $_SERVER ['PATH_INFO'] ) ? $_SERVER ['PATH_INFO'] : '';
    $relate_url = isset ( $_SERVER ['REQUEST_URI'] ) ? $_SERVER ['REQUEST_URI'] : $php_self . (isset ( $_SERVER ['QUERY_STRING'] ) ? '?' . $_SERVER ['QUERY_STRING'] : $path_info);
    return $sys_protocal . (isset ( $_SERVER ['HTTP_HOST'] ) ? $_SERVER ['HTTP_HOST'] : '') . $relate_url;
}
/**
 * 获取网站域名
 */
function get_domain(){
    $sys_protocal = isset ($_SERVER ['SERVER_PORT']) && $_SERVER ['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    return $sys_protocal . (isset ($_SERVER ['HTTP_HOST']) ? $_SERVER ['HTTP_HOST'] : '') ;
}

function getVirDir(){
    $virdir=str_replace($_SERVER['DOCUMENT_ROOT'],'',ROOT_PATH);
    $virdir=str_replace('\\','/',$virdir);
    return $virdir;
}



/**
 * 显示键值对的值
 * @param $value
 * @param $dic  默认dic是按key=>value形式组合的数组
 * @param string $key
 * @param string $val
 * @return string
 */
function show_dic_value($value, $dic, $key = 'key',$val = 'value')
{
    $str = '';
    foreach ($dic as $k => $v) {
        if ($value == $v [$val]) {
            $str = $v [$key];
            break;
        }
    }
    return $str;
}

/**
 * 获取客户端IP
 * @return array|false|string
 */
function get_client_ip(){
    if(isset($_SERVER)){
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        }else{
            $realip = $_SERVER['REMOTE_ADDR'];
        }
    }else{
        //不允许就使用getenv获取
        if(getenv("HTTP_X_FORWARDED_FOR")){
            $realip = getenv( "HTTP_X_FORWARDED_FOR");
        }elseif(getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        }else{
            $realip = getenv("REMOTE_ADDR");
        }
    }

    return $realip;
}

function html_print_r($value){
    echo '<!--';
    print_r($value);
    echo '-->';
}

/**
 * 输出图片。根据需求自动裁剪图片
 *
 * @param unknown $file
 * @param number $width
 * @param number $height
 * @param string $def
 * @return unknown|mixed 使用例子：< img src="{:img('/wyzxqy/Tools/Upload/../../Upload/image/20150529/20150529103500_39141.jpg',90,90)}" />
 */
function img($file, $width = 200, $height = 200, $def = '') {
    $root = dirname ( $_SERVER ['DOCUMENT_ROOT'] . "/aa" ) . "/";
    if (preg_match ( '/^http:\/\//', $file ) || preg_match ( '/^https:\/\//', $file )) {
        // 如果是远程文件
        $virFloder = substr ( $_SERVER ['SCRIPT_NAME'], 0, strrpos ( $_SERVER ['SCRIPT_NAME'], '/' ) );
        $basePath = $virFloder . '/Resource/img/temp/';
        if (! is_readable ( $root . $basePath )) { // 判断文件夹是否存在，不存在就创建
            is_file ( $root . $basePath ) or mkdir ( $root . $basePath, 0777, true );
            // var_dump(mkdir ( $root.$basePath, 0777 ));
        }
        // 下载远程图片
        $disFile = $file;
        $fileName = md5 ( $file ) . '.jpg';
        $saveFile = $root . $basePath . $fileName;
        $file = $basePath . $fileName;
        // 判断远程文件是否存在
        if (! file_exists ( $root . $file )) { // 判断原文件是否存在,不存在直接返回。
            $content = file_get_contents ( $disFile );
            file_put_contents ( $saveFile, $content );
        }
    }
    if (empty ( $file )) {
        return $def;
    }
    // var_dump( $_SERVER['DOCUMENT_ROOT']);
    $realFile = $root . $file;
    // var_dump($root);

    // 获得文件扩展名
    $temp_arr = explode ( ".", $file );
    $file_ext = array_pop ( $temp_arr );
    $file_ext = trim ( $file_ext );
    $file_ext = strtolower ( $file_ext );

    $baseFile = basename ( $file ); // 找到文件名
    $basePath = str_replace ( $baseFile, "", $file ) . "temp/"; // 找到目录
    $baseFile = str_replace ( ".", "", $baseFile ); // 替换掉“.”
    $baseFile .= $width . "x" . $height . ".jpg";
    // $basePath = str_replace ( C ( 'VIR_DIR' ), ".", $basePath ); // 替换掉虚拟目录

    if (! is_readable ( $root . $basePath )) { // 判断文件夹是否存在，不存在就创建
        is_file ( $root . $basePath ) or mkdir ( $root . $basePath, 0777 );
    }
    $baseFile = $basePath . $baseFile;
    // trace ( $baseFile, "basefiel" );
    // $file = str_replace ( C ( 'VIR_DIR' ), ".", $file ); // 替换掉虚拟目录
    // $file=$baseFile.

    // var_dump($file);

    if (! file_exists ( $root . $file )) { // 判断原文件是否存在,不存在直接返回。
        if (empty ( $def )) { // 如果没有默认图片
            return $file;
        } else {
            // trace($def,"def");
            return $def;
        }
    }

    // 后缀名
    if ($file_ext == "gif") {
        return $file;
    }

    if (! file_exists ( $root . $baseFile )) { // 判断文件是否存在
//        $image = new \Think\Image ();
//        $image->open ( $root . $file ); // 生成一个缩放后填充大小的缩略图并保存
//
//        $image->thumb ( $width, $height, \Think\Image::IMAGE_THUMB_CENTER )->save ( $root . $baseFile ); // 生成缩略图
        $image = \think\Image::open($root . $file);
        $image->thumb(200,200,\think\Image::THUMB_CENTER)->save( $root . $baseFile);


    }
    $str2 = substr ( $baseFile, 0, 2 ); // 取前两个字符串

    // if ($str2 == "./") {
    // $baseFile = C ( 'VIR_DIR' ) . substr ( $baseFile, 1 ); // 取前两个字符串
    // }
    return $baseFile;
}
