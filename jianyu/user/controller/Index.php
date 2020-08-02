<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://jianyuluntan.com All rights reserved.
 */
namespace app\user\controller;
use catfishcms\Catfish;
class Index extends CatfishCMS
{
    public function index()
    {
        $this->checkUser();
        $resmz = Catfish::getForum();
        $forum = $this->myforum($resmz);
        $reur = Catfish::db('users')->where('id',Catfish::getSession('user_id'))->field('createtime,fatie')->find();
        $needvcode = 0;
        if($resmz['yanzhengzt'] > $reur['fatie']){
            $needvcode = 1;
        }
        if(Catfish::isPost(20)){
            $sid = Catfish::getPost('sid');
            if($sid == 0){
                echo Catfish::lang('Section must be selected');
                exit();
            }
            $tietype = Catfish::getPost('tietype');
            if($tietype == 0){
                echo Catfish::lang('Type must be selected');
                exit();
            }
            $data = $this->sendnewpostsPost($needvcode);
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                if($reur['fatie'] == 0 && Catfish::shixian($reur['createtime'], $resmz['shichangzt']) == false){
                    echo Catfish::lang('Newly registered users are temporarily unable to post');
                    exit();
                }
                $jifenleixing = 0;
                if(Catfish::hasPost('jifenleixing')){
                    $jifenleixing = intval(Catfish::getPost('jifenleixing'));
                }
                $jifen = 0;
                if(Catfish::hasPost('jifen')){
                    $jifen = intval(Catfish::getPost('jifen'));
                }
                if($jifen < 0){
                    echo Catfish::lang('Points cannot be negative');
                    exit();
                }
                $zhengwen = Catfish::getPost('zhengwen', false);
                if($forum['lianjie'] == 0){
                    $zhengwen = Catfish::removea($zhengwen);
                }
                if(Catfish::getSession('user_type') != 1){
                    if(!$this->checkIllegal($zhengwen, $forum['mingan']) || !$this->checkIllegal($data['biaoti'], $forum['mingan'])){
                        echo Catfish::lang('Contains prohibited content, please modify and try again');
                        exit();
                    }
                }
                $fujian = '';
                $annex = 0;
                $size = 0;
                $file = request()->file('fujian');
                if($file){
                    $validate = [
                        'ext' => $forum['geshi']
                    ];
                    $info = $file->validate($validate)->move(ROOT_PATH . 'data' . DS . 'annex');
                    if($info){
                        $size = $file->getInfo('size');
                        $fujian = 'data/annex/'.str_replace('\\','/',$info->getSaveName());
                    }else{
                        echo Catfish::lang('Attachment upload failed') . ': ' . $file->getError();
                        exit();
                    }
                }
                if(!empty($fujian)){
                    $annex = 1;
                }
                $ttname = Catfish::db('tietype')->where('id',$tietype)->field('tpname')->find();
                $ttname = 'tj' . $ttname['tpname'];
                $this->newtongjitb();
                $now = Catfish::now();
                $chengzhang = Catfish::getGrowing();
                $review = 1;
                if($forum['preaudit'] == 1){
                    $review = 0;
                }
                $tus = $this->extractPics($zhengwen);
                $uid = Catfish::getSession('user_id');
                Catfish::dbStartTrans();
                try{
                    $reid = Catfish::db('tie')->insertGetId([
                        'uid' => Catfish::getSession('user_id'),
                        'sid' => $sid,
                        'guanjianzi' => '',
                        'fabushijian' => $now,
                        'biaoti' => $data['biaoti'],
                        'zhaiyao' => Catfish::getPost('zhaiyao'),
                        'review' => $review,
                        'ordertime' => $now,
                        'tietype' => $tietype,
                        'annex' => $annex,
                        'tu' => $tus,
                        'jifenleixing' => $jifenleixing,
                        'jifen' => $jifen
                    ]);
                    Catfish::db('tienr')->insert([
                        'tid' => $reid,
                        'zhengwen' => $zhengwen,
                        'fujian' => $fujian,
                        'fjsize' => $size
                    ]);
                    Catfish::db('users')
                        ->where('id', $uid)
                        ->update([
                            'lastfatie' => $now,
                            'fatie' => Catfish::dbRaw('fatie+1'),
                            'jifen' => Catfish::dbRaw('jifen+'.$chengzhang['jifen']['post']),
                            'chengzhang' => Catfish::dbRaw('chengzhang+'.$chengzhang['chengzhang']['post'])
                        ]);
                    if($chengzhang['jifen']['post'] != 0){
                        Catfish::db('points_book')->insert([
                            'uid' => $uid,
                            'zengjian' => $chengzhang['jifen']['post'],
                            'booktime' => $now,
                            'miaoshu' => Catfish::lang('Post')
                        ]);
                    }
                    Catfish::db('users_tongji')
                        ->where('uid', Catfish::getSession('user_id'))
                        ->update([
                            $ttname => Catfish::dbRaw($ttname.'+1')
                        ]);
                    Catfish::db('tietype')
                        ->where('id', $tietype)
                        ->update([
                            'tongji' => Catfish::dbRaw('tongji+1')
                        ]);
                    Catfish::db('msort')
                        ->where('id', $sid)
                        ->update([
                            'zhutie' => Catfish::dbRaw('zhutie+1'),
                            $ttname => Catfish::dbRaw($ttname.'+1')
                        ]);
                    Catfish::db('users_tongji_'.date('Ym'))
                        ->where('uid', Catfish::getSession('user_id'))
                        ->update([
                            'yuefatie' => Catfish::dbRaw('yuefatie+1')
                        ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                Catfish::tongji('zhutie');
                Catfish::clearCache('shouye');
                Catfish::clearCache('column');
                echo 'ok';
                exit();
            }
        }
        $this->getTieType();
        $fenlei = Catfish::getCache('sort_id_sname_virtual_parentid');
        if($fenlei === false){
            $fenlei = Catfish::getSort('msort', 'id,sname,virtual,parentid');
            Catfish::setCache('sort_id_sname_virtual_parentid',$fenlei,3600);
        }
        $this->adddisabled($fenlei);
        Catfish::allot('fenlei', $fenlei);
        Catfish::allot('forum', $forum);
        Catfish::allot('needvcode', $needvcode);
        return $this->show(Catfish::lang('Send new posts'), 'sendnewposts', true);
    }
    public function mymainpost()
    {
        $this->checkUser();
        $data = Catfish::db('tie')->where('uid', Catfish::getSession('user_id'))->where('status', 1)->field('id,fabushijian,biaoti,isclose,pinglunshu,yuedu,zan,cai,annex')->order('id desc')->paginate(20);
        Catfish::allot('data', $data->items());
        Catfish::allot('pages', $data->render());
        return $this->show(Catfish::lang('My main post'), 'mymainpost');
    }
    public function delmymainpost()
    {
        if(Catfish::isPost(20)){
            $id = Catfish::getPost('id');
            $tmp = Catfish::db('tie')->where('id',$id)->field('uid')->find();
            if($tmp['uid'] != Catfish::getSession('user_id')){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $re = Catfish::db('tie')
                ->where('id', $id)
                ->update([
                    'status' => 0,
                    'recoverytime' => Catfish::now()
                ]);
            if($re == 1){
                Catfish::removeCache('post_'.$id);
                Catfish::clearCache('postgentie_'.$id);
                echo 'ok';
            }
            else{
                echo Catfish::lang('The operation failed, please try again later');
            }
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function modifymainpost()
    {
        $this->checkUser();
        $tid = Catfish::getGet('c');
        $forum = $this->myforum();
        if(Catfish::isPost(20)){
            if($tid == false){
                $tid = Catfish::getPost('tid');
            }
            $tie = Catfish::db('tie')->where('id',$tid)->field('id,uid,sid,biaoti,tietype,annex')->find();
            $sid = Catfish::getPost('sid');
            if($sid == 0){
                echo Catfish::lang('Section must be selected');
                exit();
            }
            $tietype = Catfish::getPost('tietype');
            if($tietype == 0){
                echo Catfish::lang('Type must be selected');
                exit();
            }
            if($tie['uid'] != Catfish::getSession('user_id')){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $data = $this->sendnewpostsPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $jifenleixing = 0;
                if(Catfish::hasPost('jifenleixing')){
                    $jifenleixing = intval(Catfish::getPost('jifenleixing'));
                }
                $jifen = 0;
                if(Catfish::hasPost('jifen')){
                    $jifen = intval(Catfish::getPost('jifen'));
                }
                if($jifen < 0){
                    echo Catfish::lang('Points cannot be negative');
                    exit();
                }
                $zhengwen = Catfish::getPost('zhengwen', false);
                if($forum['lianjie'] == 0){
                    $zhengwen = Catfish::removea($zhengwen);
                }
                if(Catfish::getSession('user_type') != 1){
                    if(!$this->checkIllegal($zhengwen, $forum['mingan']) || !$this->checkIllegal($data['biaoti'], $forum['mingan'])){
                        echo Catfish::lang('Contains prohibited content, please modify and try again');
                        exit();
                    }
                }
                $ofujian = '';
                if($tie['annex'] == 1){
                    $tfj = Catfish::db('tienr')->where('tid',$tid)->field('fujian')->find();
                    $ofujian = $tfj['fujian'];
                }
                $fujian = '';
                $annex = $tie['annex'];
                $size = 0;
                $file = request()->file('fujian');
                if($file){
                    $validate = [
                        'ext' => $forum['geshi']
                    ];
                    $info = $file->validate($validate)->move(ROOT_PATH . 'data' . DS . 'annex');
                    if($info){
                        $size = $file->getInfo('size');
                        $fujian = 'data/annex/'.str_replace('\\','/',$info->getSaveName());
                    }else{
                        echo Catfish::lang('Attachment upload failed') . ': ' . $file->getError();
                        exit();
                    }
                }
                if(!empty($fujian)){
                    $annex = 1;
                    if($tie['annex'] == 1 && $fujian != $ofujian){
                        if(!empty($ofujian) && Catfish::isDataPath($ofujian)){
                            $tmparr = explode('/', $ofujian);
                            $fnm = array_pop($tmparr);
                            $path = ROOT_PATH . 'data' . DS . 'annex' . DS . array_pop($tmparr) . DS . $fnm;
                            @unlink($path);
                        }
                    }
                }
                else{
                    $fujian = $ofujian;
                }
                $tieyptb = Catfish::db('tietype')->field('id, tpname')->select();
                $nttname = '';
                $ottname = '';
                foreach($tieyptb as $key => $val){
                    if($val['id'] == $tietype){
                        $nttname = 'tj' . $val['tpname'];
                    }
                    if($val['id'] == $tie['tietype']){
                        $ottname = 'tj' . $val['tpname'];
                    }
                }
                $tietop = Catfish::db('tie_top')->where('tid', $tid)->field('id,sid')->find();
                $tietuijian = Catfish::db('tie_tuijian')->where('tid', $tid)->field('id,sid')->find();
                if(!empty($nttname) && !empty($ottname)){
                    $review = 1;
                    if($forum['preaudit'] == 1){
                        $review = 0;
                    }
                    $tus = $this->extractPics($zhengwen);
                    Catfish::dbStartTrans();
                    try{
                        Catfish::db('tie')->where('id', $tid)->update([
                            'sid' => $sid,
                            'biaoti' => $data['biaoti'],
                            'zhaiyao' => Catfish::getPost('zhaiyao'),
                            'review' => $review,
                            'tietype' => $tietype,
                            'annex' => $annex,
                            'tu' => $tus,
                            'jifenleixing' => $jifenleixing,
                            'jifen' => $jifen
                        ]);
                        Catfish::db('tienr')->where('tid', $tid)->update([
                            'zhengwen' => $zhengwen,
                            'fujian' => $fujian,
                            'fjsize' => $size
                        ]);
                        if($tie['tietype'] != $tietype){
                            Catfish::db('tietype')
                                ->where('id', $tie['tietype'])
                                ->update([
                                    'tongji' => Catfish::dbRaw('tongji-1')
                                ]);
                            Catfish::db('tietype')
                                ->where('id', $tietype)
                                ->update([
                                    'tongji' => Catfish::dbRaw('tongji+1')
                                ]);
                        }
                        if($ottname != $nttname){
                            Catfish::db('users_tongji')
                                ->where('uid', Catfish::getSession('user_id'))
                                ->update([
                                    $ottname => Catfish::dbRaw($ottname.'-1'),
                                    $nttname => Catfish::dbRaw($nttname.'+1')
                                ]);
                        }
                        Catfish::db('msort')
                            ->where('id', $tie['sid'])
                            ->update([
                                $ottname => Catfish::dbRaw($ottname.'-1')
                            ]);
                        Catfish::db('msort')
                            ->where('id', $sid)
                            ->update([
                                $nttname => Catfish::dbRaw($nttname.'+1')
                            ]);
                        if(!empty($tietop) && $tietop['sid'] != $sid){
                            Catfish::db('tie_top')
                                ->where('id', $tietop['id'])
                                ->update([
                                    'sid' => $sid
                                ]);
                        }
                        if(!empty($tietuijian) && $tietuijian['sid'] != $sid){
                            Catfish::db('tie_tuijian')
                                ->where('id', $tietuijian['id'])
                                ->update([
                                    'sid' => $sid
                                ]);
                        }
                        Catfish::dbCommit();
                    } catch (\Exception $e) {
                        Catfish::dbRollback();
                        echo Catfish::lang('The operation failed, please try again later');
                        exit();
                    }
                    Catfish::removeCache('post_'.$tid);
                    echo 'ok';
                }
                else{
                    echo Catfish::lang('The operation failed, please try again later');
                }
                exit();
            }
        }
        $tie = Catfish::db('tie')->where('id',$tid)->where('status',1)->field('id,uid,sid,biaoti,tietype,annex,jifenleixing,jifen')->find();
        if($tie['uid'] != Catfish::getSession('user_id')){
            Catfish::allot('illegal', Catfish::lang('Your operation is illegal'));
            return $this->show(Catfish::lang('Modify the main post'), 'mymainpost', false, 'illegal');
        }
        $tienr = Catfish::db('tienr')->where('tid',$tid)->field('zhengwen,fujian')->find();
        $tienr['zhengwen'] = str_replace('&','&amp;',$tienr['zhengwen']);
        if(!empty($tienr['fujian'])){
            $tmparr = explode('/', $tienr['fujian']);
            $tienr['fujian'] = end($tmparr);
        }
        Catfish::allot('tie', array_merge($tie, $tienr));
        $this->getTieType();
        $fenlei = Catfish::getCache('sort_id_sname_virtual_parentid');
        if($fenlei === false){
            $fenlei = Catfish::getSort('msort', 'id,sname,virtual,parentid');
            Catfish::setCache('sort_id_sname_virtual_parentid',$fenlei,3600);
        }
        $this->adddisabled($fenlei);
        Catfish::allot('fenlei', $fenlei);
        Catfish::allot('forum', $forum);
        return $this->show(Catfish::lang('Modify the main post'), 'mymainpost', true);
    }
    public function delannex()
    {
        if(Catfish::isPost(20)){
            $tid = Catfish::getPost('id');
            $tmp = Catfish::db('tie')->where('id',$tid)->field('uid')->find();
            if($tmp['uid'] != Catfish::getSession('user_id')){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $tfj = Catfish::db('tienr')->where('tid',$tid)->field('fujian')->find();
            Catfish::dbStartTrans();
            try{
                Catfish::db('tie')
                    ->where('id', $tid)
                    ->update([
                        'annex' => 0
                    ]);
                Catfish::db('tienr')
                    ->where('tid', $tid)
                    ->update([
                        'fujian' => ''
                    ]);
                Catfish::dbCommit();
            } catch (\Exception $e) {
                Catfish::dbRollback();
                echo Catfish::lang('The operation failed, please try again later');
                exit();
            }
            if(!empty($tfj['fujian']) && Catfish::isDataPath($tfj['fujian'])){
                $tmparr = explode('/', $tfj['fujian']);
                $fnm = array_pop($tmparr);
                $path = ROOT_PATH . 'data' . DS . 'annex' . DS . array_pop($tmparr) . DS . $fnm;
                @unlink($path);
            }
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function recyclebin()
    {
        $this->checkUser();
        $data = Catfish::db('tie')->where('uid', Catfish::getSession('user_id'))->where('status', 0)->field('id,fabushijian,biaoti,pinglunshu,yuedu,zan,cai,annex')->order('recoverytime desc')->paginate(20);
        Catfish::allot('data', $data->items());
        Catfish::allot('pages', $data->render());
        return $this->show(Catfish::lang('Recycle bin'), 'recyclebin');
    }
    public function reductionpost()
    {
        if(Catfish::isPost(20)){
            $id = Catfish::getPost('id');
            $tmp = Catfish::db('tie')->where('id',$id)->field('uid')->find();
            if($tmp['uid'] != Catfish::getSession('user_id')){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $re = Catfish::db('tie')
                ->where('id', $id)
                ->update([
                    'status' => 1,
                    'recoverytime' => Catfish::now()
                ]);
            if($re == 1){
                Catfish::removeCache('post_'.$id);
                Catfish::clearCache('postgentie_'.$id);
                echo 'ok';
            }
            else{
                echo Catfish::lang('The operation failed, please try again later');
            }
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function removepost()
    {
        if(Catfish::isPost(20)){
            $id = Catfish::getPost('id');
            $tmp = Catfish::db('tie')->where('id',$id)->field('uid,sid,fabushijian,tietype')->find();
            if($tmp['uid'] != Catfish::getSession('user_id')){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $ttname = Catfish::db('tietype')->where('id',$tmp['tietype'])->field('tpname')->find();
            $ttname = 'tj' . $ttname['tpname'];
            $yue = date('Ym', strtotime($tmp['fabushijian']));
            $tbnm = Catfish::prefix().'users_tongji_'.$yue;
            $istb = Catfish::hastable($tbnm);
            $tcstr = '';
            $gentieshu = 0;
            $tcontact = Catfish::db('tie_comm_ontact')->where('tid',$id)->field('cid')->select();
            foreach((array)$tcontact as $key => $val){
                $tcstr .= empty($tcstr) ? $val['cid'] : ',' . $val['cid'];
                $gentieshu ++;
            }
            Catfish::dbStartTrans();
            try{
                Catfish::db('tie')
                    ->where('id',$id)
                    ->delete();
                Catfish::db('tienr')
                    ->where('tid',$id)
                    ->delete();
                if(!empty($tcstr)){
                    Catfish::db('tie_comments')
                        ->where('id','in',$tcstr)
                        ->delete();
                    Catfish::db('tie_comm_ontact')
                        ->where('tid',$id)
                        ->delete();
                }
                Catfish::db('tie_favorites')
                    ->where('tid',$id)
                    ->delete();
                Catfish::db('users')
                    ->where('id', Catfish::getSession('user_id'))
                    ->update([
                        'fatie' => Catfish::dbRaw('fatie-1')
                    ]);
                Catfish::db('users_tongji')
                    ->where('uid', Catfish::getSession('user_id'))
                    ->update([
                        $ttname => Catfish::dbRaw($ttname.'-1')
                    ]);
                Catfish::db('tietype')
                    ->where('id', $tmp['tietype'])
                    ->update([
                        'tongji' => Catfish::dbRaw('tongji-1')
                    ]);
                Catfish::db('msort')
                    ->where('id', $tmp['sid'])
                    ->update([
                        'zhutie' => Catfish::dbRaw('zhutie-1'),
                        'gentie' => Catfish::dbRaw('gentie-'.$gentieshu),
                        $ttname => Catfish::dbRaw($ttname.'-1')
                    ]);
                Catfish::db('tongji')
                    ->where('riqi', date("Y-m-d", strtotime($tmp['fabushijian'])))
                    ->update([
                        'zhutie' => Catfish::dbRaw('zhutie-1')
                    ]);
                if($istb == true){
                    Catfish::db('users_tongji_'.$yue)
                        ->where('uid', Catfish::getSession('user_id'))
                        ->update([
                            'yuefatie' => Catfish::dbRaw('yuefatie-1')
                        ]);
                }
                Catfish::db('tie_fstop')
                    ->where('tid',$id)
                    ->delete();
                Catfish::db('tie_fstuijian')
                    ->where('tid',$id)
                    ->delete();
                Catfish::db('tie_top')
                    ->where('tid',$id)
                    ->delete();
                Catfish::db('tie_tuijian')
                    ->where('tid',$id)
                    ->delete();
                Catfish::dbCommit();
            } catch (\Exception $e) {
                Catfish::dbRollback();
                echo Catfish::lang('The operation failed, please try again later');
                exit();
            }
            Catfish::removeCache('post_'.$id);
            Catfish::clearCache('postgentie_'.$id);
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function uploadhandyeditor()
    {
        $file = request()->file('file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg,svg'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            echo Catfish::domain().'data/uploads/'.str_replace('\\','/',$info->getSaveName());
        }else{
            echo $file->getError();
        }
    }
    public function myfollowuppost()
    {
        $this->checkUser();
        $data = Catfish::view('tie_comments','id,createtime,zan,cai,status,content')
            ->view('tie_comm_ontact','tid','tie_comm_ontact.cid=tie_comments.id')
            ->view('tie','biaoti','tie.id=tie_comm_ontact.tid')
            ->where('tie_comments.uid', Catfish::getSession('user_id'))
            ->order('id desc')
            ->paginate(20);
        Catfish::allot('pages', $data->render());
        $data = $data->items();
        foreach($data as $key => $val){
            if($val['status'] == 1){
                $data[$key]['status'] = '<i class="fa fa-check text-success" data-container="body" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="'.Catfish::lang('Approved').'"></i>';
            }
            else{
                $data[$key]['status'] = '<i class="fa fa-times text-black-50" data-container="body" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="'.Catfish::lang('Did not pass').'"></i>';
            }
        }
        Catfish::allot('data', $data);
        return $this->show(Catfish::lang('My follow-up post'), 'myfollowuppost');
    }
    public function delmyfollowuppost()
    {
        if(Catfish::isPost(20)){
            $cid = intval(Catfish::getPost('id'));
            $tid = intval(Catfish::getPost('tid'));
            $uid = Catfish::getSession('user_id');
            $getuser = Catfish::db('tie_comments')->where('id', $cid)->field('uid,sid,createtime')->find();
            if(empty($getuser) || $getuser['uid'] != $uid){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            else{
                $mtie = Catfish::db('tie_comm_ontact')->where('cid',$cid)->field('tid')->find();
                if($mtie['tid'] != $tid){
                    echo Catfish::lang('Your operation is illegal');
                    exit();
                }
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
                Catfish::clearCache('postgentie_'.$tid);
                echo 'ok';
                exit();
            }
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function xiugaigentie()
    {
        if(Catfish::isPost(20)){
            $cid = intval(Catfish::getPost('id'));
            $tid = intval(Catfish::getPost('tid'));
            $uid = Catfish::getSession('user_id');
            $gentie = Catfish::getPost('gtnr',false);
            $gentie = trim($gentie);
            if(empty($gentie)){
                echo Catfish::lang('The content of the post cannot be empty');
                exit();
            }
            $getuser = Catfish::db('tie_comments')->where('id', $cid)->field('uid')->find();
            if(empty($getuser) || $getuser['uid'] != $uid){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            else{
                $now = Catfish::now();
                Catfish::db('tie_comments')->where('id', $cid)->update([
                    'xiugai' => $now,
                    'content' => $gentie
                ]);
                Catfish::clearCache('postgentie_'.$tid);
                echo 'ok';
                exit();
            }
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function myicon()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        if(Catfish::isPost(20)){
            $path = substr(md5($uid), 0, 2);
            $filename = 'u_'.$uid.'.png';
            $file = request()->file('file');
            $file->move(ROOT_PATH . 'data' . DS . 'avatar' . DS . $path, $filename);
            $tx = $path . '/' . $filename;
            Catfish::db('users')
                ->where('id', $uid)
                ->update([
                    'touxiang' => $tx
                ]);
            Catfish::setSession('touxiang',Catfish::domain() . 'data/avatar/' . $tx);
            echo 'ok';
            exit();
        }
        $retx = Catfish::db('users')->where('id',$uid)->field('touxiang')->find();
        $touxiang = $retx['touxiang'];
        if(!empty($touxiang)){
            $touxiang = Catfish::domain() . 'data/avatar/' . $touxiang;
        }
        Catfish::allot('touxiang', $touxiang);
        return $this->show(Catfish::lang('My icon'), 'myicon');
    }
    public function myprofile()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        if(Catfish::isPost(20)){
            $data = $this->myprofilePost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $re = Catfish::db('users')->where('email',$data['email'])->where('id', '<>',$uid)->field('id')->find();
                if(!empty($re)){
                    Catfish::lang('Email has been used');
                    exit();
                }
                $shouji = Catfish::getPost('shouji');
                if(!empty($shouji)){
                    $re = Catfish::db('users')->where('shouji',$shouji)->where('id', '<>',$uid)->field('id')->find();
                    if(!empty($re)){
                        Catfish::lang('Mobile number is already in use');
                        exit();
                    }
                }
                $shengri = Catfish::getPost('shengri');
                if(empty($shengri)){
                    $shengri = '2000-01-01';
                }
                Catfish::dbStartTrans();
                try{
                    Catfish::db('users')
                        ->where('id', $uid)
                        ->update([
                            'nicheng' => $data['nicheng'],
                            'email' => $data['email'],
                            'shouji' => $shouji,
                            'xingbie' => Catfish::getPost('xingbie'),
                            'qianming' => Catfish::getPost('qianming')
                        ]);
                    Catfish::db('users_info')
                        ->where('uid', $uid)
                        ->update([
                            'url' => Catfish::getPost('url'),
                            'shengri' => $shengri,
                            'xuexiao' => Catfish::getPost('xuexiao'),
                            'qq' => Catfish::getPost('qq'),
                            'weibo' => Catfish::getPost('weibo'),
                            'wechat' => Catfish::getPost('wechat'),
                            'facebook' => Catfish::getPost('facebook'),
                            'twitter' => Catfish::getPost('twitter')
                        ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                echo 'ok';
                exit();
            }
        }
        $jianyuuser = Catfish::db('users')->where('id',$uid)->field('nicheng,email,shouji,xingbie,qianming')->find();
        $jianyuuinfo = Catfish::db('users_info')->where('uid',$uid)->field('url,shengri,xuexiao,qq,weibo,wechat,facebook,twitter')->find();
        if($jianyuuinfo['shengri'] == '2000-01-01'){
            $jianyuuinfo['shengri'] = '';
        }
        $jianyuuser = array_merge($jianyuuser, $jianyuuinfo);
        Catfish::allot('jianyuuinfo', $jianyuuser);
        return $this->show(Catfish::lang('My Profile'), 'myprofile', true);
    }
    public function changepassword()
    {
        $this->checkUser();
        if(Catfish::isPost(20)){
            $data = $this->changepasswordPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                if($data['npwd'] != $data['rpwd']){
                    echo Catfish::lang('Confirm that the new password and the new password do not match');
                    exit();
                }
                $uid = Catfish::getSession('user_id');
                $recod = Catfish::db('users')->where('id',$uid)->field('password,randomcode')->find();
                if($recod['password'] != md5($data['opwd'].$recod['randomcode']))
                {
                    echo Catfish::lang('The original password is wrong');
                    exit();
                }
                $re = Catfish::db('users')->where('id',$uid)->update([
                    'password' => md5($data['npwd'].$recod['randomcode'])
                ]);
                if($re == 1){
                    echo 'ok';
                }
                else{
                    echo Catfish::lang('Failure to submit');
                }
                exit();
            }
        }
        return $this->show(Catfish::lang('Change Password'), 'changepassword');
    }
    public function _empty()
    {
        Catfish::toError();
    }
    public function forummainpost()
    {
        $this->checkUser();
        $umod = $this->isModerator(Catfish::getSession('user_id'));
        $sidstr = '';
        $sidarr = [];
        foreach($umod as $key => $val){
            $sidstr .= empty($sidstr)? $val['sid'] : ',' . $val['sid'];
            $sidarr[$val['sid']] = $val['mtype'];
        }
        $section = Catfish::db('msort')->where('id','in',$sidstr)->field('id,sname')->select();
        Catfish::allot('section', $section);
        $guanjianzi = Catfish::getGet('guanjianzi');
        if($guanjianzi === false){
            $guanjianzi = '';
        }
        $query = [];
        $catfish = Catfish::view('tie','id,sid,fabushijian,biaoti,review,yuedu,istop,recommended,jingpin,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.sid','in',$sidstr);
        if($guanjianzi != ''){
            $catfish = $catfish->where('tie.biaoti','like','%'.$guanjianzi.'%');
            $query['guanjianzi'] = $guanjianzi;
        }
        $catfish = $catfish->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20,false,[
                'query' => $query
            ]);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        $typeidnm = $this->gettypeidname();
        foreach($catfish as $key => $val){
            $catfish[$key]['tietype'] = $typeidnm[$val['tietype']];
            $catfish[$key]['banzhu'] = $sidarr[$val['sid']];
        }
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('mtype', Catfish::getSession('mtype'));
        return $this->show(Catfish::lang('Forum main post'), 'forummainpost');
    }
    public function delforummainpost()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $uid = Catfish::getSession('user_id');
            $tmp = Catfish::db('tie')->where('id',$id)->field('uid,sid,fabushijian,tietype')->find();
            $fumt = Catfish::db('mod_sec_ontact')->where('sid',$tmp['sid'])->where('uid',$uid)->field('mtype')->find();
            if(empty($fumt) || $fumt['mtype'] < 15){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $umt = Catfish::db('users')->where('id',$uid)->field('mtype')->find();
            if($umt['mtype'] < 15){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $ttname = Catfish::db('tietype')->where('id',$tmp['tietype'])->field('tpname')->find();
            $ttname = 'tj' . $ttname['tpname'];
            $yue = date('Ym', strtotime($tmp['fabushijian']));
            $tbnm = Catfish::prefix().'users_tongji_'.$yue;
            $istb = Catfish::hastable($tbnm);
            $tcstr = '';
            $gentieshu = 0;
            $tcontact = Catfish::db('tie_comm_ontact')->where('tid',$id)->field('cid')->select();
            foreach((array)$tcontact as $key => $val){
                $tcstr .= empty($tcstr) ? $val['cid'] : ',' . $val['cid'];
                $gentieshu ++;
            }
            Catfish::dbStartTrans();
            try{
                Catfish::db('tie')
                    ->where('id',$id)
                    ->delete();
                Catfish::db('tienr')
                    ->where('tid',$id)
                    ->delete();
                if(!empty($tcstr)){
                    Catfish::db('tie_comments')
                        ->where('id','in',$tcstr)
                        ->delete();
                    Catfish::db('tie_comm_ontact')
                        ->where('tid',$id)
                        ->delete();
                }
                Catfish::db('tie_favorites')
                    ->where('tid',$id)
                    ->delete();
                Catfish::db('users')
                    ->where('id', $tmp['uid'])
                    ->update([
                        'fatie' => Catfish::dbRaw('fatie-1')
                    ]);
                Catfish::db('users_tongji')
                    ->where('uid', $tmp['uid'])
                    ->update([
                        $ttname => Catfish::dbRaw($ttname.'-1')
                    ]);
                Catfish::db('tietype')
                    ->where('id', $tmp['tietype'])
                    ->update([
                        'tongji' => Catfish::dbRaw('tongji-1')
                    ]);
                Catfish::db('msort')
                    ->where('id', $tmp['sid'])
                    ->update([
                        'zhutie' => Catfish::dbRaw('zhutie-1'),
                        'gentie' => Catfish::dbRaw('gentie-'.$gentieshu),
                        $ttname => Catfish::dbRaw($ttname.'-1')
                    ]);
                Catfish::db('tongji')
                    ->where('riqi', date("Y-m-d", strtotime($tmp['fabushijian'])))
                    ->update([
                        'zhutie' => Catfish::dbRaw('zhutie-1')
                    ]);
                if($istb == true){
                    Catfish::db('users_tongji_'.$yue)
                        ->where('uid', $tmp['uid'])
                        ->update([
                            'yuefatie' => Catfish::dbRaw('yuefatie-1')
                        ]);
                }
                Catfish::db('tie_fstop')
                    ->where('tid',$id)
                    ->delete();
                Catfish::db('tie_fstuijian')
                    ->where('tid',$id)
                    ->delete();
                Catfish::db('tie_top')
                    ->where('tid',$id)
                    ->delete();
                Catfish::db('tie_tuijian')
                    ->where('tid',$id)
                    ->delete();
                Catfish::dbCommit();
            } catch (\Exception $e) {
                Catfish::dbRollback();
                echo Catfish::lang('The operation failed, please try again later');
                exit();
            }
            Catfish::removeCache('post_'.$id);
            Catfish::clearCache('postgentie_'.$id);
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function manaforummainpost()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $uid = Catfish::getSession('user_id');
            $tmp = Catfish::db('tie')->where('id',$id)->field('sid')->find();
            $fumt = Catfish::db('mod_sec_ontact')->where('sid',$tmp['sid'])->where('uid',$uid)->field('mtype')->find();
            if(empty($fumt) || $fumt['mtype'] < 10){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $umt = Catfish::db('users')->where('id',$uid)->field('mtype')->find();
            if($umt['mtype'] < 10){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $chkarr = ['review', 'istop', 'recommended', 'jingpin'];
            $chk = intval(Catfish::getPost('chk'));
            if($chk > 1){
                $chk = 1;
            }
            $opt = Catfish::getPost('opt');
            if(in_array($opt, $chkarr)){
                Catfish::dbStartTrans();
                try{
                    Catfish::db('tie')->where('id',$id)->update([
                        $opt => $chk
                    ]);
                    if($opt == 'istop'){
                        if($chk == 1){
                            Catfish::db('tie_top')->insert([
                                'tid' => $id,
                                'sid' => $tmp['sid']
                            ]);
                        }
                        elseif($chk == 0){
                            Catfish::db('tie_top')
                                ->where('tid',$id)
                                ->delete();
                        }
                    }
                    if($opt == 'recommended'){
                        if($chk == 1){
                            Catfish::db('tie_tuijian')->insert([
                                'tid' => $id,
                                'sid' => $tmp['sid']
                            ]);
                        }
                        elseif($chk == 0){
                            Catfish::db('tie_tuijian')
                                ->where('tid',$id)
                                ->delete();
                        }
                    }
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                Catfish::clearCache('column_zhiding_tuijian');
                Catfish::clearCache('column');
                if($opt == 'review'){
                    Catfish::removeCache('post_' . $id);
                }
                echo 'ok';
            }
            else{
                echo Catfish::lang('Your operation is illegal');
            }
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function followingupsection()
    {
        $this->checkUser();
        $umod = $this->isModerator(Catfish::getSession('user_id'));
        $sidstr = '';
        $sidarr = [];
        foreach($umod as $key => $val){
            $sidstr .= empty($sidstr)? $val['sid'] : ',' . $val['sid'];
            $sidarr[$val['sid']] = $val['mtype'];
        }
        $section = Catfish::db('msort')->where('id','in',$sidstr)->field('id,sname')->select();
        Catfish::allot('section', $section);
        $catfish = Catfish::view('tie_comments','id,uid,sid,createtime,status,content')
            ->view('tie_comm_ontact','tid','tie_comm_ontact.cid=tie_comments.id', 'LEFT')
            ->view('users','nicheng','users.id=tie_comments.uid', 'LEFT')
            ->where('tie_comments.sid','in',$sidstr)
            ->order('tie_comments.xiugai desc')
            ->paginate(20);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        foreach($catfish as $key => $val){
            $catfish[$key]['banzhu'] = $sidarr[$val['sid']];
        }
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('mtype', Catfish::getSession('mtype'));
        return $this->show(Catfish::lang('Following-up of the section'), 'followingupsection');
    }
    public function manafollowpost()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $tid = intval(Catfish::getPost('tid'));
            $nicheng = Catfish::getPost('nicheng');
            $content = Catfish::getPost('content');
            $createtime = Catfish::getPost('createtime');
            $uid = Catfish::getSession('user_id');
            $tmp = Catfish::db('tie_comments')->where('id',$id)->field('sid')->find();
            $fumt = Catfish::db('mod_sec_ontact')->where('sid',$tmp['sid'])->where('uid',$uid)->field('mtype')->find();
            if(empty($fumt) || $fumt['mtype'] < 5){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $umt = Catfish::db('users')->where('id',$uid)->field('mtype')->find();
            if($umt['mtype'] < 5){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $chkarr = ['status'];
            $chk = intval(Catfish::getPost('chk'));
            if($chk > 1){
                $chk = 1;
            }
            $opt = Catfish::getPost('opt');
            if(in_array($opt, $chkarr)){
                Catfish::dbStartTrans();
                try{
                    Catfish::db('tie_comments')->where('id',$id)->update([
                        $opt => $chk
                    ]);
                    Catfish::db('tie_comm_ontact')->where('cid',$id)->update([
                        'status' => $chk
                    ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                $tiepl = Catfish::db('tie')->where('id', $tid)->field('pinglun')->find();
                if(!empty($tiepl['pinglun'])){
                    $pinglun = unserialize($tiepl['pinglun']);
                    if($chk == 1){
                        if(count($pinglun) > 2){
                            $pinglun = array_slice($pinglun, 0, 2);
                        }
                        $plarr = [
                            'id' => $id,
                            'nicheng' => subtext($nicheng, 8),
                            'shijian' => $createtime,
                            'neirong' => subtext(trim(strip_tags($content)), 57)
                        ];
                        array_unshift($pinglun, $plarr);
                    }
                    else{
                        foreach($pinglun as $key => $val){
                            if($val['id'] == $id){
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
                elseif($chk == 1){
                    $pinglun[] = [
                        'id' => $id,
                        'nicheng' => subtext($nicheng, 8),
                        'shijian' => $createtime,
                        'neirong' => subtext(trim(strip_tags($content)), 57)
                    ];
                    $pinglun = serialize($pinglun);
                    Catfish::db('tie')->where('id', $tid)->update([
                        'pinglun' => $pinglun
                    ]);
                }
                $rep = Catfish::db('tie_comm_ontact')->where('cid', $id)->field('tid')->find();
                Catfish::clearCache('postgentie_'.$rep['tid']);
                echo 'ok';
            }
            else{
                echo Catfish::lang('Your operation is illegal');
            }
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function delfollowpost()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $uid = Catfish::getSession('user_id');
            $tmp = Catfish::db('tie_comments')->where('id',$id)->field('uid,sid,createtime')->find();
            $fumt = Catfish::db('mod_sec_ontact')->where('sid',$tmp['sid'])->where('uid',$uid)->field('mtype')->find();
            if(empty($fumt) || $fumt['mtype'] < 10){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $umt = Catfish::db('users')->where('id',$uid)->field('mtype')->find();
            if($umt['mtype'] < 10){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $mtie = Catfish::db('tie_comm_ontact')->where('cid',$id)->field('tid')->find();
            $tid = $mtie['tid'];
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
                    ->where('id', $tid)
                    ->update([
                        'pinglunshu' => Catfish::dbRaw('pinglunshu-1')
                    ]);
                Catfish::db('msort')
                    ->where('id', $tmp['sid'])
                    ->update([
                        'gentie' => Catfish::dbRaw('gentie-1')
                    ]);
                Catfish::db('tongji')
                    ->where('riqi', date("Y-m-d", strtotime($tmp['createtime'])))
                    ->update([
                        'gentie' => Catfish::dbRaw('gentie-1')
                    ]);
                Catfish::db('users')
                    ->where('id', $tmp['uid'])
                    ->update([
                        'pinglun' => Catfish::dbRaw('pinglun-1')
                    ]);
                Catfish::db('gentie_zan')
                    ->where('cid', $id)
                    ->delete();
                Catfish::db('gentie_cai')
                    ->where('cid', $id)
                    ->delete();
                Catfish::dbCommit();
            } catch (\Exception $e) {
                Catfish::dbRollback();
                echo Catfish::lang('The operation failed, please try again later');
                exit();
            }
            Catfish::clearCache('postgentie_'.$tid);
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function toppost()
    {
        $this->checkUser();
        $umod = $this->isModerator(Catfish::getSession('user_id'));
        $sidstr = '';
        $sidarr = [];
        foreach($umod as $key => $val){
            $sidstr .= empty($sidstr)? $val['sid'] : ',' . $val['sid'];
            $sidarr[$val['sid']] = $val['mtype'];
        }
        $section = Catfish::db('msort')->where('id','in',$sidstr)->field('id,sname')->select();
        Catfish::allot('section', $section);
        $catfish = Catfish::view('tie','id,sid,fabushijian,biaoti,review,yuedu,istop,recommended,jingpin,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.sid','in',$sidstr)
            ->where('tie.istop','=',1)
            ->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        $typeidnm = $this->gettypeidname();
        foreach($catfish as $key => $val){
            $catfish[$key]['tietype'] = $typeidnm[$val['tietype']];
            $catfish[$key]['banzhu'] = $sidarr[$val['sid']];
        }
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('mtype', Catfish::getSession('mtype'));
        return $this->show(Catfish::lang('Top post'), 'toppost');
    }
    public function recommendedpost()
    {
        $this->checkUser();
        $umod = $this->isModerator(Catfish::getSession('user_id'));
        $sidstr = '';
        $sidarr = [];
        foreach($umod as $key => $val){
            $sidstr .= empty($sidstr)? $val['sid'] : ',' . $val['sid'];
            $sidarr[$val['sid']] = $val['mtype'];
        }
        $section = Catfish::db('msort')->where('id','in',$sidstr)->field('id,sname')->select();
        Catfish::allot('section', $section);
        $catfish = Catfish::view('tie','id,sid,fabushijian,biaoti,review,yuedu,istop,recommended,jingpin,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.sid','in',$sidstr)
            ->where('tie.recommended','=',1)
            ->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        $typeidnm = $this->gettypeidname();
        foreach($catfish as $key => $val){
            $catfish[$key]['tietype'] = $typeidnm[$val['tietype']];
            $catfish[$key]['banzhu'] = $sidarr[$val['sid']];
        }
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('mtype', Catfish::getSession('mtype'));
        return $this->show(Catfish::lang('Recommended post'), 'recommendedpost');
    }
    public function finepost()
    {
        $this->checkUser();
        $umod = $this->isModerator(Catfish::getSession('user_id'));
        $sidstr = '';
        $sidarr = [];
        foreach($umod as $key => $val){
            $sidstr .= empty($sidstr)? $val['sid'] : ',' . $val['sid'];
            $sidarr[$val['sid']] = $val['mtype'];
        }
        $section = Catfish::db('msort')->where('id','in',$sidstr)->field('id,sname')->select();
        Catfish::allot('section', $section);
        $catfish = Catfish::view('tie','id,sid,fabushijian,biaoti,review,yuedu,istop,recommended,jingpin,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.sid','in',$sidstr)
            ->where('tie.jingpin','=',1)
            ->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        $typeidnm = $this->gettypeidname();
        foreach($catfish as $key => $val){
            $catfish[$key]['tietype'] = $typeidnm[$val['tietype']];
            $catfish[$key]['banzhu'] = $sidarr[$val['sid']];
        }
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('mtype', Catfish::getSession('mtype'));
        return $this->show(Catfish::lang('Fine post'), 'finepost');
    }
    public function getmainpost()
    {
        if(Catfish::isPost(20)){
            $post = Catfish::db('tienr')->where('tid', Catfish::getPost('id'))->field('zhengwen')->find();
            return $post['zhengwen'];
        }
        return '';
    }
    public function myscores()
    {
        $this->checkUser();
        $jifen = Catfish::db('users')->where('id', Catfish::getSession('user_id'))->field('jifen')->find();
        Catfish::allot('jifen', $jifen['jifen']);
        return $this->show(Catfish::lang('My scores'), 'myscores');
    }
    public function earnpoints()
    {
        $this->checkUser();
        $mj = [
            'login' => Catfish::lang('Log in'),
            'post' => Catfish::lang('Send a post'),
            'followup' => Catfish::lang('Follow the post'),
            'reply' => Catfish::lang('Reply to the post'),
            'access' => Catfish::lang('Visit the main post'),
            'like' => Catfish::lang('Give it a like'),
            'stepon' => Catfish::lang('Step on it'),
            'flike' => Catfish::lang('Like the follow-up post'),
            'fstepon' => Catfish::lang('Step on the following post'),
            'collection' => Catfish::lang('Collection'),
        ];
        $growth = Catfish::db('chengzhang')->field('czname,jifen')->select();
        foreach($growth as $key => $val){
            $growth[$key]['czname'] = $mj[$val['czname']];
        }
        Catfish::allot('growth', $growth);
        $jifen = Catfish::get('jifenduihuan');
        if($jifen == false){
            $jifen = 0;
        }
        Catfish::allot('jifenduihuan', $jifen);
        Catfish::allot('openpay', Catfish::get('openpay'));
        return $this->show(Catfish::lang('How to earn points'), 'earnpoints');
    }
    public function pointsbill()
    {
        $this->checkUser();
        $data = Catfish::db('points_book')->where('uid', Catfish::getSession('user_id'))->field('id,zengjian,booktime,miaoshu')->order('id desc')->paginate(20);
        Catfish::allot('pages', $data->render());
        $datarr = $data->items();
        foreach($datarr as $key => $val){
            if($val['zengjian'] > 0){
                $datarr[$key]['zengjian'] = '+' . $val['zengjian'];
            }
        }
        Catfish::allot('data', $datarr);
        return $this->show(Catfish::lang('Points bill'), 'pointsbill');
    }
    public function checkin()
    {
        $this->checkUser();
        if(Catfish::hasPost('act')){
            $benyue = Catfish::getPost('act');
        }
        else{
            $benyue = date("Y-m");
        }
        $start = $benyue . '-01';
        $startweek = date('w', strtotime($start));
        $end = date("Y-m-d", strtotime($start . ' +1 month -1 day'));
        $qiandao = Catfish::db('sign_in')->where('uid', Catfish::getSession('user_id'))->where('qiandao', 'between time', [$start,$end])->field('id,qiandao')->select();
        $riqi = [];
        $tmpArr = [];
        foreach($qiandao as $key => $val){
            $tmpArr[date('j', strtotime($val['qiandao']))] = $val['qiandao'];
        }
        if($startweek > 0){
            for($i = 0; $i < $startweek; $i ++){
                $riqi[] = [
                    'ri' => '00',
                    'qiandao' => 0,
                    'jintian' => 0,
                    'kong' => 1
                ];
            }
        }
        $ts = date('t', strtotime($start));
        for($i = 1; $i <= $ts; $i ++){
            $today = 0;
            if($i == date('j') && $benyue == date("Y-m")){
                $today = 1;
            }
            if($i < 10){
                $ri = '0' . $i;
            }
            else{
                $ri = $i;
            }
            if(isset($tmpArr[$i])){
                $riqi[] = [
                    'ri' => $ri,
                    'qiandao' => 1,
                    'jintian' => $today,
                    'kong' => 0
                ];
            }
            else{
                $riqi[] = [
                    'ri' => $ri,
                    'qiandao' => 0,
                    'jintian' => $today,
                    'kong' => 0
                ];
            }
        }
        $alen = count($riqi) % 7;
        if($alen > 0){
            $alen = 7 - $alen;
            for($i = 0; $i < $alen; $i ++){
                $riqi[] = [
                    'ri' => '00',
                    'qiandao' => 0,
                    'jintian' => 0,
                    'kong' => 1
                ];
            }
        }
        Catfish::allot('data', $riqi);
        $qiandao = Catfish::get('qiandaojifen');
        if(empty($qiandao)){
            $qiandao = [
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
            ];
        }
        else{
            $qiandao = unserialize($qiandao);
        }
        Catfish::allot('qiandao', $qiandao);
        $prev = date("Y-m", strtotime($start . ' -1 month'));
        Catfish::allot('shanggeyue', $prev);
        if($benyue == date("Y-m")){
            $xiageyue = '';
        }
        else{
            $xiageyue = date("Y-m", strtotime($start . ' +1 month'));
        }
        Catfish::allot('xiageyue', $xiageyue);
        Catfish::allot('benyue', $benyue);
        if(Catfish::hasPost('act')){
            return Catfish::output('checkins');
        }
        else{
            return $this->show(Catfish::lang('Check in'), 'checkin');
        }
    }
    public function qiandao()
    {
        if(Catfish::isPost(20)){
            if(Catfish::hasPost('act') && Catfish::getPost('act') == 'qiandao'){
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
                    'message' => Catfish::lang('Your operation is illegal')
                ];
                return json($result);
            }
        }
        else{
            $result = [
                'result' => 'error',
                'message' => Catfish::lang('Your operation is illegal')
            ];
            return json($result);
        }
    }
}