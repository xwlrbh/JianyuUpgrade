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
class User extends Jsonapi
{
    private $everyPageShows = 20;
    private $time = 1200;
    public function getData($param, $uid)
    {
        $jianyuuser = Catfish::db('users')->where('id',$uid)->field('nicheng,email,shouji,touxiang,xingbie,qianming')->find();
        $jianyuuinfo = Catfish::db('users_info')->where('uid',$uid)->field('url,shengri,xuexiao,qq,weibo,wechat,facebook,twitter')->find();
        if($jianyuuinfo['shengri'] == '2000-01-01'){
            $jianyuuinfo['shengri'] = '';
        }
        $data = $this->createData('user', $uid, [
            'id' => $uid,
            'nickname' => $jianyuuser['nicheng'],
            'avatar' => Catfish::domain() . 'data/avatar/' . $jianyuuser['touxiang'],
            'email' => $jianyuuser['email'],
            'phone' => $jianyuuser['shouji'],
            'gender' => $jianyuuser['xingbie'],
            'birthday' => $jianyuuinfo['shengri'],
            'signature' => $jianyuuser['qianming'],
            'url' => $jianyuuinfo['url'],
            'school' => $jianyuuinfo['xuexiao'],
            'qq' => $jianyuuinfo['qq'],
            'weibo' => $jianyuuinfo['weibo'],
            'wechat' => $jianyuuinfo['wechat'],
            'facebook' => $jianyuuinfo['facebook'],
            'twitter' => $jianyuuinfo['twitter'],
        ]);
        $this->addData($data);
        return $this->outJsonApi();
    }
    public function modify($param, $uid, $id)
    {
        if($uid != $id){
            $err = $this->createError('720', 'No operation authority', 'Operation not allowed');
            $this->addError($err);
        }
        else{
            if(isset($param['email']) && !filter_var($param['email'], FILTER_VALIDATE_EMAIL)){
                $err = $this->createError('721', 'The e-mail format is incorrect', 'Fail to edit');
                $this->addError($err);
            }
            elseif(isset($param['gender']) && !in_array($param['gender'], [0,1,2])){
                $err = $this->createError('722', 'Gender only allows three numbers: 0, 1, 2', 'Fail to edit');
                $this->addError($err);
            }
            elseif(isset($param['birthday']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $param['birthday'])){
                $err = $this->createError('723', 'The birthday format must be: YYYY-MM-DD', 'Fail to edit');
                $this->addError($err);
            }
            else{
                $uupdata = [];
                if(isset($param['nickname'])){
                    $uupdata['nicheng'] = $param['nickname'];
                }
                if(isset($param['email'])){
                    $uupdata['email'] = $param['email'];
                }
                if(isset($param['phone'])){
                    $uupdata['shouji'] = $param['phone'];
                }
                if(isset($param['gender'])){
                    $uupdata['xingbie'] = $param['gender'];
                }
                if(isset($param['signature'])){
                    $uupdata['qianming'] = $param['signature'];
                }
                $iupdata = [];
                if(isset($param['url'])){
                    $iupdata['url'] = $param['url'];
                }
                if(isset($param['birthday'])){
                    $iupdata['shengri'] = $param['birthday'];
                }
                if(isset($param['school'])){
                    $iupdata['xuexiao'] = $param['school'];
                }
                if(isset($param['qq'])){
                    $iupdata['qq'] = $param['qq'];
                }
                if(isset($param['weibo'])){
                    $iupdata['weibo'] = $param['weibo'];
                }
                if(isset($param['wechat'])){
                    $iupdata['wechat'] = $param['wechat'];
                }
                if(isset($param['facebook'])){
                    $iupdata['facebook'] = $param['facebook'];
                }
                if(isset($param['twitter'])){
                    $iupdata['twitter'] = $param['twitter'];
                }
                Catfish::dbStartTrans();
                try{
                    if(!empty($uupdata)){
                        Catfish::db('users')
                            ->where('id', $uid)
                            ->update($uupdata);
                    }
                    if(!empty($iupdata)){
                        Catfish::db('users_info')
                            ->where('uid', $uid)
                            ->update($iupdata);
                    }
                    Catfish::dbCommit();
                    $data = $this->createData('user', $uid, [
                        'result' => 'ok'
                    ]);
                    $this->addData($data);
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    $err = $this->createError('710', 'Database storage failure', 'Execution error');
                    $this->addError($err);
                }
            }
        }
        return $this->outJsonApi();
    }
    public function getPost($param, $uid)
    {
        if(isset($param['per'])){
            $this->everyPageShows = $param['per'];
        }
        $url = Catfish::domain() . 'api/get/posts';
        $page = 1;
        if(isset($param['page'])){
            $page = $param['page'];
        }
        $cachename = 'api_usergetpost_count';
        $allpostcount = Catfish::getCache($cachename);
        if($allpostcount === false){
            $count = Catfish::db('tie')
                ->where('uid', $uid)
                ->where('status', 1)
                ->field('id')
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
            Catfish::tagCache('api_usergetpost')->set($cachename,$allpostcount,$this->time);
        }
        $allpost = Catfish::db('tie')
            ->where('uid', $uid)->where('status', 1)
            ->field('id,fabushijian,xiugai,biaoti,zhaiyao,pinglunshu,yuedu,zan,cai')
            ->order('id desc')
            ->limit(($this->everyPageShows * ($page - 1)), $this->everyPageShows)
            ->select();
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
                $data = $this->createData('posts', $val['id'], [
                    'title' => $val['biaoti'],
                    'summary' => $val['zhaiyao'],
                    'created' => $val['fabushijian'],
                    'updated' => $val['xiugai'],
                    'comments' => $val['pinglunshu'],
                    'reading' => $val['yuedu'],
                    'like' => $val['zan'],
                    'dislike' => $val['cai']
                ], [
                    'self' => Catfish::domain() . 'post/' . $val['id']
                ]);
                $this->addData($data);
            }
        }
        return $this->outJsonApi();
    }
    public function getComment($param, $uid)
    {
        if(isset($param['per'])){
            $this->everyPageShows = $param['per'];
        }
        $url = Catfish::domain() . 'api/get/discuss';
        $page = 1;
        if(isset($param['page'])){
            $page = $param['page'];
        }
        $cachename = 'api_usergetcomment_count';
        $allpostcount = Catfish::getCache($cachename);
        if($allpostcount === false){
            $count = Catfish::db('tie_comments')
                ->where('uid', $uid)
                ->field('id')
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
            Catfish::tagCache('api_usergetcomment')->set($cachename,$allpostcount,$this->time);
        }
        $allpost = Catfish::view('tie_comments','id,createtime,xiugai,zan,cai,content')
            ->view('tie_comm_ontact','tid','tie_comm_ontact.cid=tie_comments.id')
            ->view('tie','biaoti','tie.id=tie_comm_ontact.tid')
            ->where('tie_comments.uid', $uid)
            ->order('id desc')
            ->limit(($this->everyPageShows * ($page - 1)), $this->everyPageShows)
            ->select();
        if(empty($allpost)){
            $err = $this->createError('604', 'The comment list is empty', 'No comments found');
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
                $data = $this->createData('discuss', $val['id'], [
                    'body' => $this->filterContent($val['content']),
                    'created' => $val['createtime'],
                    'updated' => $val['xiugai'],
                    'like' => $val['zan'],
                    'dislike' => $val['cai']
                ], null, [
                    'post' => [
                        'links' => [
                            'self' => Catfish::domain() . 'post/' . $val['tid']
                        ],
                        'data' => [
                            'type' => 'post',
                            'id' => $val['tid'],
                            'attributes' => [
                                'title' => $val['biaoti']
                            ]
                        ]
                    ]
                ]);
                $this->addData($data);
            }
        }
        return $this->outJsonApi();
    }
    public function getError($param)
    {
        $err = $this->createError('700', 'Request not allowed', 'Request failed');
        $this->addError($err);
        return $this->outJsonApi();
    }
}