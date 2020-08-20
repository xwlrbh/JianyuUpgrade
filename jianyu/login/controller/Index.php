<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://jianyuluntan.com All rights reserved.
 */
namespace app\login\controller;
use catfishcms\Catfish;
class Index extends CatfishCMS
{
    public function index()
    {
        $this->checkUser();
        ob_clean();
        if(Catfish::getPost('user') !== false)
        {
            $data = $this->chklogin($this->captcha);
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $user = Catfish::db('users')->where('yonghu',$data['user'])->field('id,password,touxiang,lastlogin,randomcode,status,utype,mtype,dengji,jifen,chengzhang')->find();
                if(empty($user))
                {
                    echo Catfish::lang('Username error');
                    exit();
                }
                if($user['password'] != md5($data['pwd'].$user['randomcode']))
                {
                    echo Catfish::lang('Password error');
                    exit();
                }
                if($user['status'] == 0)
                {
                    echo Catfish::lang('Account has been disabled, please contact the administrator');
                    exit();
                }
                elseif($user['status'] == 2){
                    Catfish::setSession('resend',$data['user']);
                    echo Catfish::lang('Your account has not been activated. Please log in to your email to activate your account.').'<br>'.Catfish::lang('If you have not received your activation email, please click the link below to resend the activation email').'<br><small><a id="resend" href="'.Catfish::url('login/Index/resend').'">'.Catfish::lang('Resend activation email').'</a></small>';
                    exit();
                }
                $this->logined($user, $data);
                echo 'ok';
                exit();
            }
        }
        if(Catfish::hasSession('user_id')){
            $user_type = Catfish::getSession('user_type');
            if($user_type < 6){
                Catfish::redirect('admin/Index/index');
                exit();
            }
            else{
                Catfish::redirect('user/Index/index');
                exit();
            }
        }
        if(Catfish::hasGet('jumpto')){
            Catfish::allot('jumpto', Catfish::getGet('jumpto'));
        }
        else{
            Catfish::allot('jumpto', '');
        }
        Catfish::allot('shouji', $this->mobile());
        $view = Catfish::output();
        return $view;
    }
    public function denglu()
    {
        if(Catfish::getPost('user') !== false)
        {
            $captcha = Catfish::get('captcha');
            if(Catfish::getPost('captcha') !== false || $captcha == 1){
                $captcha = 1;
            }
            $data = $this->chklogin($captcha);
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $user = Catfish::db('users')->where('yonghu',$data['user'])->field('id,password,touxiang,lastlogin,randomcode,status,utype,mtype,dengji,jifen,chengzhang')->find();
                if(empty($user))
                {
                    echo Catfish::lang('Username error');
                    exit();
                }
                if($user['password'] != md5($data['pwd'].$user['randomcode']))
                {
                    echo Catfish::lang('Password error');
                    exit();
                }
                if($user['status'] == 0)
                {
                    echo Catfish::lang('Account has been disabled, please contact the administrator');
                    exit();
                }
                $this->logined($user, $data);
                echo 'ok';
                exit();
            }
        }
    }
    public function ljump()
    {
        if(Catfish::hasSession('user_id')){
            $user_type = Catfish::getSession('user_type');
            if($user_type < 6){
                Catfish::redirect('admin/Index/index');
                exit();
            }
            else{
                Catfish::redirect('user/Index/index');
                exit();
            }
        }
        else{
            Catfish::toError();
        }
    }
    public function repsd()
    {
        $this->checkUser();
        ob_clean();
        if(Catfish::getPost('user') !== false)
        {
            $rule = [
                'user' => 'require|alphaDash',
                'email' => 'require|email',
                'captcha|'.Catfish::lang('Captcha')=>'require|captcha'
            ];
            $msg = [
                'user.require' => Catfish::lang('The user name must be filled in'),
                'user.alphaDash' => Catfish::lang('Username can only consist of letters and numbers, underscores _ and dashes -'),
                'email.require' => Catfish::lang('E-mail address is required'),
                'email.email' => Catfish::lang('The e-mail format is incorrect')
            ];
            $data = [
                'user' => Catfish::getPost('user'),
                'email' => Catfish::getPost('email'),
                'captcha' => Catfish::getPost('captcha')
            ];
            $validate = Catfish::validate($rule, $msg, $data);
            if($validate !== true)
            {
                echo $validate;
                exit();
            }
            $user = Catfish::db('users')->where('yonghu',$data['user'])->field('id,yonghu,email,randomcode')->find();
            if(!empty($user))
            {
                if($user['email'] == $data['email']){
                    $newpwd = uniqid();
                    Catfish::db('users')->where('id', $user['id'])->update([
                        'password' => md5($newpwd.$user['randomcode'])
                    ]);
                    Catfish::sendmail($data['email'], $data['user'], Catfish::lang('Retrieve password'), Catfish::lang('This is your new password, please change your password immediately after login.'). '<br><br>'.Catfish::lang('Password').': '.$newpwd);
                    echo 'ok';
                    exit();
                }
                else{
                    echo Catfish::lang('Mailbox error');
                    exit();
                }
            }
            else{
                echo Catfish::lang('User does not exist');
                exit();
            }
        }
        Catfish::allot('shouji', $this->mobile());
        $view = Catfish::output();
        return $view;
    }
    public function resend()
    {
        if(Catfish::hasSession('resend')){
            $user = Catfish::getSession('resend');
            $data = Catfish::db('users')->where('yonghu',$user)->field('id,email,randomcode')->find();
            $url = Catfish::url('index/Index/active').'?u='.$data['id'].'&v=e&c='.md5($user.$data['randomcode']);
            Catfish::sendmail($data['email'], $user, Catfish::lang('Account activation'), Catfish::lang('This is an account activation email, please click on the link below to activate your account.'). '<br><br><a href="'.$url.'">'.$url.'</a>');
            Catfish::deleteSession('resend');
            Catfish::success(Catfish::lang('Activation email has been sent'));
        }
        else{
            Catfish::toError();
        }
    }
    public function register()
    {
        $this->checkUser();
        if(Catfish::getPost('user') !== false)
        {
            $rule = [
                'user' => 'require|alphaDash',
                'pwd' => 'require|min:8',
                'repeat' => 'require',
                'email' => 'require|email'
            ];
            $msg = [
                'user.require' => Catfish::lang('The user name must be filled in'),
                'user.alphaDash' => Catfish::lang('Username can only consist of letters and numbers, underscores _ and dashes -'),
                'pwd.require' => Catfish::lang('Password must be filled in'),
                'pwd.min' => Catfish::lang('Password cannot be less than 8 characters'),
                'repeat.require' => Catfish::lang('Confirm password is required'),
                'email.require' => Catfish::lang('E-mail address is required'),
                'email.email' => Catfish::lang('The e-mail format is incorrect')
            ];
            $data = [
                'user' => Catfish::getPost('user'),
                'pwd' => Catfish::getPost('pwd'),
                'repeat' => Catfish::getPost('repeat'),
                'email' => Catfish::getPost('email')
            ];
            $validate = Catfish::validate($rule, $msg, $data);
            if($validate !== true)
            {
                echo $validate;
                exit();
            }
            if(Catfish::getPost('pwd') != Catfish::getPost('repeat'))
            {
                echo Catfish::lang('Confirm the password must be the same as the password');
                exit();
            }
            $filter = Catfish::get('filtername');
            if(!empty($filter)){
                $filter = Catfish::toComma($filter);
                $filter = explode(',', $filter);
                if(in_array($data['user'], $filter)){
                    echo Catfish::lang('Please use a different username');
                    exit();
                }
            }
            $user = Catfish::db('users')->where('yonghu',$data['user'])->whereOr('email',$data['email'])->field('id,yonghu,email')->select();
            if(!empty($user))
            {
                foreach($user as $key => $val){
                    if(strtolower($val['yonghu']) == strtolower($data['user'])){
                        echo Catfish::lang('User name has been registered');
                        exit();
                    }
                    if(strtolower($val['email']) == strtolower($data['email'])){
                        echo Catfish::lang('Email has been used');
                        exit();
                    }
                }
            }
            $create_date = Catfish::now();
            $rmd = md5($create_date . '_' . rand());
            $status = 1;
            $regvery = Catfish::get('regvery');
            if($regvery == 1){
                $status = 2;
            }
            $reid = Catfish::db('users')->insertGetId([
                'yonghu' => $data['user'],
                'password' => md5($data['pwd'].$rmd),
                'nicheng' => substr(md5($rmd),0,6),
                'email' => $data['email'],
                'createtime' => $create_date,
                'randomcode' => $rmd,
                'status' => $status
            ]);
            if($regvery == 1){
                $url = Catfish::url('index/Index/active').'?u='.$reid.'&v=e&c='.md5($data['user'].$rmd);
                Catfish::sendmail($data['email'], $data['user'], Catfish::lang('Account activation'), Catfish::lang('This is an account activation email, please click on the link below to activate your account.'). '<br><br><a href="'.$url.'">'.$url.'</a>');
            }
            Catfish::db('users_tongji')->insert([
                'uid' => $reid
            ]);
            Catfish::db('users_info')->insert([
                'uid' => $reid
            ]);
            $users = intval(Catfish::get('users'));
            $users ++;
            Catfish::set('users', $users);
            Catfish::tongji('zhuce');
            if($regvery == 1){
                echo 'eok';
            }
            else{
                echo 'ok';
            }
            exit();
        }
        Catfish::allot('shouji', $this->mobile());
        $view = Catfish::output();
        return $view;
    }
    public function quit()
    {
        Catfish::deleteSession('user_id');
        Catfish::deleteSession('user');
        Catfish::deleteSession('user_type');
        Catfish::deleteSession('mtype');
        Catfish::deleteSession('dengji');
        Catfish::deleteSession('touxiang');
        Catfish::deleteSession('logincode');
        if(Catfish::hasSession('resend')){
            Catfish::deleteSession('resend');
        }
        Catfish::deleteCookie('user_id');
        Catfish::deleteCookie('user');
        Catfish::deleteCookie('user_p');
        Catfish::redirect('index/Index/index');
        exit();
    }
}