<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * Licensed: catfish cms licenses ( http://www.catfish-cms.com )
 * Copyright: http://www.jianyuluntan.com All rights reserved.
 */
function subtext($text, $length)
{
    if(mb_strlen($text, 'utf8') > $length)
        return mb_substr($text, 0, $length, 'utf8').'...';
    return $text;
}
function suffix($text, $suffix = '')
{
    if(!empty($text)){
        return $text . $suffix;
    }
    return $text;
}
function prefix($text, $prefix = '')
{
    if(!empty($text)){
        return $prefix . $text;
    }
    return $text;
}