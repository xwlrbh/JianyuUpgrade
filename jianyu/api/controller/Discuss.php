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
class Discuss extends Jsonapi
{
    private $everyPageShows = 20;
    private $time = 1200;
    public function getData($param)
    {
        if(isset($param['per'])){
            $this->everyPageShows = $param['per'];
        }
        $find = $param['post'];
        $url = Catfish::domain() . 'api/post/' . $find . '/discuss/all';
        $page = 1;
        if(isset($param['page'])){
            $page = $param['page'];
        }
        $order = '';
        if(isset($param['order'])){
            $order = $param['order'];
            $url .= '/order/' . $param['order'];
        }
        if($order == 'latest'){
            $orderstr = 'id desc';
        }
        else{
            $orderstr = 'id asc';
        }
        $cachename = 'api_postgentie_'.$find.'_count';
        $gentiecount = Catfish::getCache($cachename);
        if($gentiecount === false){
            $count = Catfish::db('tie_comm_ontact')
                ->where('tid', $find)
                ->where('status',1)
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
            $gentiecount = [
                'total' => $count,
                'pages' => $pages,
                'first' => $url . '/page/1',
                'last' => $url . '/page/' . $pages,
                'prev' => $prev,
                'next' => $next,
                'self' => $self,
            ];
            Catfish::tagCache('api_postgentie')->set($cachename,$gentiecount,$this->time);
        }
        $subcachename = $order.'_'.$page;
        $cachename = 'api_postgentie_'.$find.'_'.$subcachename;
        $gentie = Catfish::getCache($cachename);
        if($gentie === false){
            $cdata = Catfish::db('tie_comm_ontact')
                ->where('tid', $find)
                ->where('status',1)
                ->field('cid')
                ->order($orderstr)
                ->limit(($this->everyPageShows * ($page - 1)), $this->everyPageShows)
                ->select();
            $tmpstr = '';
            foreach($cdata as $val){
                $tmpstr .= empty($tmpstr) ? $val['cid'] : ','.$val['cid'];
            }
            $gentie = Catfish::view('tie_comments','id,uid,createtime,xiugai,zan,cai,content')
                ->view('users','nicheng,touxiang','users.id=tie_comments.uid')
                ->where('tie_comments.id','in',$tmpstr)
                ->where('tie_comments.status','=',1)
                ->order('tie_comments.'.$orderstr)
                ->select();
            Catfish::tagCache('api_postgentie')->set($cachename,$gentie,$this->time);
        }
        if(empty($gentie)){
            $err = $this->createError('604', 'The comment list is empty', 'No comments found');
            $this->addError($err);
        }
        else{
            $this->addLinks([
                'self' => $gentiecount['self'],
                'first' => $gentiecount['first'],
                'last' => $gentiecount['last'],
                'prev' => $gentiecount['prev'],
                'next' => $gentiecount['next'],
            ]);
            foreach($gentie as $key => $val){
                $author = $this->createData('people', $val['uid'], [
                    'nickname' => $val['nicheng'],
                    'avatar' => Catfish::domain() . 'data/avatar/' . $val['touxiang']
                ]);
                $data = $this->createData('discuss', $val['id'], [
                    'body' => $this->filterContent($val['content']),
                    'created' => $val['createtime'],
                    'updated' => $val['xiugai'],
                    'like' => $val['zan'],
                    'dislike' => $val['cai']
                ], null, [
                    'author' => [
                        'data' => $author
                    ]
                ]);
                $this->addData($data);
            }
        }
        return $this->outJsonApi();
    }
    public function add($param, $uid)
    {
        $tiefl = Catfish::db('tie')->where('id', $param['post'])->field('sid,pinglun')->find();
        if(empty($tiefl)){
            $err = $this->createError('715', 'Post no longer exists', 'Failed to follow');
            $this->addError($err);
        }
        else{
            $pid = 0;
            $chengzhang = Catfish::getGrowing();
            $isself = true;
            if(isset($param['discuss']) && !empty($param['discuss'])){
                $pid = $param['discuss'];
                $huifu = 1;
                $jifenbd = $chengzhang['jifen']['reply'];
                $chengzhangbd = $chengzhang['chengzhang']['reply'];
                $rec = Catfish::db('tie_comm_ontact')->where('cid', $pid)->field('tid')->find();
                if($rec['tid'] != $param['post']){
                    $isself = false;
                }
            }
            else{
                $huifu = 0;
                $jifenbd = $chengzhang['jifen']['followup'];
                $chengzhangbd = $chengzhang['chengzhang']['followup'];
            }
            if(!$isself){
                $err = $this->createError('716', 'The post does not match the content of the reply', 'Failed to follow');
                $this->addError($err);
            }
            else{
                $forum = $this->myforumpost($uid);
                if(!$this->checkIllegal($param['content'], $forum['mingan'])){
                    $err = $this->createError('717', 'Posting content contains content that is not allowed', 'Failed to follow');
                    $this->addError($err);
                }
                else{
                    if($forum['lianjie'] == 0){
                        $param['content'] = Catfish::removea($param['content']);
                    }
                    $now = Catfish::now();
                    $review = 1;
                    if($forum['fpreaudit'] == 1){
                        $review = 0;
                    }
                    if($review == 1){
                        if(empty($tiefl['pinglun'])){
                            $pinglun = [];
                        }
                        else{
                            $pinglun = unserialize($tiefl['pinglun']);
                            if(count($pinglun) > 2){
                                $pinglun = array_slice($pinglun, 0, 2);
                            }
                        }
                    }
                    else{
                        $pinglun = $tiefl['pinglun'];
                    }
                    Catfish::dbStartTrans();
                    try{
                        $cid = Catfish::db('tie_comments')->insertGetId([
                            'uid' => $uid,
                            'sid' => $tiefl['sid'],
                            'createtime' => $now,
                            'xiugai' => $now,
                            'parentid' => $pid,
                            'status' => $review,
                            'content' => $param['content']
                        ]);
                        Catfish::db('tie_comm_ontact')->insert([
                            'tid' => $param['post'],
                            'cid' => $cid,
                            'uid' => $uid,
                            'status' => $review
                        ]);
                        if($review == 1){
                            $plarr = [
                                'id' => $cid,
                                'nicheng' => subtext($forum['nicheng'], 8),
                                'shijian' => $now,
                                'neirong' => subtext(trim(strip_tags($param['content'])), 57)
                            ];
                            array_unshift($pinglun, $plarr);
                            $pinglun = serialize($pinglun);
                        }
                        Catfish::db('tie')
                            ->where('id', $param['post'])
                            ->update([
                                'commentime' => $now,
                                'ordertime' => $now,
                                'luid' => $uid,
                                'pinglunshu' => Catfish::dbRaw('pinglunshu+1'),
                                'pinglun' => $pinglun
                            ]);
                        Catfish::db('users')
                            ->where('id', $uid)
                            ->update([
                                'lastgentie' => $now,
                                'pinglun' => Catfish::dbRaw('pinglun+1'),
                                'jifen' => Catfish::dbRaw('jifen+'.$jifenbd),
                                'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhangbd)
                            ]);
                        if($huifu != 1){
                            Catfish::db('points_book')->insert([
                                'uid' => $uid,
                                'zengjian' => $jifenbd,
                                'booktime' => $now,
                                'miaoshu' => Catfish::lang('Follow posts')
                            ]);
                        }
                        else{
                            Catfish::db('points_book')->insert([
                                'uid' => $uid,
                                'zengjian' => $jifenbd,
                                'booktime' => $now,
                                'miaoshu' => Catfish::lang('Reply to follow posts')
                            ]);
                        }
                        Catfish::db('msort')
                            ->where('id', $tiefl['sid'])
                            ->update([
                                'gentie' => Catfish::dbRaw('gentie+1')
                            ]);
                        Catfish::dbCommit();
                        $data = $this->createData('discuss', $cid, [
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
        }
        return $this->outJsonApi();
    }
    public function modify($param, $uid, $id)
    {
        $getuser = Catfish::db('tie_comments')->where('id', $id)->field('uid')->find();
        if($getuser['uid'] != $uid){
            $err = $this->createError('720', 'No operation authority', 'Operation not allowed');
            $this->addError($err);
        }
        else{
            $forum = $this->myforumpost($uid);
            if(!$this->checkIllegal($param['content'], $forum['mingan'])){
                $err = $this->createError('717', 'Posting content contains content that is not allowed', 'Failed to follow');
                $this->addError($err);
            }
            else{
                $now = Catfish::now();
                Catfish::db('tie_comments')->where('id', $id)->update([
                    'xiugai' => $now,
                    'content' => $param['content']
                ]);
                $data = $this->createData('discuss', $id, [
                    'result' => 'ok'
                ]);
                $this->addData($data);
            }
        }
        return $this->outJsonApi();
    }
    public function delete($param, $uid, $id)
    {
        $getuser = Catfish::db('tie_comments')->where('id', $id)->field('uid,sid,createtime')->find();
        if($getuser['uid'] != $uid){
            $err = $this->createError('720', 'No operation authority', 'Operation not allowed');
            $this->addError($err);
        }
        else{
            $mtie = Catfish::db('tie_comm_ontact')->where('cid',$id)->field('tid')->find();
            Catfish::dbStartTrans();
            try{
                Catfish::db('tie_comments')
                    ->where('id', $id)
                    ->delete();
                Catfish::db('tie_comm_ontact')
                    ->where('cid', $id)
                    ->delete();
                Catfish::db('tie_comments')
                    ->where('parentid', $id)
                    ->update([
                        'parentid' => 0
                    ]);
                Catfish::db('tie')
                    ->where('id', $mtie['tid'])
                    ->update([
                        'pinglunshu' => Catfish::dbRaw('pinglunshu-1')
                    ]);
                Catfish::db('msort')
                    ->where('id', $getuser['sid'])
                    ->update([
                        'gentie' => Catfish::dbRaw('gentie-1')
                    ]);
                Catfish::db('tongji')
                    ->where('riqi', date("Y-m-d", strtotime($getuser['createtime'])))
                    ->update([
                        'gentie' => Catfish::dbRaw('gentie-1')
                    ]);
                Catfish::db('gentie_zan')
                    ->where('cid', $id)
                    ->delete();
                Catfish::db('gentie_cai')
                    ->where('cid', $id)
                    ->delete();
                Catfish::dbCommit();
                $data = $this->createData('discuss', $id, [
                    'result' => 'ok'
                ]);
                $this->addData($data);
            } catch (\Exception $e) {
                Catfish::dbRollback();
                $err = $this->createError('710', 'Database storage failure', 'Execution error');
                $this->addError($err);
            }
        }
        return $this->outJsonApi();
    }
    public function patch($param, $uid, $id)
    {
        $getuser = Catfish::db('tie_comments')->where('id', $id)->field('uid')->find();
        if($param['method'] == 'like'){
            if($getuser['uid'] == $uid){
                $err = $this->createError('731', 'Can not like yourself', 'Operation not allowed');
                $this->addError($err);
            }
            else{
                $hasrec = Catfish::db('gentie_zan')->where('cid', $id)->where('uid', $uid)->field('id')->find();
                if(empty($hasrec)){
                    Catfish::dbStartTrans();
                    try{
                        Catfish::db('tie_comments')
                            ->where('id', $id)
                            ->update([
                                'zan' => Catfish::dbRaw('zan+1')
                            ]);
                        Catfish::db('gentie_zan')->insert([
                            'cid' => $id,
                            'uid' => $uid,
                            'accesstime' => Catfish::now()
                        ]);
                        Catfish::dbCommit();
                        $data = $this->createData('discuss', $id, [
                            'result' => 'ok'
                        ]);
                        $this->addData($data);
                    } catch (\Exception $e) {
                        Catfish::dbRollback();
                        $err = $this->createError('710', 'Database storage failure', 'Execution error');
                        $this->addError($err);
                    }
                }
                else{
                    $err = $this->createError('732', 'Already liked', 'Operation not allowed');
                    $this->addError($err);
                }
            }
        }
        elseif($param['method'] == 'dislike'){
            if($getuser['uid'] == $uid){
                $err = $this->createError('733', 'Can not dislike yourself', 'Operation not allowed');
                $this->addError($err);
            }
            else{
                $hasrec = Catfish::db('gentie_cai')->where('cid', $id)->where('uid', $uid)->field('id')->find();
                if(empty($hasrec)){
                    Catfish::dbStartTrans();
                    try{
                        Catfish::db('tie_comments')
                            ->where('id', $id)
                            ->update([
                                'cai' => Catfish::dbRaw('cai+1')
                            ]);
                        Catfish::db('gentie_cai')->insert([
                            'cid' => $id,
                            'uid' => $uid,
                            'accesstime' => Catfish::now()
                        ]);
                        Catfish::dbCommit();
                        $data = $this->createData('discuss', $id, [
                            'result' => 'ok'
                        ]);
                        $this->addData($data);
                    } catch (\Exception $e) {
                        Catfish::dbRollback();
                        $err = $this->createError('710', 'Database storage failure', 'Execution error');
                        $this->addError($err);
                    }
                }
                else{
                    $err = $this->createError('734', 'Already disliked', 'Operation not allowed');
                    $this->addError($err);
                }
            }
        }
        else{
            $err = $this->createError('730', 'Operation instructions are not clear', 'Operation not allowed');
            $this->addError($err);
        }
    }
}