<?php


namespace app\api\controller;


class Upload extends Common
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
                $file=str_replace("\\","/",$file);
                $image = \think\Image::open($root . $file);
                $image->thumb(750,750,\think\Image::THUMB_SCALING)->save( $root . $file);
                $filename=$info->getInfo("name");
                $filename=str_replace(strrchr($filename, "."),"",$filename);
                // 成功上传后 获取上传信息
                // 输出 jpg
                $data=[
                    'url'=>get_domain(). $file,
                    'size'=>$info->getSize(),
                    'filename'=>$filename
                ];
                $this->return_msg( true, "上传成功",$data);
            }else{
                // 上传失败获取错误信息
                $this->return_msg( false, "上传失败");

            }
        }
        else{
            $this->return_msg( false, "未选择文件");

        }
    }
}