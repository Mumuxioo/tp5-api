<?php

namespace app\index\model;

use think\Model;

/**
 * Class Carousel
 * 轮播图片模型
 * @package app\index\model
 */
class Carousel extends Model{

	// 设置完整的数据表（包含前缀）
    protected $table = 'fuli_carousel';

     // 设置数据表（不含前缀）
    protected $name = 'carousel';


    public function getCarouselList(){

        return $this->join('fuli_picture','fuli_carousel.pid = fuli_picture.id','LEFT')
                    ->field(' fuli_carousel.id,fuli_carousel.url,fuli_picture.url as path,fuli_carousel.create_time,fuli_carousel.status')
                    ->where('fuli_carousel.status=1')
                    ->order('fuli_carousel.id desc')
                    ->select();
    }
}


?>