<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://jianyuluntan.com All rights reserved.
 */
namespace app\user\controller;
use catfishcms\Catfish;
class CatfishCMS
{
    private $time = 1200;
    protected function checkUser()
    {
        if(!Catfish::hasSession('user_id') && Catfish::hasCookie('user_id')){
            $cookie_user_p = Catfish::getCache('cookie_user_p');
            if($cookie_user_p !== false && Catfish::hasCookie('user_p')){
                $user = Catfish::db('users')->where('id',Catfish::getCookie('user_id'))->field('id,yonghu,password,touxiang,randomcode,status,utype,mtype,dengji')->find();
                if(!empty($user) && $user['status'] == 1 && Catfish::getCookie('user_p') == md5($cookie_user_p.$user['password'].$user['randomcode'])){
                    Catfish::setSession('user_id',$user['id']);
                    Catfish::setSession('user',$user['yonghu']);
                    Catfish::setSession('user_type',$user['utype']);
                    Catfish::setSession('mtype',$user['mtype']);
                    Catfish::setSession('dengji',$user['dengji']);
                    $touxiang = empty($user['touxiang']) ? Catfish::domain() . 'public/common/images/avatar.png' : Catfish::domain() . 'data/avatar/' . $user['touxiang'];
                    Catfish::setSession('touxiang',$touxiang);
                    Catfish::setSession('logincode',md5($user['randomcode'].'/'.$user['yonghu']));
                }
            }
        }
        if(!Catfish::hasSession('user_id'))
        {
            Catfish::redirect('login/Index/index');
            exit();
        }
        elseif(!Catfish::checkUser()){
            Catfish::redirect('login/Index/quit');
            exit();
        }
        if(Catfish::getSession('user_type') < 6){
            Catfish::allot('jianyumanagement', 1);
        }
        else{
            Catfish::allot('jianyumanagement', 0);
        }
        $this->options();
    }
    private function options()
    {
        $data_options = Catfish::autoload();
        foreach($data_options as $key => $val)
        {
            if($val['name'] == 'statistics')
            {
                Catfish::allot($val['name'], unserialize($val['value']));
            }
            elseif($val['name'] == 'crt')
            {
                $crt = Catfish::iszero(Catfish::remind()) ? Catfish::bd(implode('', unserialize($val['value']))) : '';
                Catfish::allot(Catfish::bd('emhpY2hp'), $crt);
            }
            elseif($val['name'] == 'serial')
            {
                unset($data_options[$key]);
            }
            elseif($val['name'] == 'domain'){
                $root = Catfish::domainAmend($val['value']);
                Catfish::allot($val['name'], $root);
                $dm = Catfish::url('/');
                if(strpos($dm,'/index.php') !== false)
                {
                    $root .= 'index.php/';
                }
                Catfish::allot('root', $root);
            }
            elseif($val['name'] == 'logo'){
                $ytu = Catfish::domain().'public/common/images/jianyu_white.png';
                if(empty($val['value'])){
                    $val['value'] = $ytu;
                }
                Catfish::allot($val['name'], $val['value']);
            }
            else
            {
                Catfish::allot($val['name'], $val['value']);
            }
        }
    }
    protected function show($menuname = '', $current = '', $star = false, $template = null)
    {
        Catfish::allot('menuname', $menuname);
        Catfish::allot('current', $current);
        Catfish::allot('star', $star);
        Catfish::allot('user', Catfish::getSession('user'));
        Catfish::allot('touxiang', Catfish::getSession('touxiang'));
        Catfish::allot('tuichu', Catfish::url('login/Index/quit'));
        Catfish::allot('verification', Catfish::verifyCode());
        $isModerator = 0;
        $umod = $this->isModerator(Catfish::getSession('user_id'));
        if(Catfish::getSession('mtype') > 0 && is_array($umod) && count($umod) > 0){
            $isModerator = 1;
        }
        Catfish::allot('isModerator', $isModerator);
        return Catfish::output($template);
    }
    private function validatePost(&$rule, &$msg, &$data)
    {
        $validate = Catfish::validate($rule, $msg, $data);
        if($validate !== true)
        {
            return $validate;
        }
        else{
            return $data;
        }
    }
    protected function getTieType()
    {
        $tie_type = Catfish::getCache('tie_type');
        if($tie_type === false){
            $tie_type = Catfish::db('tietype')
                ->field('id,tpname')
                ->order('id asc')
                ->select();
            foreach($tie_type as $key => $val){
                $tie_type[$key]['tpname'] = ucfirst($val['tpname']);
            }
            Catfish::setCache('tie_type',$tie_type,1200);
        }
        foreach($tie_type as $key => $val){
            $tie_type[$key]['tpname'] = Catfish::lang($val['tpname']);
        }
        Catfish::allot('tieleixing', $tie_type);
    }
    protected function sendnewpostsPost($needvcode = 0)
    {
        $rule = [
            'biaoti' => 'require',
            'zhengwen' => 'require'
        ];
        $msg = [
            'biaoti.require' => Catfish::lang('Post title must be filled in'),
            'zhengwen.require' => Catfish::lang('Post content must be filled in')
        ];
        $data = [
            'biaoti' => Catfish::getPost('biaoti'),
            'zhengwen' => Catfish::getPost('zhengwen')
        ];
        if($needvcode == 1){
            $rule['captcha|'.Catfish::lang('Captcha')] = 'require|captcha';
            $data['captcha'] = Catfish::getPost('captcha');
        }
        return $this->validatePost($rule, $msg, $data);
    }
    protected function newtongjitb()
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
        $uid = Catfish::getSession('user_id');
        $re = Catfish::db($tbnm)->where('uid',$uid)->field('id')->find();
        if(empty($re)){
            Catfish::db($tbnm)->insert([
                'uid' => $uid
            ]);
        }
    }
    protected function adddisabled(&$fenlei)
    {
        foreach($fenlei as $key => $val){
            if($val['virtual'] == 1 && Catfish::getSession('user_type') != 1){
                $fenlei[$key]['disabled'] = 1;
            }
            else{
                $fenlei[$key]['disabled'] = 0;
            }
        }
    }
    protected function changepasswordPost()
    {
        $rule = [
            'opwd' => 'require',
            'npwd' => 'require|min:8',
            'rpwd' => 'require'
        ];
        $msg = [
            'opwd.require' => Catfish::lang('The original password must be filled in'),
            'npwd.require' => Catfish::lang('The new password must be filled in'),
            'npwd.min' => Catfish::lang('The new password can not be shorter than 8 characters'),
            'rpwd.require' => Catfish::lang('Confirm the new password must be filled out')
        ];
        $data = [
            'opwd' => Catfish::getPost('opwd'),
            'npwd' => Catfish::getPost('npwd'),
            'rpwd' => Catfish::getPost('rpwd'),
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function myprofilePost()
    {
        $rule = [
            'nicheng' => 'require',
            'email' => 'require|email'
        ];
        $msg = [
            'nicheng.require' => Catfish::lang('Nickname must be filled in'),
            'email.require' => Catfish::lang('Email must be filled in'),
            'email.email' => Catfish::lang('The e-mail format is incorrect')
        ];
        $data = [
            'nicheng' => Catfish::getPost('nicheng'),
            'email' => Catfish::getPost('email')
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function myforum($forum = null)
    {
        if($forum == null){
            $forum = Catfish::getForum();
        }
        $utype = Catfish::getSession('user_type');
        $mtype = Catfish::getSession('mtype');
        $dengji = Catfish::getSession('dengji');
        $myforum['mingan'] = $forum['mingan'];
        $myforum['preaudit'] = $forum['preaudit'];
        $tmp_geshi = str_replace(' ', '', strtolower($forum['geshi']));
        $tmp_geshi = str_replace(',php,', ',', $tmp_geshi);
        $tmp_geshi = str_replace([',php', 'php,'], '', $tmp_geshi);
        $myforum['geshi'] = $tmp_geshi;
        switch($forum['fujian']){
            case 0:
                $myforum['fujian'] = ($forum['fujiandj'] <= $dengji || $utype < 20 || $mtype > 0) ? 1 : 0;
                break;
            case 5:
                $myforum['fujian'] = ($mtype >= 5 || $utype <= 5) ? 1 : 0;
                break;
            case 10:
                $myforum['fujian'] = ($mtype >= 10 || $utype <= 5) ? 1 : 0;
                break;
            case 15:
                $myforum['fujian'] = ($mtype >= 15 || $utype <= 5) ? 1 : 0;
                break;
            case 20:
                $myforum['fujian'] = ($utype <= 5) ? 1 : 0;
                break;
            case 25:
                $myforum['fujian'] = ($utype <= 3) ? 1 : 0;
                break;
            case 30:
                $myforum['fujian'] = ($utype == 1) ? 1 : 0;
                break;
        }
        switch($forum['tupian']){
            case 0:
                $myforum['tupian'] = ($forum['tupiandj'] <= $dengji || $utype < 20 || $mtype > 0) ? 1 : 0;
                break;
            case 5:
                $myforum['tupian'] = ($mtype >= 5 || $utype <= 5) ? 1 : 0;
                break;
            case 10:
                $myforum['tupian'] = ($mtype >= 10 || $utype <= 5) ? 1 : 0;
                break;
            case 15:
                $myforum['tupian'] = ($mtype >= 15 || $utype <= 5) ? 1 : 0;
                break;
            case 20:
                $myforum['tupian'] = ($utype <= 5) ? 1 : 0;
                break;
            case 25:
                $myforum['tupian'] = ($utype <= 3) ? 1 : 0;
                break;
            case 30:
                $myforum['tupian'] = ($utype == 1) ? 1 : 0;
                break;
        }
        switch($forum['lianjie']){
            case 0:
                $myforum['lianjie'] = ($forum['lianjiedj'] <= $dengji || $utype < 20 || $mtype > 0) ? 1 : 0;
                break;
            case 5:
                $myforum['lianjie'] = ($mtype >= 5 || $utype <= 5) ? 1 : 0;
                break;
            case 10:
                $myforum['lianjie'] = ($mtype >= 10 || $utype <= 5) ? 1 : 0;
                break;
            case 15:
                $myforum['lianjie'] = ($mtype >= 15 || $utype <= 5) ? 1 : 0;
                break;
            case 20:
                $myforum['lianjie'] = ($utype <= 5) ? 1 : 0;
                break;
            case 25:
                $myforum['lianjie'] = ($utype <= 3) ? 1 : 0;
                break;
            case 30:
                $myforum['lianjie'] = ($utype == 1) ? 1 : 0;
                break;
        }
        switch($forum['jifen']){
            case 0:
                $myforum['jifen'] = ($forum['jifendj'] <= $dengji || $utype < 20 || $mtype > 0) ? 1 : 0;
                break;
            case 5:
                $myforum['jifen'] = ($mtype >= 5 || $utype <= 5) ? 1 : 0;
                break;
            case 10:
                $myforum['jifen'] = ($mtype >= 10 || $utype <= 5) ? 1 : 0;
                break;
            case 15:
                $myforum['jifen'] = ($mtype >= 15 || $utype <= 5) ? 1 : 0;
                break;
            case 20:
                $myforum['jifen'] = ($utype <= 5) ? 1 : 0;
                break;
            case 25:
                $myforum['jifen'] = ($utype <= 3) ? 1 : 0;
                break;
            case 30:
                $myforum['jifen'] = ($utype == 1) ? 1 : 0;
                break;
        }
        return $myforum;
    }
    protected function checkIllegal($str, $rule)
    {
        $str = str_replace(["\r\n","\r","\n"], '', strip_tags($str));
        $rule = str_replace('~~~jianyuluntan~~~', '^^^jianyuluntan^^^', $rule);
        $rule = str_replace(["\r\n","\r","\n"], '~~~jianyuluntan~~~', $rule);
        $rulearr = explode('~~~jianyuluntan~~~', $rule);
        foreach($rulearr as $val){
            $val = trim($val);
            if(!empty($val)){
                $val = str_replace('^^^jianyuluntan^^^', '~~~jianyuluntan~~~', $val);
                if(Catfish::isRegular($val)){
                    if(preg_match($val, $str)){
                        return false;
                    }
                }
                else{
                    if(stripos($str, $val) !== false){
                        return false;
                    }
                }
            }
        }
        return true;
    }
    protected function isModerator($uid)
    {
        $moderator = Catfish::getCache('moderator_'.$uid);
        if($moderator === false){
            $moderator = Catfish::db('mod_sec_ontact')->where('uid',$uid)->field('sid,mtype')->select();
            Catfish::tagCache('moderator')->set('moderator_'.$uid,$moderator,$this->time);
        }
        return $moderator;
    }
    protected function gettypeidname()
    {
        $leixing = Catfish::getCache('leixing_id_name');
        if($leixing === false){
            $leixing = [];
            $leixingarr = Catfish::db('tietype')
                ->field('id,tpname')
                ->select();
            foreach($leixingarr as $key => $val){
                $leixing[$val['id']] = Catfish::lang(ucfirst($val['tpname']));
            }
            Catfish::setCache('leixing_id_name', $leixing, $this->time);
        }
        return $leixing;
    }
    protected function extractPics($string, $len = 600)
    {
        $tu = '';
        preg_match_all('/<img [\s\S]+?>/i', $string, $matches);
        if(is_array($matches[0]) && count($matches[0]) > 0){
            foreach($matches[0] as $key => $val){
                preg_match('/src="(\S+?)"/i', $val, $submatches);
                if(isset($submatches[1])){
                    if(strlen($tu) + strlen($submatches[1]) < $len - 1){
                        $tu .= empty($tu) ? $submatches[1] : ',' . $submatches[1];
                    }
                    else{
                        break;
                    }
                }
            }
        }
        return $tu;
    }
}