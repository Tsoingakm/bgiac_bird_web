<?php
/**
 * Created by PhpStorm.
 * User: yckj_lzj
 * Date: 2018/8/11
 * Time: 0:54
 */

namespace app\common\model;


class ConfigModel
{
    private $configM;
    public function __construct() {
        $this->configM=db('config');
    }
    // ===========私有的方法======================//

    /**
     * 获取配置信息
     *
     * @param unknown $code
     */
    Public function getSettingModel($code) {
        $where ['code'] = $code; // 配置代码
        $configM = $this->configM;
        $cfgModel = $configM->where ( $where )->find ();
        if (! $cfgModel) {
            $data ['code'] = $code;
            $data ['content'] = serialize ( $data );
            $data ['addtime'] = time ();
            $data ['updatetime'] = time ();
            $configM->data ( $data )->insert(); // 如果没有记录就添加
            $cfgModel = $configM->where ( $where )->find ();
        }
        $model = $this->decodeConfig ( $cfgModel );
        return $model ['content'];
    }
    // 解码content字段
    private function decodeConfig($config) {
        $config ['content_str'] = $config ['content'];
        $config ['content'] = unserialize ( $config ['content_str'] );
        return $config;
    }
}