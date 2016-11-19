<?php

namespace app\index\model;

use think\Model;

class UserCoupon extends Model{

	// 设置完整的数据表（包含前缀）
    protected $table = 'fuli_user_coupon';

     // 设置数据表（不含前缀）
    protected $name = 'user_coupon';


}


?>