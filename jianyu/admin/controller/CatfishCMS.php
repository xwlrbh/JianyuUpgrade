<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://www.jianyuluntan.com All rights reserved.
 */
namespace app\admin\controller;
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
        elseif(Catfish::getSession('user_type') > 5){
            Catfish::redirect('user/Index/index');
            exit();
        }
        elseif(!Catfish::checkUser()){
            Catfish::redirect('login/Index/quit');
            exit();
        }
        $this->options();
    }
    private function options()
    {
        $data_options = Catfish::autoload();
        $dom = '';
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
            elseif($val['name'] == 'domain'){
                Catfish::allot($val['name'], $val['value']);
                $dom = $val['value'];
                $root = $val['value'];
                $dm = Catfish::url('/');
                if(strpos($dm,'/index.php') !== false)
                {
                    $root .= 'index.php/';
                }
                Catfish::allot('root', $root);
            }
            elseif($val['name'] == 'title'){
                Catfish::allot($val['name'], Catfish::iszero(Catfish::remind()) ? Catfish::getnm() : $val['value']);
            }
            elseif($val['name'] == 'logo'){
                $ytu = Catfish::domain().'public/common/images/jianyu_white.png';
                if(empty($val['value'])){
                    $val['value'] = $ytu;
                }
                else{
                    $val['value'] = Catfish::iszero(Catfish::remind()) ? $ytu : $val['value'];
                }
                Catfish::allot($val['name'], $val['value']);
            }
            else
            {
                Catfish::allot($val['name'], $val['value']);
            }
        }
        Catfish::allot('remind', Catfish::differ($dom));
    }
    protected function show($title, $ugroup = 0, $option = '', $backstageMenu = '', $star = false, $template = null)
    {
        $utype = Catfish::getSession('user_type');
        $aml = Catfish::iszero(Catfish::remind()) ? Catfish::getnm().Catfish::getvn() : '';
        Catfish::allot('tuichu', Catfish::url('login/Index/quit'));
        Catfish::allot('user', Catfish::getSession('user'));
        Catfish::allot('touxiang', Catfish::getSession('touxiang'));
        Catfish::allot('ugroup', $utype);
        Catfish::allot('', $aml);
        Catfish::allot('backstagetitle', $title);
        Catfish::allot('backstageMenu', $backstageMenu);
        Catfish::allot('option', $option);
        Catfish::allot('star', $star);
        Catfish::allot('verification', Catfish::verifyCode());
        if($ugroup != 0 && $utype > $ugroup){
            $template = 'error';
            Catfish::allot('error', Catfish::lang('You are not authorized to access this page'));
        }
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
    protected function newclassificationPost()
    {
        $rule = [
            'sname' => 'require'
        ];
        $msg = [
            'sname.require' => Catfish::lang('Section name must be filled in')
        ];
        $data = [
            'sname' => Catfish::getPost('sname')
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function order($table)
    {
        if(Catfish::getPost('paixu') == 'paixu'){
            $paixu = Catfish::getPost();
            foreach((array)$paixu as $key => $val)
            {
                if(is_numeric($key))
                {
                    Catfish::db($table)
                        ->where('id', $key)
                        ->update(['listorder' => intval($val)]);
                }
            }
        }
    }
    protected function gettypeidname()
    {
        $leixing = Catfish::getCache('leixing_id_name');
        if($leixing == false){
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
    protected function transferclassificationPost()
    {
        $rule = [
            'osid' => 'require',
            'nsid' => 'require',
        ];
        $msg = [
            'osid.require' => Catfish::lang('Transfer out section must be selected'),
            'nsid.require' => Catfish::lang('Transfer to the section must be selected')
        ];
        $data = [
            'osid' => Catfish::getPost('osid'),
            'nsid' => Catfish::getPost('nsid')
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function websitesettingsPost()
    {
        $rule = [
            'title' => 'require',
            'domain' => 'require',
        ];
        $msg = [
            'title.require' => Catfish::lang('Forum name must be filled in'),
            'domain.require' => Catfish::lang('Forum domain name must be filled in')
        ];
        $data = [
            'title' => Catfish::getPost('title'),
            'domain' => Catfish::getPost('domain')
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function addfriendshiplinkPost()
    {
        $rule = [
            'mingcheng' => 'require',
            'dizhi' => 'require',
        ];
        $msg = [
            'mingcheng.require' => Catfish::lang('Friendly link name must be filled in'),
            'dizhi.require' => Catfish::lang('Friendly link address must be filled in')
        ];
        $data = [
            'mingcheng' => Catfish::getPost('mingcheng'),
            'dizhi' => Catfish::getPost('dizhi')
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function smtpsettingsPost()
    {
        $rule = [
            'host' => 'require',
            'port' => 'require',
            'user' => 'require',
            'password' => 'require',
        ];
        $msg = [
            'host.require' => Catfish::lang('SMTP server address must be filled in'),
            'port.require' => Catfish::lang('Port number must be filled in'),
            'user.require' => Catfish::lang('Mailbox users must fill in'),
            'password.require' => Catfish::lang('Password must be filled in')
        ];
        $data = [
            'host' => Catfish::getPost('host'),
            'port' => Catfish::getPost('port'),
            'user' => Catfish::getPost('user'),
            'password' => Catfish::getPost('password')
        ];
        return $this->validatePost($rule, $msg, $data);
    }
}