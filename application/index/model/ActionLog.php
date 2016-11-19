<?php

namespace app\index\model;

use think\Model;


class ActionLog extends Model{

	// 设置完整的数据表（包含前缀）
    protected $table = 'fuli_action_log';

     // 设置数据表（不含前缀）
    protected $name = 'action_log';


}


?>