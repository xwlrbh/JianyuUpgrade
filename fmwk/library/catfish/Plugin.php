<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://jianyujishu.com All rights reserved.
 */
namespace catfishcms;
class Plugin
{
    public static function add(&$params, $name, $alias = '', $func = '', $way = 'append')
    {
        $calltrace = debug_backtrace();
        $call = basename(dirname($calltrace[0]['file']));
        if(is_array($name)){
            foreach($name as $key => $val){
                $params['item'][] = [
                    'plugin' => $call,
                    'name' => $val['name'],
                    'alias' => $val['alias'],
                    'function' => $val['function'],
                    'way' => $way
                ];
            }
        }
        else{
            $params['item'][] = [
                'plugin' => $call,
                'name' => $name,
                'alias' => $alias,
                'function' => $func,
                'way' => $way
            ];
        }
    }
    public static function pluginOutput($templateFile)
    {
        $calltrace = debug_backtrace();
        $call = basename(dirname($calltrace[0]['file']));
        if(substr($templateFile, -5) != '.html'){
            $templateFile .= '.html';
        }
        $templateFile = ltrim(ltrim($templateFile, '/'), '\\');
        return Catfish::output(ROOT_PATH . 'plugins/' . $call . '/' . $templateFile);
    }
    public static function pluginCss($cssFile)
    {
        $calltrace = debug_backtrace();
        $call = basename(dirname($calltrace[0]['file']));
        $recss = '';
        if(is_array($cssFile)){
            foreach($cssFile as $key => $val){
                if(substr($val, -4) != '.css'){
                    $val .= '.css';
                }
                $recss .= file_get_contents(ROOT_PATH . 'plugins/' . $call . '/' . $val);
            }
        }
        else{
            if(substr($cssFile, -4) != '.css'){
                $cssFile .= '.css';
            }
            $recss .= file_get_contents(ROOT_PATH . 'plugins/' . $call . '/' . $cssFile);
        }
        return '<style>' . $recss . '</style>';
    }
    public static function pluginJs($jsFile)
    {
        $calltrace = debug_backtrace();
        $call = basename(dirname($calltrace[0]['file']));
        $rejs = '';
        if(is_array($jsFile)){
            foreach($jsFile as $key => $val){
                if(substr($val, -3) != '.js'){
                    $val .= '.js';
                }
                $rejs .= file_get_contents(ROOT_PATH . 'plugins/' . $call . '/' . $val);
            }
        }
        else{
            if(substr($jsFile, -3) != '.js'){
                $jsFile .= '.js';
            }
            $rejs .= file_get_contents(ROOT_PATH . 'plugins/' . $call . '/' . $jsFile);
        }
        return '<script>' . $rejs . '</script>';
    }
    public static function pluginAssign($name, $value = '')
    {
        return Catfish::allot('p_' . $name, $value);
    }
    public static function themeOutput($templateName, $templateFile)
    {
        if(substr($templateFile, -5) != '.html'){
            $templateFile .= '.html';
        }
        return Catfish::output(ROOT_PATH . 'public/theme/' . $templateName . '/theme/' . $templateFile);
    }
    public static function themeAssign($name, $value = '')
    {
        return Catfish::allot('t_' . $name, $value);
    }
    public static function get($key)
    {
        $key = 'p_' . $key;
        $option = Catfish::getCache('jianyu_plugin_options_'.$key);
        if($option === false){
            $option = Catfish::db('options')->where('option_name',$key)->field('option_value')->find();
            Catfish::setCache('jianyu_plugin_options_'.$key,$option,3600);
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
    public static function delete($key)
    {
        $key = 'p_' . $key;
        Catfish::db('options')
            ->where('option_name',$key)
            ->delete();
        Catfish::removeCache('jianyu_plugin_options_'.$key);
    }
    public static function set($key,$value,$protection = false)
    {
        $key = 'p_' . $key;
        $re = Catfish::db('options')->where('option_name',$key)->field('option_value')->find();
        if(empty($re))
        {
            $data = [
                'option_name' => $key,
                'option_value' => $value,
                'autoload' => 0
            ];
            Catfish::db('options')->insert($data);
        }
        else
        {
            if($protection == false)
            {
                Catfish::db('options')
                    ->where('option_name', $key)
                    ->update(['option_value' => $value]);
            }
        }
        Catfish::removeCache('jianyu_plugin_options_'.$key);
    }
    public static function toraw($str)
    {
        return htmlspecialchars_decode($str);
    }
    public static function getPost($param = '', $filter = true)
    {
        Catfish::getPost($param, $filter, false);
    }
    public static function getGet($param = '', $filter = true)
    {
        Catfish::getGet($param, $filter);
    }
    public static function uploadImg()
    {
        $file = request()->file('file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            $position = 'data/uploads/'.str_replace('\\','/',$info->getSaveName());
            return Catfish::domain().$position;
        }else{
            return false;
        }
    }
}