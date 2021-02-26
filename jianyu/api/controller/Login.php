<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://jianyuluntan.com All rights reserved.
 */
namespace app\api\controller;
use catfishcms\Catfish;
class Login extends Jsonapi
{
    public function getResults($param)
    {
        $user = Catfish::db('users')->where('yonghu',$param['username'])->field('id,password,nicheng,touxiang,xingbie,qianming,lastlogin,randomcode,status,jifen,chengzhang')->find();
        if(empty($user)){
            $err = $this->createError('701', 'This user does not exist', 'Username not found');
            $this->addError($err);
        }
        elseif($user['password'] != md5($param['password'].$user['randomcode']))
        {
            $err = $this->createError('702', 'The entered password is incorrect', 'Wrong password');
            $this->addError($err);
        }
        elseif($user['status'] == 0)
        {
            $err = $this->createError('703', 'Account has been banned', 'Account is disabled');
            $this->addError($err);
        }
        else{
            $this->logined($user);
            $token = new Jwttoken();
            $data = $this->createData('token', $user['id'], [
                'nickname' => $user['nicheng'],
                'avatar' => Catfish::domain() . 'data/avatar/' . $user['touxiang'],
                'gender' => $user['xingbie'],
                'signature' => $user['qianming'],
                'last_login' => $user['lastlogin'],
                'token' => $token->generateJwt($user['randomcode'], $user['id'])
            ]);
            $this->addData($data);
        }
        return $this->outJsonApi();
    }
    private function logined(&$user)
    {
        $ip = Catfish::ip();
        $chengzhang = Catfish::getGrowing();
        $chengzhangplus = 0;
        $jifenplus = 0;
        if(!$this->istoday($user['lastlogin'])){
            $chengzhangplus = $chengzhang['chengzhang']['login'];
            $jifenplus = $chengzhang['jifen']['login'];
        }
        $cz = $user['chengzhang'] + $chengzhangplus;
        $jf = $user['jifen'] + $jifenplus;
        $dengji = $this->jibie($cz);
        Catfish::db('users')
            ->where('id', $user['id'])
            ->update([
                'lastlogin' => Catfish::now(),
                'lastonline' => Catfish::now(),
                'loginip' => $ip,
                'dengji' => $dengji,
                'jifen' => $jf,
                'chengzhang' => $cz
            ]);
        Catfish::db('users_tongji')
            ->where('uid', $user['id'])
            ->update([
                'denglu' => Catfish::dbRaw('denglu+1')
            ]);
        if($jifenplus != 0){
            Catfish::db('points_book')->insert([
                'uid' => $user['id'],
                'zengjian' => $jifenplus,
                'booktime' => Catfish::now(),
                'miaoshu' => Catfish::lang('Log in')
            ]);
        }
        $this->yuetongji($user['id']);
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
}