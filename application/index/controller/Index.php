<?php
namespace app\index\controller;

use think\Request;
use app\index\model\Member as MemberModel;
use app\index\model\Category as CategoryModel;
use app\index\model\Document as DocumentModel;
use app\index\model\Carousel as CarouselModel;

use app\common\util\ApiUtil;

class Index extends Base
{

    /**
     * 轮播图片地址
     */
    public function getCarousel(){

        $carouselModel = new  CarouselModel();

        $data = $carouselModel->getCarouselList();

        return ApiUtil::resultArray($data);
    }

    /**
     * 获取栏目接口
     * @return [type] [description]
     */
    public function getCategory(){

        $cateoryModel = new CategoryModel();

        $data = $cateoryModel->getCategoryList();//栏目

        return ApiUtil::resultArray($data);
    }

    public function getNews(){

        $documentModel= new DocumentModel();

        $data = $documentModel->getNews();//优惠券列表

        return ApiUtil::resultArray($data);
    }

    /**
     * 获取优惠券列表
     * @return [type] [description]
     */
    public function getDocumet($page = 0,$limit = 10){

     	$documentModel= new DocumentModel();

        $data['document'] = $documentModel->getDocumetList($page,$limit);//优惠券列表
		
        $data['document_count'] =$documentModel->getDocumetList_count();

        return ApiUtil::resultArray($data);
    }

}
