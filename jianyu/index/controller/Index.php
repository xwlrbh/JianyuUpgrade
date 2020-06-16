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
class Index extends CatfishCMS
{
    public function index()
    {
        $this->readydisplay();
        Catfish::allot('daohang', [
            [
                'label' => Catfish::lang('Home'),
                'href' => Catfish::url('index/Index/index'),
                'icon' => '',
                'active' => 0
            ]
        ]);
        Catfish::allot('biaoti','');
        $this->shouye();
        $htmls = $this->show();
        return $htmls;
    }
    public function column($find = 0)
    {
        $this->readydisplay();
        $this->getcolumn(intval($find));
        $htmls = $this->show('column',$find,'');
        return $htmls;
    }
    public function post($find = 0)
    {
        $this->readydisplay();
        $sort = $this->getpost(intval($find));
        $htmls = $this->show('post',$sort,'');
        return $htmls;
    }
    public function search($find = '')
    {
        $this->readydisplay();
        Catfish::allot('daohang', [
            [
                'label' => Catfish::lang('Home'),
                'href' => Catfish::url('index/Index/index'),
                'icon' => '',
                'active' => 0
            ],
            [
                'label' => Catfish::lang('Search results'),
                'href' => '#!',
                'icon' => '',
                'active' => 1
            ]
        ]);
        Catfish::allot('biaoti',$find);
        $this->getsearch($find);
        $htmls = $this->show('column');
        return $htmls;
    }
    public function type($find = '')
    {
        $this->readydisplay();
        $this->gettype(intval($find));
        $htmls = $this->show('column');
        return $htmls;
    }
    public function gentie()
    {
        if(Catfish::isLogin()){
            $gentie = Catfish::getPost('gtnr',false);
            $gentie = trim($gentie);
            $gtxt = strip_tags($gentie);
            if(empty($gtxt)){
                $re['result'] = 'error';
                $re['message'] = Catfish::lang('The content of the post cannot be empty');
                return json($re);
            }
            elseif($this->needvcode() == 1 && !captcha_check(Catfish::getPost('captcha'))){
                $re['result'] = 'error';
                $re['message'] = Catfish::lang('Verification code error');
                return json($re);
            }
            else{
                $reur = Catfish::db('users')->where('id',Catfish::getSession('user_id'))->field('createtime,pinglun')->find();
                if($reur['pinglun'] == 0){
                    $resmz = Catfish::getForum();
                    if(Catfish::shixian($reur['createtime'], $resmz['shichanggt']) == false){
                        $re['result'] = 'error';
                        $re['message'] = Catfish::lang('Newly registered users are temporarily unable to follow up');
                        return json($re);
                    }
                }
                $forum = $this->myforumpost();
                if($forum['lianjie'] == 0){
                    $gentie = Catfish::removea($gentie);
                }
                if(!$this->checkIllegal($gentie, $forum['mingan'])){
                    $re['result'] = 'error';
                    $re['message'] = Catfish::lang('Contains prohibited content, please modify and try again');
                    return json($re);
                }
                $now = Catfish::now();
                $tid = intval(Catfish::getPost('pid'));
                $uid = Catfish::getSession('user_id');
                $tiefl = Catfish::db('tie')->where('id', $tid)->field('sid')->find();
                if(empty($tiefl)){
                    $re['result'] = 'error';
                    $re['message'] = Catfish::lang('Follow-up has been closed');
                    return json($re);
                }
                $chengzhang = Catfish::getGrowing();
                $review = 1;
                if($forum['fpreaudit'] == 1){
                    $review = 0;
                }
                Catfish::dbStartTrans();
                try{
                    $cid = Catfish::db('tie_comments')->insertGetId([
                        'uid' => $uid,
                        'sid' => $tiefl['sid'],
                        'createtime' => $now,
                        'xiugai' => $now,
                        'status' => $review,
                        'content' => $gentie
                    ]);
                    Catfish::db('tie_comm_ontact')->insert([
                        'tid' => $tid,
                        'cid' => $cid,
                        'uid' => $uid,
                        'status' => $review
                    ]);
                    Catfish::db('tie')
                        ->where('id', $tid)
                        ->update([
                            'commentime' => $now,
                            'ordertime' => $now,
                            'luid' => $uid,
                            'pinglunshu' => Catfish::dbRaw('pinglunshu+1')
                        ]);
                    Catfish::db('users')
                        ->where('id', $uid)
                        ->update([
                            'lastgentie' => $now,
                            'pinglun' => Catfish::dbRaw('pinglun+1'),
                            'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['followup'])
                        ]);
                    Catfish::db('msort')
                        ->where('id', $tiefl['sid'])
                        ->update([
                            'gentie' => Catfish::dbRaw('gentie+1')
                        ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    $re['result'] = 'error';
                    $re['message'] = Catfish::lang('The operation failed, please try again later');
                    return json($re);
                }
                $re = Catfish::db('users')->where('id', $uid)->field('nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie')->find();
                $re['id'] = $cid;
                $re['uid'] = $uid;
                $re['gentie'] = $gentie;
                $re['gentieshijian'] = $now;
                $this->filtergentief($re);
                $re['result'] = 'ok';
                $re['message'] = '';
                Catfish::tongji('gentie');
                Catfish::clearCache('postgentie_'.$tid);
                Catfish::removeCache('fujianxiazai_'.$tid.'_'.$uid);
                if(Catfish::getCache('needvcode_'.$uid) == 1){
                    Catfish::removeCache('needvcode_'.$uid);
                }
                return json($re);
            }
        }
        else{
            $re['result'] = 'error';
            $re['message'] = Catfish::lang('Post only after login');
            return json($re);
        }
    }
    public function huifu()
    {
        if(Catfish::isLogin()){
            $gentie = Catfish::getPost('gtnr',false);
            $gentie = trim($gentie);
            $gtxt = strip_tags($gentie);
            if(empty($gtxt)){
                $re['result'] = 'error';
                $re['message'] = Catfish::lang('The content of the post cannot be empty');
                return json($re);
            }
            elseif($this->needvcode() == 1 && !captcha_check(Catfish::getPost('captcha'))){
                $re['result'] = 'error';
                $re['message'] = Catfish::lang('Verification code error');
                return json($re);
            }
            else{
                $reur = Catfish::db('users')->where('id',Catfish::getSession('user_id'))->field('createtime,pinglun')->find();
                if($reur['pinglun'] == 0){
                    $resmz = Catfish::getForum();
                    if(Catfish::shixian($reur['createtime'], $resmz['shichanggt']) == false){
                        $re['result'] = 'error';
                        $re['message'] = Catfish::lang('Newly registered users are temporarily unable to follow up');
                        return json($re);
                    }
                }
                $forum = $this->myforumpost();
                if($forum['lianjie'] == 0){
                    $gentie = Catfish::removea($gentie);
                }
                if(!$this->checkIllegal($gentie, $forum['mingan'])){
                    $re['result'] = 'error';
                    $re['message'] = Catfish::lang('Contains prohibited content, please modify and try again');
                    return json($re);
                }
                $now = Catfish::now();
                $tid = intval(Catfish::getPost('pid'));
                $cid = intval(Catfish::getPost('cid'));
                $uid = Catfish::getSession('user_id');
                $rec = Catfish::db('tie_comm_ontact')->where('cid', $cid)->field('tid')->find();
                if($rec['tid'] != $tid){
                    $re['result'] = 'error';
                    $re['message'] = Catfish::lang('The operation failed, please try again later');
                    return json($re);
                }
                $tiefl = Catfish::db('tie')->where('id', $tid)->field('sid')->find();
                if(empty($tiefl)){
                    $re['result'] = 'error';
                    $re['message'] = Catfish::lang('Follow-up has been closed');
                    return json($re);
                }
                $chengzhang = Catfish::getGrowing();
                $review = 1;
                if($forum['fpreaudit'] == 1){
                    $review = 0;
                }
                Catfish::dbStartTrans();
                try{
                    $subcid = Catfish::db('tie_comments')->insertGetId([
                        'uid' => $uid,
                        'sid' => $tiefl['sid'],
                        'createtime' => $now,
                        'xiugai' => $now,
                        'parentid' => $cid,
                        'status' => $review,
                        'content' => $gentie
                    ]);
                    Catfish::db('tie_comm_ontact')->insert([
                        'tid' => $tid,
                        'cid' => $subcid,
                        'uid' => $uid,
                        'status' => $review
                    ]);
                    Catfish::db('tie')
                        ->where('id', $tid)
                        ->update([
                            'commentime' => $now,
                            'ordertime' => $now,
                            'luid' => $uid,
                            'pinglunshu' => Catfish::dbRaw('pinglunshu+1')
                        ]);
                    Catfish::db('users')
                        ->where('id', $uid)
                        ->update([
                            'pinglun' => Catfish::dbRaw('pinglun+1'),
                            'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['reply'])
                        ]);
                    Catfish::db('msort')
                        ->where('id', $tiefl['sid'])
                        ->update([
                            'gentie' => Catfish::dbRaw('gentie+1')
                        ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    $re['result'] = 'error';
                    $re['message'] = Catfish::lang('The operation failed, please try again later');
                    return json($re);
                }
                $re = Catfish::db('users')->where('id', $uid)->field('nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie')->find();
                $re['id'] = $subcid;
                $re['uid'] = $uid;
                $re['gentie'] = $gentie;
                $re['gentieshijian'] = $now;
                $this->filtergentief($re);
                $replied = Catfish::view('tie_comments','id,uid,createtime as gentieshijian,content as neirong')
                    ->view('users','nicheng,touxiang,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie_comments.uid')
                    ->where('tie_comments.id',$cid)
                    ->where('tie_comments.status','=',1)
                    ->find();
                if(!empty($replied)){
                    $re['beihuifu'] = $this->filterplr($replied);
                }
                else{
                    $re['beihuifu'] = [];
                }
                $re['result'] = 'ok';
                $re['message'] = '';
                Catfish::tongji('gentie');
                Catfish::clearCache('postgentie_'.$tid);
                Catfish::removeCache('fujianxiazai_'.$tid.'_'.$uid);
                if(Catfish::getCache('needvcode_'.$uid) == 1){
                    Catfish::removeCache('needvcode_'.$uid);
                }
                return json($re);
            }
        }
        else{
            $re['result'] = 'error';
            $re['message'] = Catfish::lang('Post only after login');
            return json($re);
        }
    }
    public function postzan()
    {
        if(Catfish::isLogin()){
            $tid = Catfish::getPost('pid');
            $uid = Catfish::getSession('user_id');
            $getuser = Catfish::db('tie')->where('id', $tid)->field('uid')->find();
            if($getuser['uid'] == $uid){
                echo Catfish::lang('You can\'t give yourself a compliment');
                exit();
            }
            $hasrec = Catfish::db('tie_zan')->where('tid', $tid)->where('uid', $uid)->field('id')->find();
            if(empty($hasrec)){
                Catfish::dbStartTrans();
                try{
                    Catfish::db('tie')
                        ->where('id', $tid)
                        ->update([
                            'zan' => Catfish::dbRaw('zan+1')
                        ]);
                    Catfish::db('tie_zan')->insert([
                        'tid' => $tid,
                        'uid' => $uid
                    ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                $chengzhang = Catfish::getGrowing();
                Catfish::db('users')
                    ->where('id', $uid)
                    ->update([
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['like'])
                    ]);
                $post = Catfish::getCache('post_'.$tid);
                if($post != false){
                    $post['zan'] ++ ;
                    Catfish::tagCache('post')->set('post_'.$tid,$post,$this->time);
                }
                echo 'ok';
            }
            else{
                echo Catfish::lang('You have already liked it, you can\'t repeat it');
            }
        }
        else{
            echo Catfish::lang('Please log in first');
        }
        exit();
    }
    public function postcai()
    {
        if(Catfish::isLogin()){
            $tid = Catfish::getPost('pid');
            $uid = Catfish::getSession('user_id');
            $getuser = Catfish::db('tie')->where('id', $tid)->field('uid')->find();
            if($getuser['uid'] == $uid){
                echo Catfish::lang('You can\'t give yourself a bad review');
                exit();
            }
            $hasrec = Catfish::db('tie_cai')->where('tid', $tid)->where('uid', $uid)->field('id')->find();
            if(empty($hasrec)){
                Catfish::dbStartTrans();
                try{
                    Catfish::db('tie')
                        ->where('id', $tid)
                        ->update([
                            'cai' => Catfish::dbRaw('cai+1')
                        ]);
                    Catfish::db('tie_cai')->insert([
                        'tid' => $tid,
                        'uid' => $uid
                    ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                $chengzhang = Catfish::getGrowing();
                Catfish::db('users')
                    ->where('id', $uid)
                    ->update([
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['stepon'])
                    ]);
                $post = Catfish::getCache('post_'.$tid);
                if($post != false){
                    $post['cai'] ++ ;
                    Catfish::tagCache('post')->set('post_'.$tid,$post,$this->time);
                }
                echo 'ok';
            }
            else{
                echo Catfish::lang('You have given a bad review, you can\'t repeat it');
            }
        }
        else{
            echo Catfish::lang('Please log in first');
        }
        exit();
    }
    public function postshoucang()
    {
        if(Catfish::isLogin()){
            $tid = Catfish::getPost('pid');
            $uid = Catfish::getSession('user_id');
            $getuser = Catfish::db('tie')->where('id', $tid)->field('uid')->find();
            if($getuser['uid'] == $uid){
                echo Catfish::lang('You can\'t bookmark your own posts');
                exit();
            }
            $hasrec = Catfish::db('tie_favorites')->where('tid', $tid)->where('uid', $uid)->field('id')->find();
            if(empty($hasrec)){
                $now = Catfish::now();
                Catfish::dbStartTrans();
                try{
                    Catfish::db('tie_favorites')->insert([
                        'uid' => $uid,
                        'tid' => $tid,
                        'createtime' => $now
                    ]);
                    Catfish::db('tie')
                        ->where('id', $tid)
                        ->update([
                            'shoucang' => Catfish::dbRaw('shoucang+1'),
                            'cangtime' => $now
                        ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                $chengzhang = Catfish::getGrowing();
                Catfish::db('users')
                    ->where('id', $uid)
                    ->update([
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['collection'])
                    ]);
                $post = Catfish::getCache('post_'.$tid);
                if($post != false){
                    $post['shoucang'] ++ ;
                    Catfish::tagCache('post')->set('post_'.$tid,$post,$this->time);
                }
                echo 'ok';
            }
            else{
                echo Catfish::lang('You have already collected, can\'t repeat collection');
            }
        }
        else{
            echo Catfish::lang('Please log in first');
        }
        exit();
    }
    public function gentiezan()
    {
        if(Catfish::isLogin()){
            $tid = Catfish::getPost('pid');
            $cid = Catfish::getPost('cid');
            $subcname = Catfish::bd(Catfish::getPost('subcname'));
            $uid = Catfish::getSession('user_id');
            $getuser = Catfish::db('tie_comments')->where('id', $cid)->field('uid')->find();
            if($getuser['uid'] == $uid){
                echo Catfish::lang('You can\'t give yourself a compliment');
                exit();
            }
            $hasrec = Catfish::db('gentie_zan')->where('cid', $cid)->where('uid', $uid)->field('id')->find();
            if(empty($hasrec)){
                Catfish::dbStartTrans();
                try{
                    Catfish::db('tie_comments')
                        ->where('id', $cid)
                        ->update([
                            'zan' => Catfish::dbRaw('zan+1')
                        ]);
                    Catfish::db('gentie_zan')->insert([
                        'cid' => $cid,
                        'uid' => $uid
                    ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                $chengzhang = Catfish::getGrowing();
                Catfish::db('users')
                    ->where('id', $uid)
                    ->update([
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['flike'])
                    ]);
                $post = Catfish::getCache('postgentie_'.$tid.'_'.$subcname);
                if($post != false){
                    foreach($post['tie'] as $key => $val){
                        if($val['id'] == $cid){
                            $post['tie'][$key]['zan'] ++;
                            break;
                        }
                    }
                    Catfish::tagCache('postgentie_'.$tid)->set('postgentie_'.$tid.'_'.$subcname,$post,$this->time);
                }
                echo 'ok';
            }
            else{
                echo Catfish::lang('You have already liked it, you can\'t repeat it');
            }
        }
        else{
            echo Catfish::lang('Please log in first');
        }
        exit();
    }
    public function gentiecai()
    {
        if(Catfish::isLogin()){
            $tid = Catfish::getPost('pid');
            $cid = Catfish::getPost('cid');
            $subcname = Catfish::bd(Catfish::getPost('subcname'));
            $uid = Catfish::getSession('user_id');
            $getuser = Catfish::db('tie_comments')->where('id', $cid)->field('uid')->find();
            if($getuser['uid'] == $uid){
                echo Catfish::lang('You can\'t give yourself a bad review');
                exit();
            }
            $hasrec = Catfish::db('gentie_cai')->where('cid', $cid)->where('uid', $uid)->field('id')->find();
            if(empty($hasrec)){
                Catfish::dbStartTrans();
                try{
                    Catfish::db('tie_comments')
                        ->where('id', $cid)
                        ->update([
                            'cai' => Catfish::dbRaw('cai+1')
                        ]);
                    Catfish::db('gentie_cai')->insert([
                        'cid' => $cid,
                        'uid' => $uid
                    ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                $chengzhang = Catfish::getGrowing();
                Catfish::db('users')
                    ->where('id', $uid)
                    ->update([
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['fstepon'])
                    ]);
                $post = Catfish::getCache('postgentie_'.$tid.'_'.$subcname);
                if($post != false){
                    foreach($post['tie'] as $key => $val){
                        if($val['id'] == $cid){
                            $post['tie'][$key]['cai'] ++;
                            break;
                        }
                    }
                    Catfish::tagCache('postgentie_'.$tid)->set('postgentie_'.$tid.'_'.$subcname,$post,$this->time);
                }
                echo 'ok';
            }
            else{
                echo Catfish::lang('You have given a bad review, you can\'t repeat it');
            }
        }
        else{
            echo Catfish::lang('Please log in first');
        }
        exit();
    }
    public function xiugai()
    {
        if(Catfish::isLogin()){
            $gentie = Catfish::getPost('gtnr',false);
            $gentie = trim($gentie);
            $gtxt = strip_tags($gentie);
            if(empty($gtxt)){
                $re['result'] = 'error';
                $re['message'] = Catfish::lang('The content of the post cannot be empty');
                return json($re);
            }
            else{
                $now = Catfish::now();
                $tid = intval(Catfish::getPost('pid'));
                $cid = intval(Catfish::getPost('cid'));
                $subcname = Catfish::bd(Catfish::getPost('subcname'));
                $uid = Catfish::getSession('user_id');
                $getuser = Catfish::db('tie_comments')->where('id', $cid)->field('uid')->find();
                if($getuser['uid'] != $uid){
                    $re['result'] = 'error';
                    $re['message'] = Catfish::lang('Your operation is illegal');
                    return json($re);
                }
                Catfish::db('tie_comments')->where('id', $cid)->update([
                    'xiugai' => $now,
                    'content' => $gentie
                ]);
                $re['result'] = 'ok';
                $re['message'] = '';
                Catfish::removeCache('postgentie_'.$tid.'_'.$subcname);
                return json($re);
            }
        }
        else{
            $re['result'] = 'error';
            $re['message'] = Catfish::lang('Please log in first');
            return json($re);
        }
    }
    public function shanchugentie()
    {
        if(Catfish::isLogin()){
            $tid = intval(Catfish::getPost('pid'));
            $cid = intval(Catfish::getPost('cid'));
            $subcname = Catfish::bd(Catfish::getPost('subcname'));
            $uid = Catfish::getSession('user_id');
            $getuser = Catfish::db('tie_comments')->where('id', $cid)->field('uid,sid,createtime')->find();
            if($getuser['uid'] != $uid){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            else{
                Catfish::dbStartTrans();
                try{
                    Catfish::db('tie_comments')
                        ->where('id', $cid)
                        ->delete();
                    Catfish::db('tie_comm_ontact')
                        ->where('cid', $cid)
                        ->delete();
                    Catfish::db('tie_comments')
                        ->where('parentid', $cid)
                        ->update([
                            'parentid' => 0
                        ]);
                    Catfish::db('tie')
                        ->where('id', $tid)
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
                        ->where('cid', $cid)
                        ->delete();
                    Catfish::db('gentie_cai')
                        ->where('cid', $cid)
                        ->delete();
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                $post = Catfish::getCache('postgentie_'.$tid.'_'.$subcname);
                if($post != false){
                    foreach($post['tie'] as $key => $val){
                        if($val['id'] == $cid){
                            unset($post['tie'][$key]);
                            break;
                        }
                    }
                    if(count($post['tie']) < 5){
                        Catfish::clearCache('postgentie_'.$tid);
                    }
                    else{
                        Catfish::tagCache('postgentie_'.$tid)->set('postgentie_'.$tid.'_'.$subcname,$post,$this->time);
                    }
                }
                echo 'ok';
            }
        }
        else{
            echo Catfish::lang('Please log in first');
        }
        exit();
    }
    public function feedback()
    {
        $pid = intval(Catfish::getPost('pid'));
        $islog = Catfish::isLogin();
        $uid = 0;
        if($islog){
            $uid = intval(Catfish::getSession('user_id'));
        }
        if($pid > 0){
            Catfish::db('tie')
                ->where('id', Catfish::getPost('pid'))
                ->update([
                    'yuedu' => Catfish::dbRaw('yuedu+1'),
                    'lastvisit' => Catfish::now()
                ]);
            if($islog){
                $chengzhang = Catfish::getGrowing();
                Catfish::db('users')
                    ->where('id', $uid)
                    ->update([
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['access'])
                    ]);
            }
        }
        $fkip = Catfish::ip(1);
        $hasuser = Catfish::db('online')->where('fkip', $fkip)->field('id,uid')->find();
        if(!empty($hasuser)){
            Catfish::db('online')->where('id', $hasuser['id'])->update([
                'uid' => $uid,
                'onlinetime' => time()
            ]);
        }
        else{
            Catfish::db('online')->insert([
                'fkip' => $fkip,
                'uid' => $uid,
                'onlinetime' => time()
            ]);
        }
    }
    public function active()
    {
        $id = Catfish::getGet('u');
        $vstr = Catfish::getGet('v');
        if($vstr == 'e'){
            $jianyu = Catfish::db('users')->where('id', $id)->field('yonghu,randomcode,status')->find();
            if($jianyu['status'] == 2){
                if(md5($jianyu['yonghu'].$jianyu['randomcode']) == Catfish::getGet('c')){
                    Catfish::db('users')->where('id', $id)->update([
                        'status' => 1
                    ]);
                    Catfish::redirect('login/Index/index');
                }
            }
            else{
                Catfish::toError();
            }
        }
        else{
            Catfish::toError();
        }
    }
}