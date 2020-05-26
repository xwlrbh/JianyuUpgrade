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
class CatfishCMS
{
    protected $captcha;
    protected function checkUser()
    {
        if(!is_file(APP_PATH . 'database.php')){
            Catfish::redirect(Catfish::oUrl('install/Index/index'));
            exit();
        }
        if(!Catfish::hasSession('user_id'))
        {
            $this->options();
        }
        else{
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
    }
    private function options()
    {
        $data_options = Catfish::autoload();
        foreach($data_options as $key => $val)
        {
            if($val['name'] == 'copyright' || $val['name'] == 'statistics')
            {
                Catfish::allot($val['name'], unserialize($val['value']));
            }
            elseif($val['name'] == 'captcha'){
                $this->captcha = $val['value'];
                Catfish::allot($val['name'], $val['value']);
            }
            else
            {
                Catfish::allot($val['name'], $val['value']);
            }
        }
    }
    protected function chklogin($captcha)
    {
        if(Catfish::getPost('captcha') !== false)
        {
            $rule = [
                'user' => 'require',
                'pwd' => 'require',
                'captcha|'.Catfish::lang('Captcha')=>'require|captcha'
            ];
        }
        else
        {
            $rule = [
                'user' => 'require',
                'pwd' => 'require'
            ];
        }
        $msg = [
            'user.require' => Catfish::lang('The user name must be filled in'),
            'pwd.require' => Catfish::lang('Password must be filled in')
        ];
        if($captcha == 1)
        {
            $data = [
                'user' => Catfish::getPost('user'),
                'pwd' => Catfish::getPost('pwd'),
                'captcha' => Catfish::getPost('captcha')
            ];
        }
        else
        {
            $data = [
                'user' => Catfish::getPost('user'),
                'pwd' => Catfish::getPost('pwd')
            ];
        }
        $validate = Catfish::validate($rule, $msg, $data);
        if($validate !== true)
        {
            return $validate;
        }
        else{
            return $data;
        }
    }
    protected function logined(&$user, &$data)
    {
        $ip = Catfish::ip();
        $chengzhang = Catfish::getGrowing();
        $chengzhangplus = 0;
        if(!$this->istoday($user['lastlogin'])){
            $chengzhangplus = $chengzhang['login'];
        }
        $cz = $user['chengzhang'] + $chengzhangplus;
        $dengji = $this->jibie($cz);
        Catfish::db('users')
            ->where('id', $user['id'])
            ->update([
                'lastlogin' => Catfish::now(),
                'lastonline' => Catfish::now(),
                'loginip' => $ip,
                'dengji' => $dengji,
                'chengzhang' => $cz
            ]);
        Catfish::db('users_tongji')
            ->where('uid', $user['id'])
            ->update([
                'denglu' => Catfish::dbRaw('denglu+1')
            ]);
        $this->yuetongji($user['id']);
        Catfish::setSession('user_id',$user['id']);
        Catfish::setSession('user',$data['user']);
        Catfish::setSession('user_type',$user['utype']);
        Catfish::setSession('mtype',$user['mtype']);
        Catfish::setSession('dengji',$user['dengji']);
        $touxiang = empty($user['touxiang']) ? Catfish::domain() . 'public/common/images/avatar.png' : Catfish::domain() . 'data/avatar/' . $user['touxiang'];
        Catfish::setSession('touxiang',$touxiang);
        Catfish::setSession('logincode',md5($user['randomcode'].'/'.$data['user']));
        if(Catfish::getPost('remember'))
        {
            Catfish::setCookie('user_id',$user['id'],604800);
            Catfish::setCookie('user',$data['user'],604800);
            $cookie_user_p = Catfish::getCache('cookie_user_p');
            if($cookie_user_p == false)
            {
                $cookie_user_p = md5(time());
                Catfish::setCache('cookie_user_p',$cookie_user_p,604800);
            }
            Catfish::setCookie('user_p',md5($cookie_user_p.$user['password'].$user['randomcode']),604800);
        }
    }
    private function yuetongji($uid)
    {
        $tbnm = 'users_tongji_' . date('Ym');
        $prefix = Catfish::prefix();
        if(!Catfish::hastable($prefix . $tbnm)){
            $sql = Catfish::fgc(APP_PATH . 'install/data/jianyu.sql');
            $sql = str_replace("\r", "\n", $sql);
            $sql = explode(";\n", $sql);
            $default_tablepre = "catfish_";
            $sql = str_replace([" `{$default_tablepre}", '#yue#'], [" `{$prefix}", date('Ym')], $sql);
            foreach ($sql as $item) {
                $item = trim($item);
                if(empty($item)) continue;
                Catfish::dbExecute($item);
            }
        }
        $re = Catfish::db($tbnm)->where('uid',$uid)->field('id')->find();
        if(empty($re)){
            Catfish::db($tbnm)->insert([
                'uid' => $uid
            ]);
        }
        Catfish::db($tbnm)
            ->where('uid', $uid)
            ->update([
                'yuedenglu' => Catfish::dbRaw('yuedenglu+1')
            ]);
    }
    private function istoday($time)
    {
        $comday = date("Ymd", strtotime($time));
        $today = date("Ymd");
        if($comday == $today){
            return true;
        }
        else{
            return false;
        }
    }
    private function jibie($chengzhang)
    {
        $jibie = 0;
        $dengji = Catfish::getCache('dengji_jibie_chengzhang');
        if($dengji == false){
            $dengji = [];
            $dengjiarr = Catfish::db('dengji')
                ->where('jibie', '>', 0)
                ->field('jibie,chengzhang')
                ->order('jibie asc')
                ->select();
            foreach($dengjiarr as $key => $val){
                $dengji[$val['jibie']] = $val['chengzhang'];
            }
            Catfish::setCache('dengji_jibie_chengzhang', $dengji, 86400);
        }
        foreach($dengji as $key => $val){
            if($chengzhang >= $val){
                $jibie = $key;
            }
            else{
                break;
            }
        }
        return $jibie;
    }
    protected function mobile()
    {
        $isMobile = 0;
        if(Catfish::isMobile()){
            $isMobile = 1;
        }
        return $isMobile;
    }
}