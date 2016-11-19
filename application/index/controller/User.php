<?php

namespace app\index\controller;

use app\common\util\ApiUtil;
use app\index\model\Picture;
use app\index\model\User as UserModel;
use app\index\model\Member as MemberModel;
use app\index\model\UserCoupon;
use app\index\model\Userdata;
use jwt\JWT;
use think\Request;
use think\Validate;

class User
{

    public function index($token){
        $uid = session($token);

        if($uid){
            $userModel = new MemberModel();
            $where = array('fuli_member.uid'=>$uid,'fuli_picture.status'=>1);
            $user = $userModel
                        ->join('fuli_picture','fuli_member.avatar=fuli_picture.id')
                        ->where($where)
                        ->field('fuli_member.mobile,fuli_member.score,fuli_picture.url')->find();

            return ApiUtil::resultArray($user);

        }else{
            return ApiUtil::resultMessage('请登录后操作',false,10000);
        }
    }

    /* 登录页面 */
    /**
     * @param string $username
     * @param string $password
     * @param string $verify
     * @return array
     */
    public function login($username = '', $password = '', $verify = '')
    {
        if (Request::instance()->isPOst()) { // 登录验证
            /* 检测验证码 */
//             if(!check_verify($verify)){
//                $this->error('验证码输入错误！');
//             }

            $rule = [
                'username'  => 'require|max:16',
                'password'   => 'require|min:6',
            ];

            $msg = [
                'username.require' => '请输入登录名',
                'username.max'     => '登录名长度小于16个字符',
                'password.require'   => '请输入密码',
                'password.min'   => '密码长必须大于6个字符',
            ];

            $data = [
                'username'  => $username,
                'password'   => $password,
            ];

            $validate = new validate($rule, $msg);

            if(!$validate->check($data)){
                /** @var TYPE_NAME $validate */
                return ApiUtil::resultMessage($validate->getError(),false);
            }
            /* 调用UC登录接口登录 */
            $user = new UserModel();
            $uid = $user->login($username, $password, 3);

            if (0 < $uid) { // UC登录成功
                /* 登录用户 */
                $Member = new MemberModel();
                if ($user = $Member->login($uid)) { // 登录用户
                    return ApiUtil::resultArray($user);

                } else {
                    /** @var TYPE_NAME $Member */
                    return ApiUtil::resultMessage($Member->getError(),false);
                }
            } else { // 登录失败
                switch ($uid) {
                    case - 1:
                        $error = '用户不存在或被禁用！';
                        break; // 系统级别禁用
                    case - 2:
                        $error = '密码错误！';
                        break;
                    default:
                        $error = '未知错误！';
                        break; // 0-接口参数错误（调试阶段使用）
                }
                return ApiUtil::resultMessage($error,false);

            }
        }
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout($token = 0){
        session('user_auth', null);
        session($token, null);
    }

    /* 注册页面 */
    public function register(Request $request,$username = '', $password = '',  $verify = ''){

        if ($request->isPOst()) { // 注册用户
            /* 检测验证码 */
            // if(!check_verify($verify)){
            // $this->error('验证码输入错误！');
            // }


            $rule = [
                'username'  => 'require|max:16|regex:1[34578]\d{9}',
                'verify'  => 'require',
                'password'=>'require|min:6',

            ];

            $msg = [
                'username.require' => '请输入手机号',
                'username.regex' => '请输入正确的手机号',
                'verify.require' => '请输入短信验证码',
                'username.max'     => '登录名长度小于16个字符',
                'password.require'   => '请输入密码',
                'password.min'   => '密码长必须大于6个字符',
            ];

            $data = [
                'username'  => $username,
                'verify'  => $verify,
                'password'   => $password,
            ];

            $validate = new validate($rule, $msg);

            if(!$validate->check($data)){
                /** @var TYPE_NAME $validate */
                return ApiUtil::resultMessage($validate->getError(),false);
            }

            $code = session('verify_code');
            if (empty($code) || $code == '') {
                return ApiUtil::resultMessage('验证码已过期，请重新获取！',false);
            }
            if ($code['code'] != $verify ) {
//                session('verify_code', null);
                return ApiUtil::resultMessage('验证码不正确！',false);
            }

            $result = $this->IsHasMobile($username);
            if ($result) {
                return ApiUtil::resultMessage('该手机号码已经注册',false);
            }

            /* 调用注册接口注册用户 */
            $user = new UserModel();
            $uid = $user->registerByMobile($username, $password);

            if (0 < $uid) { // 注册成功

                // 直接登录
                $uid = $user->login($username, $password, 3);
                if (0 < $uid) { // UC登录成功
                    /* 登录用户 */
                    $Member = new MemberModel();
                    if ($user = $Member->login($uid)) { // 登录用户
                        return ApiUtil::resultArray($user);

                    } else {
                        /** @var TYPE_NAME $Member */
                        return ApiUtil::resultMessage($Member->getError(),false);
                    }
                } else { // 登录失败
                    return ApiUtil::resultMessage('注册成功',true);
                }
            } else { // 注册失败，显示错误信息
                return ApiUtil::resultMessage($this->showRegError($uid),false);
            }
//            session('verify_code', null);
        }
    }


    /* 我的优惠券 */
    public function myCoupon($status = 0,$token = 0,$page =1,$limit = 30)
    {
        $uid = session($token);
        if($uid){
            $where = array(
                "fuli_user_coupon.uid" => $uid
            );
            if ($status == 1) {
                $where['_string'] = 'fuli_document.deadline>=' . time() . ' OR fuli_document.deadline=0';
            } else if ($status == 3) {
                $where['_string'] = 'fuli_document.deadline<' . time() . ' and fuli_document.deadline<>0';
            }

            $where['fuli_document.status'] = 1;

            $model = new UserCoupon();
//            $totalCount = $model->join('fuli_document',' fuli_user_coupon.document_id=fuli_document.id','LEFT')
//                ->where($where)
//                ->count();

            $data = $model->join('fuli_document',' fuli_user_coupon.document_id=fuli_document.id','LEFT')
                ->join('left join fuli_picture ',' fuli_document.cover_id=fuli_picture.id','LEFT')
                ->join('left join fuli_user_group ',' fuli_document.user_group_id=fuli_user_group.id','LEFT')
                ->join('left join fuli_category ',' fuli_document.category_id=fuli_category.id','LEFT')
                ->where($where)
                ->field('fuli_document.id,fuli_document.title,fuli_document.use_limit,fuli_user_group.title as group_title,fuli_document.startline,fuli_document.deadline,fuli_picture.path,fuli_document.worth,fuli_category.cover_color')
                ->order('fuli_user_coupon.create_time desc')
                ->page($page,$limit)
                ->select();

            return ApiUtil::resultArray($data);
        }else{
            return ApiUtil::resultMessage('请登录后操作',false,10000);
        }


    }

    /* 我的收藏 */
    public function myCollection($token = 0,$page =1,$limit = 30)
    {

        $uid = session($token);
        if($uid){
            $where = array(
                "fuli_userdata.type" => 1,
                "fuli_userdata.uid" => $uid
            );

            $model = new Userdata();
//            $totalCount = $model->where($where)->count();
            $where['fuli_document.status'] = 1;
            $data = $model->join('fuli_document','fuli_userdata.target_id=fuli_document.id','LEFT')
                ->join('fuli_picture','fuli_document.cover_id=fuli_picture.id','LEFT')
                ->join('fuli_user_group ',' fuli_document.user_group_id=fuli_user_group.id','LEFT')
                ->join('fuli_category ','fuli_document.category_id=fuli_category.id','LEFT')
                ->where($where)
                ->field('fuli_document.id,fuli_document.title,fuli_document.use_limit,fuli_user_group.title as group_title,fuli_document.startline,fuli_document.deadline,fuli_picture.path,fuli_document.worth,fuli_category.cover_color')
                ->order('fuli_userdata.create_time desc')
                ->page($page,$limit)
                ->select();

            return ApiUtil::resultArray($data);
        }else{

            return ApiUtil::resultMessage('请登录后操作',false,10000);
        }
    }


    public function profile_avatar($base64 = '',$token = 0)
    {
        $uid = session($token);
        $member = new MemberModel();
        if ($uid) {

            $score_flag = false;


            $u = $member->where('uid=' . $uid)->find();
            if (! $u)
                return ApiUtil::resultArray(null,'该用户不存在',false);

            if ($u['avatar'] == 0)
                $score_flag = true;

            // $base64_image = str_replace(' ', '+', $base64);
            // // post的数据里面，加号会被替换为空格，需要重新替换回来，如果不是post的数据，则注释掉这一行
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)) {

                // 服务器文件存储路径
                $file = base64_decode(str_replace($result[1], '', $base64));

                $path = './Uploads';
                makeDir($path);
                $path = $path . '/Avatar';
                makeDir($path);
                $path = $path . '/' . date('Y-m-d', time());
                makeDir($path);
                $path = $path . '/' . uniqid() . '.jpg';

                /* 记录图片信息 */
                if (file_put_contents($path, $file)) {
                    $md5 = md5_file($base64);
                    $sha1 = sha1_file($base64);

                    $pic = new Picture();
                    $data = array(
                        'path' => substr($path,1),
                        'md5' => $md5,
                        'sha1' => $sha1,
                        'create_time' => time(),
                        'status' => 1
                    );
                    $picture = $pic->insert($data);

                    $member->where('uid=' . $uid)->update(array(
                        'avatar' => $picture['id']
                    ));
                    // 判断积分
                    if ($score_flag)
                        add_user_score($uid, 'edit_profile');

                    oss_upload($path);
                    return ApiUtil::resultMessage('修改成功',true);
                } else {
                    return ApiUtil::resultMessage('修改失败',false);
                }
            } else {
                return ApiUtil::resultMessage('图片格式不正确',true);
            }
        }else{
            return ApiUtil::resultMessage('请登录后操作',false,10000);
        }

    }

    public function sendVerifyMsg($mobile = '')
    {
        session('verify_code', array('mobile'=>$mobile,'code'=>'123123'));die;
        $result = $this->IsHasMobile($mobile);
        if ($result) {
            return ApiUtil::resultMessage('该手机号码已经注册',false);
        }

        verifymsg($mobile);
    }

    private function IsHasMobile($mobile)
    {
        if ($mobile == '') {
            return false;
        }
        $user = new UserModel();
        $model = $user->where(array('mobile' => $mobile))->find();
        return $model;
    }

    /**
     * 获取用户注册错误信息
     *
     * @param integer $code
     *            错误编码
     * @return string 错误信息
     */
    private function showRegError($code = 0)
    {
        switch ($code) {
            case - 1:
                $error = '用户名长度必须在16个字符以内！';
                break;
            case - 2:
                $error = '用户名被禁止注册！';
                break;
            case - 3:
                $error = '用户名被占用！';
                break;
            case - 4:
                $error = '密码长度必须在6-30个字符之间！';
                break;
            case - 5:
                $error = '邮箱格式不正确！';
                break;
            case - 6:
                $error = '邮箱长度必须在1-32个字符之间！';
                break;
            case - 7:
                $error = '邮箱被禁止注册！';
                break;
            case - 8:
                $error = '邮箱被占用！';
                break;
            case - 9:
                $error = '手机格式不正确！';
                break;
            case - 10:
                $error = '手机被禁止注册！';
                break;
            case - 11:
                $error = '手机号被占用！';
                break;
            default:
                $error = '未知错误';
        }
        return $error;
    }

}