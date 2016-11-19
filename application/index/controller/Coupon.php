<?php

namespace app\index\controller;

use app\index\model\Document as DocumentModel;
use app\common\util\ApiUtil;
use app\index\model\Userdata as UserDataModel;
use app\index\model\UserCoupon as UserCouponModel;
use app\index\model\CouponCode as CouponCodeModel;
use app\index\model\UserGroup as UserGroupModel;
use app\index\model\Member as MemberModel;
use think\Request;

class Coupon extends Base
{

    //分类列表
    public function getCouponByCid($page = 0,$limit = 10,$cid = 0){
        $documentModel= new DocumentModel();

        $data['document'] = $documentModel->getDocumetList($page,$limit,$cid,0);//优惠券列表

        $data['document_count'] =$documentModel->getDocumetList_count($cid);

        return ApiUtil::resultArray($data);
    }

    /**
     * @param int $page
     * @param int $limit
     * @param int $cid
     * @param $sortType  $hot=0,$recommend=0,$rare=0
     * @return array
     */
    public function getHotCoupon($page = 0,$limit = 10,$cid = 0,$sortType = 1){

        $documentModel= new DocumentModel();

        $data['document'] = $documentModel->getDocumetList($page,$limit,$cid,$sortType);//优惠券列表

        $data['document_count'] =$documentModel->getDocumetList_count($cid);

        return ApiUtil::resultArray($data);
    }


    public function detail($id = 0){

        if($id <= 0){
            ApiUtil::resultArray("","id不能为空",false);
        }

        $documentModel= new DocumentModel();

        $data = $documentModel->getDetail($id);

        return ApiUtil::resultArray($data);
    }

    //收藏
    public function collection($token = 0,$did = 0){

        if (Request::instance()->isPost()){
            $docModel = new DocumentModel();

            if(!$did){
                return ApiUtil::resultArray(null,'优惠券id为空',false);
            }else{
                $num = $docModel->where('id=:id and status=1')->bind(['id'=>$did])->count();
                if($num == 0){
                    return ApiUtil::resultArray(null,'优惠券不存在',false);
                }
            }
            $uid = session($token);
            if($uid){
                //获得用户信息
                $userdata= new UserDataModel();
                if ($userdata->where(array('uid'=>$uid,'target_id'=>$did,'type'=>1))->find())
                {
                    return ApiUtil::resultArray(null,'您已经收藏过了',false);
                }

                $data=array('target_id'=>$did,'uid'=>$uid,'create_time'=>time(),'type'=>1);

                $udid=$userdata->insert($data);

                if ($udid)
                {

                    $docModel->where(array('id'=>$did))->setInc('bookmark');

                    return ApiUtil::resultArray(null,'收藏成功！');
                }
                else{
                    return ApiUtil::resultArray(null,'收藏失败！',false);
                }

            }
            else{
                return ApiUtil::resultArray(null,'还没有登录？登录后再来收藏！',false);
            }
        }

    }


    /**
     * @param int $token
     * @param int $did
     * @return array
     */
    public function receive($token = 0, $did = 0)
    {
        if (Request::instance()->isPost()){

            $docModel = new DocumentModel();

            if(!$did){
                return ApiUtil::resultArray(null,'优惠券id为空',false);
            }else{
                $doc = $docModel->where('id=:id and status=1')->bind(['id'=>$did])->find();
                if(!$doc){
                    return ApiUtil::resultArray(null,'优惠券不存在',false);
                }

                if ($doc["code_count"]<=0){
                    return ApiUtil::resultArray(null,'sorry,优惠券都被领光了',false);
                }

                $currentTime=time();
                if($doc['startline']!=0&&$doc['startline']>$currentTime)
                {
                    return ApiUtil::resultArray(null,'sorry,优惠券还没开放领取',false);
                }
                if($doc['deadline']!=0&&$doc['deadline']<$currentTime)
                {
                    return ApiUtil::resultArray(null,'sorry,优惠券已过期，不能领取',false);
                }
            }
            $uid = session($token);

            if($uid){

                //获得用户信息
                $userCoupon= new UserCouponModel();
                if ($userCoupon->where(array('uid'=>$uid,'document_id'=>$did))->find())
                {
                    return ApiUtil::resultArray(null,'您已经领取过了',false);
                }

                //获得优惠券的限制用户组。
                $UserGroup = new UserGroupModel();

                $couponUserGroup = $UserGroup->join('fuli_document','fuli_document.user_group_id=fuli_user_group.id','LEFT')
                        ->field('fuli_user_group.*')
                        ->where('fuli_document.status=1 and fuli_document.id='.$did)
                        ->find();

                if (!$couponUserGroup)
                {
                    return ApiUtil::resultArray(null,'领取失败',false);
                }


                //获得用户信息
                $member = new MemberModel();
                $user = $member->where('fuli_member.status=1 and fuli_member.uid='.$uid)->find();

                if (!$user)
                {
                    return ApiUtil::resultArray(null,'领取失败',false);
                }

                if ($couponUserGroup['creditslower']>$user['score'])
                {
                    $msg ="想一步到位成为".$couponUserGroup['title']."用户任意领取福利吗？点击首页顶部“WO圈有”办理联通微信沃派卡，所有福利一网打尽！";
                    return ApiUtil::resultArray(null,$msg,false);
                }

                //验证领取策略
                //获取在时间段领取数量
                $rulearr=analy_coupon_rule($doc['get_cycle_count']);
                $userCoupon = new UserCouponModel();

                if($rulearr){
                    //获取在时间段领取数量
                    $map['create_time'] = array(array('gt',$rulearr['starttime']),array('lt',$rulearr['endtime'])) ;
                    $getcount=$userCoupon->where($map)->count();
                    if($getcount>=$rulearr['count']){
                        return ApiUtil::resultArray(null,'已领完',false);
                    }
                }

                $couponCodeModel=new CouponCodeModel();
                $couponCode=$couponCodeModel->where(array('status'=>0,'document_id'=>$did))->find();
                if($couponCode){
                    $couponCodeModel->where('id='.$couponCode['id'])->update(array('status'=>'1'));
                    $data=array('document_id'=>$did,'uid'=>$uid,'create_time'=>time(),'coupon_id'=>$couponCode['id']);
                }
                else{
                    $data=array('document_id'=>$did,'uid'=>$uid,'create_time'=>time(),'coupon_id'=>-1);
                }

                $ucid=$userCoupon->insertGetId($data);

                if ($ucid)
                {
                    $docModel->where(array('id'=>$did))->setInc('get_count');
                    $docModel->where(array('id'=>$did))->setDec('code_count');

                    //增加用户积分
                    add_user_score($uid, 'get_fuli');
                    if(config('sendMessage')){
                        //发送验证码
                        //couponmsg($user['mobile'],$doc['title'],$doc['link_str']);
                        couponmsg('15201255173',$doc['title'],$doc['link_str']);
                    }
                    return ApiUtil::resultArray(null,'领取成功！马上使用您手中的优惠券吧！',true);
                }
                else{
                    return ApiUtil::resultArray(null,'领取失败',false);
                }

            }
            else{
                return ApiUtil::resultArray(null,'还没有登录？登录后再来领取！',false);
            }
        }

    }


}