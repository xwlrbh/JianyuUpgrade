<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://jianyuluntan.com All rights reserved.
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
                $dom = Catfish::domainAmend($val['value']);
                Catfish::allot($val['name'], $dom);
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
            elseif($val['name'] == 'icon'){
                $icon = Catfish::domain().'public/common/images/favicon.ico';
                if(empty($val['value'])){
                    $val['value'] = $icon;
                }
                else{
                    $val['value'] = Catfish::iszero(Catfish::remind()) ? $icon : $val['value'];
                }
                Catfish::allot($val['name'], $val['value']);
            }
            else
            {
                Catfish::allot($val['name'], $val['value']);
            }
        }
        Catfish::allot('openpay', Catfish::get('openpay'));
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
    protected function strint($si)
    {
        if($si === null){
            return 'NULL';
        }
        elseif(is_int($si)){
            return intval($si);
        }
        else{
            return '\''.str_replace('\'','\'\'',$si).'\'';
        }
    }
    protected function semiinsert($table, $field, &$value, &$bkstr)
    {
        $restr = 'INSERT INTO `'.$table.'` ('.$field.') VALUES'.$value.';'.PHP_EOL;
        $bkstr .= '--CATFISH\'CMS->JianYuLunTan'.PHP_EOL.$restr;
    }
    protected function showdbbackup()
    {
        $dbrec = Catfish::get('dbbackup');
        if(!empty($dbrec)){
            $dbrecarr = explode(',', $dbrec);
            $dbrecarr = array_reverse($dbrecarr);
        }
        else{
            $dbrecarr = [];
        }
        foreach($dbrecarr as $key => $val){
            $onlbnm = basename($val, '.jyb');
            $onlbnmarr = explode('_', $onlbnm);
            $onlbnmarr[1] = str_replace('-', ': ', $onlbnmarr[1]);
            $bdate = $onlbnmarr[0] . ' ' . $onlbnmarr[1];
            $dbrecarr[$key] = [
                'path' => $val,
                'name' => 'JianYuLunTan'.str_replace(['-', '_', ':', ' '], '', $bdate . '.jyb'),
                'date' => $bdate,
                'down' => Catfish::domain() . 'data/dbbackup/' . $val
            ];
        }
        return $dbrecarr;
    }
    protected function restoredb($file)
    {
        if(is_file($file)){
            $dbrec = Catfish::get('dbbackup');
            $dbnm = Catfish::getConfig('database.database');
            $dbPrefix = Catfish::getConfig('database.prefix');
            $sql = "SHOW TABLES FROM {$dbnm} LIKE '{$dbPrefix}%'";
            $renm = Catfish::dbExecute($sql);
            foreach($renm as $nmval){
                reset($nmval);
                $tbnm = current($nmval);
                $sql = 'TRUNCATE TABLE `'.$tbnm.'`';
                Catfish::dbExecute($sql);
            }
            $bkf = gzuncompress(file_get_contents($file));
            $bkarr = explode('--CATFISH\'CMS->JianYuLunTan',$bkf);
            $zstr = '';
            $fstin = stripos($bkarr[0], 'INSERT INTO');
            if($fstin === false){
                $zstr = array_shift($bkarr);
            }
            else{
                $zstr = substr($bkarr[0], 0, $fstin);
                $bkarr[0] = trim(substr($bkarr[0], $fstin));
            }
            $zarr = explode(PHP_EOL, $zstr);
            $prefix = '';
            foreach($zarr as $key => $val){
                $ppos = stripos($val, 'Table prefix:');
                if($ppos !== false){
                    $ppos = $ppos + strlen('Table prefix:');
                    $prefix = trim(substr($val, $ppos));
                    break;
                }
            }
            foreach($bkarr as $q){
                $q = trim($q);
                if(!empty($prefix)){
                    $inlen = strlen('INSERT INTO `') + strlen($prefix);
                    $q = 'INSERT INTO `' . $dbPrefix . substr($q, $inlen);
                }
                Catfish::dbExecute($q);
            }
            Catfish::set('dbbackup', $dbrec);
            return 'ok';
        }
        else{
            return Catfish::lang('Backup file has expired');
        }
    }
    protected function deletefile($delfile)
    {
        if(Catfish::isDataPath($delfile)){
            if(@unlink(ROOT_PATH . str_replace('/', DS, $delfile))){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }
    protected function bound($in, $lower, $upper = null)
    {
        if($in < $lower){
            $in = $lower;
        }
        if($upper != null && $in > $upper){
            $in = $upper;
        }
        return $in;
    }
    protected function recurseCopy($src,$dst){
        $dir=opendir($src);
        @mkdir($dst);
        while(false!==($file=readdir($dir))){
            if(($file!='.' )&&($file!='..')){
                if(is_dir($src.'/'.$file)){
                    $this->recurseCopy($src.'/'.$file,$dst.'/'.$file);
                }
                else{
                    copy($src.'/'.$file,$dst.'/'.$file);
                }
            }
        }
        closedir($dir);
    }
    protected function delFolder($folder)
    {
        if(is_dir($folder)){
            $fd = scandir($folder);
            foreach($fd as $val){
                if($val != '.' && $val != '..'){
                    $tmp = $folder.DS.$val;
                    if(is_dir($tmp)){
                        $this->delFolder($tmp);
                        @rmdir($tmp);
                    }
                    else{
                        @unlink($tmp);
                    }
                }
            }
        }
        else{
            @unlink($folder);
        }
    }
    protected function alipayPost()
    {
        $rule = [
            'appid' => 'require',
            'merchantuid' => 'require',
            'privatekey' => 'require'
        ];
        $msg = [
            'appid.require' => Catfish::lang('AppId must be filled in'),
            'merchantuid.require' => Catfish::lang('Merchant UID must be filled in'),
            'privatekey.require' => Catfish::lang('Application private key must be filled in')
        ];
        $data = [
            'appid' => Catfish::getPost('appid'),
            'merchantuid' => Catfish::getPost('merchantuid'),
            'privatekey' => Catfish::getPost('privatekey', false)
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function wechatPost()
    {
        $rule = [
            'appid' => 'require',
            'merchantuid' => 'require',
            'privatekey' => 'require'
        ];
        $msg = [
            'appid.require' => Catfish::lang('AppId must be filled in'),
            'merchantuid.require' => Catfish::lang('Merchant UID must be filled in'),
            'privatekey.require' => Catfish::lang('Application key must be filled in')
        ];
        $data = [
            'appid' => Catfish::getPost('appid'),
            'merchantuid' => Catfish::getPost('merchantuid'),
            'privatekey' => Catfish::getPost('privatekey', false)
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function increasepointsPost()
    {
        $rule = [
            'increase' => 'require|integer'
        ];
        $msg = [
            'increase.require' => Catfish::lang('Input can not be empty'),
            'increase.integer' => Catfish::lang('Only integers can be entered')
        ];
        $data = [
            'increase' => Catfish::getPost('increase')
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function decreasepointsPost()
    {
        $rule = [
            'decrease' => 'require|integer'
        ];
        $msg = [
            'decrease.require' => Catfish::lang('Input can not be empty'),
            'decrease.integer' => Catfish::lang('Only integers can be entered')
        ];
        $data = [
            'decrease' => Catfish::getPost('decrease')
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function redemptionpointsPost()
    {
        $rule = [
            'jifen' => 'require|integer'
        ];
        $msg = [
            'jifen.require' => Catfish::lang('Input can not be empty'),
            'jifen.integer' => Catfish::lang('Only integers can be entered')
        ];
        $data = [
            'jifen' => Catfish::getPost('jifen')
        ];
        return $this->validatePost($rule, $msg, $data);
    }
    protected function checkinsettingsPost()
    {
        $rule = [
            'checkin' => 'require|integer',
            'checkincontinu' => 'require|integer',
            'checkinthreedays' => 'require|integer',
            'checkinweek' => 'require|integer',
            'checkintwoweek' => 'require|integer',
            'checkinmonth' => 'require|integer',
            'checkintwomonth' => 'require|integer',
            'checkinthreemonth' => 'require|integer',
            'checkinhalfyear' => 'require|integer',
            'checkinyear' => 'require|integer'
        ];
        $msg = [
            'checkin.require' => Catfish::lang('Input can not be empty'),
            'checkin.integer' => Catfish::lang('Only integers can be entered'),
            'checkincontinu.require' => Catfish::lang('Input can not be empty'),
            'checkincontinu.integer' => Catfish::lang('Only integers can be entered'),
            'checkinthreedays.require' => Catfish::lang('Input can not be empty'),
            'checkinthreedays.integer' => Catfish::lang('Only integers can be entered'),
            'checkinweek.require' => Catfish::lang('Input can not be empty'),
            'checkinweek.integer' => Catfish::lang('Only integers can be entered'),
            'checkintwoweek.require' => Catfish::lang('Input can not be empty'),
            'checkintwoweek.integer' => Catfish::lang('Only integers can be entered'),
            'checkinmonth.require' => Catfish::lang('Input can not be empty'),
            'checkinmonth.integer' => Catfish::lang('Only integers can be entered'),
            'checkintwomonth.require' => Catfish::lang('Input can not be empty'),
            'checkintwomonth.integer' => Catfish::lang('Only integers can be entered'),
            'checkinthreemonth.require' => Catfish::lang('Input can not be empty'),
            'checkinthreemonth.integer' => Catfish::lang('Only integers can be entered'),
            'checkinhalfyear.require' => Catfish::lang('Input can not be empty'),
            'checkinhalfyear.integer' => Catfish::lang('Only integers can be entered'),
            'checkinyear.require' => Catfish::lang('Input can not be empty'),
            'checkinyear.integer' => Catfish::lang('Only integers can be entered')
        ];
        $data = [
            'checkin' => Catfish::getPost('checkin'),
            'checkincontinu' => Catfish::getPost('checkincontinu'),
            'checkinthreedays' => Catfish::getPost('checkinthreedays'),
            'checkinweek' => Catfish::getPost('checkinweek'),
            'checkintwoweek' => Catfish::getPost('checkintwoweek'),
            'checkinmonth' => Catfish::getPost('checkinmonth'),
            'checkintwomonth' => Catfish::getPost('checkintwomonth'),
            'checkinthreemonth' => Catfish::getPost('checkinthreemonth'),
            'checkinhalfyear' => Catfish::getPost('checkinhalfyear'),
            'checkinyear' => Catfish::getPost('checkinyear'),
        ];
        return $this->validatePost($rule, $msg, $data);
    }
}