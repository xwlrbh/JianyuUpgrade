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
class Section extends Jsonapi
{
    private $everyPageShows = 20;
    private $time = 1200;
    public function getData($param)
    {
        if(isset($param['per'])){
            $this->everyPageShows = $param['per'];
        }
        $find = $param['section'];
        $url = Catfish::domain() . 'api/section/' . $find;
        $page = 1;
        if(isset($param['page'])){
            $page = $param['page'];
        }
        $order = 'default';
        if(isset($param['order'])){
            $order = $param['order'];
            $url .= '/order/' . $param['order'];
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
        $subSort = '';
        $fenleiarr = $this->getSortCache();
        foreach($fenleiarr as $key => $val){
            $subSort .= empty($subSort) ? (string)$val['id'] : ','.(string)$val['id'];
        }
        $cachename = 'api_column_'.$find.'_count';
        $columncount = Catfish::getCache($cachename);
        if($columncount === false){
            $count = Catfish::view('tie','id')
                ->view('users','id as uid','users.id=tie.uid')
                ->where('tie.sid','in',$subSort)
                ->where('tie.status','=',1)
                ->where('tie.review','=',1)
                ->where('tie.istop','=',0)
                ->count();
            $pages = ceil($count / $this->everyPageShows);
            $prev = '';
            if($page > 1 && $page <= $pages){
                $prev = $url . '/page/' . ($page - 1);
            }
            $next = '';
            if($page < $pages){
                $next = $url . '/page/' . ($page + 1);
            }
            $self = $url;
            if(isset($param['page'])){
                $self = $url . '/page/' . $page;
            }
            $columncount = [
                'total' => $count,
                'pages' => $pages,
                'first' => $url . '/page/1',
                'last' => $url . '/page/' . $pages,
                'prev' => $prev,
                'next' => $next,
                'self' => $self,
            ];
            Catfish::tagCache('api_column')->set($cachename,$columncount,$this->time);
        }
        $cachename = 'api_column_'.$find.'_'.$order.'_'.$page;
        $column = Catfish::getCache($cachename);
        if($column === false){
            $column = Catfish::view('tie','id,uid,sid,fabushijian,xiugai,biaoti,zhaiyao,pinglunshu,yuedu,jingpin,annex,shipin,tu')
                ->view('tienr','zhengwen','tienr.tid=tie.id')
                ->view('users','nicheng,touxiang','users.id=tie.uid')
                ->where('tie.sid','in',$subSort)
                ->where('tie.status','=',1)
                ->where('tie.review','=',1)
                ->where('tie.istop','=',0)
                ->order($orderstr.'tie.id desc')
                ->limit(($this->everyPageShows * ($page - 1)), $this->everyPageShows)
                ->select();
            Catfish::tagCache('api_column')->set($cachename,$column,$this->time);
        }
        if(empty($column)){
            $err = $this->createError('602', 'Post list is empty', 'No posts found');
            $this->addError($err);
        }
        else{
            $this->addLinks([
                'self' => $columncount['self'],
                'first' => $columncount['first'],
                'last' => $columncount['last'],
                'prev' => $columncount['prev'],
                'next' => $columncount['next'],
            ]);
            foreach($column as $key => $val){
                $tu = '';
                if(!empty($val['tu'])){
                    $tmptu = explode(',', $val['tu']);
                    $tu = Catfish::domain() . $tmptu[0];
                }
                $author = $this->createData('people', $val['uid'], [
                    'nickname' => $val['nicheng'],
                    'avatar' => Catfish::domain() . 'data/avatar/' . $val['touxiang']
                ]);
                $data = $this->createData('posts', $val['id'], [
                    'title' => $val['biaoti'],
                    'summary' => $val['zhaiyao'],
                    'body' => $this->filterContent($val['zhengwen']),
                    'created' => $val['fabushijian'],
                    'updated' => $val['xiugai'],
                    'comments' => $val['pinglunshu'],
                    'reading' => $val['yuedu'],
                    'fine' => $val['jingpin'],
                    'annex' => $val['annex'],
                    'image' => $tu,
                    'video' => Catfish::domain() . $val['shipin']
                ], [
                    'self' => Catfish::domain() . 'post/' . $val['id']
                ], [
                    'author' => [
                        'data' => $author
                    ]
                ]);
                $this->addData($data);
            }
        }
        return $this->outJsonApi();
    }
    public function getSection($param)
    {
        if($param['section'] == 'all'){
            $fenleiarr = $this->getSortCache();
            foreach($fenleiarr as $key => $val){
                $data = $this->createData('section', $val['id'], [
                    'name' => $val['sname'],
                    'level' => $val['level'],
                    'disabled' => $val['disabled']
                ], [
                    'self' => Catfish::domain() . 'section/' . $val['id']
                ]);
                $this->addData($data);
            }
        }
        else{
            $err = $this->createError('600', 'No content found', 'Return empty');
            $this->addError($err);
        }
        return $this->outJsonApi();
    }
    private function getSortCache($field = 'id,sname,virtual,parentid')
    {
        $getSortCache = Catfish::getCache('api_getsortcache_'.$field);
        if($getSortCache === false){
            $getSortCache = Catfish::getSort('msort',$field, '#', ['islink', 0]);
            foreach($getSortCache as $key => $val){
                if($val['virtual'] == 1){
                    $getSortCache[$key]['disabled'] = 1;
                }
                else{
                    $getSortCache[$key]['disabled'] = 0;
                }
                unset($getSortCache[$key]['virtual']);
                unset($getSortCache[$key]['parentid']);
            }
            Catfish::tagCache('api_column')->set('api_getsortcache_'.$field, $getSortCache, $this->time * 10);
        }
        return $getSortCache;
    }
}