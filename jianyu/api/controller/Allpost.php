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
class Allpost extends Jsonapi
{
    private $everyPageShows = 20;
    private $time = 1200;
    public function getData($param)
    {
        if(isset($param['per'])){
            $this->everyPageShows = $param['per'];
        }
        $url = Catfish::domain() . 'api/post/all';
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
        $cachename = 'api_allpost_count';
        $allpostcount = Catfish::getCache($cachename);
        if($allpostcount === false){
            $count = Catfish::view('tie','id')
                ->view('users','id as uid','users.id=tie.uid')
                ->where('tie.status','=',1)
                ->where('tie.review','=',1)
                ->where('tie.fstop','=',0)
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
            $allpostcount = [
                'total' => $count,
                'pages' => $pages,
                'first' => $url . '/page/1',
                'last' => $url . '/page/' . $pages,
                'prev' => $prev,
                'next' => $next,
                'self' => $self,
            ];
            Catfish::tagCache('api_allpost')->set($cachename,$allpostcount,$this->time);
        }
        $cachename = 'api_allpost_'.$order.'_'.$page;
        $allpost = Catfish::getCache($cachename);
        if($allpost === false){
            $allpost = Catfish::view('tie','id,uid,sid,fabushijian,xiugai,biaoti,zhaiyao,pinglunshu,yuedu,jingpin,annex,shipin,tu')
                ->view('tienr','zhengwen','tienr.tid=tie.id')
                ->view('users','nicheng,touxiang','users.id=tie.uid')
                ->where('tie.status','=',1)
                ->where('tie.review','=',1)
                ->where('tie.fstop','=',0)
                ->order($orderstr.'tie.id desc')
                ->limit(($this->everyPageShows * ($page - 1)), $this->everyPageShows)
                ->select();
            Catfish::tagCache('api_allpost')->set($cachename,$allpost,$this->time);
        }
        if(empty($allpost)){
            $err = $this->createError('601', 'Post list is empty', 'No posts found');
            $this->addError($err);
        }
        else{
            $this->addLinks([
                'self' => $allpostcount['self'],
                'first' => $allpostcount['first'],
                'last' => $allpostcount['last'],
                'prev' => $allpostcount['prev'],
                'next' => $allpostcount['next'],
            ]);
            foreach($allpost as $key => $val){
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
                    'video' => Catfish::domain() . $val['shipin'],
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
}