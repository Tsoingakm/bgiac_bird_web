<?php

namespace app\admin\controller;



class QueryBase extends Base{

    public function __construct(){
      parent::__construct();
      $this->addBread('数据查询');
    }


}
