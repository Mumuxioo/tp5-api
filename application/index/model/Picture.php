<?php

namespace app\index\model;

use think\Model;


class Picture extends Model{

	// 设置完整的数据表（包含前缀）
    protected $table = 'fuli_picture';

     // 设置数据表（不含前缀）
    protected $name = 'picture';


}


?>