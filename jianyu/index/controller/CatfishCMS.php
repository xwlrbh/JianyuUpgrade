<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://jianyuluntan.com All rights reserved.
 */
namespace app\index\controller;
use catfishcms\Catfish;
class CatfishCMS
{
    protected $template = 'default';
    protected $tempPath = 'public/theme/';
    protected $time = 1200;
    private $everyPageShows = 20;
    private $postEveryPageShows = 20;
    private $domain;
    private $root;
    protected function readydisplay($loadoptions = true)
    {
        if(!is_file(APP_PATH . 'database.php')){
            Catfish::redirect(Catfish::oUrl('install/Index/index'));
            exit();
        }
        Catfish::begin();
        Catfish::setAllowLangList(['zh-cn']);
        if($loadoptions){
            $this->options();
            $lang = Catfish::detectLang();
            if(is_file(ROOT_PATH.'public/theme/'.$this->template.'/lang/'.$lang.'.php')){
                Catfish::loadLang(ROOT_PATH.'public/theme/'.$this->template.'/lang/'.$lang.'.php');
            }
        }
        $this->autologin();
    }
    private function options()
    {
        $data_options = Catfish::getCache('options');
        if($data_options === false){
            $data_options = Catfish::autoload();
            Catfish::setCache('options',$data_options,$this->time);
        }
        $islogo = 0;
        $isicon = 0;
        $crtm = date("Y");
        $title = '';
        foreach($data_options as $key => $val)
        {
            Catfish::allot('guanbizhuce', 0);
            if($val['name'] == 'statistics')
            {
                $statistics = unserialize($val['value']);
                $params = [
                    'statistics' => $statistics
                ];
                $this->plantHook('statistics', $params);
                if(isset($params['statistics'])){
                    $statistics = $params['statistics'];
                }
                Catfish::allot($val['name'], $statistics);
            }
            elseif($val['name'] == 'title'){
                $title = $val['value'];
                Catfish::allot($val['name'], $val['value']);
            }
            elseif($val['name'] == 'crt')
            {
                $crt = Catfish::iszero(Catfish::remind()) ? Catfish::bd(implode('', unserialize($val['value']))) : '';
                Catfish::allot(Catfish::bd('emhpY2hp'), $crt);
            }
            elseif($val['name'] == 'creationtime')
            {
                $ctm = date("Y", strtotime($val['value']));
                if($ctm != $crtm){
                    $crtm = $ctm . '&nbsp;-&nbsp;' . $crtm;
                }
            }
            elseif($val['name'] == 'serial')
            {
                unset($data_options[$key]);
            }
            elseif($val['name'] == 'cyrt')
            {
                Catfish::allot('copyright', str_replace(['date', '5YmR6bG86K665Z2b', '54mI5p2D5omA5pyJ'], [date("Y"), $title, Catfish::bd('54mI5p2D5omA5pyJ')], unserialize($val['value'])));
            }
            elseif($val['name'] == 'domain'){
                $this->domain = Catfish::domainAmend($val['value']);
                Catfish::allot($val['name'], $this->domain);
                $root = $this->domain;
                $dm = $this->geturl('/');
                if(strpos($dm,'/index.php') !== false)
                {
                    $root .= 'index.php/';
                }
                $this->root = $root;
                Catfish::allot('root', $root);
            }
            elseif($val['name'] == 'template'){
                $this->template = $val['value'];
                Catfish::allot($val['name'], $val['value']);
            }
            elseif($val['name'] == 'logo'){
                $islogo = empty($val['value']) ? 0 : 1;
                Catfish::allot($val['name'], $val['value']);
            }
            elseif($val['name'] == 'icon'){
                $isicon = empty($val['value']) ? 0 : 1;
                Catfish::allot($val['name'], $val['value']);
            }
            elseif($val['name'] == 'spare'){
                if(!empty($val['value'])){
                    $spare = unserialize($val['value']);
                    if(isset($spare['notfollow'])){
                        Catfish::allot('notfollow', $spare['notfollow']);
                    }
                    else{
                        Catfish::allot('notfollow', 0);
                    }
                    if(isset($spare['guanbizhuce'])){
                        Catfish::allot('guanbizhuce', $spare['guanbizhuce']);
                    }
                    else{
                        Catfish::allot('guanbizhuce', 0);
                    }
                }
                else{
                    Catfish::allot('notfollow', 0);
                }
            }
            else
            {
                Catfish::allot($val['name'], $val['value']);
            }
        }
        if($islogo == 0){
            Catfish::allot('logo', $this->domain.'public/common/images/jianyu.png');
        }
        if($isicon == 0){
            Catfish::allot('icon', $this->domain.'public/common/images/favicon.ico');
        }
        $qiandao = 0;
        $qiandaocookie = 'qiandao_' . Catfish::getSession('user_id');
        if(Catfish::hasSession('user_id') && Catfish::hasCookie($qiandaocookie) && Catfish::getCookie($qiandaocookie) == date("Y-m-d")){
            $qiandao = 1;
        }
        Catfish::allot('qiandao', $qiandao);
    }
    private function autologin()
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
    }
    protected function show($template = 'index.html', $sort = 0, $page = 'index')
    {
        if(strpos($template,'.') === false){
            $template .= '.html';
        }
        Catfish::allot('caidan', $this->getMenu($sort));
        Catfish::allot('mokuai', $this->getModule());
        Catfish::allot('huandeng', $this->getSlides());
        $this->getlinks($page);
        $this->tongji();
        $order = '';
        $getarr = Catfish::getGet();
        if(!empty($getarr)){
            unset($getarr['order']);
            foreach((array)$getarr as $key => $val){
                $order .= empty($order) ? $key.'='.$val : '&'.$key.'='.$val;
            }
        }
        $urlarr = Catfish::getCache('urlarr_'.$order);
        if($urlarr === false){
            $faceUrl = [];
            $faces = glob(ROOT_PATH.$this->tempPath.$this->template.DS.'face'.DS.'*.html');
            foreach($faces as $key => $val){
                $find = basename($val);
                $find = substr($find, 0, strrpos($find, '.'));
                $faceUrl[$find] = $this->geturl('index/Index/face', ['find' => $find]);
            }
            $urlarr = [
                'default' => empty($order) ? '?order=default' : '?'.$order.'&order=default',
                'reply' => empty($order) ? '?order=reply' : '?'.$order.'&order=reply',
                'release' => empty($order) ? '?order=release' : '?'.$order.'&order=release',
                'latest' => empty($order) ? '?order=latest' : '?'.$order.'&order=latest',
                'shouye' => $this->geturl('index/Index/index'),
                'postzan' => $this->geturl('index/Index/postzan'),
                'postcai' => $this->geturl('index/Index/postcai'),
                'postshoucang' => $this->geturl('index/Index/postshoucang'),
                'gentiezan' => $this->geturl('index/Index/gentiezan'),
                'gentiecai' => $this->geturl('index/Index/gentiecai'),
                'denglu' => $this->geturl('login/Index/index'),
                'adenglu' => $this->geturl('login/Index/denglu'),
                'zhuce' => $this->geturl('login/Index/register'),
                'tuichu' => $this->geturl('login/Index/quit'),
                'sousuo' => $this->geturl('index/Index/search'),
                'yonghuzhongxin' => $this->geturl('user/Index/index'),
                'gentie' => $this->geturl('index/Index/gentie'),
                'xiugai' => $this->geturl('index/Index/xiugai'),
                'shanchugentie' => $this->geturl('index/Index/shanchugentie'),
                'huifu' => $this->geturl('index/Index/huifu'),
                'fatie' => $this->geturl('user/Index/newpost'),
                'feedback' => $this->geturl('index/Index/feedback'),
                'qiandao' => $this->geturl('index/Index/qiandao'),
                'fajiantie' => $this->geturl('index/Index/newpost'),
                'qiandaobiao' => $this->geturl('index/Index/qiandaobiao'),
                'jinriqiandao' => $this->geturl('index/Index/jinriqiandao'),
                'face' => $faceUrl
            ];
            Catfish::setCache('urlarr_'.$order,$urlarr,$this->time);
        }
        $isMobile = 0;
        if(Catfish::isMobile()){
            $isMobile = 1;
        }
        Catfish::allot('isMobile',$isMobile);
        Catfish::allot('url', $urlarr);
        Catfish::allot('isLogin', intval(Catfish::isLogin()));
        Catfish::allot('nicheng', Catfish::getNickname());
        Catfish::allot('myid', Catfish::getSession('user_id'));
        Catfish::allot('touxiang', Catfish::getSession('touxiang'));
        Catfish::allot(Catfish::bd('amlhbnl1bHVudGFu'), $this->push());
        $params = [
            'template' => $this->template
        ];
        $this->plantHook('all', $params);
        $footer = '';
        $this->plantHook('footer', $footer);
        Catfish::allot('footer', $footer);
        $sidetop = '';
        $this->plantHook('sidetop', $sidetop);
        Catfish::allot('sidetop', $sidetop);
        $sidebottom = '';
        $this->plantHook('sidebottom', $sidebottom);
        Catfish::allot('sidebottom', $sidebottom);
        $tempath = ROOT_PATH.$this->tempPath.$this->template;
        if(!Catfish::cj($tempath) && $template != '404.html'){
            Catfish::toError();
        }
        if($isMobile == 1 && is_file($tempath.'/mobile/'.$template)){
            $outfile = $tempath.'/mobile/'.$template;
        }
        else{
            $outfile = $tempath.'/'.$template;
        }
        if($template == '404.html' && !is_file($outfile)){
            $outfile = APP_PATH.'index/view/index/index.html';
        }
        Catfish::allot('runTime', Catfish::getRunTime());
        return Catfish::output($outfile);
    }
    protected function showpart($template = 'index.html')
    {
        if(strpos($template,'.') === false){
            $template .= '.html';
        }
        Catfish::allot('myid', Catfish::getSession('user_id'));
        Catfish::allot('isLogin', intval(Catfish::isLogin()));
        $isMobile = 0;
        if(Catfish::isMobile()){
            $isMobile = 1;
        }
        $tempath = ROOT_PATH.$this->tempPath.$this->template;
        if($isMobile == 1 && is_file($tempath.'/mobile/part/'.$template)){
            $outfile = $tempath.'/mobile/part/'.$template;
        }
        else{
            $outfile = $tempath.'/part/'.$template;
        }
        return Catfish::output($outfile);
    }
    private function getMenu($sort)
    {
        $caidan = Catfish::getCache('caidan_'.$sort);
        if($caidan === false){
            $caidan = Catfish::db('msort')
                ->field('id,sname,bieming,ismenu,icon,icons,islink,linkurl,parentid')
                ->order('listorder asc,id asc')
                ->select();
            if(is_array($caidan) && count($caidan) > 0){
                $caidan = Catfish::treeForHtml($caidan);
                $tmparr = [];
                foreach($caidan as $key => $val){
                    if($val['ismenu'] == 0){
                        $tmparr[$val['id']] = $val['parentid'];
                        unset($caidan[$key]);
                    }
                    else{
                        if($val['parentid'] != 0 && isset($tmparr[$val['parentid']])){
                            $caidan[$key]['parentid'] = $tmparr[$val['parentid']];
                        }
                        $caidan[$key]['name'] = empty($val['bieming']) ? $val['sname'] : $val['bieming'];
                        if($val['islink'] == 1){
                            $caidan[$key]['href'] = $val['linkurl'];
                        }
                        else{
                            $caidan[$key]['href'] = $this->geturl('index/Index/column',['find'=>$val['id']]);
                        }
                        if($sort == $val['id']){
                            $caidan[$key]['active'] = 1;
                        }
                        else{
                            $caidan[$key]['active'] = 0;
                        }
                        if(!empty($val['icons'])){
                            $caidan[$key]['icon'] = $val['icons'];
                        }
                        unset($caidan[$key]['icons']);
                        unset($caidan[$key]['sname']);
                        unset($caidan[$key]['bieming']);
                        unset($caidan[$key]['ismenu']);
                        unset($caidan[$key]['linkurl']);
                        unset($caidan[$key]['level']);
                    }
                }
                if(count($caidan) > 0){
                    $caidan = Catfish::tree($caidan);
                }
            }
            else{
                $caidan = [];
            }
            Catfish::tagCache('caidan')->set('caidan_'.$sort,$caidan,$this->time);
        }
        return $caidan;
    }
    private function getlinks($page)
    {
        $youlian = Catfish::getCache('youlian');
        if($youlian === false){
            $youlian = Catfish::db('links')
                ->field('id,dizhi,mingcheng,tubiao,target,miaoshu,shouye')
                ->where('status',1)
                ->order('listorder asc')
                ->select();
            foreach($youlian as $key => $val){
                if(!empty($val['tubiao'])){
                    $youlian[$key]['tubiao'] = Catfish::domain() . $val['tubiao'];
                }
            }
            Catfish::setCache('youlian',$youlian,$this->time);
        }
        foreach($youlian as $key => $val){
            if($page == 'index' && $val['shouye'] == 0){
                unset($youlian[$key]);
            }
            unset($youlian[$key]['shouye']);
        }
        Catfish::allot('youlian', $youlian);
    }
    protected function shouye()
    {
        $page = Catfish::getGet('page');
        if($page == false){
            $page = 0;
        }
        $order = Catfish::getGet('order');
        if($order == false){
            $order = '';
        }
        if($order == 'release'){
            $orderstr = '';
        }
        elseif($order == 'reply'){
            $orderstr = 'tie.commentime desc,';
        }
        else{
            $orderstr = 'tie.ordertime desc,';
        }
        $cachezongjilu = 'shouye_'.$order.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $cachename = 'shouye_'.$order.'_'.$page;
        $shouye = Catfish::getCache($cachename);
        if($shouye === false){
            $subQuery = $this->getfstop();
            $cachezhiding = 'shouye_'.$order.'_'.md5($subQuery).'_zhiding';
            $zhiding = Catfish::getCache($cachezhiding);
            if($zhiding === false){
                $zhiding = Catfish::view('tie','id,uid,sid,fabushijian,xiugai as xiugaishijian,biaoti,zhaiyao,isclose as jietie,lastvisit as zuijinfangwen,commentime as zuijinpinglun,luid,pinglunshu as gentieliang,yuedu,zan,cai,shoucang,cangtime as zuijinshoucang,fstop as zhiding,fsrecommended as tuijian,jingpin,tietype as leixing,annex as daifujian,video as daishipin,shipin,tu,pinglun,jifenleixing,jinbileixing,huiyuanleixing,zhifufangshi')
                    ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie.uid')
                    ->where('tie.id','in',$subQuery)
                    ->where('tie.status','=',1)
                    ->where('tie.review','=',1)
                    ->where('tie.chentie','=',0)
                    ->order($orderstr.'tie.id desc')
                    ->select();
                $zhiding = $this->filterResults($zhiding);
                Catfish::tagCache('shouye')->set($cachezhiding,$zhiding,$this->time);
            }
            $shouye['zhiding'] = $zhiding;
            $data = Catfish::view('tie','id,uid,sid,fabushijian,xiugai as xiugaishijian,biaoti,zhaiyao,isclose as jietie,lastvisit as zuijinfangwen,commentime as zuijinpinglun,luid,pinglunshu as gentieliang,yuedu,zan,cai,shoucang,cangtime as zuijinshoucang,fstop as zhiding,fsrecommended as tuijian,jingpin,tietype as leixing,annex as daifujian,video as daishipin,shipin,tu,pinglun,jifenleixing,jinbileixing,huiyuanleixing,zhifufangshi')
                ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie.uid')
                ->where('tie.status','=',1)
                ->where('tie.review','=',1)
                ->where('tie.fstop','=',0)
                ->where('tie.chentie','=',0)
                ->order($orderstr.'tie.id desc')
                ->paginate($this->everyPageShows,$zongjilu,[
                    'query' => [
                        'order' => $order
                    ]
                ]);
            if($zongjilu === false){
                $zongjilu = $data->total();
                Catfish::tagCache('shouye')->set($cachezongjilu,$zongjilu,$this->time);
            }
            $shouye['tie'] = $this->filterResults($data->items());
            $pages= $data->render();
            if(empty($pages)){
                $pages = '';
            }
            $shouye['pages'] = $pages;
            Catfish::tagCache('shouye')->set($cachename,$shouye,$this->time);
        }
        $params = [
            'template' => $this->template,
            'shouye' => $shouye
        ];
        $this->plantHook('index', $params);
        $indextop = '';
        $this->plantHook('indextop', $indextop);
        Catfish::allot('indextop', $indextop);
        $indexbottom = '';
        $this->plantHook('indexbottom', $indexbottom);
        Catfish::allot('indexbottom', $indexbottom);
        Catfish::allot('jianyu', $params['shouye']);
    }
    protected function getcolumn($find)
    {
        $find = intval($find);
        $subSort = Catfish::getCache('subsortcache_'.$find);
        if($subSort === false){
            $fenleiarr = $this->getSortCache('id,sname,bieming,guanjianzi,description,icon,parentid');
            $ctl = false;
            $levelen = 0;
            $subSort['idstr'] = '';
            $subSort['biaoti'] = '';
            $subSort['keyword'] = '';
            $subSort['description'] = '';
            $pathSort = [];
            foreach($fenleiarr as $key => $val){
                if($ctl == false){
                    if($val['id'] == $find){
                        $ctl = true;
                        $levelen = strlen($val['level']);
                        $subSort['idstr'] = (string)$val['id'];
                        $subSort['biaoti'] = empty($val['bieming']) ? $val['sname'] : $val['bieming'];
                        $subSort['keyword'] = empty($val['guanjianzi']) ? $val['sname'] : $val['guanjianzi'];
                        $subSort['description'] = $val['description'];
                    }
                    $tmpend = end($pathSort);
                    while($tmpend !== false && strlen($tmpend['level']) >= strlen($val['level'])){
                        array_pop($pathSort);
                        $tmpend = end($pathSort);
                    }
                    array_push($pathSort, $val);
                    continue;
                }
                if($ctl == true && strlen($val['level']) > $levelen){
                    $subSort['idstr'] .= ','.(string)$val['id'];
                    continue;
                }
                if($ctl == true && strlen($val['level']) <= $levelen){
                    break;
                }
            }
            $subSort['path'] = $pathSort;
            Catfish::tagCache('sortcache')->set('subsortcache_'.$find, $subSort, $this->time * 10);
        }
        $page = Catfish::getGet('page');
        if($page == false){
            $page = 0;
        }
        $order = Catfish::getGet('order');
        if($order == false){
            $order = '';
        }
        if($order == 'release'){
            $orderstr = '';
        }
        elseif($order == 'reply'){
            $orderstr = 'tie.commentime desc,';
        }
        else{
            $orderstr = 'tie.ordertime desc,';
        }
        $cachezongjilu = 'column_'.$order.'_'.$find.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $cachename = 'column_'.$order.'_'.$find.'_'.$page;
        $column = Catfish::getCache($cachename);
        if($column === false){
            $subQuery = $this->gettop($find, $subSort['idstr']);
            $cachezhiding = 'column_'.$order.'_'.$find.'_'.md5($subQuery).'_zhiding';
            $zhiding = Catfish::getCache($cachezhiding);
            if($zhiding === false){
                $zhiding = Catfish::view('tie','id,uid,sid,fabushijian,xiugai as xiugaishijian,biaoti,zhaiyao,isclose as jietie,lastvisit as zuijinfangwen,commentime as zuijinpinglun,luid,pinglunshu as gentieliang,yuedu,zan,cai,shoucang,cangtime as zuijinshoucang,istop as zhiding,recommended as tuijian,jingpin,tietype as leixing,annex as daifujian,video as daishipin,shipin,tu,pinglun,jifenleixing,jinbileixing,huiyuanleixing,zhifufangshi')
                    ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie.uid')
                    ->where('tie.id','in',$subQuery)
                    ->where('tie.status','=',1)
                    ->where('tie.review','=',1)
                    ->where('tie.chentie','=',0)
                    ->order($orderstr.'tie.id desc')
                    ->select();
                $zhiding = $this->filterResults($zhiding);
                Catfish::tagCache('column')->set($cachezhiding,$zhiding,$this->time);
            }
            $column['zhiding'] = $zhiding;
            $data = Catfish::view('tie','id,uid,sid,fabushijian,xiugai as xiugaishijian,biaoti,zhaiyao,isclose as jietie,lastvisit as zuijinfangwen,commentime as zuijinpinglun,luid,pinglunshu as gentieliang,yuedu,zan,cai,shoucang,cangtime as zuijinshoucang,istop as zhiding,recommended as tuijian,jingpin,tietype as leixing,annex as daifujian,video as daishipin,shipin,tu,pinglun,jifenleixing,jinbileixing,huiyuanleixing,zhifufangshi')
                ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie.uid')
                ->where('tie.sid','in',$subSort['idstr'])
                ->where('tie.status','=',1)
                ->where('tie.review','=',1)
                ->where('tie.istop','=',0)
                ->where('tie.chentie','=',0)
                ->order($orderstr.'tie.id desc')
                ->paginate($this->everyPageShows,$zongjilu,[
                    'query' => [
                        'order' => $order
                    ]
                ]);
            if($zongjilu === false){
                $zongjilu = $data->total();
                Catfish::tagCache('column')->set($cachezongjilu,$zongjilu,$this->time);
            }
            $column['tie'] = $this->filterResults($data->items());
            $pages= $data->render();
            if(empty($pages)){
                $pages = '';
            }
            $column['pages'] = $pages;
            Catfish::tagCache('column')->set($cachename,$column,$this->time);
        }
        $daohang[] = [
            'label' => Catfish::lang('Home'),
            'href' => $this->geturl('index/Index/index'),
            'icon' => '',
            'active' => 0
        ];
        foreach($subSort['path'] as $key => $val){
            $daohang[] = [
                'label' => empty($val['bieming']) ? $val['sname'] : $val['bieming'],
                'href' => $this->geturl('index/Index/column', ['find'=>$val['id']]),
                'icon' => $val['icon'],
                'active' => 0
            ];
        }
        $params = [
            'template' => $this->template,
            'biaoti' => $subSort['biaoti'],
            'keyword' => $subSort['keyword'],
            'description' => $subSort['description'],
            'daohang' => $daohang,
            'jianyu' => $column
        ];
        $this->plantHook('column', $params);
        $columntop = '';
        $this->plantHook('columntop', $columntop);
        Catfish::allot('columntop', $columntop);
        $columnbottom = '';
        $this->plantHook('columnbottom', $columnbottom);
        Catfish::allot('columnbottom', $columnbottom);
        $this->getidentity($find);
        Catfish::allot('biaoti', $params['biaoti']);
        Catfish::allot('keyword', $params['keyword']);
        Catfish::allot('description', $params['description']);
        Catfish::allot('daohang', $params['daohang']);
        Catfish::allot('jianyu', $params['jianyu']);
    }
    protected function getpost($find)
    {
        $find = intval($find);
        $cachename = 'post_'.$find;
        $post = Catfish::getCache($cachename);
        if($post === false){
            $post = Catfish::view('tie','id,uid,sid,guanjianzi,fabushijian,xiugai as xiugaishijian,biaoti,zhaiyao,closecomment as guanbipinglun,isclose as jietie,lastvisit as zuijinfangwen,pinglunshu as gentieliang,yuedu,zan,cai,shoucang,cangtime as zuijinshoucang,istop as zhiding,recommended as tuijian,jingpin,tietype as leixing,annex as daifujian,video as daishipin,shipin,jifenleixing,jifen,jinbileixing,jinbi,huiyuanleixing,zhifufangshi')
                ->view('tienr','laiyuan,zhengwen,fujian,fujianming,fjsize as daxiao','tienr.tid=tie.id')
                ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie,jingpin as jingpinliang,chengzhang','users.id=tie.uid')
                ->where('tie.id','=',$find)
                ->where('tie.status','=',1)
                ->where('tie.review','=',1)
                ->find();
            if(!empty($post)){
                $this->filterPost($post);
                $fenleiarr = $this->getSortCache('id,sname,bieming,icon,parentid');
                $pathSort = [];
                foreach($fenleiarr as $key => $val){
                    $tmpend = end($pathSort);
                    while($tmpend !== false && strlen($tmpend['level']) >= strlen($val['level'])){
                        array_pop($pathSort);
                        $tmpend = end($pathSort);
                    }
                    array_push($pathSort, $val);
                    if($val['id'] == $post['sid']){
                        break;
                    }
                }
                $post['path'] = $pathSort;
            }
            Catfish::tagCache('post')->set($cachename,$post,$this->time);
        }
        if(!empty($post)){
            $modify = '';
            if(Catfish::hasSession('user_id') && Catfish::getSession('user_id') == $post['uid']){
                $modify = $this->geturl('user/Index/modifymainpost') . '?c=' . $post['id'] . '&jumpto=' . urlencode($this->geturl('index/Index/post', ['find' => $find]));
            }
            $post['xiugai'] = $modify;
        }
        $page = Catfish::getGet('page');
        if($page == false){
            $page = 0;
        }
        $order = Catfish::getGet('order');
        if($order == false){
            $order = '';
        }
        $feedback = 0;
        if(!empty($post) && $post['uid'] != Catfish::getSession('user_id') && $page == 0 && $order == ''){
            $post = Catfish::getCache($cachename);
            $post['yuedu'] ++;
            Catfish::tagCache('post')->set($cachename,$post,$this->time);
            $feedback = 1;
        }
        Catfish::allot('feedback', $feedback);
        if($order == 'latest'){
            $orderstr = 'id desc';
        }
        else{
            $orderstr = 'id asc';
        }
        $cachezongjilu = 'postgentie_'.$find.'_'.$order.'_zongjilu';
        $total = Catfish::getCache($cachezongjilu);
        $subcachename = $order.'_'.$page;
        $cachename = 'postgentie_'.$find.'_'.$subcachename;
        $gentie = Catfish::getCache($cachename);
        if($gentie === false){
            $cdata = Catfish::db('tie_comm_ontact')
                ->where('tid', $find)
                ->where('status',1)
                ->field('cid')
                ->order($orderstr)
                ->paginate($this->postEveryPageShows,$total,[
                    'query' => [
                        'order' => $order
                    ]
                ]);
            $currentPage = $cdata->currentPage();
            if($total === false){
                $total = $cdata->total();
                Catfish::tagCache('postgentie_'.$find)->set($cachezongjilu,$total,$this->time);
            }
            if($order == 'latest'){
                $lou = ($total + 1) - ($currentPage - 1) * $this->postEveryPageShows;
            }
            else{
                $lou = ($currentPage - 1) * $this->postEveryPageShows + 1;
            }
            $tmpstr = '';
            $tmpc = $cdata->items();
            foreach($tmpc as $val){
                $tmpstr .= empty($tmpstr) ? $val['cid'] : ','.$val['cid'];
            }
            $pldata = Catfish::view('tie_comments','id,uid,createtime as gentieshijian,xiugai as xiugaishijian,parentid,zan,cai,content as neirong,yuanyin as xiugaiyuanyin')
                ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie_comments.uid')
                ->where('tie_comments.id','in',$tmpstr)
                ->where('tie_comments.status','=',1)
                ->order('tie_comments.'.$orderstr)
                ->select();
            $pustr = '';
            foreach($pldata as $key => $val){
                if($val['parentid'] != 0){
                    $pustr .= ($pustr == '') ? $val['parentid'] : ','.$val['parentid'];
                }
                $pldata[$key] = $this->filtergentie($val);
                if($order == 'latest'){
                    $pldata[$key]['lou'] = $lou -- ;
                }
                else{
                    $pldata[$key]['lou'] = ++ $lou;
                }
            }
            $puidarr = [];
            if(!empty($pustr)){
                $pustrarr = array_unique(explode(',', $pustr));
                $pustr = implode(',', $pustrarr);
                $puiddata = Catfish::view('tie_comments','id,uid,createtime as gentieshijian,content as neirong')
                    ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie_comments.uid')
                    ->where('tie_comments.id','in',$pustr)
                    ->where('tie_comments.status','=',1)
                    ->select();
                foreach($puiddata as $key => $val){
                    $puidarr[$val['id']] = $this->filterplr($val);
                }
            }
            foreach($pldata as $key => $val){
                if(isset($puidarr[$val['parentid']])){
                    $pldata[$key]['beihuifu'] = $puidarr[$val['parentid']];
                }
                else{
                    $pldata[$key]['beihuifu'] = [];
                }
            }
            $gentie['tie'] = $pldata;
            $gentie['zongliang'] = intval($total);
            $gentie['dangqianye'] = intval($currentPage);
            $gentie['zuidalou'] = intval($total + 1);
            $gentie['subcname'] = base64_encode($subcachename);
            $pages= $cdata->render();
            if(empty($pages)){
                $pages = '';
            }
            $gentie['pages'] = $pages;
            Catfish::tagCache('postgentie_'.$find)->set($cachename,$gentie,$this->time);
        }
        if(!empty($post)){
            $quanxian = $this->quanxian($find);
            $ispaid = 1;
            $ispaidown = 1;
            $ispaidsp = 1;
            if($quanxian['mianfu'] == 0){
                $zhifufangshi = $post['zhifufangshi'];
                $ispaid = 0;
                $leixing = 0;
                $shuliang = 0;
                if($zhifufangshi == 1){
                    $leixing = $post['jifenleixing'];
                    $shuliang = $post['jifen'];
                }
                elseif($zhifufangshi == 2){
                    $leixing = $post['jinbileixing'];
                    $shuliang = $post['jinbi'];
                }
                if($leixing == 1 && $shuliang > 0 && !in_array($post['zhifufangshi'], [0,3])){
                    if(Catfish::hasSession('user_id')){
                        $uid = Catfish::getSession('user_id');
                        if($uid == $post['uid']){
                            $ispaid = 1;
                        }
                        else{
                            $paid = Catfish::db('tie_jifen')->where('uid', $uid)->where('tid', $post['id'])->field('id')->limit(1)->find();
                            if(!empty($paid)){
                                $ispaid = 1;
                            }
                        }
                    }
                }
                elseif($post['zhifufangshi'] == 3 && $post['huiyuanleixing'] == 1){
                    if(Catfish::hasSession('user_id')){
                        $uid = Catfish::getSession('user_id');
                        if($uid == $post['uid']){
                            $ispaid = 1;
                        }
                        else{
                            $ispaid = 2;
                        }
                    }
                    else{
                        $ispaid = 2;
                    }
                }
                else{
                    $ispaid = 1;
                }
                if($ispaid == 0){
                    $yuyan = $zhifufangshi == 1 ? Catfish::lang(' points to read this post') : Catfish::lang(' forum coins to read this post');
                    $post['zhengwen'] = Catfish::lang('You need to pay ') . $shuliang . $yuyan;
                    if(!Catfish::hasSession('user_id')){
                        $post['zhengwen'] .= ' <a href="' . $this->geturl('login/Index/index') . '?jumpto=' . urlencode($this->geturl('index/Index/post', ['find' => $post['id']])) . '">' . Catfish::lang('Please log in first') . '</a>';
                    }
                    else{
                        $post['zhengwen'] .= ' <button type="button" id="paypoints" class="btn btn-light">' . Catfish::lang('Pay immediately') . '<i class="fa fa-refresh fa-spin ml-2 d-none"></i></button><span id="paypointsresult" style="color:#dd0000;margin-left:10px"></span><script src="' . Catfish::domain() . 'public/common/js/paypoints.js"></script>';
                    }
                }
                elseif($ispaid == 2){
                    if(!Catfish::hasSession('user_id')){
                        $post['zhengwen'] = Catfish::lang('VIP members can access') . ' <a href="' . $this->geturl('login/Index/index') . '?jumpto=' . urlencode($this->geturl('index/Index/post', ['find' => $post['id']])) . '">' . Catfish::lang('Please log in first') . '</a>';
                    }
                    else{
                        $post['zhengwen'] = Catfish::lang('VIP members can access') . ' <a href="' . $this->geturl('user/Index/vipmember') . '?jumpto=' . urlencode($this->geturl('index/Index/post', ['find' => $post['id']])) . '">' . Catfish::lang('Upgrade to VIP member immediately') . '</a>';
                    }
                }
                $post['liulan'] = $quanxian['liulan'];
                if($quanxian['liulan'] == 1){
                    $post['zhengwen'] = Catfish::lang('Log in to view posts');
                }
                if(isset($post['daifujian']) && $post['daifujian'] == 1){
                    $ispaidown = 1;
                    if($ispaid != 1){
                        $ispaidown = 0;
                        $post['fujianurl'] = '';
                    }
                    elseif($quanxian['fujian'] == 1){
                        $post['fujianurl'] = Catfish::lang('You can download attachments after logging in');
                    }
                    elseif($quanxian['fujian'] == 2){
                        $post['fujianurl'] = Catfish::lang('After replying, you can download attachments');
                    }
                    else{
                        if($leixing == 2 && $shuliang > 0 && !in_array($post['zhifufangshi'], [0,3])){
                            $ispaidown = 0;
                            if(Catfish::hasSession('user_id')){
                                $uid = Catfish::getSession('user_id');
                                if($uid == $post['uid']){
                                    $ispaidown = 1;
                                }
                                else{
                                    $paid = Catfish::db('tie_jifen')->where('uid', $uid)->where('tid', $post['id'])->field('id')->limit(1)->find();
                                    if(!empty($paid)){
                                        $ispaidown = 1;
                                    }
                                }
                            }
                        }
                        elseif($post['zhifufangshi'] == 3 && $post['huiyuanleixing'] == 2){
                            if(Catfish::hasSession('user_id')){
                                $uid = Catfish::getSession('user_id');
                                if($uid == $post['uid']){
                                    $ispaidown = 1;
                                }
                                else{
                                    $ispaidown = 2;
                                }
                            }
                            else{
                                $ispaidown = 2;
                            }
                        }
                        if($ispaidown == 0){
                            $yuyan = $zhifufangshi == 1 ? Catfish::lang(' points to download the attachment') : Catfish::lang(' forum coins to download the attachment');
                            $post['fujianurl'] = Catfish::lang('You need to pay ') . $shuliang . $yuyan;
                            if(!Catfish::hasSession('user_id')){
                                $post['fujianurl'] .= ' <a href="' . $this->geturl('login/Index/index') . '?jumpto=' . urlencode($this->geturl('index/Index/post', ['find' => $post['id']])) . '">' . Catfish::lang('Please log in first') . '</a>';
                            }
                            else{
                                $post['fujianurl'] .= ' <button type="button" id="paypoints" class="btn btn-light">' . Catfish::lang('Pay immediately') . '<i class="fa fa-refresh fa-spin ml-2 d-none"></i></button><span id="paypointsresult" style="color:#dd0000;margin-left:10px"></span><script src="' . Catfish::domain() . 'public/common/js/paypoints.js"></script>';
                            }
                        }
                        elseif($ispaidown == 2){
                            if(!Catfish::hasSession('user_id')){
                                $post['fujianurl'] = Catfish::lang('Only VIP members can download attachments') . ' <a href="' . $this->geturl('login/Index/index') . '?jumpto=' . urlencode($this->geturl('index/Index/post', ['find' => $post['id']])) . '">' . Catfish::lang('Please log in first') . '</a>';
                            }
                            else{
                                $post['fujianurl'] = Catfish::lang('Only VIP members can download attachments') . ' <a href="' . $this->geturl('user/Index/vipmember') . '?jumpto=' . urlencode($this->geturl('index/Index/post', ['find' => $post['id']])) . '">' . Catfish::lang('Upgrade to VIP member immediately') . '</a>';
                            }
                        }
                    }
                }
                if(isset($post['daishipin']) && $post['daishipin'] == 1){
                    $ispaidsp = 1;
                    if($ispaid != 1){
                        $ispaidsp = 0;
                        $post['shipin'] = '';
                    }
                    elseif($quanxian['shipin'] == 1){
                        $post['shipin'] = Catfish::lang('You can view the video after logging in');
                    }
                    elseif($quanxian['shipin'] == 2){
                        $post['shipin'] = Catfish::lang('You can view the video after replying');
                    }
                    else{
                        if($leixing == 3 && $shuliang > 0 && !in_array($post['zhifufangshi'], [0,3])){
                            $ispaidsp = 0;
                            if(Catfish::hasSession('user_id')){
                                $uid = Catfish::getSession('user_id');
                                if($uid == $post['uid']){
                                    $ispaidsp = 1;
                                }
                                else{
                                    $paid = Catfish::db('tie_jifen')->where('uid', $uid)->where('tid', $post['id'])->field('id')->limit(1)->find();
                                    if(!empty($paid)){
                                        $ispaidsp = 1;
                                    }
                                }
                            }
                        }
                        elseif($post['zhifufangshi'] == 3 && $post['huiyuanleixing'] == 3){
                            if(Catfish::hasSession('user_id')){
                                $uid = Catfish::getSession('user_id');
                                if($uid == $post['uid']){
                                    $ispaidsp = 1;
                                }
                                else{
                                    $ispaidsp = 2;
                                }
                            }
                            else{
                                $ispaidsp = 2;
                            }
                        }
                        if($ispaidsp == 0){
                            $yuyan = $zhifufangshi == 1 ? Catfish::lang(' points to watch the video') : Catfish::lang(' forum coins to watch the video');
                            $post['shipin'] = Catfish::lang('You need to pay ') . $shuliang . $yuyan;
                            if(!Catfish::hasSession('user_id')){
                                $post['shipin'] .= ' <a href="' . $this->geturl('login/Index/index') . '?jumpto=' . urlencode($this->geturl('index/Index/post', ['find' => $post['id']])) . '">' . Catfish::lang('Please log in first') . '</a>';
                            }
                            else{
                                $post['shipin'] .= ' <button type="button" id="paypoints" class="btn btn-light">' . Catfish::lang('Pay immediately') . '<i class="fa fa-refresh fa-spin ml-2 d-none"></i></button><span id="paypointsresult" style="color:#dd0000;margin-left:10px"></span><script src="' . Catfish::domain() . 'public/common/js/paypoints.js"></script>';
                            }
                        }
                        elseif($ispaidsp == 2){
                            if(!Catfish::hasSession('user_id')){
                                $post['shipin'] = Catfish::lang('VIP members can watch the video') . ' <a href="' . $this->geturl('login/Index/index') . '?jumpto=' . urlencode($this->geturl('index/Index/post', ['find' => $post['id']])) . '">' . Catfish::lang('Please log in first') . '</a>';
                            }
                            else{
                                $post['shipin'] = Catfish::lang('VIP members can watch the video') . ' <a href="' . $this->geturl('user/Index/vipmember') . '?jumpto=' . urlencode($this->geturl('index/Index/post', ['find' => $post['id']])) . '">' . Catfish::lang('Upgrade to VIP member immediately') . '</a>';
                            }
                        }
                    }
                }
            }
            $post['yueduyifujifen'] = $ispaid;
            $post['xiazaiyifujifen'] = $ispaidown;
            $post['shipinyifujifen'] = $ispaidsp;
            $this->getidentity($post['sid']);
        }
        $jianyu['zhutie'] = $post;
        $jianyu['gentie'] = $gentie;
        $jianyu['quanxian'] = $this->myforumpost();
        $daohang[] = [
            'label' => Catfish::lang('Home'),
            'href' => $this->geturl('index/Index/index'),
            'icon' => '',
            'active' => 0
        ];
        if(isset($post['path'])){
            foreach($post['path'] as $key => $val){
                $daohang[] = [
                    'label' => empty($val['bieming']) ? $val['sname'] : $val['bieming'],
                    'href' => $this->geturl('index/Index/column', ['find'=>$val['id']]),
                    'icon' => $val['icon'],
                    'active' => 0
                ];
            }
            $daohang[] = [
                'label' => $post['biaoti'],
                'href' => $this->geturl('index/Index/post', ['find'=>$post['id']]),
                'icon' => '',
                'active' => 0
            ];
        }
        $params = [
            'template' => $this->template,
            'biaoti' => isset($post['biaoti']) ? $post['biaoti'] : '',
            'keyword' => isset($post['guanjianzi']) ? $post['guanjianzi'] : '',
            'description' => isset($post['zhaiyao']) ? $post['zhaiyao'] : '',
            'daohang' => $daohang,
            'jianyu' => $jianyu
        ];
        $this->plantHook('post', $params);
        Catfish::allot('biaoti', $params['biaoti']);
        Catfish::allot('keyword', $params['keyword']);
        Catfish::allot('description', $params['description']);
        Catfish::allot('jianyu', $params['jianyu']);
        Catfish::allot('daohang', $params['daohang']);
        $posttop = [
            'postId' => $find,
            'content' => ''
        ];
        $this->plantHook('posttop', $posttop);
        Catfish::allot('posttop', $posttop['content']);
        $postbottom = [
            'postId' => $find,
            'content' => ''
        ];
        $this->plantHook('postbottom', $postbottom);
        Catfish::allot('postbottom', $postbottom['content']);
        $commenttop = '';
        $this->plantHook('commenttop', $commenttop);
        Catfish::allot('commenttop', $commenttop);
        $commentbottom = '';
        $this->plantHook('commentbottom', $commentbottom);
        Catfish::allot('commentbottom', $commentbottom);
        Catfish::allot('needvcode', $this->needvcode());
        if(isset($post['path'])){
            $pfl = end($post['path']);
            return $pfl['id'];
        }
        else{
            return 0;
        }
    }
    protected function filterplr($plr)
    {
        $now = time();
        if(!empty($plr['touxiang'])){
            $plr['touxiang'] = Catfish::domain() . 'data/avatar/' . $plr['touxiang'];
        }
        $plr['shicha']['gentieshijian'] = $this->timedif($plr['gentieshijian'], $now);
        $plr['shicha']['jiaru'] = $this->timedif($plr['jiaru'], $now);
        $plr['shicha']['zuijindenglu'] = $this->timedif($plr['zuijindenglu'], $now);
        $plr['shicha']['zuijinzaixian'] = $this->timedif($plr['zuijinzaixian'], $now);
        $plr['gentieshijian'] = $this->decompositiontime($plr['gentieshijian']);
        $plr['jiaru'] = $this->decompositiontime($plr['jiaru']);
        $plr['zuijindenglu'] = $this->decompositiontime($plr['zuijindenglu']);
        $plr['zuijinzaixian'] = $this->decompositiontime($plr['zuijinzaixian']);
        $dengji = $this->getdjidname();
        $plr['dengjiming'] = $dengji[$plr['dengji']];
        if(empty($plr['touxiang'])){
            $plr['touxiang'] = Catfish::domain().'public/common/images/avatar.png';
        }
        return $plr;
    }
    private function filterResults($results)
    {
        if(is_array($results) && count($results) > 0){
            $now = time();
            $luids = '';
            $fenlei = $this->getsortidname(1);
            $leixing = $this->gettypeidname();
            $dengji = $this->getdjidname();
            $domain = Catfish::domain();
            $forum = Catfish::getForum();
            foreach($results as $key => $val){
                if(!empty($val['touxiang'])){
                    $results[$key]['touxiang'] = $domain . 'data/avatar/' . $val['touxiang'];
                }
                $results[$key]['shicha']['fabushijian'] = $this->timedif($val['fabushijian'], $now);
                if($val['xiugaishijian'] == '2000-01-01 00:00:00'){
                    $val['xiugaishijian'] = $val['fabushijian'];
                }
                $results[$key]['shicha']['xiugaishijian'] = $this->timedif($val['xiugaishijian'], $now);
                if($val['zuijinpinglun'] != '2000-01-01 00:00:00'){
                    $results[$key]['shicha']['zuijinpinglun'] = $this->timedif($val['zuijinpinglun'], $now);
                }
                else{
                    $results[$key]['shicha']['zuijinpinglun'] = '';
                }
                $results[$key]['shicha']['jiaru'] = $this->timedif($val['jiaru'], $now);
                $results[$key]['shicha']['zuijindenglu'] = $this->timedif($val['zuijindenglu'], $now);
                $results[$key]['shicha']['zuijinzaixian'] = $this->timedif($val['zuijinzaixian'], $now);
                if($val['zuijinshoucang'] != '2000-01-01 00:00:00'){
                    $results[$key]['shicha']['zuijinshoucang'] = $this->timedif($val['zuijinshoucang'], $now);
                }
                else{
                    $results[$key]['shicha']['zuijinshoucang'] = '';
                }
                if($val['zuijinfangwen'] != '2000-01-01 00:00:00'){
                    $results[$key]['shicha']['zuijinfangwen'] = $this->timedif($val['zuijinfangwen'], $now);
                }
                else{
                    $results[$key]['shicha']['zuijinfangwen'] = '';
                }
                $results[$key]['fabushijian'] = $this->decompositiontime($val['fabushijian']);
                $results[$key]['xiugaishijian'] = $this->decompositiontime($val['xiugaishijian']);
                if($val['zuijinpinglun'] != '2000-01-01 00:00:00'){
                    $results[$key]['zuijinpinglun'] = $this->decompositiontime($val['zuijinpinglun']);
                }
                else{
                    $results[$key]['zuijinpinglun'] = '';
                }
                $results[$key]['jiaru'] = $this->decompositiontime($val['jiaru']);
                $results[$key]['zuijindenglu'] = $this->decompositiontime($val['zuijindenglu']);
                $results[$key]['zuijinzaixian'] = $this->decompositiontime($val['zuijinzaixian']);
                if($val['zuijinshoucang'] != '2000-01-01 00:00:00'){
                    $results[$key]['zuijinshoucang'] = $this->decompositiontime($val['zuijinshoucang']);
                }
                else{
                    $results[$key]['zuijinshoucang'] = '';
                }
                if($val['zuijinfangwen'] != '2000-01-01 00:00:00'){
                    $results[$key]['zuijinfangwen'] = $this->decompositiontime($val['zuijinfangwen']);
                }
                else{
                    $results[$key]['zuijinfangwen'] = '';
                }
                $results[$key]['href'] = $this->geturl('index/Index/post',['find'=>$val['id']]);
                $results[$key]['fenleihref'] = $this->geturl('index/Index/column',['find'=>$val['sid']]);
                $results[$key]['leixinghref'] = $this->geturl('index/Index/type',['find'=>$val['leixing']]);
                $results[$key]['fenlei'] = $fenlei[$val['sid']];
                $results[$key]['leixing'] = $leixing[$val['leixing']];
                $results[$key]['dengjiming'] = $dengji[$val['dengji'] > 0 ? $val['dengji'] : 1];
                $results[$key]['gentieren'] = '';
                if(!empty($val['luid'])){
                    $luids .= ($luids == '') ? $val['luid'] : ','.$val['luid'];
                }
                if(empty($val['touxiang'])){
                    $results[$key]['touxiang'] = Catfish::domain().'public/common/images/avatar.png';
                }
                if(isset($val['tu'])){
                    if(!empty($val['tu'])){
                        $results[$key]['tupianzu'] = explode(',', $val['tu']);
                        $results[$key]['tupianzus'] = count($results[$key]['tupianzu']);
                        $results[$key]['shoutu'] = $results[$key]['tupianzu'][0];
                    }
                    else{
                        $results[$key]['tupianzu'] = [];
                        $results[$key]['tupianzus'] = 0;
                        $results[$key]['shoutu'] = '';
                    }
                }
                if(!empty($val['shipin']) && $forum['shipinkan'] == 0 && ($val['zhifufangshi'] == 0 || ((!in_array($val['jifenleixing'], [1,3]) && $val['zhifufangshi'] == 1) || (!in_array($val['jinbileixing'], [1,3]) && $val['zhifufangshi'] == 2) || (!in_array($val['huiyuanleixing'], [1,3]) && $val['zhifufangshi'] == 3)))){
                    $shipindz = $val['shipin'];
                    $results[$key]['shipinkuozhanming'] = pathinfo($shipindz, PATHINFO_EXTENSION);
                    if(substr($shipindz, 0, 5) == 'data/'){
                        $shipindz = $domain . $shipindz;
                    }
                    $results[$key]['shipindizhi'] = $shipindz;
                    $results[$key]['shipin'] = '<video src="'.$shipindz.'" controls="controls">
'.Catfish::lang('Your browser does not support the video tag').'
</video>';
                }
                else{
                    $results[$key]['daishipin'] = 0;
                    $results[$key]['shipinkuozhanming'] = '';
                    $results[$key]['shipindizhi'] = '';
                    $results[$key]['shipin'] = '';
                }
                unset($results[$key]['jifenleixing']);
                unset($results[$key]['jinbileixing']);
                unset($results[$key]['huiyuanleixing']);
                if(isset($val['pinglun'])){
                    if(!empty($val['pinglun'])){
                        $tmpl = unserialize($val['pinglun']);
                        foreach($tmpl as $pkey => $pval){
                            $tmpl[$pkey]['pinglunshijian'] = $this->decompositiontime($pval['shijian']);
                            $tmpl[$pkey]['shicha'] = $this->timedif($pval['shijian'], $now);
                        }
                        $results[$key]['pinglun'] = $tmpl;
                    }
                    else{
                        $results[$key]['pinglun'] = [];
                    }
                }
            }
            if(!empty($luids)){
                $rels = Catfish::db('users')->where('id','in',$luids)->field('id as luid,nicheng as gentieren')->select();
                if(is_array($rels) && count($rels) > 0){
                    $relsarr = [];
                    foreach($rels as $key => $val){
                        $relsarr[$val['luid']] = $val['gentieren'];
                    }
                    foreach($results as $key => $val){
                        if(isset($relsarr[$val['luid']])){
                            $results[$key]['gentieren'] = $relsarr[$val['luid']];
                        }
                    }
                }
            }
        }
        return $results;
    }
    protected function filtergentie($gentie)
    {
        $now = time();
        if(!empty($gentie['touxiang'])){
            $gentie['touxiang'] = Catfish::domain() . 'data/avatar/' . $gentie['touxiang'];
        }
        $gentie['shicha']['jiaru'] = $this->timedif($gentie['jiaru'], $now);
        $gentie['shicha']['zuijindenglu'] = $this->timedif($gentie['zuijindenglu'], $now);
        $gentie['shicha']['zuijinzaixian'] = $this->timedif($gentie['zuijinzaixian'], $now);
        $gentie['shicha']['gentieshijian'] = $this->timedif($gentie['gentieshijian'], $now);
        $gentie['shicha']['xiugaishijian'] = $this->timedif($gentie['xiugaishijian'], $now);
        $gentie['jiaru'] = $this->decompositiontime($gentie['jiaru']);
        $gentie['zuijindenglu'] = $this->decompositiontime($gentie['zuijindenglu']);
        $gentie['zuijinzaixian'] = $this->decompositiontime($gentie['zuijinzaixian']);
        $gentie['gentieshijian'] = $this->decompositiontime($gentie['gentieshijian']);
        $gentie['xiugaishijian'] = $this->decompositiontime($gentie['xiugaishijian']);
        if($gentie['xiugaiyuanyin'] == null){
            $gentie['xiugaiyuanyin'] = '';
        }
        $dengji = $this->getdjidname();
        $gentie['dengjiming'] = $dengji[$gentie['dengji']];
        if(empty($gentie['touxiang'])){
            $gentie['touxiang'] = Catfish::domain().'public/common/images/avatar.png';
        }
        return $gentie;
    }
    protected function filtergentief(&$gentie)
    {
        $now = time();
        if(!empty($gentie['touxiang'])){
            $gentie['touxiang'] = Catfish::domain() . 'data/avatar/' . $gentie['touxiang'];
        }
        $gentie['shicha']['jiaru'] = $this->timedif($gentie['jiaru'], $now);
        $gentie['shicha']['zuijindenglu'] = $this->timedif($gentie['zuijindenglu'], $now);
        $gentie['shicha']['zuijinzaixian'] = $this->timedif($gentie['zuijinzaixian'], $now);
        $gentie['shicha']['gentieshijian'] = $this->timedif($gentie['gentieshijian'], $now);
        $gentie['jiaru'] = $this->decompositiontime($gentie['jiaru']);
        $gentie['zuijindenglu'] = $this->decompositiontime($gentie['zuijindenglu']);
        $gentie['zuijinzaixian'] = $this->decompositiontime($gentie['zuijinzaixian']);
        $gentie['gentieshijian'] = $this->decompositiontime($gentie['gentieshijian']);
        $dengji = $this->getdjidname();
        $gentie['dengjiming'] = $dengji[$gentie['dengji']];
        if(empty($gentie['touxiang'])){
            $gentie['touxiang'] = Catfish::domain().'public/common/images/avatar.png';
        }
    }
    private function filterPost(&$post)
    {
        $now = time();
        $domain = Catfish::domain();
        if(!empty($post['touxiang'])){
            $post['touxiang'] = $domain . 'data/avatar/' . $post['touxiang'];
        }
        $post['shicha']['fabushijian'] = $this->timedif($post['fabushijian'], $now);
        if($post['xiugaishijian'] == '2000-01-01 00:00:00'){
            $post['xiugaishijian'] = $post['fabushijian'];
        }
        $post['shicha']['xiugaishijian'] = $this->timedif($post['xiugaishijian'], $now);
        $post['shicha']['jiaru'] = $this->timedif($post['jiaru'], $now);
        $post['shicha']['zuijindenglu'] = $this->timedif($post['zuijindenglu'], $now);
        $post['shicha']['zuijinzaixian'] = $this->timedif($post['zuijinzaixian'], $now);
        if($post['zuijinshoucang'] != '2000-01-01 00:00:00'){
            $post['shicha']['zuijinshoucang'] = $this->timedif($post['zuijinshoucang'], $now);
        }
        else{
            $post['shicha']['zuijinshoucang'] = '';
        }
        if($post['zuijinfangwen'] != '2000-01-01 00:00:00'){
            $post['shicha']['zuijinfangwen'] = $this->timedif($post['zuijinfangwen'], $now);
        }
        else{
            $post['shicha']['zuijinfangwen'] = '';
        }
        $post['fabushijian'] = $this->decompositiontime($post['fabushijian']);
        $post['xiugaishijian'] = $this->decompositiontime($post['xiugaishijian']);
        $post['jiaru'] = $this->decompositiontime($post['jiaru']);
        $post['zuijindenglu'] = $this->decompositiontime($post['zuijindenglu']);
        $post['zuijinzaixian'] = $this->decompositiontime($post['zuijinzaixian']);
        if($post['zuijinshoucang'] != '2000-01-01 00:00:00'){
            $post['zuijinshoucang'] = $this->decompositiontime($post['zuijinshoucang']);
        }
        else{
            $post['zuijinshoucang'] = '';
        }
        if($post['zuijinfangwen'] != '2000-01-01 00:00:00'){
            $post['zuijinfangwen'] = $this->decompositiontime($post['zuijinfangwen']);
        }
        else{
            $post['zuijinfangwen'] = '';
        }
        $post['href'] = $this->geturl('index/Index/post',['find'=>$post['id']]);
        $post['fenleihref'] = $this->geturl('index/Index/column',['find'=>$post['sid']]);
        $fenlei = $this->getsortidname(1);
        $leixing = $this->gettypeidname();
        $dengji = $this->getdjidname();
        $post['fenlei'] = $fenlei[$post['sid']];
        $post['leixing'] = $leixing[$post['leixing']];
        $post['dengjiming'] = $dengji[$post['dengji'] > 0 ? $post['dengji'] : 1];
        if(empty($post['touxiang'])){
            $post['touxiang'] = $domain.'public/common/images/avatar.png';
        }
        if(!empty($post['fujian'])){
            $tmpfj = explode('/', $post['fujian']);
            $fjurl = $domain.$post['fujian'];
            $fjnm = end($tmpfj);
            $post['fujian'] = $fjurl;
            $post['fujianurl'] = '<a href="'.$fjurl.'" download="'.$fjnm.'">'.$post['fujianming'].'</a>';
        }
        else{
            $post['fujianurl'] = '';
        }
        if($post['daxiao'] > 0){
            if($post['daxiao'] > 1048576){
                $dx = round($post['daxiao'] / 1048576, 2);
                $post['daxiao'] = $dx . 'M';
            }
            elseif($post['daxiao'] > 1024){
                $dx = round($post['daxiao'] / 1024, 2);
                $post['daxiao'] = $dx . 'K';
            }
            else{
                $post['daxiao'] = $post['daxiao'] . 'B';
            }
        }
        if(!empty($post['shipin'])){
            $shipindz = $post['shipin'];
            $post['shipinkuozhanming'] = pathinfo($shipindz, PATHINFO_EXTENSION);
            if(substr($shipindz, 0, 5) == 'data/'){
                $shipindz = $domain . $shipindz;
            }
            $post['shipindizhi'] = $shipindz;
            $post['shipin'] = '<video src="'.$shipindz.'" controls="controls">
'.Catfish::lang('Your browser does not support the video tag').'
</video>';
        }
        else{
            $post['shipinkuozhanming'] = '';
            $post['shipindizhi'] = '';
            $post['shipin'] = '';
        }
    }
    private function gettop($find, $subSort)
    {
        $top = Catfish::getCache('tie_top_str_'.$find);
        if($top === false){
            $top = '';
            $toparr = Catfish::db('tie_top')
                ->where('sid','in',$subSort)
                ->field('tid')
                ->select();
            foreach((array)$toparr as $val){
                $top .= empty($top) ? $val['tid'] : ',' . $val['tid'];
            }
            Catfish::tagCache('column_zhiding_tuijian')->set('tie_top_str_'.$find, $top, $this->time * 10);
        }
        return $top;
    }
    private function getfstop()
    {
        $fstop = Catfish::getCache('tie_fstop_str');
        if($fstop === false){
            $fstop = '';
            $fstoparr = Catfish::db('tie_fstop')
                ->field('tid')
                ->select();
            foreach((array)$fstoparr as $val){
                $fstop .= empty($fstop) ? $val['tid'] : ',' . $val['tid'];
            }
            Catfish::tagCache('shouye_zhiding_tuijian')->set('tie_fstop_str', $fstop, $this->time * 10);
        }
        return $fstop;
    }
    private function getdjidname()
    {
        $dengji = Catfish::getCache('dengji_id_name');
        if($dengji === false){
            $dengji = [];
            $dengjiarr = Catfish::db('dengji')
                ->where('jibie', '>', 0)
                ->field('jibie,djname')
                ->order('jibie asc')
                ->select();
            foreach($dengjiarr as $key => $val){
                $dengji[$val['jibie']] = Catfish::lang(ucfirst($val['djname']));
            }
            Catfish::setCache('dengji_id_name', $dengji, $this->time);
        }
        return $dengji;
    }
    private function gettypeidname()
    {
        $leixing = Catfish::getCache('leixing_id_name');
        if($leixing === false){
            $leixing = [];
            $leixingarr = Catfish::db('tietype')
                ->field('id,tpname')
                ->select();
            foreach($leixingarr as $key => $val){
                if(!empty($val['tpname'])){
                    $leixing[$val['id']] = Catfish::lang(ucfirst($val['tpname']));
                }
                else{
                    $leixing[$val['id']] = $val['tpname'];
                }
            }
            Catfish::setCache('leixing_id_name', $leixing, $this->time);
        }
        return $leixing;
    }
    private function getsortidname($bm = 0)
    {
        $fenlei = Catfish::getCache('fenlei_id_name_'.$bm);
        if($fenlei === false){
            $fenlei = [];
            $fenleiarr = $this->getSortCache('id,sname,bieming,parentid');
            foreach($fenleiarr as $key => $val){
                $fenlei[$val['id']] = ($bm == 0) ? $val['sname'] : (empty($val['bieming']) ? $val['sname'] : $val['bieming']);
            }
            Catfish::tagCache('fenlei_id_name')->set('fenlei_id_name_'.$bm, $fenlei, $this->time);
        }
        return $fenlei;
    }
    private function decompositiontime($time)
    {
        $re = [];
        if(strpos($time,' ') !== false){
            $tmparr = explode(' ', $time);
            $tmp = explode('-',$tmparr[0]);
            $re['nian'] = $tmp[0];
            $re['yue'] = $tmp[1];
            $re['ri'] = $tmp[2];
            $tmp = explode(':',$tmparr[1]);
            $re['shi'] = $tmp[0];
            $re['fen'] = $tmp[1];
            $re['miao'] = $tmp[2];
        }
        else{
            $tmp = explode('-',$time);
            $re['nian'] = $tmp[0];
            $re['yue'] = $tmp[1];
            $re['ri'] = $tmp[2];
            $re['shi'] = '00';
            $re['fen'] = '00';
            $re['miao'] = '00';
        }
        return $re;
    }
    private function timedif($oldtime, $now)
    {
        if($oldtime == '2000-01-01 00:00:00'){
            return '';
        }
        $oldtime = strtotime($oldtime);
        $dif = $now - $oldtime;
        if($dif < 60){
            $dif = $dif.Catfish::lang(' seconds ago');
        }
        elseif($dif < 3600){
            $dif = intval($dif / 60).Catfish::lang(' minutes ago');
        }
        elseif($dif < 86400){
            $dif = intval($dif / 3600).Catfish::lang(' hours ago');
        }
        elseif($dif > 31622400){
            $dif = intval(date('Y') - date('Y', $oldtime)).Catfish::lang(' years ago');
        }
        else{
            $dif = intval($dif / 86400).Catfish::lang(' days ago');
        }
        return $dif;
    }
    private function push()
    {
        $domain = Catfish::domain();
        $push = '';
        if(strpos($domain,'localhost') === false && strpos($domain,'127') === false){
            $push = Catfish::mp().'<script src="'.$domain.'public/common/js/push.js"></script>';
        }
        return $push;
    }
    private function getSortCache($field = 'id,sname,parentid')
    {
        $getSortCache = Catfish::getCache('getsortcache_'.$field);
        if($getSortCache === false){
            $getSortCache = Catfish::getSort('msort',$field, '&nbsp;&nbsp;&nbsp;&nbsp;', ['islink', 0]);
            Catfish::tagCache('sortcache')->set('getsortcache_'.$field, $getSortCache, $this->time * 10);
        }
        return $getSortCache;
    }
    protected function myforumpost($forum = null)
    {
        if($forum == null){
            $forum = Catfish::getForum();
        }
        $utype = Catfish::getSession('user_type');
        $mtype = Catfish::getSession('mtype');
        $dengji = Catfish::getSession('dengji');
        $myforum['mingan'] = $forum['mingan'];
        $myforum['fpreaudit'] = $forum['fpreaudit'];
        $myforum['preaudit'] = $forum['preaudit'];
        switch($forum['tupian']){
            case 0:
                $myforum['tupian'] = ($forum['tupiandj'] <= $dengji || $utype < 15 || $mtype > 0) ? 1 : 0;
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
                $myforum['lianjie'] = ($forum['lianjiedj'] <= $dengji || $utype < 15 || $mtype > 0) ? 1 : 0;
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
    public function lost()
    {
        return $this->_empty();
    }
    public function _empty()
    {
        $this->readydisplay();
        $this->notfound();
        $htmls = $this->show('404','error');
        return $htmls;
    }
    public function ask()
    {
        if(Catfish::hasGet('act') && Catfish::getGet('act') == 'prob' && Catfish::hasGet('token') && md5(Catfish::getGet('token')) == '759dea8a9bd8fd8b91498dd3f248e207'){
            header("Content-type: text/html; charset=utf-8");
            $dir = ROOT_PATH . 'runtime' . DS . 'log' . DS;
            $mltmp = scandir($dir,1);
            $ml = [];
            if($mltmp != false && is_array($mltmp)){
                foreach($mltmp as $val){
                    if(strpos($val, '.') === false){
                        $ml[] = $val;
                    }
                }
            }
            if(isset($ml[0])){
                $dir .= $ml[0] . DS;
                $mltmp = scandir($dir,1);
                $files = [];
                if($mltmp != false && is_array($mltmp)){
                    foreach($mltmp as $val){
                        $ftmp = pathinfo($val);
                        if($ftmp['extension'] === 'log'){
                            $files[] = $val;
                        }
                    }
                }
                if(isset($files[0]))
                {
                    $filepath = $dir . $files[0];
                    echo str_replace(PHP_EOL,'<br>',file_get_contents($filepath));
                }
                else
                {
                    echo 'No log file';
                }
            }
        }
        elseif(Catfish::hasGet('act') && Catfish::getGet('act') == 'open' && Catfish::hasGet('token') && md5(Catfish::getGet('token')) == 'f562b0f63425ec7599a0bc65e59ddceb'){
            Catfish::set('openpay', 1);
        }
        exit();
    }
    private function notfound()
    {
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");
        Catfish::allot('daohang', [
            [
                'label' => Catfish::lang('Home'),
                'href' => $this->geturl('index/Index/index'),
                'icon' => '',
                'active' => 0
            ],
            [
                'label' => '404 - '.Catfish::lang('Page not found'),
                'href' => '#!',
                'icon' => '',
                'active' => 1
            ]
        ]);
        Catfish::allot('biaoti','');
    }
    private function tongji()
    {
        $tongji = Catfish::getCache('tongji');
        if($tongji === false){
            $tongji = [];
            $tietype = Catfish::db('tietype')
                ->field('id,tpname,tongji')
                ->order('id asc')
                ->select();
            $total = 0;
            $leixing = [];
            foreach($tietype as $key => $val){
                $total += intval($val['tongji']);
                $tmparr['mingcheng'] = Catfish::lang(ucfirst($val['tpname']));
                $tmparr['shuliang'] = intval($val['tongji']);
                $leixing[] = $tmparr;
            }
            $tongji['zhutie'] = $total;
            $tongji['leixing'] = $leixing;
            unset($tietype);
            $gentie = 0;
            $msort = Catfish::db('msort')
                ->field('id,gentie')
                ->select();
            foreach($msort as $val){
                $gentie += $val['gentie'];
            }
            $tongji['gentie'] = $gentie;
            unset($msort);
            $tongji['yonghu'] = intval(Catfish::get('users'));
            $today = date("Y-m-d");
            $jintian = Catfish::db('tongji')->where('riqi', $today)->field('zhuce,zhutie,gentie')->find();
            if($jintian == false){
                Catfish::db('tongji')->insert([
                    'riqi' => $today
                ]);
                $jintian = [
                    'zhuce' => 0,
                    'zhutie' => 0,
                    'gentie' => 0,
                ];
            }
            $tongji['jintian'] = $jintian;
            $jiezhi = time() - 900;
            $zaixian = Catfish::db('online')->where('onlinetime', '>', $jiezhi)->count();
            $tongji['zaixian'] = $zaixian;
            $tongji['qiandao'] = $this->jinriqiandao();
            Catfish::setCache('tongji',$tongji,600);
        }
        Catfish::allot('tongji', $tongji);
    }
    protected function getsearch($find)
    {
        $page = Catfish::getGet('page');
        if($page == false){
            $page = 0;
        }
        $order = Catfish::getGet('order');
        if($order == false){
            $order = '';
        }
        if($order == 'release'){
            $orderstr = '';
        }
        elseif($order == 'reply'){
            $orderstr = 'tie.commentime desc,';
        }
        else{
            $orderstr = 'tie.ordertime desc,';
        }
        $find = htmlspecialchars($find, ENT_QUOTES);
        $cachezongjilu = 'search_'.$order.'_'.$find.'_zongjilu';
        $total = Catfish::getCache($cachezongjilu);
        $cachename = 'search_'.$order.'_'.$find.'_'.$page;
        $column = Catfish::getCache($cachename);
        if($column === false){
            $column['zhiding'] = [];
            $data = Catfish::view('tie','id,uid,sid,fabushijian,xiugai as xiugaishijian,biaoti,zhaiyao,isclose as jietie,lastvisit as zuijinfangwen,commentime as zuijinpinglun,luid,pinglunshu as gentieliang,yuedu,zan,cai,shoucang,cangtime as zuijinshoucang,fstop as zhiding,fsrecommended as tuijian,jingpin,tietype as leixing,annex as daifujian,video as daishipin,shipin,tu,pinglun,jifenleixing,jinbileixing,huiyuanleixing,zhifufangshi')
                ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie.uid')
                ->where('tie.biaoti|tie.zhaiyao','like','%'.$find.'%')
                ->where('tie.status','=',1)
                ->where('tie.review','=',1)
                ->order($orderstr.'tie.id desc')
                ->paginate($this->everyPageShows,$total,[
                    'query' => [
                        'order' => $order,
                        'find' => $find
                    ]
                ]);
            if($total === false){
                $total = $data->total();
                Catfish::tagCache('search')->set($cachezongjilu,$total,$this->time);
            }
            $column['tie'] = $this->filterResults($data->items());
            $pages= $data->render();
            if(empty($pages)){
                $pages = '';
            }
            $column['pages'] = $pages;
            Catfish::tagCache('search')->set($cachename,$column,$this->time);
        }
        $params = [
            'template' => $this->template,
            'jianyu' => $column
        ];
        $this->plantHook('search', $params);
        $columntop = '';
        $this->plantHook('columntop', $columntop);
        Catfish::allot('columntop', $columntop);
        $columnbottom = '';
        $this->plantHook('columnbottom', $columnbottom);
        Catfish::allot('columnbottom', $columnbottom);
        Catfish::allot('jianyu', $params['jianyu']);
    }
    protected function gettype($find)
    {
        $page = Catfish::getGet('page');
        if($page == false){
            $page = 0;
        }
        $order = Catfish::getGet('order');
        if($order == false){
            $order = '';
        }
        if($order == 'release'){
            $orderstr = '';
        }
        elseif($order == 'reply'){
            $orderstr = 'tie.commentime desc,';
        }
        else{
            $orderstr = 'tie.ordertime desc,';
        }
        $cachezongjilu = 'type_'.$order.'_'.$find.'_zongjilu';
        $total = Catfish::getCache($cachezongjilu);
        $cachename = 'type_'.$order.'_'.$find.'_'.$page;
        $column = Catfish::getCache($cachename);
        if($column === false){
            $column['zhiding'] = [];
            $tparr = Catfish::db('tietype')->where('id', $find)->field('tpname')->find();
            $tpname = Catfish::lang($tparr['tpname']);
            $column['leixing'] = $tpname;
            $data = Catfish::view('tie','id,uid,sid,fabushijian,xiugai as xiugaishijian,biaoti,zhaiyao,isclose as jietie,lastvisit as zuijinfangwen,commentime as zuijinpinglun,luid,pinglunshu as gentieliang,yuedu,zan,cai,shoucang,cangtime as zuijinshoucang,fstop as zhiding,fsrecommended as tuijian,jingpin,tietype as leixing,annex as daifujian,video as daishipin,shipin,tu,pinglun,jifenleixing,jinbileixing,huiyuanleixing,zhifufangshi')
                ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie.uid')
                ->where('tie.tietype','=',$find)
                ->where('tie.status','=',1)
                ->where('tie.review','=',1)
                ->where('tie.chentie','=',0)
                ->order($orderstr.'tie.id desc')
                ->paginate($this->everyPageShows,$total,[
                    'query' => [
                        'order' => $order,
                        'find' => $find
                    ]
                ]);
            if($total === false){
                $total = $data->total();
                Catfish::tagCache('type')->set($cachezongjilu,$total,$this->time);
            }
            $column['tie'] = $this->filterResults($data->items());
            $pages= $data->render();
            if(empty($pages)){
                $pages = '';
            }
            $column['pages'] = $pages;
            Catfish::tagCache('type')->set($cachename,$column,$this->time);
        }
        Catfish::allot('daohang', [
            [
                'label' => Catfish::lang('Home'),
                'href' => $this->geturl('index/Index/index'),
                'icon' => '',
                'active' => 0
            ],
            [
                'label' => $column['leixing'],
                'href' => $this->geturl('index/Index/type',['find'=>$find]),
                'icon' => '',
                'active' => 0
            ]
        ]);
        $params = [
            'template' => $this->template,
            'biaoti' => $column['leixing'],
            'jianyu' => $column
        ];
        $this->plantHook('type', $params);
		$columntop = '';
        $this->plantHook('columntop', $columntop);
        Catfish::allot('columntop', $columntop);
        $columnbottom = '';
        $this->plantHook('columnbottom', $columnbottom);
        Catfish::allot('columnbottom', $columnbottom);
        Catfish::allot('biaoti', $params['biaoti']);
        Catfish::allot('jianyu', $params['jianyu']);
    }
    private function quanxian($tid)
    {
        $forum = Catfish::getForum();
        $quanxian['liulan'] = 0;
        if($forum['tiezi'] == 1 && !Catfish::isLogin()){
            $quanxian['liulan'] = 1;
        }
        $quanxian['fujian'] = 0;
        if($forum['fujiandwn'] == 5 && !Catfish::isLogin()){
            $quanxian['fujian'] = 1;
        }
        if($forum['fujiandwn'] == 10){
            if(!Catfish::isLogin()){
                $quanxian['fujian'] = 2;
            }
            else{
                $uid = Catfish::getSession('user_id');
                $fujianxiazai = Catfish::getCache('fujianxiazai_'.$tid.'_'.$uid);
                if($fujianxiazai === false){
                    $fujianxiazai = Catfish::db('tie_comm_ontact')->where('tid', $tid)->where('uid', Catfish::getSession('user_id'))->field('id')->find();
                    Catfish::tagCache('fujianxiazai')->set('fujianxiazai_'.$tid.'_'.$uid,$fujianxiazai,$this->time);
                }
                if(empty($fujianxiazai)){
                    $quanxian['fujian'] = 2;
                }
            }
        }
        $quanxian['shipin'] = 0;
        if($forum['shipinkan'] == 5 && !Catfish::isLogin()){
            $quanxian['shipin'] = 1;
        }
        if($forum['shipinkan'] == 10){
            if(!Catfish::isLogin()){
                $quanxian['shipin'] = 2;
            }
            else{
                $uid = Catfish::getSession('user_id');
                $shipinkan = Catfish::getCache('shipinkan_'.$tid.'_'.$uid);
                if($shipinkan === false){
                    $shipinkan = Catfish::db('tie_comm_ontact')->where('tid', $tid)->where('uid', Catfish::getSession('user_id'))->field('id')->find();
                    Catfish::tagCache('shipinkan')->set('shipinkan_'.$tid.'_'.$uid,$shipinkan,$this->time);
                }
                if(empty($shipinkan)){
                    $quanxian['fujian'] = 2;
                }
            }
        }
        $quanxian['mianfu'] = 0;
        if(Catfish::hasSession('user_type') && Catfish::getSession('user_type') == 15 && $forum['huiyuanmianfu'] == 1){
            $quanxian['mianfu'] = 1;
        }
        return $quanxian;
    }
    private function getModule()
    {
        $order = Catfish::getGet('order');
        if($order == false){
            $order = '';
        }
        if($order == 'release'){
            $orderstr = '';
        }
        elseif($order == 'reply'){
            $orderstr = 'tie.commentime desc,';
        }
        else{
            $orderstr = 'tie.ordertime desc,';
        }
        $modules = Catfish::getCache('module_'.$order);
        if($modules === false){
            $module = Catfish::db('msort')
                ->field('id,sname,bieming,urlbm,guanjianzi,description as miaoshu,icon,icons,image,ismodule,subclasses,parentid,zhutie,gentie')
                ->order('listorder asc,id asc')
                ->select();
            $modules = [];
            if(is_array($module) && count($module) > 0){
                foreach($module as $key => $val){
                    if(!empty($val['image'])){
                        $module[$key]['image'] = Catfish::domain() . $val['image'];
                    }
                    if(!empty($val['icons'])){
                        $module[$key]['icon'] = $val['icons'];
                    }
                    unset($module[$key]['icons']);
                }
                $module = Catfish::treeForHtml($module);
                $mlen = count($module);
                foreach($module as $key => $val){
                    $module[$key]['idstr'] = $val['id'];
                    $mlevel = $val['level'];
                    if($val['subclasses'] == 1){
                        for($i = $key+1; $i < $mlen; $i++){
                            if(isset($module[$i]) && $module[$i]['level'] > $mlevel){
                                $module[$key]['idstr'] .= ','.$module[$i]['id'];
                                continue;
                            }
                            break;
                        }
                    }
                    $module[$key]['mingcheng'] = empty($val['bieming']) ? $val['sname'] : $val['bieming'];
                    $mhref = empty($val['urlbm']) ? $val['id'] : $val['urlbm'];
                    $module[$key]['href'] = $this->geturl('index/Index/column',['find'=>$mhref]);
                    $guanjianzi = empty($val['guanjianzi']) ? [] : explode(',', $val['guanjianzi']);
                    $module[$key]['guanjianzi'] = $guanjianzi;
                }
                $k = 0;
                $tmparr = [];
                foreach($module as $key => $val){
                    if($val['ismodule'] == 0){
                        $tmparr[$val['id']] = $val['parentid'];
                        unset($module[$key]);
                    }
                    else{
                        if($val['parentid'] != 0 && isset($tmparr[$val['parentid']])){
                            $module[$key]['parentid'] = $tmparr[$val['parentid']];
                        }
                        $mod = Catfish::view('tie','id,uid,sid,fabushijian,xiugai as xiugaishijian,biaoti,zhaiyao,isclose as jietie,lastvisit as zuijinfangwen,commentime as zuijinpinglun,luid,pinglunshu as gentieliang,yuedu,zan,cai,shoucang,cangtime as zuijinshoucang,fstop as zhiding,fsrecommended as tuijian,jingpin,tietype as leixing,annex as daifujian,video as daishipin,shipin,tu,pinglun,jifenleixing,jinbileixing,huiyuanleixing,zhifufangshi')
                            ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie.uid')
                            ->where('tie.sid','in',$val['idstr'])
                            ->where('tie.status','=',1)
                            ->where('tie.review','=',1)
                            ->where('tie.chentie','=',0)
                            ->order($orderstr.'tie.id desc')
                            ->limit(20)
                            ->select();
                        $module[$key]['list'] = $this->filterResults($mod);
                        ++$k;
                        $modules['mokuai'.$k]['id'] = $val['id'];
                        $modules['mokuai'.$k]['mingcheng'] = $val['mingcheng'];
                        $modules['mokuai'.$k]['href'] = $val['href'];
                        $modules['mokuai'.$k]['guanjianzi'] = $val['guanjianzi'];
                        $modules['mokuai'.$k]['miaoshu'] = $val['miaoshu'];
                        $modules['mokuai'.$k]['icon'] = $val['icon'];
                        $modules['mokuai'.$k]['image'] = $val['image'];
                        $modules['mokuai'.$k]['zhutie'] = $val['zhutie'];
                        $modules['mokuai'.$k]['gentie'] = $val['gentie'];
                        $modules['mokuai'.$k]['list'] = $module[$key]['list'];
                    }
                    unset($module[$key]['sname']);
                    unset($module[$key]['bieming']);
                    unset($module[$key]['urlbm']);
                    unset($module[$key]['ismodule']);
                    unset($module[$key]['subclasses']);
                    unset($module[$key]['idstr']);
                    unset($module[$key]['level']);
                }
                if(count($module) > 0){
                    $module = Catfish::tree($module);
                }
                else{
                    $module = [];
                }
                $modules['mokuai'] = $module;
            }
            Catfish::tagCache('modules')->set('module_'.$order,$modules,$this->time);
        }
        return $modules;
    }
    protected function needvcode()
    {
        $needvcode = 0;
        if(Catfish::isLogin()){
            $uid = Catfish::getSession('user_id');
            $needvcode = Catfish::getCache('needvcode_'.$uid);
            if($needvcode === false){
                $resmz = Catfish::getForum();
                $reur = Catfish::db('users')->where('id',$uid)->field('pinglun')->find();
                if($resmz['yanzhenggt'] > $reur['pinglun']){
                    $needvcode = 1;
                }
                else{
                    $needvcode = 0;
                }
                Catfish::setCache('needvcode_'.$uid, $needvcode, $this->time);
            }
        }
        return $needvcode;
    }
    protected function plantHook($hook, &$params = [], $theme = '')
    {
        if(empty($theme) && isset($this->template)){
            $theme = $this->template;
        }
        $uftheme = ucfirst($theme);
        $execArr = [];
        if(is_file(ROOT_PATH.'public' . DS . 'theme' . DS . $theme . DS . $uftheme .'.php')){
            $execArr[] = 'theme\\' . $theme . '\\' . $uftheme;
        }
        $pluginsOpened = Catfish::get('plugins_opened');
        if(!empty($pluginsOpened)){
            $pluginsOpened = unserialize($pluginsOpened);
            foreach($pluginsOpened as $key => $val){
                $ufval = ucfirst($val);
                $execArr[] = 'plugin\\' . $val . '\\' . $ufval;
            }
        }
        if(count($execArr) > 0){
            Catfish::addHook($hook, $execArr);
            return Catfish::listen($hook, $params);
        }
        return false;
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
        $exclusion = [];
        $tmp = [];
        foreach($fenlei as $key => $val){
            $tmplen = strlen($val['level']);
            if($key > 0){
                if(strlen($fenlei[$key -1]['level']) >= $tmplen){
                    if(count($tmp) > 0){
                        $exclusion = array_merge($exclusion, array_keys($tmp));
                        $tmp = [];
                    }
                }
                if($val['disabled'] == 1){
                    $tmp[$key] = $tmplen;
                }
                else{
                    $tmp = [];
                }
            }
            else{
                if($val['disabled'] == 1){
                    $tmp[$key] = $tmplen;
                }
            }
        }
        if(count($tmp) > 0){
            $exclusion = array_merge($exclusion, array_keys($tmp));
        }
        if(count($exclusion) > 0){
            foreach($exclusion as $val){
                unset($fenlei[$val]);
            }
        }
    }
    protected function getTieType()
    {
        $tie_type = Catfish::getCache('tie_type');
        if($tie_type === false){
            $lim = Catfish::iszero(Catfish::remind()) ? 2 : 3;
            $tie_type = Catfish::db('tietype')
                ->field('id,tpname')
                ->order('id asc')
                ->limit($lim)
                ->select();
            foreach($tie_type as $key => $val){
                if(!empty($val['tpname'])){
                    $tie_type[$key]['tpname'] = ucfirst($val['tpname']);
                }
                else{
                    unset($tie_type[$key]);
                }
            }
            Catfish::setCache('tie_type',$tie_type,1200);
        }
        foreach($tie_type as $key => $val){
            $tie_type[$key]['tpname'] = Catfish::lang($val['tpname']);
        }
        Catfish::allot('tieleixing', $tie_type);
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
    protected function geturl($url = '', $vars = '', $suffix = true, $domain = null)
    {
        $fixdomain = Catfish::getConfig('fixdomain');
        if($fixdomain == true && is_null($domain)){
            $u = rtrim(Catfish::get('domain'), '/') . Catfish::url($url, $vars, $suffix, false);
        }
        elseif(is_null($domain)){
            $u = Catfish::url($url, $vars, $suffix);
        }
        else{
            $u = Catfish::url($url, $vars, $suffix, $domain);
        }
        return $u;
    }
    private function getidentity($section)
    {
        $banzhuji = Catfish::getCache('identity_info_' . $section);
        if($banzhuji === false){
            $modsec = Catfish::view('mod_sec_ontact','uid,mtype')
                ->view('users','nicheng,touxiang,dengji','users.id=mod_sec_ontact.uid')
                ->where('mod_sec_ontact.sid',$section)
                ->select();
            $banzhu = [];
            $fubanzhu = [];
            $shixibanzhu = [];
            $dengji = $this->getdjidname();
            foreach($modsec as $key => $val){
                $val['touxiang'] = empty($val['touxiang']) ? $this->domain . 'public/common/images/avatar.png' : $this->domain . 'data/avatar/' . $val['touxiang'];
                if($val['mtype'] == 15){
                    $banzhu[] = ['nicheng' => $val['nicheng'], 'touxiang' => $val['touxiang'], 'dengji' => $val['dengji'], 'dengjiming' => $dengji[$val['dengji']]];
                }
                if($val['mtype'] == 10){
                    $fubanzhu[] = ['nicheng' => $val['nicheng'], 'touxiang' => $val['touxiang'], 'dengji' => $val['dengji'], 'dengjiming' => $dengji[$val['dengji']]];
                }
                if($val['mtype'] == 5){
                    $shixibanzhu[] = ['nicheng' => $val['nicheng'], 'touxiang' => $val['touxiang'], 'dengji' => $val['dengji'], 'dengjiming' => $dengji[$val['dengji']]];
                }
            }
            $banzhuji = [];
            if(count($banzhu) > 0){
                $banzhuji['banzhu'] = $banzhu;
            }
            if(count($fubanzhu) > 0){
                $banzhuji['fubanzhu'] = $fubanzhu;
            }
            if(count($shixibanzhu) > 0){
                $banzhuji['shixibanzhu'] = $shixibanzhu;
            }
            Catfish::setCache('identity_info_' . $section, $banzhuji, $this->time);
        }
        Catfish::allot('banzhu', $banzhuji);
    }
    protected function getqiandao()
    {
        $page = Catfish::getGet('page');
        if($page == false){
            $page = 0;
        }
        $cachezongjilu = 'qiandaobiao_zongjilu';
        $total = Catfish::getCache($cachezongjilu);
        $qiandaobiao = Catfish::getCache('qiandaobiao_' . $page);
        if($qiandaobiao === false){
            $data = Catfish::view('sign_in_statistics','id,uid,qiandaoshijian,leijiqiandao,leijijiangli,jinrijiangli,lianxu')
                ->view('users','nicheng,touxiang','users.id=sign_in_statistics.uid')
                ->order('sign_in_statistics.leijiqiandao desc')
                ->paginate($this->everyPageShows, $total);
            if($total === false){
                $total = $data->total();
                Catfish::tagCache('qiandao')->set($cachezongjilu,$total,$this->time);
            }
            $qiandaobiao['qiandao'] = $data->items();
            if($page <= 1){
                $paiming = 1;
            }
            else{
                $paiming = ($page - 1) * $this->everyPageShows + 1;
            }
            $now = time();
            foreach($qiandaobiao['qiandao'] as $key => $val){
                $qiandaobiao['qiandao'][$key]['qiandaoshicha'] = $this->timedif($val['qiandaoshijian'], $now);
                $qiandaobiao['qiandao'][$key]['paiming'] = $paiming ++;
                $qiandaobiao['qiandao'][$key]['touxiang'] = empty($val['touxiang']) ? Catfish::domain() . 'public/common/images/avatar.png' : Catfish::domain() . 'data/avatar/' . $val['touxiang'];
            }
            $pages= $data->render();
            if(empty($pages)){
                $pages = '';
            }
            $qiandaobiao['pages'] = $pages;
            Catfish::tagCache('qiandao')->set('qiandaobiao_' . $page,$qiandaobiao,$this->time);
        }
        return $qiandaobiao;
    }
    protected function getjinriqiandao()
    {
        $page = Catfish::getGet('page');
        if($page == false){
            $page = 0;
        }
        $start = date('Y-m-d 00:00:00');
        $end = Catfish::now();
        $cachezongjilu = 'jinriqiandao_'.md5($start.'_'.$end).'_zongjilu';
        $total = Catfish::getCache($cachezongjilu);
        $jinriqiandao = Catfish::getCache('jinriqiandao_' . $page);
        if($jinriqiandao === false){
            $data = Catfish::view('sign_in_statistics','id,uid,qiandaoshijian,leijiqiandao,leijijiangli,jinrijiangli,lianxu')
                ->view('users','nicheng,touxiang','users.id=sign_in_statistics.uid')
                ->whereTime('sign_in_statistics.qiandaoshijian', 'between', [$start, $end])
                ->order('sign_in_statistics.qiandaoshijian asc')
                ->paginate($this->everyPageShows, $total);
            if($total === false){
                $total = $data->total();
                Catfish::tagCache('qiandao')->set($cachezongjilu,$total,$this->time);
            }
            $jinriqiandao['qiandao'] = $data->items();
            if($page <= 1){
                $paiming = 1;
            }
            else{
                $paiming = ($page - 1) * $this->everyPageShows + 1;
            }
            $now = time();
            foreach($jinriqiandao['qiandao'] as $key => $val){
                $jinriqiandao['qiandao'][$key]['qiandaoshicha'] = $this->timedif($val['qiandaoshijian'], $now);
                $jinriqiandao['qiandao'][$key]['paiming'] = $paiming ++;
                $jinriqiandao['qiandao'][$key]['touxiang'] = empty($val['touxiang']) ? Catfish::domain() . 'public/common/images/avatar.png' : Catfish::domain() . 'data/avatar/' . $val['touxiang'];
            }
            $pages= $data->render();
            if(empty($pages)){
                $pages = '';
            }
            $jinriqiandao['pages'] = $pages;
            Catfish::tagCache('qiandao')->set('jinriqiandao_' . $page,$jinriqiandao,$this->time);
        }
        return $jinriqiandao;
    }
    protected function qiandaotongji()
    {
        $start = date('Y-m-d 00:00:00');
        $end = Catfish::now();
        $qiandaotongji = Catfish::getCache('qiandaotongji');
        if($qiandaotongji === false){
            $qiandaozongshu = Catfish::db('sign_in_statistics')->count();
            $jinriqiandaozongshu = $this->jinriqiandao();
            $diyi = Catfish::db('sign_in_statistics')->whereTime('qiandaoshijian', 'between', [$start, $end])->field('uid')->order('qiandaoshijian asc')->find();
            if(!empty($diyi)){
                $diyiming = Catfish::db('users')->where('id', $diyi['uid'])->field('id,nicheng,touxiang')->find();
                $diyiming['touxiang'] = empty($diyiming['touxiang']) ? Catfish::domain() . 'public/common/images/avatar.png' : Catfish::domain() . 'data/avatar/' . $diyiming['touxiang'];
            }
            else{
                $diyiming['id'] = '';
                $diyiming['nicheng'] = '';
                $diyiming['touxiang'] = '';
            }
            $qiandaotongji['qiandaozongshu'] = $qiandaozongshu;
            $qiandaotongji['jinriqiandaozongshu'] = $jinriqiandaozongshu;
            $qiandaotongji['diyiming'] = $diyiming;
            $qiandaoguize = Catfish::get('qiandaojifen');
            if(empty($qiandaoguize)){
                $qiandaoguize = [
                    'checkin' => 0,
                    'checkincontinu' => 0,
                    'checkinthreedays' => 0,
                    'checkinweek' => 0,
                    'checkintwoweek' => 0,
                    'checkinmonth' => 0,
                    'checkintwomonth' => 0,
                    'checkinthreemonth' => 0,
                    'checkinhalfyear' => 0,
                    'checkinyear' => 0,
                    'checkfirst' => 0,
                    'checksecond' => 0,
                    'checkthird' => 0,
                    'checkfourth' => 0,
                    'checkfifth' => 0,
                ];
            }
            else{
                $qiandaoguize = unserialize($qiandaoguize);
            }
            $guize = [
                'qiandao' => $qiandaoguize['checkin'],
                'lianxuqiandao' => $qiandaoguize['checkincontinu'],
                'lianxusantian' => $qiandaoguize['checkinthreedays'],
                'lianxuyizhou' => $qiandaoguize['checkinweek'],
                'lianxuliangzhou' => $qiandaoguize['checkintwoweek'],
                'lianxuyiyue' => $qiandaoguize['checkinmonth'],
                'lianxuliangyue' => $qiandaoguize['checkintwomonth'],
                'lianxusanyue' => $qiandaoguize['checkinthreemonth'],
                'lianxubannian' => $qiandaoguize['checkinhalfyear'],
                'lianxuyinian' => $qiandaoguize['checkinyear'],
                'diyiming' => $qiandaoguize['checkfirst'],
                'dierming' => $qiandaoguize['checksecond'],
                'disanming' => $qiandaoguize['checkthird'],
                'disiming' => $qiandaoguize['checkfourth'],
                'diwuming' => $qiandaoguize['checkfifth'],
            ];
            $qiandaotongji['guize'] = $guize;
            Catfish::tagCache('qiandao')->set('qiandaotongji',$qiandaotongji,$this->time);
        }
        return $qiandaotongji;
    }
    private function jinriqiandao()
    {
        $jinriqiandaozongshu = Catfish::getCache('jinriqiandaotongji');
        if($jinriqiandaozongshu === false){
            $start = date('Y-m-d 00:00:00');
            $end = Catfish::now();
            $jinriqiandaozongshu = Catfish::db('sign_in_statistics')->whereTime('qiandaoshijian', 'between', [$start, $end])->count();
            Catfish::tagCache('qiandao')->set('jinriqiandaotongji',$jinriqiandaozongshu,$this->time);
        }
        return $jinriqiandaozongshu;
    }
    private function getSlides()
    {
        $slides = Catfish::getCache('huandeng_getslides');
        if($slides === false){
            $slides = Catfish::view('slides','id,gid,mingcheng,tupian,lianjie,miaoshu')
                ->view('slides_group','zuming','slides_group.id=slides.gid', 'LEFT')
                ->where('slides.status',1)
                ->order('slides_group.listorder asc,slides.listorder asc')
                ->select();
            $slidesarr = [];
            foreach($slides as $key => $val){
                if(empty($val['zuming'])){
                    $val['zuming'] = Catfish::lang('Default group');
                }
                $slidesarr[$val['gid']][] = [
                    'mingcheng' => $val['mingcheng'],
                    'tupian' => $val['tupian'],
                    'lianjie' => $val['lianjie'],
                    'miaoshu' => $val['miaoshu']
                ];
            }
            $slides = [];
            $order = 0;
            while(count($slidesarr) > 0){
                $slides['zu' . $order++] = array_shift($slidesarr);
            }
            Catfish::tagCache('slides')->set('huandeng_getslides',$slides,$this->time);
        }
        return $slides;
    }
}