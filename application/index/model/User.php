<?php

namespace app\index\model;

use think\Model;
class User extends Model
{
    // 设置完整的数据表（包含前缀）
    protected $table = 'fuli_ucenter_member';

    // 设置数据表（不含前缀）
    protected $name = 'ucenter_member';

    protected $auto = ['password', 'reg_ip', 'reg_time'];
    protected $insert = ['status' => 1];
    protected $update = ['update_time'];

    protected function setRegIpAttr()
    {
        return get_client_ip(1);
    }

    protected function setRegTimeAttr()
    {
        return time();
    }

    protected function setPasswordAttr($value)
    {
        return think_ucenter_md5($value,config('UC_AUTH_KEY'));
    }

    protected function setUpdateTimeAttr(){
        return time();
    }


    /**
     * 更新用户登录信息
     * @param  integer $uid 用户ID
     */
    protected function updateLogin($uid){
        $data = array(
//            'id'              => $uid,
            'last_login_time' => time(),
            'last_login_ip'   => get_client_ip(1),
        );
        $this->where('id='.$uid)->update($data);
    }

    /**
     * 用户登录认证
     * @param  string  $username 用户名
     * @param  string  $password 用户密码
     * @param  integer $type     用户名类型 （1-用户名，2-邮箱，3-手机，4-UID）
     * @return integer           登录成功-用户ID，登录失败-错误编号
     */
    public function login($username, $password, $type = 1){
        $map = array();
        switch ($type) {
            case 1:
                $map['username'] = $username;
                break;
            case 2:
                $map['email'] = $username;
                break;
            case 3:
                $map['mobile'] = $username;
                break;
            case 4:
                $map['id'] = $username;
                break;
            default:
                return 0; //参数错误
        }

        /* 获取用户数据 */
        $user = $this->where($map)->find();

//        $a = think_ucenter_md5($password, config('UC_AUTH_KEY'));
//        dump($a);die;
        if($user && $user['status']){
            /* 验证用户密码 */
            if(think_ucenter_md5($password, config('UC_AUTH_KEY')) === $user['password']){
                $this->updateLogin($user['id']); //更新用户登录信息
                return $user['id']; //登录成功，返回用户ID
            } else {
                return -2; //密码错误
            }
        } else {
            return -1; //用户不存在或被禁用
        }
    }

    public function registerByMobile($mobile, $password){
        $data = array(
            'username' => $mobile,
            'password' => $password,
            'mobile'   => $mobile,
        );

        if($user = $this->create($data)){
//            $uid = $this->insertGetId($data);
            return $user ? $user->id : 0; //0-未知错误，大于0-注册成功
        } else {
            return $this->getError(); //错误详情见自动验证注释
        }
    }


    /**
     * 获取用户信息
     * @param  string  $uid         用户ID或用户名
     * @param  boolean $is_username 是否使用用户名查询
     * @return array                用户信息
     */
    public function info($uid, $is_username = false){
        $map = array();
        if($is_username){ //通过用户名获取
            $map['username'] = $uid;
        } else {
            $map['id'] = $uid;
        }

        $user = $this->where($map)->field('id,username,email,mobile,status')->find();
        if($user && $user['status'] = 1){
            return array($user['id'], $user['username'], $user['email'], $user['mobile']);
        } else {
            return -1; //用户不存在或被禁用
        }
    }
}