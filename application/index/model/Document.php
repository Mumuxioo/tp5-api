<?php

namespace app\index\model;

use think\Model;

class Document extends Model{

	// 设置完整的数据表（包含前缀）
    protected $table = 'fuli_document';

     // 设置数据表（不含前缀）
    protected $name = 'document';

    public function getDocumetList($page,$limit,$cid,$sortType){

    	$where['fuli_document.status']='1';

        $where['fuli_document.display']='1';

        if($cid > 0){
            $where['fuli_document.category_id']=$cid;
        }

        if($sortType){
            $where['fuli_document.hot_sort_type']=$sortType;
        }

        return $this
                ->join('fuli_picture dp',' fuli_document.cover_id=dp.id','LEFT')
				->join('fuli_merchant','fuli_document.merchant_id=fuli_merchant.id','LEFT')
				->join('fuli_user_group ',' fuli_document.user_group_id=fuli_user_group.id','LEFT')
				->join('fuli_category ',' fuli_document.category_id=fuli_category.id','LEFT')
				->join('fuli_picture p',' fuli_merchant.cover_id = p.id','LEFT')
				->where($where)
				->field('fuli_document.id,fuli_document.title,fuli_document.use_limit,fuli_document.startline,fuli_document.deadline,dp.url as path,fuli_document.worth,fuli_document.code_count,fuli_merchant.lat,fuli_document.link_str,fuli_user_group.title as group_title,
					fuli_document.view,fuli_merchant.title as merchantName,p.url as merchantPath,
					fuli_category.cover_color')
				->order('fuli_document.view desc,fuli_document.create_time desc,fuli_document.id desc')
				->page($page,$limit)
				->select();

    }




    public function getDocumetList_count($cid = 0){

		$where['fuli_document.status']='1';

        $where['fuli_document.display']='1';

        if($cid > 0){
            $where['fuli_document.category_id']=$cid;
        }

        return $this->join('fuli_picture ',' fuli_document.cover_id=fuli_picture.id')
				->join('fuli_merchant','fuli_document.merchant_id=fuli_merchant.id','LEFT')
				->join('fuli_user_group ',' fuli_document.user_group_id=fuli_user_group.id','LEFT')
				->join('fuli_category ',' fuli_document.category_id=fuli_category.id','LEFT')
				->where($where)
				->count();
    }

    public function getNews(){
        $where['fuli_document.status']='1';

        $where['fuli_document.display']='1';

        $where['fuli_document.type_value']='1';

        return $this->where($where)
            ->field('fuli_document.id,fuli_document.title')
            ->order('fuli_document.id desc')
            ->limit(10)
            ->select();
    }

    public function getDetail($id){

        $where['fuli_document.status']='1';

        $where['fuli_document.display']='1';

        $where['fuli_document.id'] = $id;

        $uid = 0;

        return $this->join('fuli_picture coverPicture',' fuli_document.cover_id=coverPicture.id')
            ->join('fuli_merchant','fuli_document.merchant_id=fuli_merchant.id','LEFT')
            ->join('fuli_user_group ',' fuli_document.user_group_id=fuli_user_group.id','LEFT')
            ->join('fuli_category ',' fuli_document.category_id=fuli_category.id','LEFT')
            ->join('fuli_picture topPicture',' fuli_document.detail_id=topPicture.id','LEFT')
            ->join('fuli_userdata', 'fuli_document.id = fuli_userdata.target_id and fuli_userdata.uid = 0 and fuli_userdata.type=1','LEFT')
            ->join('fuli_user_coupon coupon', ' fuli_document.id = coupon.document_id and coupon.uid = 0','LEFT')
            ->where($where)
            ->field('fuli_document.id,fuli_document.title,fuli_document.use_limit,fuli_document.startline,fuli_document.deadline,coverPicture.url as coverPicture,fuli_document.worth,fuli_document.code_count,fuli_merchant.lat,fuli_document.link_str,fuli_user_group.title as group_title,
					fuli_document.view,fuli_category.cover_color,topPicture.url as topPath,fuli_userdata.uid as bookmark,coupon.id as receive')
            ->find();
    }

}


?>