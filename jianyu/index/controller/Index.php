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
        if(Catfish::hasGet('pulldown')){
            $htmls = $this->showpart();
        }
        else{
            $htmls = $this->show();
        }
        return $htmls;
    }
    public function column($find = 0)
    {
        $this->readydisplay();
        $this->getcolumn(intval($find));
        if(Catfish::hasGet('pulldown')){
            $htmls = $this->showpart('column');
        }
        else{
            $htmls = $this->show('column',$find,'');
        }
        return $htmls;
    }
    public function post($find = 0)
    {
        if(Catfish::hasPost('act')){
            $act = Catfish::getPost('act');
            if($act == 'paypoints'){
                $tid = intval(Catfish::getPost('pid'));
                $uid = Catfish::getSession('user_id');
                $tie = Catfish::db('tie')->where('id', $tid)->field('uid,jifen,jinbi,zhifufangshi')->limit(1)->find();
                $user = Catfish::db('users')->where('id', $uid)->field('jifen,jinbi')->limit(1)->find();
                $paid = Catfish::db('tie_jifen')->where('uid', $uid)->where('tid', $tid)->field('id')->limit(1)->find();
                if(!empty($paid)){
                    echo Catfish::lang('You have already paid');
                    exit();
                }
                if($tie['zhifufangshi'] == 1 && $tie['jifen'] > $user['jifen']){
                    echo Catfish::lang('You don\'t have enough points') . '(' . Catfish::lang('Points balance') . ': ' . $user['jifen'] . ')';
                    exit();
                }
                elseif($tie['zhifufangshi'] == 2 && $tie['jinbi'] > $user['jinbi']){
                    echo Catfish::lang('You don\'t have enough forum coins') . '(' . Catfish::lang('Forum coins balance') . ': ' . $user['jinbi'] . ')';
                    exit();
                }
                else{
                    $now = Catfish::now();
                    $tie['jifen'] = intval($tie['jifen']);
                    $tie['jinbi'] = intval($tie['jinbi']);
                    Catfish::dbStartTrans();
                    try{
                        if($tie['zhifufangshi'] == 1){
                            Catfish::db('users')
                                ->where('id', $uid)
                                ->update([
                                    'jifen' => Catfish::dbRaw('jifen-' . $tie['jifen'])
                                ]);
                            Catfish::db('users')
                                ->where('id', $tie['uid'])
                                ->update([
                                    'jifen' => Catfish::dbRaw('jifen+' . $tie['jifen'])
                                ]);
                            if($tie['jifen'] != 0){
                                Catfish::db('points_book')->insert([
                                    'uid' => $uid,
                                    'zengjian' => - $tie['jifen'],
                                    'booktime' => $now,
                                    'miaoshu' => Catfish::lang('See posts to pay points')
                                ]);
                                Catfish::db('points_book')->insert([
                                    'uid' => $tie['uid'],
                                    'zengjian' => $tie['jifen'],
                                    'booktime' => $now,
                                    'miaoshu' => Catfish::lang('Posts received points')
                                ]);
                            }
                        }
                        elseif($tie['zhifufangshi'] == 2){
                            Catfish::db('users')
                                ->where('id', $uid)
                                ->update([
                                    'jinbi' => Catfish::dbRaw('jinbi-' . $tie['jinbi'])
                                ]);
                            Catfish::db('users')
                                ->where('id', $tie['uid'])
                                ->update([
                                    'jinbi' => Catfish::dbRaw('jinbi+' . $tie['jinbi'])
                                ]);
                            if($tie['jinbi'] != 0){
                                Catfish::db('coin_bill')->insert([
                                    'uid' => $uid,
                                    'zengjian' => - $tie['jinbi'],
                                    'booktime' => $now,
                                    'miaoshu' => Catfish::lang('See posts to pay forum coins')
                                ]);
                                Catfish::db('coin_bill')->insert([
                                    'uid' => $tie['uid'],
                                    'zengjian' => $tie['jinbi'],
                                    'booktime' => $now,
                                    'miaoshu' => Catfish::lang('Posts received forum coins')
                                ]);
                            }
                        }
                        if($tie['zhifufangshi'] > 0){
                            Catfish::db('tie_jifen')->insert([
                                'tid' => $tid,
                                'uid' => $uid,
                                'paytime' => $now
                            ]);
                        }
                        Catfish::dbCommit();
                        echo 'ok';
                        exit();
                    } catch (\Exception $e) {
                        Catfish::dbRollback();
                        echo Catfish::lang('The operation failed, please try again later');
                        exit();
                    }
                }
            }
            exit();
        }
        $this->readydisplay();
        $sort = $this->getpost(intval($find));
        if(Catfish::hasGet('pulldown')){
            $htmls = $this->showpart('post');
        }
        else{
            $htmls = $this->show('post',$sort,'');
        }
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
        if(Catfish::hasGet('pulldown')){
            $htmls = $this->showpart('column');
        }
        else{
            $htmls = $this->show('column');
        }
        return $htmls;
    }
    public function type($find = '')
    {
        $this->readydisplay();
        $this->gettype(intval($find));
        if(Catfish::hasGet('pulldown')){
            $htmls = $this->showpart('column');
        }
        else{
            $htmls = $this->show('column');
        }
        return $htmls;
    }
    public function face($find = '')
    {
        $this->readydisplay();
        $title = Catfish::lang('Column');
        $tempath = ROOT_PATH.$this->tempPath.$this->template.DS.'face'.DS.$find.'.html';
        if(is_file($tempath)){
            $file = file($tempath);
            $first = trim($file[0]);
            if(substr($first, 0, 4) == '<!--' && substr($first, -3) == '-->'){
                $title = substr($first, 4, strlen($first)-7);
                $title = Catfish::lang($title);
            }
        }
        else{
            Catfish::toError();
        }
        Catfish::allot('daohang', [
            [
                'label' => Catfish::lang('Home'),
                'href' => Catfish::url('index/Index/index'),
                'icon' => '',
                'active' => 0
            ],
            [
                'label' => $title,
                'href' => Catfish::url('index/Index/face', ['find' => $find]),
                'icon' => '',
                'active' => 0
            ]
        ]);
        Catfish::allot('biaoti',$title);
        $htmls = $this->show('face/'.$find);
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
                $reur = Catfish::db('users')->where('id',Catfish::getSession('user_id'))->field('nicheng,createtime,pinglun')->find();
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
                $tiefl = Catfish::db('tie')->where('id', $tid)->field('sid,pinglun')->find();
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
                        'status' => $review,
                        'content' => $gentie
                    ]);
                    Catfish::db('tie_comm_ontact')->insert([
                        'tid' => $tid,
                        'cid' => $cid,
                        'uid' => $uid,
                        'status' => $review
                    ]);
                    if($review == 1){
                        $plarr = [
                            'id' => $cid,
                            'nicheng' => subtext($reur['nicheng'], 8),
                            'shijian' => $now,
                            'neirong' => subtext(trim(strip_tags($gentie)), 57)
                        ];
                        array_unshift($pinglun, $plarr);
                        $pinglun = serialize($pinglun);
                    }
                    Catfish::db('tie')
                        ->where('id', $tid)
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
                            'jifen' => Catfish::dbRaw('jifen+'.$chengzhang['jifen']['followup']),
                            'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['chengzhang']['followup'])
                        ]);
                    if($chengzhang['jifen']['followup'] != 0){
                        Catfish::db('points_book')->insert([
                            'uid' => $uid,
                            'zengjian' => $chengzhang['jifen']['followup'],
                            'booktime' => $now,
                            'miaoshu' => Catfish::lang('Follow posts')
                        ]);
                    }
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
                $reur = Catfish::db('users')->where('id',Catfish::getSession('user_id'))->field('nicheng,createtime,pinglun')->find();
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
                $tiefl = Catfish::db('tie')->where('id', $tid)->field('sid,pinglun')->find();
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
                    if($review == 1){
                        $plarr = [
                            'id' => $subcid,
                            'nicheng' => subtext($reur['nicheng'], 8),
                            'shijian' => $now,
                            'neirong' => subtext(trim(strip_tags($gentie)), 57)
                        ];
                        array_unshift($pinglun, $plarr);
                        $pinglun = serialize($pinglun);
                    }
                    Catfish::db('tie')
                        ->where('id', $tid)
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
                            'pinglun' => Catfish::dbRaw('pinglun+1'),
                            'jifen' => Catfish::dbRaw('jifen+'.$chengzhang['jifen']['reply']),
                            'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['chengzhang']['reply'])
                        ]);
                    if($chengzhang['jifen']['reply'] != 0){
                        Catfish::db('points_book')->insert([
                            'uid' => $uid,
                            'zengjian' => $chengzhang['jifen']['reply'],
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
                    ->view('users','nicheng,touxiang,qianming,createtime as jiaru,lastlogin as zuijindenglu,lastonline as zuijinzaixian,dengji,fatie as uzhutie,pinglun as ugentie','users.id=tie_comments.uid')
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
                        'uid' => $uid,
                        'accesstime' => Catfish::now()
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
                        'jifen' => Catfish::dbRaw('jifen+'.$chengzhang['jifen']['like']),
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['chengzhang']['like'])
                    ]);
                if($chengzhang['jifen']['like'] != 0){
                    Catfish::db('points_book')->insert([
                        'uid' => $uid,
                        'zengjian' => $chengzhang['jifen']['like'],
                        'booktime' => Catfish::now(),
                        'miaoshu' => Catfish::lang('Like')
                    ]);
                }
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
                        'uid' => $uid,
                        'accesstime' => Catfish::now()
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
                        'jifen' => Catfish::dbRaw('jifen+'.$chengzhang['jifen']['stepon']),
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['chengzhang']['stepon'])
                    ]);
                if($chengzhang['jifen']['stepon'] != 0){
                    Catfish::db('points_book')->insert([
                        'uid' => $uid,
                        'zengjian' => $chengzhang['jifen']['stepon'],
                        'booktime' => Catfish::now(),
                        'miaoshu' => Catfish::lang('Dislike')
                    ]);
                }
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
                        'jifen' => Catfish::dbRaw('jifen+'.$chengzhang['jifen']['collection']),
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['chengzhang']['collection'])
                    ]);
                if($chengzhang['jifen']['collection'] != 0){
                    Catfish::db('points_book')->insert([
                        'uid' => $uid,
                        'zengjian' => $chengzhang['jifen']['collection'],
                        'booktime' => Catfish::now(),
                        'miaoshu' => Catfish::lang('Collect')
                    ]);
                }
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
                        'uid' => $uid,
                        'accesstime' => Catfish::now()
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
                        'jifen' => Catfish::dbRaw('jifen+'.$chengzhang['jifen']['flike']),
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['chengzhang']['flike'])
                    ]);
                if($chengzhang['jifen']['flike'] != 0){
                    Catfish::db('points_book')->insert([
                        'uid' => $uid,
                        'zengjian' => $chengzhang['jifen']['flike'],
                        'booktime' => Catfish::now(),
                        'miaoshu' => Catfish::lang('Like to follow')
                    ]);
                }
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
                        'uid' => $uid,
                        'accesstime' => Catfish::now()
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
                        'jifen' => Catfish::dbRaw('jifen+'.$chengzhang['jifen']['fstepon']),
                        'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['chengzhang']['fstepon'])
                    ]);
                if($chengzhang['jifen']['fstepon'] != 0){
                    Catfish::db('points_book')->insert([
                        'uid' => $uid,
                        'zengjian' => $chengzhang['jifen']['fstepon'],
                        'booktime' => Catfish::now(),
                        'miaoshu' => Catfish::lang('Dislike following posts')
                    ]);
                }
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
                $tiepl = Catfish::db('tie')->where('id', $tid)->field('pinglun')->find();
                if(!empty($tiepl['pinglun'])){
                    $pinglun = unserialize($tiepl['pinglun']);
                    $resmz = Catfish::getForum();
                    if($resmz['fpreaudit'] == 0){
                        foreach($pinglun as $key => $val){
                            if($val['id'] == $cid){
                                $pinglun[$key]['shijian'] = $now;
                                $pinglun[$key]['neirong'] = subtext(trim(strip_tags($gentie)), 57);
                                break;
                            }
                        }
                    }
                    else{
                        foreach($pinglun as $key => $val){
                            if($val['id'] == $cid){
                                unset($pinglun[$key]);
                                break;
                            }
                        }
                    }
                    $pinglun = serialize($pinglun);
                    Catfish::db('tie')->where('id', $tid)->update([
                        'pinglun' => $pinglun
                    ]);
                }
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
                $tiepl = Catfish::db('tie')->where('id', $tid)->field('pinglun')->find();
                if(!empty($tiepl['pinglun'])){
                    $pinglun = unserialize($tiepl['pinglun']);
                    foreach($pinglun as $key => $val){
                        if($val['id'] == $cid){
                            unset($pinglun[$key]);
                            break;
                        }
                    }
                    $pinglun = serialize($pinglun);
                    Catfish::db('tie')->where('id', $tid)->update([
                        'pinglun' => $pinglun
                    ]);
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
                ->where('id', $pid)
                ->update([
                    'yuedu' => Catfish::dbRaw('yuedu+1'),
                    'lastvisit' => Catfish::now()
                ]);
            if($islog){
                $access = Catfish::db('tie_access')->where('uid', $uid)->where('tid', $pid)->field('id')->limit(1)->find();
                if(empty($access)){
                    $now = Catfish::now();
                    $chengzhang = Catfish::getGrowing();
                    Catfish::db('users')
                        ->where('id', $uid)
                        ->update([
                            'jifen' => Catfish::dbRaw('jifen+'.$chengzhang['jifen']['access']),
                            'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['chengzhang']['access'])
                        ]);
                    if($chengzhang['jifen']['access'] != 0){
                        Catfish::db('points_book')->insert([
                            'uid' => $uid,
                            'zengjian' => $chengzhang['jifen']['access'],
                            'booktime' => $now,
                            'miaoshu' => Catfish::lang('Visit the main post')
                        ]);
                    }
                    Catfish::db('tie_access')->insert([
                        'tid' => $pid,
                        'uid' => $uid,
                        'accesstime' => $now
                    ]);
                }
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
    public function qiandao()
    {
        if(Catfish::hasSession('user_id')){
            if(Catfish::hasPost('act'))
            {
                $act = Catfish::getPost('act');
                if($act == 'qiandao'){
                    $uid = Catfish::getSession('user_id');
                    $today = date("Y-m-d");
                    $lianxu = 1;
                    $isqiandao = false;
                    $qiandao = Catfish::db('sign_in')->where('uid', $uid)->field('id,qiandao,lianxu')->order('id desc')->limit(1)->find();
                    if(!empty($qiandao)){
                        if($qiandao['qiandao'] == $today){
                            $isqiandao = true;
                        }
                        elseif($qiandao['qiandao'] == date('Y-m-d', strtotime('yesterday'))){
                            $lianxu = $qiandao['lianxu'] + 1;
                        }
                    }
                    if($isqiandao == false){
                        Catfish::db('sign_in')->insert([
                            'uid' => $uid,
                            'qiandao' => $today,
                            'lianxu' => $lianxu
                        ]);
                        Catfish::setCookie('qiandao_' . $uid, $today, 86400);
                        $qiandao = Catfish::get('qiandaojifen');
                        if(!empty($qiandao)){
                            $qiandao = unserialize($qiandao);
                            $jifen = intval($qiandao['checkin']);
                            if($lianxu > 1){
                                $jifen += intval($qiandao['checkincontinu']);
                            }
                            switch($lianxu){
                                case 3:
                                    $jifen += intval($qiandao['checkinthreedays']);
                                    break;
                                case 7:
                                    $jifen += intval($qiandao['checkinweek']);
                                    break;
                                case 14:
                                    $jifen += intval($qiandao['checkintwoweek']);
                                    break;
                                case 30:
                                    $jifen += intval($qiandao['checkinmonth']);
                                    break;
                                case 60:
                                    $jifen += intval($qiandao['checkintwomonth']);
                                    break;
                                case 90:
                                    $jifen += intval($qiandao['checkinthreemonth']);
                                    break;
                                case 182:
                                    $jifen += intval($qiandao['checkinhalfyear']);
                                    break;
                                case 365:
                                    $jifen += intval($qiandao['checkinyear']);
                                    break;
                            }
                            Catfish::db('users')
                                ->where('id', $uid)
                                ->update([
                                    'jifen' => Catfish::dbRaw('jifen+'.$jifen)
                                ]);
                            if($jifen != 0){
                                Catfish::db('points_book')->insert([
                                    'uid' => $uid,
                                    'zengjian' => $jifen,
                                    'booktime' => Catfish::now(),
                                    'miaoshu' => Catfish::lang('Check in')
                                ]);
                            }
                        }
                        $result = [
                            'result' => 'ok',
                            'message' => ''
                        ];
                        return json($result);
                    }
                    else{
                        Catfish::setCookie('qiandao_' . $uid, $today, 86400);
                        $result = [
                            'result' => 'checked',
                            'message' => Catfish::lang('You have checked in today, please check in tomorrow')
                        ];
                        return json($result);
                    }
                }
                else{
                    $result = [
                        'result' => 'error',
                        'message' => Catfish::lang('The operation failed, please try again later')
                    ];
                    return json($result);
                }
            }
            else{
                $result = [
                    'result' => 'error',
                    'message' => Catfish::lang('The operation failed, please try again later')
                ];
                return json($result);
            }
        }
        else{
            $result = [
                'result' => 'nologin',
                'message' => ''
            ];
            return json($result);
        }
    }
}