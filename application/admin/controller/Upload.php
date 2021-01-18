<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Upload extends Base
{
   public function img(){
       // 获取表单上传文件 例如上传了001.jpg
       $file = request()->file('file');
       // 移动到框架应用根目录/public/uploads/ 目录下
       if($file){
           $info = $file->move(ROOT_PATH .'public' . DS . 'upload'.DS.'image');

           if($info){
               $root = dirname ( $_SERVER ['DOCUMENT_ROOT'] . "/aa" ) . "/";
               $file=dirname($_SERVER['SCRIPT_NAME'] )."/upload/image/".$info->getSaveName();

               $image = \think\Image::open($root . $file);
               $image->thumb(750,750,\think\Image::THUMB_SCALING)->save( $root . $file);
               $filename=$info->getInfo("name");
               $filename=str_replace(strrchr($filename, "."),"",$filename);
               // 成功上传后 获取上传信息
               // 输出 jpg
               $resArr=[
                   'code'=>1,
                   'msg'=>'上传成功',
                   'url'=>dirname($_SERVER['SCRIPT_NAME'] )."/upload/image/".$info->getSaveName(),
                   'size'=>$info->getSize(),
                   'filename'=>$filename
               ];
               return json($resArr);


           }else{
               // 上传失败获取错误信息
               $this->error($file->getError());

           }
       }
       else{
           $this->error('未选择文件');
       }
   }

    /**
     * 上传KML文件
     * @return \think\response\Json
     */
   public function kml(){
       $file = request()->file('file');
       //print_r($file);
       // 移动到框架应用根目录/public/uploads/ 目录下
       if($file){
           $info = $file->move(ROOT_PATH .'public' . DS . 'upload'.DS.'kml');
           //print_r($info);
           if($info){
               $root = dirname ( $_SERVER ['DOCUMENT_ROOT'] . "/aa" ) . "/";
               $file=dirname($_SERVER['SCRIPT_NAME'] )."/upload/kml/".$info->getSaveName();

               //$image = \think\Image::open($root . $file);
              // $image->thumb(750,750,\think\Image::THUMB_SCALING)->save( $root . $file);
               $filename=$info->getInfo("name");
               $filename=str_replace(strrchr($filename, "."),"",$filename);
               // 成功上传后 获取上传信息
               // 输出 jpg
               $resArr=[
                   'code'=>1,
                   'msg'=>'上传成功',
                   'url'=>dirname($_SERVER['SCRIPT_NAME'] )."/upload/kml/".$info->getSaveName(),
                   'size'=>$info->getSize(),
                   'filename'=>$filename
               ];
               return json($resArr);

           }else{
               // 上传失败获取错误信息
               $this->error($file->getError());

           }
       }
       else{
           $this->error('未选择文件');
       }
   }

    /**
     * 上传文件
     * @return \think\response\Json
     */
    public function file(){
        $file = request()->file("file");
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH .'public' . DS . 'upload'.DS.'files');

            if($info){
                $root = dirname ( $_SERVER ['DOCUMENT_ROOT'] . "/aa" ) . "/";
                $file=dirname($_SERVER['SCRIPT_NAME'] )."/upload/files/".$info->getSaveName();

                $filename=$info->getInfo("name");
                $ext=$info->getExtension();
                $filename=str_replace(strrchr($filename, "."),"",$filename);
                $url=dirname($_SERVER['SCRIPT_NAME'] )."/upload/files/".$info->getSaveName();
                $url=str_replace("\\","/",$url);
                // 成功上传后 获取上传信息
                $resArr=[
                    'code'=>1,
                    'msg'=>'上传成功',
                    'url'=>$url,
                    'size'=>$info->getSize(),
                    'filename'=>$filename,
                    'ext'=>$ext
                ];
                return json($resArr);

            }else{
                // 上传失败获取错误信息
                $this->error($file->getError());

            }
        }
        else{
            $this->error('未选择文件');
        }
    }
}
