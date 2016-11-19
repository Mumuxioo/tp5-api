<?php

namespace app\index\model;

use jwt\JWT;
use think\Model;
use app\index\model\User as UserModel;

class Member extends Model{

	// 设置完整的数据表（包含前缀）
    protected $table = 'fuli_member';

     // 设置数据表（不含前缀）
    protected $name = 'member';

    protected $auto = ['reg_ip', 'reg_time','last_login_ip','last_login_time'];
    protected $insert = ['status' => 1];
    protected function setRegIpAttr()
    {
        return get_client_ip(1);
    }

    protected function setRegTimeAttr()
    {
        return time();
    }

    protected function setLastLoginIpAttr(){
        return get_client_ip(1);
    }

    protected function setLastLoginTimeAttr(){
        return time();
    }


//    public function updates($data = null){
//
//        /* 获取数据对象 */
//        $data = $this->create($data);
//
//        if(empty($data)){
//            return false;
//        }
//        /* 添加或新增基础内容 */
//        if(empty($data['uid'])){ //新增数据
//            $id = $this->insert($data); //添加基础内容
//            if(!$id){
//                $this->error = '新增用户出错！';
//                return false;
//            }
//        } else { //更新数据
//            $status = $this->update($data); //更新基础内容
//            if(false === $status){
//                $this->error = '更新用户出错！';
//                return false;
//            }
//        }
//
//        //添加或更新完成
//        return $data;
//    }


    /**
     * 登录指定用户
     * @param  integer $uid 用户ID
     * @return boolean      ture-登录成功，false-登录失败
     */
    public function login($uid){
        /* 检测是否在当前应用注册 */
        $user = $this->find($uid);
        if(!$user){ //未注册
            /* 在当前应用中注册用户 */
            $Api = new UserModel();
            $info = $Api->info($uid);
            $user = $this->create(array('status' => 1,'score'=>0,'mobile'=>$info[3]));
//            $user['uid'] = $uid;
            $data['uid'] = $user->uid;
            //根据手机号获得归属地和运营商
            $ch = curl_init();
            $url = 'http://apis.juhe.cn/mobile/get';
            $params = array(
                'key' => '348e2455da5d6025062c38a80df27176', //您申请的手机号码归属地查询接口的appkey
                'phone' => $info[3] //要查询的手机号码
            );
            $paramsString = http_build_query($params);
            $url=$url.'?'.$paramsString;

            // 添加apikey到header
            //curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // 执行HTTP请求
            curl_setopt($ch, CURLOPT_URL, $url);
            $res = curl_exec($ch);

            $res = json_decode($res);

            if ($res->error_code == 0) {
                if (empty($res->result->province)) {
                    $data['mobile_province'] = $res->result->city;
                } else {
                    $data['mobile_province'] = $res->result->province;
                }

                $data['mobile_operator'] = $res->result->company;
                $this->where('uid=' . $uid)->update($data);
            }

            //初始化用户组

            $userGroupModel= new UserGroup();
            $userGroup=$userGroupModel->order('creditslower asc')->find();
            $data['group_id'] = $userGroup['id'];

            $data['score']=0;
            //联通用户对接，当注册手机号码为联通号码段时，自动设置用户等级为VIP3
            $numField=array('130','131','132','155','156','185','186','145','176');
            $tel_top=mb_substr($info[3],0,3);

            if($tel_top=="130"||$tel_top=="131"||$tel_top=="132"||$tel_top=="155"||$tel_top=="156"||$tel_top=="185"||$tel_top=="186"||$tel_top=="145"||$tel_top=="176"){
                $data['group_id']=4;
                $data['score']=1000;
            }


            if(!$this->update($data)){

                $this->error = '前台用户信息注册失败，请重试！';
                return false;
            }

        } elseif(1 != $user['status']) {
            $this->error = '用户未激活或已禁用！'; //应用级别禁用
            return false;
        }


        //记录行为
//        action_log('user_login', 'member', $uid, $uid);

        /* 登录用户 */
        $token = $this->autoLogin($user);
        $user['token'] = $token;
        return $user;
    }



    /**
     * 自动登录用户
     * @param  integer $user 用户信息数组
     */
    private function autoLogin($user){
        /* 更新登录信息 */
        $data = array(
            'uid'             => $user['uid'],
            'login'           => array('exp', '`login`+1'),
            'last_login_time' => time(),
            'last_login_ip'   => get_client_ip(1),
        );
        $this->update($data);

        /* 记录登录SESSION和COOKIES */
        $auth = array(
            'uid'             => $user['uid'],
            'username'        => $user['uid'],
            'last_login_time' => $user['last_login_time'],
        );

        $token = JWT::encode($user['uid'],config('JWT_KEY'));
        session($token,$user['uid']);
        session('user_auth', $auth);

        return $token;

    }
}


?>