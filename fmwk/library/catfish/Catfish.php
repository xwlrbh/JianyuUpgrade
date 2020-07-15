<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://jianyujishu.com All rights reserved.
 */
namespace catfishcms;
use think\Request;
use think\Session;
use think\Cookie;
use think\Lang;
use think\Cache;
use think\Url;
use think\Db;
use think\Validate;
use think\View;
use think\Config;
use think\Response;
use think\Debug;
use think\response\Redirect;
use think\exception\HttpResponseException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class Catfish
{
    private static $catfishcms;
    public static function isRewrite()
    {
        if(function_exists('apache_get_modules'))
        {
            $rew = apache_get_modules();
            if(in_array('mod_rewrite', $rew) && is_file(APP_PATH . '../.htaccess'))
            {
                return true;
            }
        }
        return false;
    }
    public static function getPost($param = '', $filter = true, $filterjs = true)
    {
        if($param == '')
        {
            $tmp = Request::instance()->post();
            if(empty($tmp))
            {
                return false;
            }
            else
            {
                foreach((array)$tmp as $key => $val){
                    if($filter == true){
                        $tmp[$key] = trim(htmlspecialchars(self::urldc($val), ENT_QUOTES));
                    }
                    elseif($filterjs == true){
                        $tmp[$key] = self::filterJs(trim(self::urldc($val)));
                    }
                    else{
                        $tmp[$key] = trim(self::urldc($val));
                    }
                }
                return $tmp;
            }
        }
        else
        {
            if(strpos($param,'/') !== false){
                if(strpos($param,'/a') !== false){
                    $parr =  Request::instance()->post($param);
                    foreach((array)$parr as $key => $val){
                        if($filter == true){
                            $parr[$key] = trim(htmlspecialchars(self::urldc($val), ENT_QUOTES));
                        }
                        elseif($filterjs == true){
                            $parr[$key] = self::filterJs(trim(self::urldc($val)));
                        }
                        else{
                            $parr[$key] = trim(self::urldc($val));
                        }
                    }
                    return $parr;
                }
                else{
                    if($filter == true){
                        return trim(htmlspecialchars(self::urldc(Request::instance()->post($param)), ENT_QUOTES));
                    }
                    elseif($filterjs == true){
                        return self::filterJs(trim(Request::instance()->post($param)));
                    }
                    else{
                        return trim(Request::instance()->post($param));
                    }
                }
            }
            else{
                if(Request::instance()->has($param,'post'))
                {
                    if($filter == true){
                        return trim(htmlspecialchars(self::urldc(Request::instance()->post($param)), ENT_QUOTES));
                    }
                    elseif($filterjs == true){
                        return self::filterJs(trim(Request::instance()->post($param)));
                    }
                    else{
                        return trim(Request::instance()->post($param));
                    }
                }
                else
                {
                    return false;
                }
            }
        }
    }
    public static function getGet($param = '', $filter = true)
    {
        if($param == '')
        {
            $tmp = Request::instance()->get();
            if(empty($tmp))
            {
                return false;
            }
            else
            {
                foreach((array)$tmp as $key => $val){
                    if($filter == true){
                        $tmp[$key] = trim(htmlspecialchars(self::urldc($val), ENT_QUOTES));
                    }
                    else{
                        $tmp[$key] = self::filterJs(trim(self::urldc($val)));
                    }
                }
                return $tmp;
            }
        }
        else
        {
            if(Request::instance()->has($param,'get'))
            {
                if($filter == true){
                    return trim(htmlspecialchars(self::urldc(Request::instance()->get($param)), ENT_QUOTES));
                }
                else{
                    return self::filterJs(trim(self::urldc(Request::instance()->get($param))));
                }
            }
            else
            {
                return false;
            }
        }
    }
    public static function setSession($name, $value = '', $prefix = null)
    {
        Session::set($name,$value,$prefix);
    }
    public static function getSession($name = '', $prefix = null)
    {
        return Session::get($name,$prefix);
    }
    public static function hasSession($name, $prefix = null)
    {
        return Session::has($name, $prefix);
    }
    public static function deleteSession($name, $prefix = null)
    {
        Session::delete($name, $prefix);
    }
    public static function setCookie($name, $value = '', $option = null)
    {
        Cookie::set($name,$value,$option);
    }
    public static function getCookie($name = '', $prefix = null)
    {
        return Cookie::get($name,$prefix);
    }
    public static function hasCookie($name, $prefix = null)
    {
        return Cookie::has($name, $prefix);
    }
    public static function deleteCookie($name, $prefix = null)
    {
        Cookie::delete($name, $prefix);
    }
    public static function lang($lang)
    {
        return Lang::get($lang);
    }
    public static function setCache($name, $value, $expire = null)
    {
        Cache::set($name, $value, $expire);
    }
    public static function getCache($name, $default = false)
    {
        return Cache::get($name, $default);
    }
    public static function clearCache($tag = null)
    {
        Cache::clear($tag);
    }
    public static function removeCache($name)
    {
        return Cache::rm($name);
    }
    public static function tagCache($name, $keys = null, $overlay = false)
    {
        return Cache::tag($name, $keys, $overlay);
    }
    public static function url($url = '', $vars = '', $suffix = true, $domain = true)
    {
        $u = Url::build($url, $vars, $suffix, $domain);
        $rewrite = self::getCache('rewrite');
        if($rewrite === false){
            $rewrite = self::get('rewrite');
            self::setCache('rewrite', $rewrite, 600);
        }
        if($rewrite == 0 && strpos($u,'/index.php') === false){
            $dm = self::domain();
            if(strpos($u,$dm) !== false){
                $u = str_replace($dm, $dm.'index.php/', $u);
            }
        }
        if($rewrite == 1){
            $u = str_replace('index.php/', '', $u);
        }
        return $u;
    }
    public static function oUrl($url = '', $vars = '', $suffix = true, $domain = false)
    {
        $u = Url::build($url, $vars, $suffix, $domain);
        if(!self::isRewrite()){
            $rt = Url::build('/');
            $u = $rt.'index.php/'.substr($u,strlen($rt));
        }
        return $u;
    }
    public static function now()
    {
        return date("Y-m-d H:i:s");
    }
    public static function get($key)
    {
        $option = self::getCache('jianyu_options_'.$key);
        if($option === false){
            $option = self::db('options')->where('option_name',$key)->field('option_value')->find();
            self::setCache('jianyu_options_'.$key,$option,3600);
        }
        if(isset($option['option_value']))
        {
            return $option['option_value'];
        }
        else
        {
            return '';
        }
    }
    public static function set($key,$value,$protection = false)
    {
        $re = self::db('options')->where('option_name',$key)->field('option_value')->find();
        if(empty($re))
        {
            $data = [
                'option_name' => $key,
                'option_value' => $value,
                'autoload' => 0
            ];
            self::db('options')->insert($data);
        }
        else
        {
            if($protection == false)
            {
                self::db('options')
                    ->where('option_name', $key)
                    ->update(['option_value' => $value]);
            }
        }
        self::removeCache('jianyu_options_'.$key);
    }
    public static function toComma($str, $unique = false)
    {
        $str = str_replace(["\r\n","\r","\n","，"], ',', $str);
        $str = preg_replace("/,+/", ',', $str);
        if($unique == true){
            $strarr = explode(',', $str);
            $strarr = array_unique($strarr);
            $str = implode(',', $strarr);
        }
        return $str;
    }
    public static function removea($str)
    {
        return preg_replace('/<a [^>]*>(.*?)<\/a>/', '$1', $str);
    }
    public static function ip($type = 0, $adv = true)
    {
        return Request::instance()->ip($type, $adv);
    }
    public static function getUrl($all = true)
    {
        $all = $all == false ? null : true;
        return Request::instance()->url($all);
    }
    public static function isAjax($ajax = false)
    {
        return Request::instance()->isAjax($ajax);
    }
    public static function autoload()
    {
        $con = self::getConfig(self::bd('amlhbnl1'));
        unset($con['version']);
        $constr = substr(md5(implode('-', $con)), 0, 6);
        if($constr != '268b86'){
            self::toError();
        }
        $options = self::getCache('options');
        if($options === false){
            $options = self::db('options')->where('autoload',1)->field('option_name as name,option_value as value')->select();
            self::setCache('options', $options, 600);
        }
        return $options;
    }
    public static function getNickname()
    {
        $nickname = self::getCache('nickname_' . self::getSession('user_id'));
        if($nickname === false){
            $nickname = self::db('users')->where('id',self::getSession('user_id'))->field('nicheng')->find();
            if(isset($nickname['nicheng'])){
                $nickname = $nickname['nicheng'];
            }
            else{
                $nickname = '';
            }
            self::setCache('nickname_' . self::getSession('user_id'), $nickname, 600);
        }
        return $nickname;
    }
    public static function getSort($table,$fields = 'id,sname,parentid', $replace = '&nbsp;&nbsp;&nbsp;&nbsp;',$where = '',$order = '')
    {
        if(is_array($where)){
            if(empty($order)){
                if(count($where) == 2){
                    $data = self::db($table)->where($where[0],$where[1])->field($fields)->select();
                }
                elseif(count($where) == 3){
                    $data = self::db($table)->where($where[0],$where[1],$where[2])->field($fields)->select();
                }
                else{
                    $data = self::db($table)->field($fields)->select();
                }
            }
            else{
                if(count($where) == 2){
                    $data = self::db($table)->where($where[0],$where[1])->field($fields)->order($order)->select();
                }
                elseif(count($where) == 3){
                    $data = self::db($table)->where($where[0],$where[1],$where[2])->field($fields)->order($order)->select();
                }
                else{
                    $data = self::db($table)->field($fields)->order($order)->select();
                }
            }
        }
        else{
            if(empty($order)){
                $data = self::db($table)->field($fields)->select();
            }
            else{
                $data = self::db($table)->field($fields)->order($order)->select();
            }
        }
        if(is_array($data) && count($data) > 0)
        {
            $r = self::treeForHtml($data);
            foreach($r as $key => $val){
                $r[$key]['level'] = str_repeat($replace,$val['level']);
            }
            return $r;
        }
        else
        {
            return [];
        }
    }
    public static function treeForHtml(&$data)
    {
        return Tree::makeTreeForHtml($data);
    }
    public static function tree(&$data)
    {
        return Tree::makeTree($data);
    }
    public static function getSortNoSelf($table, $me, $fields = 'id,sname,parentid', $replace = '&nbsp;&nbsp;&nbsp;&nbsp;',$where = '',$order = '')
    {
        if(is_array($where)){
            if(empty($order)){
                if(count($where) == 2){
                    $data = self::db($table)->where($where[0],$where[1])->field($fields)->select();
                }
                elseif(count($where) == 3){
                    $data = self::db($table)->where($where[0],$where[1],$where[2])->field($fields)->select();
                }
                else{
                    $data = self::db($table)->field($fields)->select();
                }
            }
            else{
                if(count($where) == 2){
                    $data = self::db($table)->where($where[0],$where[1])->field($fields)->order($order)->select();
                }
                elseif(count($where) == 3){
                    $data = self::db($table)->where($where[0],$where[1],$where[2])->field($fields)->order($order)->select();
                }
                else{
                    $data = self::db($table)->field($fields)->order($order)->select();
                }
            }
        }
        else{
            if(empty($order)){
                $data = self::db($table)->field($fields)->select();
            }
            else{
                $data = self::db($table)->field($fields)->order($order)->select();
            }
        }
        if(is_array($data) && count($data) > 0)
        {
            $r = self::treeForHtml($data);
            $start = false;
            $level = 0;
            foreach($r as $key => $val){
                if($val['id'] == $me){
                    $start = true;
                    $level = $val['level'];
                    $r[$key]['level'] = -1;
                    continue;
                }
                if($start == true){
                    if($val['level'] > $level){
                        $r[$key]['level'] = -1;
                        continue;
                    }
                    else{
                        $start = false;
                    }
                }
                $r[$key]['level'] = str_repeat($replace,$val['level']);
            }
            foreach($r as $key => $val){
                if($val['level'] == -1){
                    unset($r[$key]);
                }
            }
            return $r;
        }
        else
        {
            return [];
        }
    }
    public static function verifyCode()
    {
        if(self::hasSession('user_id')){
            $user = self::db('users')->where('id',self::getSession('user_id'))->field('randomcode')->find();
            if(!empty($user)){
                $restr = substr($user['randomcode'], 0, 16);
                return md5($restr);
            }
            else{
                return '';
            }
        }
        else{
            return '';
        }
    }
    public static function verify($str)
    {
        if(self::hasSession('user_id')){
            $user = self::db('users')->where('id',self::getSession('user_id'))->field('randomcode')->find();
            if(!empty($user))
            {
                if($str == md5(substr($user['randomcode'], 0, 16))){
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
        else{
            return false;
        }
    }
    public static function getTemplate($folder)
    {
        $template = self::get('template');
        $path = ROOT_PATH.'public/theme/'.$template.'/'.$folder;
        if(is_dir($path)){
            $re = glob($path.'/*.html');
            foreach($re as $key => $val) {
                $tmpdir = basename($val);
                $re[$key] = $tmpdir;
            }
            return $re;
        }
        else{
            return [];
        }
    }
    public static function isPost($utype = 5,$chk = true)
    {
        if(Request::instance()->isPost()){
            if(!self::hasSession('user_type')){
                return false;
            }
            else{
                if(self::getSession('user_type') > $utype){
                    return false;
                }
            }
            if($chk == true){
                if(self::verify(self::getPost('verification'))){
                    return true;
                }
                else{
                    return false;
                }
            }
            else{
                return true;
            }
        }
        else{
            return false;
        }
    }
    public static function isGet($chk = true)
    {
        if(Request::instance()->isGet()){
            if($chk == true){
                if(self::verify(self::getGet('verification'))){
                    return true;
                }
                else{
                    return false;
                }
            }
            else{
                return true;
            }
        }
        else{
            return false;
        }
    }
    public static function validate(&$rule, &$msg, &$data)
    {
        $validate =  new Validate($rule, $msg);
        if(!$validate->check($data))
        {
            return $validate->getError();
        }
        else{
            return true;
        }
    }
    public static function prefix()
    {
        return Config::get('database.prefix');
    }
    public static function db($name)
    {
        return Db::name($name);
    }
    public static function table($name)
    {
        return Db::table($name);
    }
    public static function field($field)
    {
        return Db::field($field);
    }
    public static function dbRaw($name)
    {
        return Db::raw($name);
    }
    public static function dbStartTrans()
    {
        Db::startTrans();
    }
    public static function dbCommit()
    {
        Db::commit();
    }
    public static function dbRollback()
    {
        Db::rollback();
    }
    public static function dbExecute($str)
    {
        if(strtolower(substr($str, 0, 6)) == 'select' || strtolower(substr($str, 0, 4)) == 'show'){
            return Db::query($str);
        }
        else{
            return Db::execute($str);
        }
    }
    public static function isDataPath($path)
    {
        if(substr($path,0,5) == 'data/' && stripos($path, '..') === false){
            return true;
        }
        else{
            return false;
        }
    }
    public static function isRegular($str)
    {
        if(preg_match("/^\/.*\/i?$/", $str)){
            return true;
        }
        else{
            return false;
        }
    }
    public static function domain()
    {
        return self::domainAmend(self::get('domain'));
    }
    public static function domainAmend($domain)
    {
        $dm = $_SERVER['HTTP_HOST'];
        if(substr($dm,0,4) != 'www.'){
            $domain = str_replace('://www.','://',$domain);
        }
        else{
            if(stripos($domain,'://www.') === false){
                $domain = str_replace('://','://www.',$domain);
            }
        }
        return $domain;
    }
    public static function view($table, $field)
    {
        return Db::view($table, $field);
    }
    public static function json($str)
    {
        return json_encode($str, JSON_UNESCAPED_UNICODE);
    }
    public static function bd($str)
    {
        return base64_decode($str);
    }
    public static function fgc($fp)
    {
        $restr = '';
        if(is_array($fp)){
            foreach($fp as $key => $val){
                $restr .= file_get_contents($val);
            }
        }
        else{
            $restr = file_get_contents($fp);
        }
        return $restr;
    }
    public static function allot($name, $value = '', $append = false)
    {
        if(!is_object(self::$catfishcms)){
            self::$catfishcms = new View();
        }
        if(empty($name)){
            $name = self::bd('amlhbnl1');
        }
        if($name != '/' && substr($name,0,1) == '/'){
            $name = substr($name,1);
            $append = true;
        }
        if($append == true){
            $tmp = $value;
            if(self::$catfishcms->__isset($name)){
                $tmp = self::$catfishcms->__get($name);
                $tmp .= $value;
            }
            return self::$catfishcms->assign($name, $tmp);
        }
        else{
            return self::$catfishcms->assign($name, $value);
        }
    }
    public static function hasAllot($name)
    {
        if(self::$catfishcms->__isset($name)){
            return true;
        }
        return false;
    }
    public static function getallot($name, $unbs = false)
    {
        if(!is_object(self::$catfishcms)){
            self::$catfishcms = new View();
        }
        $tmp = '';
        if(self::$catfishcms->__isset($name)){
            $tmp = self::$catfishcms->__get($name);
        }
        if($unbs == true && !empty($tmp)){
            $tmp = self::bd($tmp);
        }
        return $tmp;
    }
    public static function output($template = '', $vars = [], $replace = [], $config = [], $renderContent = false)
    {
        if(!is_object(self::$catfishcms)){
            self::$catfishcms = new View();
        }
        return self::$catfishcms->fetch($template, $vars, $replace, $config, $renderContent);
    }
    public static function redirect($url, $params = [], $code = 302, $with = [])
    {
        if (is_integer($params)) {
            $code   = $params;
            $params = [];
        }
        $response = new Redirect($url);
        $response->code($code)->params($params)->with($with);
        throw new HttpResponseException($response);
    }
    public static function error($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_null($url)) {
            $url = Request::instance()->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ('' !== $url && !strpos($url, '://') && 0 !== strpos($url, '/')) {
            $url = Url::build($url);
        }
        $type = self::getResponseType();
        $result = [
            'code' => 0,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];
        if ('html' == strtolower($type)) {
            $template = Config::get('template');
            $view = Config::get('view_replace_str');
            $result = View::instance($template, $view)
                ->fetch(Config::get('dispatch_error_tmpl'), $result);
        }
        $response = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }
    public static function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_null($url) && !is_null(Request::instance()->server('HTTP_REFERER'))) {
            $url = Request::instance()->server('HTTP_REFERER');
        } elseif ('' !== $url && !strpos($url, '://') && 0 !== strpos($url, '/')) {
            $url = Url::build($url);
        }
        $type = self::getResponseType();
        $result = [
            'code' => 1,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];
        if ('html' == strtolower($type)) {
            $template = Config::get('template');
            $view = Config::get('view_replace_str');
            $result = View::instance($template, $view)
                ->fetch(Config::get('dispatch_success_tmpl'), $result);
        }
        $response = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }
    public static function loadLang($file, $range = '')
    {
        Lang::load($file, $range);
    }
    public static function detectLang()
    {
        return Lang::detect();
    }
    public static function setAllowLangList($list)
    {
        Lang::setAllowLangList($list);
    }
    public static function isMobile()
    {
        return Request::instance()->isMobile();
    }
    public static function getFile($url, $filePath)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);
        $ch = curl_init();
        curl_setopt($ch , CURLOPT_URL , $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;http://www.baidu.com)');
        if(substr($url, 0, 8) == 'https://'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $fp = fopen($filePath, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }
    public static function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;http://www.baidu.com)');
        curl_setopt($ch , CURLOPT_URL , $url);
        if(substr($url, 0, 8) == 'https://'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
    public static function filterJs($str)
    {
        while(preg_match("/(<script)|(<style)|(<iframe)|(<frame)|(<object)|(<frameset)|(<bgsound)|(<video)|(<source)|(<audio)|(<track)|(<marquee)/i",$str) || preg_match("/(?<!\w)((onabort)|(onactivate)|(onafter)|(onbefore)|(onbegin)|(onblur)|(onbounce)|(oncellchange)|(onchange)|(onclick)|(oncont)|(oncopy)|(oncut)|(ondata)|(ondblclick)|(ondeactivate)|(ondrag)|(ondrop)|(onerror)|(onfilter)|(onfinish)|(onfocus)|(onhelp)|(onkey)|(onlayout)|(onlose)|(onload)|(onmouse)|(onmove)|(onpaste)|(onproperty)|(onready)|(onreset)|(onresize)|(onrow)|(onscroll)|(onselect)|(onstart)|(onstop)|(onseek)|(onsubmit)|(ontoggle)|(onunload))/i",$str))
        {
            $str = preg_replace(['/<script[\s\S]*?<\/script[\s]*>/i','/<style[\s\S]*?<\/style[\s]*>/i','/<iframe[\s\S]*?(<\/iframe|\/)[\s]*>/i','/<frame[\s\S]*?(<\/frame|\/)[\s]*>/i','/<object[\s\S]*?(<\/object|\/)[\s]*>/i','/<frameset[\s\S]*?(<\/frameset|\/)[\s]*>/i','/<bgsound[\s\S]*?(<\/bgsound|\/)[\s]*>/i','/<video[\s\S]*?(<\/video|\/)[\s]*>/i','/<source[\s\S]*?(<\/source|\/)[\s]*>/i','/<audio[\s\S]*?(<\/audio|\/)[\s]*>/i','/<track[\s\S]*?(<\/track|\/)[\s]*>/i','/<marquee[\s\S]*?(<\/marquee|\/)[\s]*>/i','/ on[A-Za-z]+[\s]*=[\s]*[\'|"][\s\S]*?[\'|"]/i','/ on[A-Za-z]+[\s]*=[\s]*[^>]+/i'],'',$str);
        }
        $str = str_replace('<!--','&lt;!--',$str);
        return $str;
    }
    public static function mp()
    {
        return '<a class="text-light" id="jianyuluntan" href="'.self::bd('aHR0cDovL2ppYW55dWx1bnRhbi5jb20v').'">'.self::bd('5YmR6bG86K665Z2b').'</a>';
    }
    public static function isDomain($dom)
    {
        if(strpos($dom,'//') !== false){
            $domarr = explode('//',$dom);
            $domp = $domarr[1];
        }
        else{
            $domp = $dom;
        }
        $domarr = explode('/',$domp);
        $domp = str_replace('.', '', $domarr[0]);
        if(strpos($dom,'localhost') === false && !is_numeric($domp)){
            return true;
        }
        return false;
    }
    public static function getConfig($name = null, $range = '')
    {
        return Config::get($name, $range);
    }
    public static function setConfig($name, $value = null, $range = '')
    {
        Config::set($name, $value, $range);
    }
    private static function getResponseType()
    {
        return Request::instance()->isAjax()
            ? Config::get('default_ajax_return')
            : Config::get('default_return_type');
    }
    public static function isLogin()
    {
        return self::hasSession('user_id');
    }
    public static function checkUser()
    {
        if(self::hasSession('user_id')){
            $user = self::db('users')->where('id',self::getSession('user_id'))->field('randomcode')->find();
            if(!empty($user)){
                if(md5($user['randomcode'].'/'.self::getSession('user')) == self::getSession('logincode')){
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
        else{
            return false;
        }
    }
    public static function bbn()
    {
        $bbn = self::getCache('jianyuluntanbbn');
        if($bbn === false){
            $dom = self::get('domain');
            $bbn = self::curl('http://jianyuluntan.com/version/?dm='.$dom.'&tl='.urlencode(self::get('title')).'&ct='.strtotime(self::get('creationtime')).'&vr='.urlencode(self::getConfig('jianyu.version')).'&nm='.urlencode(self::getConfig('jianyu.name')));
            if($bbn === false){
                $bbn = '';
            }
            self::setCache('jianyuluntanbbn',$bbn,172800);
        }
        return $bbn;
    }
    public static function hastable($tbname)
    {
        if(count(self::dbExecute("SHOW TABLES LIKE '{$tbname}'")) > 0){
            return true;
        }
        else{
            return false;
        }
    }
    public static function dump($para)
    {
        header("Content-type:text/html;charset=utf-8");
        dump($para);
        exit();
    }
    public static function getForum()
    {
        $forum = self::getCache('forumsettings');
        if($forum === false){
            $forum = self::db('forum')->where('id',1)->field('fujian,fujiandj,fujiandwn,tiezi,tupian,tupiandj,lianjie,lianjiedj,yanzhengzt,yanzhenggt,shichangzt,shichanggt,geshi,mingan,preaudit,fpreaudit,jifen,jifendj')->find();
            self::setCache('forumsettings',$forum,86400);
        }
        return $forum;
    }
    public static function remind()
    {
        $remind = self::getCache('jianyuremind');
        if($remind === false){
            $serial = self::get('serial');
            if(empty($serial)){
                $remind = 0;
            }
            else{
                $remind = self::curl('http://jianyuluntan.com/prompt/?dm='.self::get('domain').'&tl='.urlencode(self::get('title')).'&ct='.strtotime(self::get('creationtime')).'&se='.md5($serial).'&vr='.urlencode(self::getConfig('jianyu.version')).'&nm='.urlencode(self::getConfig('jianyu.name')));
                if($remind === false){
                    $remind = 0;
                }
            }
            self::setCache('jianyuremind',$remind,360000);
        }
        return $remind;
    }
    public static function getnm()
    {
        return self::bd('5YmR6bG86K665Z2b');
    }
    public static function getvn()
    {
        return self::getConfig(self::bd('amlhbnl1LnZlcnNpb24='));
    }
    public static function getGrowing()
    {
        $growing = self::getCache('growingup');
        if($growing === false){
            $growing = [
                'chengzhang' => [],
                'jifen' => []
            ];
            $growingarr = self::db('chengzhang')->select();
            foreach($growingarr as $key => $val){
                $growing['chengzhang'][$val['czname']] = $val['chengzhang'];
                $growing['jifen'][$val['czname']] = $val['jifen'];
            }
            self::setCache('growingup',$growing,86400);
        }
        return $growing;
    }
    public static function differ($dom)
    {
        $jianyudiffer = self::getCache('jianyudiffer');
        if($jianyudiffer == false){
            $jianyudiffer = strtotime(self::get('creationtime'));
            self::setCache('jianyudiffer',$jianyudiffer,36000);
        }
        $differ = 0;
        if(time() - $jianyudiffer > 31536000 && self::isDomain($dom)){
            $differ = 1;
        }
        return $differ;
    }
    public static function requestController()
    {
        return Request::instance()->controller();
    }
    public static function toError()
    {
        self::redirect('index/Index/error');
    }
    public static function cj($tp)
    {
        if(stripos(file_get_contents(str_replace('/', DS, $tp.self::bd('L2Zvb3Rlci5odG1s'))), self::bd('eyRqaWFueXVsdW50YW59')) === false){
            return false;
        }
        return true;
    }
    public static function iszero($in)
    {
        if($in == 0){
            return true;
        }
        return false;
    }
    public static function sjbdz()
    {
        $sjbdz = self::getCache('jianyuluntansjbdz');
        if($sjbdz === false){
            $dom = self::get('domain');
            $serial = self::get('serial');
            if(empty($serial)){
                $serial = '';
            }
            $sjbdz = self::curl('http://jianyuluntan.com/download/?dm='.$dom.'&se='.md5($serial));
            if(empty($sjbdz)){
                $sjbdz = [];
            }
            else{
                $sjbdz = json_decode($sjbdz, true);
            }
            self::setCache('jianyuluntansjbdz',$sjbdz,172800);
        }
        return $sjbdz;
    }
    public static function tongji($field)
    {
        $tongji = self::db('tongji')->order('id desc')->field('id,riqi')->find();
        $id = $tongji['id'];
        $today = date("Y-m-d");
        if($tongji['riqi'] != $today){
            $id = self::db('tongji')->insertGetId([
                'riqi' => $today
            ]);
        }
        self::db('tongji')->where('id', $id)->update([
            $field => self::dbRaw($field.'+1')
        ]);
    }
    public static function begin()
    {
        Debug::remark('begin');
    }
    public static function getRunTime()
    {
        Debug::remark('end');
        return Debug::getRangeTime('begin', 'end', 4).'s';
    }
    public static function urldc($str)
    {
        return urldecode(str_replace('+', '%2B', $str));
    }
    public static function sendmail($to, $toname, $subject, $body, $altbody = '', $from = '', $fromname = '', $host = '', $port = 25, $user = '', $password = '', $secure = 'tls', $auth = true)
    {
        if(empty($host)){
            $estis = unserialize(self::get('emailsettings'));
            if($estis == false){
                return false;
            }
            $host = trim($estis['host']);
            $port = intval(trim($estis['port']));
            $user = trim($estis['user']);
            $password = $estis['password'];
            $secure = trim($estis['secure']);
            $auth = (bool)$estis['auth'];
        }
        if(empty($from)){
            $from = $user;
        }
        if(empty($fromname)){
            $fromname = self::get('title');
        }
        if(empty($altbody)){
            $altbody = strip_tags($body);
        }
        $mail = new PHPMailer();
        try {
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = $auth;
            $mail->Username = $user;
            $mail->Password = $password;
            $mail->SMTPSecure = $secure;
            $mail->Port = $port;
            $mail->setFrom($from, $fromname);
            $mail->addAddress($to, $toname);
            $mail->addReplyTo($from, $fromname);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $altbody;
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    public static function shixian($comtime, $duration)
    {
        if(strtolower($duration) == 'immediately'){
            return true;
        }
        $totime = strtotime('+'.$duration, strtotime($comtime));
        if($totime > time()){
            return false;
        }
        else{
            return true;
        }
    }
    public static function getRandom()
    {
        $random = self::getCache('random');
        if($random === false){
            $random = self::get('random');
            self::setCache('random', $random, 86400);
        }
        return $random;
    }
    public static function hasGet($name)
    {
        return Request::instance()->has($name, 'get');
    }
    public static function hasPost($name)
    {
        return Request::instance()->has($name, 'post');
    }
}