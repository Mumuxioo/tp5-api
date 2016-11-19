<?php

namespace app\index\model;

use think\Model;

class Category extends Model{

	// 设置完整的数据表（包含前缀）
    protected $table = 'fuli_category';

     // 设置数据表（不含前缀）
    protected $name = 'category';

    public function getCategoryList(){

    	return $this->join('fuli_picture ',' fuli_category.icon=fuli_picture.id','LEFT')
        			->where('fuli_category.status=1')
        			->field('fuli_category.id,fuli_category.title,fuli_picture.path')->order(' sort desc,id desc')->select();

         
    }

}


?>