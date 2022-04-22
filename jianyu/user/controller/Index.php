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
        $user = Catfish::db('users')->where('id',Catfish::getSession('user_id'))->field('nicheng,createtime,lastlogin,utype,vipend,viptype,mtype,dengji,jifen,jinbi,fatie,pinglun')->find();
        $dengji = $this->getdjidname();
        foreach($user as $key => $val){
            if($key == 'createtime'){
                $user[$key] = date('Y-m-d', strtotime($val));
            }
            if($key == 'dengji'){
                $user['dengjiming'] = $dengji[$val > 0 ? $val : 1];
            }
            if($key == 'utype'){
                if($val == 15){
                    $user['isvip'] = 1;
                    $user['viptixing'] = '';
                    if($user['viptype'] == 3){
                        $user['vipend'] = Catfish::lang('Permanent member');
                    }
                    else{
                        if(time() > strtotime($user['vipend'])){
                            $user['viptixing'] = Catfish::lang('Your VIP membership has expired, please renew as soon as possible.');
                        }
                        elseif(strtotime($user['vipend']) - time() < 1209600){
                            $user['viptixing'] = Catfish::lang('Your VIP membership is about to expire, please renew as soon as possible.');
                        }
                    }
                }
                else{
                    $user['isvip'] = 0;
                    $user['vipend'] = '';
                    $user['viptixing'] = '';
                }
                switch($val){
                    case 1:
                        $user['shenfen'] = Catfish::lang('Founder');
                        break;
                    case 3:
                        $user['shenfen'] = Catfish::lang('Senior administrator');
                        break;
                    case 5:
                        $user['shenfen'] = Catfish::lang('Ordinary administrator');
                        break;
                    case 15:
                        $user['shenfen'] = Catfish::lang('VIP member');
                        break;
                    case 20:
                        $user['shenfen'] = Catfish::lang('General user');
                        break;
                }
            }
            if($key == 'mtype'){
                switch($val){
                    case 0:
                        $user['zhiwu'] = Catfish::lang('Not in a position');
                        break;
                    case 5:
                        $user['zhiwu'] = Catfish::lang('Intern moderator');
                        break;
                    case 10:
                        $user['zhiwu'] = Catfish::lang('Secondary moderator');
                        break;
                    case 15:
                        $user['zhiwu'] = Catfish::lang('Moderator');
                        break;
                }
            }
        }
        unset($user['utype']);
        unset($user['viptype']);
        unset($user['mtype']);
        Catfish::allot('info', $user);
        return $this->show(Catfish::lang('User center'), 'index');
    }
    public function newpost()
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
                $jinbileixing = 0;
                if(Catfish::hasPost('jinbileixing')){
                    $jinbileixing = intval(Catfish::getPost('jinbileixing'));
                }
                $jinbi = 0;
                if(Catfish::hasPost('jinbi')){
                    $jinbi = intval(Catfish::getPost('jinbi'));
                }
                if($jinbi < 0){
                    echo Catfish::lang('Forum coins cannot be negative');
                    exit();
                }
                $huiyuanleixing = 0;
                if(Catfish::hasPost('huiyuanleixing')){
                    $huiyuanleixing = intval(Catfish::getPost('huiyuanleixing'));
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
                $name = '';
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
                        $name = $file->getInfo('name');
                        $fujian = 'data/annex/'.str_replace('\\','/',$info->getSaveName());
                    }else{
                        echo Catfish::lang('Attachment upload failed') . ': ' . $file->getError();
                        exit();
                    }
                }
                if(!empty($fujian)){
                    $annex = 1;
                }
                $ttname = Catfish::db('tietype')->where('id',$tietype)->field('bieming')->find();
                $ttname = 'tj' . $ttname['bieming'];
                $this->newtongjitb();
                $now = Catfish::now();
                $chengzhang = Catfish::getGrowing();
                $secreview = Catfish::db('msort')->where('id',$sid)->field('preaudit')->find();
                $review = 1;
                if($secreview['preaudit'] != 2 && ($secreview['preaudit'] == 1 || $forum['preaudit'] == 1)){
                    $review = 0;
                }
                $tus = $this->extractPics($zhengwen);
                $uid = Catfish::getSession('user_id');
                $hasshipin = 0;
                $shipin = '';
                $shipinming = '';
                if(Catfish::hasPost('shipinurl')){
                    $shipin = Catfish::getPost('shipinurl');
                    if(!empty($shipin)){
                        $hasshipin = 1;
                        $shipinming = Catfish::getPost('shipinname');
                    }
                }
                $params = [
                    'biaoti' => $data['biaoti'],
                    'zhengwen' => $zhengwen,
                    'tu' => $tus,
                    'fujian' => $fujian,
                    'shipin' => $shipin
                ];
                $this->plantHook('publish', $params);
                if(isset($params['biaoti'])){
                    $data['biaoti'] = $params['biaoti'];
                }
                if(isset($params['zhengwen'])){
                    $zhengwen = $params['zhengwen'];
                }
                if(isset($params['tu'])){
                    $tus = $params['tu'];
                }
                if(isset($params['fujian'])){
                    $fujian = $params['fujian'];
                }
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
                        'video' => $hasshipin,
                        'shipin' => $shipin,
                        'tu' => $tus,
                        'jifenleixing' => $jifenleixing,
                        'jifen' => $jifen,
                        'jinbileixing' => $jinbileixing,
                        'jinbi' => $jinbi,
                        'huiyuanleixing' => $huiyuanleixing,
                        'zhifufangshi' => Catfish::getPost('zhifufangshi')
                    ]);
                    Catfish::db('tienr')->insert([
                        'tid' => $reid,
                        'zhengwen' => $zhengwen,
                        'fujian' => $fujian,
                        'fujianming' => $name,
                        'fjsize' => $size,
                        'shipinming' => $shipinming
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
                    echo Catfish::lang('The operation failed, please try again later') . $e->getMessage();
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
            $fenlei = Catfish::getSort('msort', 'id,sname,virtual,parentid', '&nbsp;&nbsp;&nbsp;&nbsp;', ['islink', 0]);
            Catfish::setCache('sort_id_sname_virtual_parentid',$fenlei,3600);
        }
        $this->adddisabled($fenlei);
        Catfish::allot('fenlei', $fenlei);
        Catfish::allot('forum', $forum);
        Catfish::allot('needvcode', $needvcode);
        Catfish::allot('maxfilesize', ini_get('upload_max_filesize'));
        return $this->show(Catfish::lang('Send new posts'), 'sendnewposts', true);
    }
    public function mymainpost()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $guanjianzi = Catfish::getGet('guanjianzi');
        if($guanjianzi === false){
            $guanjianzi = '';
        }
        $startime = Catfish::getGet('startime');
        if($startime === false){
            $startime = '';
        }
        $endtime = Catfish::getGet('endtime');
        if($endtime === false){
            $endtime = '';
        }
        $query = [
            'guanjianzi' => $guanjianzi,
            'startime' => $startime,
            'endtime' => $endtime,
        ];
        $cachezongjilu = 'user_mymainpost_' . $uid . '_' . md5(serialize($query)) . '_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $data = Catfish::db('tie')->where('uid', $uid)->where('status', 1);
        if($guanjianzi != ''){
            $data = $data->where('biaoti','like','%'.$guanjianzi.'%');
        }
        if($startime != '' || $endtime != ''){
            if(empty($startime)){
                $startime = '2000-01-01';
            }
            if(empty($endtime)){
                $endtime = date('Y-m-d');
            }
            if($startime == $endtime){
                $endtime = date('Y-m-d', strtotime('+1 day', strtotime($endtime)));
            }
            $data = $data->where('fabushijian','between time',[$startime,$endtime]);
        }
        $data = $data->field('id,fabushijian,xiugai,biaoti,review,isclose,pinglunshu,yuedu,zan,cai,annex,video')->order('id desc')->paginate(20, $zongjilu,[
            'query' => $query
        ]);
        if($zongjilu === false){
            $zongjilu = $data->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        $pages = $data->render();
        $data = $data->items();
        foreach($data as $key => $val){
            if($val['review'] == 1){
                $data[$key]['review'] = '<i class="fa fa-check text-success" data-container="body" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="'.Catfish::lang('Approved').'"></i>';
            }
            else{
                if($val['xiugai'] == '2000-01-01 00:00:00'){
                    $xiugai = $val['fabushijian'];
                }
                else{
                    $xiugai = $val['xiugai'];
                }
                $tishi = Catfish::lang('Under review...');
                if(strtotime($xiugai) - time() > 259200){
                    $tishi = Catfish::lang('Did not pass');
                }
                $data[$key]['review'] = '<i class="fa fa-times text-black-50" data-container="body" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="'.$tishi.'"></i>';
            }
        }
        Catfish::allot('data', $data);
        Catfish::allot('pages', $pages);
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
        $tid = intval(Catfish::getGet('c'));
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
                $jinbileixing = 0;
                if(Catfish::hasPost('jinbileixing')){
                    $jinbileixing = intval(Catfish::getPost('jinbileixing'));
                }
                $jinbi = 0;
                if(Catfish::hasPost('jinbi')){
                    $jinbi = intval(Catfish::getPost('jinbi'));
                }
                if($jinbi < 0){
                    echo Catfish::lang('Forum coins cannot be negative');
                    exit();
                }
                $huiyuanleixing = 0;
                if(Catfish::hasPost('huiyuanleixing')){
                    $huiyuanleixing = intval(Catfish::getPost('huiyuanleixing'));
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
                $oname = '';
                if($tie['annex'] == 1){
                    $tfj = Catfish::db('tienr')->where('tid',$tid)->field('fujian,fujianming')->find();
                    $ofujian = $tfj['fujian'];
                    $oname = $tfj['fujianming'];
                }
                $fujian = '';
                $name = '';
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
                        $name = $file->getInfo('name');
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
                    $name = $oname;
                }
                $tieyptb = Catfish::db('tietype')->field('id, bieming')->select();
                $nttname = '';
                $ottname = '';
                foreach($tieyptb as $key => $val){
                    if($val['id'] == $tietype){
                        $nttname = 'tj' . $val['bieming'];
                    }
                    if($val['id'] == $tie['tietype']){
                        $ottname = 'tj' . $val['bieming'];
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
                    $params = [
                        'biaoti' => $data['biaoti'],
                        'zhengwen' => $zhengwen,
                        'tu' => $tus,
                        'fujian' => $fujian
                    ];
                    $this->plantHook('publish', $params);
                    if(isset($params['biaoti'])){
                        $data['biaoti'] = $params['biaoti'];
                    }
                    if(isset($params['zhengwen'])){
                        $zhengwen = $params['zhengwen'];
                    }
                    if(isset($params['tu'])){
                        $tus = $params['tu'];
                    }
                    if(isset($params['fujian'])){
                        $fujian = $params['fujian'];
                    }
                    Catfish::dbStartTrans();
                    try{
                        Catfish::db('tie')->where('id', $tid)->update([
                            'sid' => $sid,
                            'xiugai' => Catfish::now(),
                            'biaoti' => $data['biaoti'],
                            'zhaiyao' => Catfish::getPost('zhaiyao'),
                            'review' => $review,
                            'tietype' => $tietype,
                            'annex' => $annex,
                            'tu' => $tus,
                            'jifenleixing' => $jifenleixing,
                            'jifen' => $jifen,
                            'jinbileixing' => $jinbileixing,
                            'jinbi' => $jinbi,
                            'huiyuanleixing' => $huiyuanleixing,
                            'zhifufangshi' => Catfish::getPost('zhifufangshi')
                        ]);
                        Catfish::db('tienr')->where('tid', $tid)->update([
                            'zhengwen' => $zhengwen,
                            'fujian' => $fujian,
                            'fujianming' => $name,
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
        $tie = Catfish::db('tie')->where('id',$tid)->where('status',1)->field('id,uid,sid,biaoti,tietype,annex,video,shipin,jifenleixing,jifen,jinbileixing,jinbi,huiyuanleixing,zhifufangshi')->find();
        if($tie['uid'] != Catfish::getSession('user_id')){
            Catfish::allot('illegal', Catfish::lang('Your operation is illegal'));
            return $this->show(Catfish::lang('Modify the main post'), 'mymainpost', false, 'illegal');
        }
        $tienr = Catfish::db('tienr')->where('tid',$tid)->field('zhengwen,fujian,fujianming,shipinming')->find();
        $tienr['zhengwen'] = str_replace('&','&amp;',$tienr['zhengwen']);
        if(!empty($tienr['fujian'])){
            $tmparr = explode('/', $tienr['fujian']);
            $tienr['fujian'] = end($tmparr);
        }
        Catfish::allot('tie', array_merge($tie, $tienr));
        $this->getTieType();
        $fenlei = Catfish::getCache('sort_id_sname_virtual_parentid');
        if($fenlei === false){
            $fenlei = Catfish::getSort('msort', 'id,sname,virtual,parentid', '&nbsp;&nbsp;&nbsp;&nbsp;', ['islink', 0]);
            Catfish::setCache('sort_id_sname_virtual_parentid',$fenlei,3600);
        }
        $this->adddisabled($fenlei);
        Catfish::allot('fenlei', $fenlei);
        Catfish::allot('forum', $forum);
        $jumpto = '';
        if(Catfish::hasGet('jumpto')){
            $jumpto = Catfish::getGet('jumpto');
            $host = parse_url($jumpto,  PHP_URL_HOST);
            if(stripos(Catfish::domain(), $host) === false){
                $jumpto = '';
            }
        }
        Catfish::allot('jumpto', $jumpto);
        Catfish::allot('maxfilesize', ini_get('upload_max_filesize'));
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
        $uid = Catfish::getSession('user_id');
        $cachezongjilu = 'user_recyclebin_' . $uid . '_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $data = Catfish::db('tie')->where('uid', $uid)->where('status', 0)->field('id,fabushijian,biaoti,pinglunshu,yuedu,zan,cai,annex,video')->order('recoverytime desc')->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $data->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
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
            $tmp = Catfish::db('tie')->where('id',$id)->field('uid,sid,fabushijian,tietype,shipin,tu')->find();
            if($tmp['uid'] != Catfish::getSession('user_id')){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $ttname = Catfish::db('tietype')->where('id',$tmp['tietype'])->field('bieming')->find();
            $ttname = 'tj' . $ttname['bieming'];
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
            $nrtmp = Catfish::db('tienr')->where('tid',$id)->field('zhengwen,fujian')->find();
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
            $params = [
                'id' => $id,
                'uid' => $tmp['uid'],
                'tu' => $tmp['tu'],
                'shipin' => $tmp['shipin'],
                'fujian' => $nrtmp['fujian']
            ];
            $this->plantHook('deleteMainPost', $params);
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
            'ext' => 'jpg,png,gif,jpeg,webp'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            $position = 'data/uploads/'.str_replace('\\','/',$info->getSaveName());
            $params = [
                'address' => $position
            ];
            $this->plantHook('editorUpload', $params);
            echo Catfish::domain().$position;
        }else{
            echo $file->getError();
        }
    }
    public function myfollowuppost()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $guanjianzi = Catfish::getGet('guanjianzi');
        if($guanjianzi === false){
            $guanjianzi = '';
        }
        $startime = Catfish::getGet('startime');
        if($startime === false){
            $startime = '';
        }
        $endtime = Catfish::getGet('endtime');
        if($endtime === false){
            $endtime = '';
        }
        $query = [
            'guanjianzi' => $guanjianzi,
            'startime' => $startime,
            'endtime' => $endtime,
        ];
        $cachezongjilu = 'user_myfollowuppost_' . $uid . '_' . md5(serialize($query)) . '_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $data = Catfish::view('tie_comments','id,createtime,xiugai,zan,cai,status,content')
            ->view('tie_comm_ontact','tid','tie_comm_ontact.cid=tie_comments.id')
            ->view('tie','biaoti','tie.id=tie_comm_ontact.tid')
            ->where('tie_comments.uid', $uid);
        if($guanjianzi != ''){
            $data = $data->where('content','like','%'.$guanjianzi.'%');
        }
        if($startime != '' || $endtime != ''){
            if(empty($startime)){
                $startime = '2000-01-01';
            }
            if(empty($endtime)){
                $endtime = date('Y-m-d');
            }
            if($startime == $endtime){
                $endtime = date('Y-m-d', strtotime('+1 day', strtotime($endtime)));
            }
            $data = $data->where('createtime','between time',[$startime,$endtime]);
        }
        $data = $data->order('id desc')
            ->paginate(20, $zongjilu,[
                'query' => $query
            ]);
        if($zongjilu === false){
            $zongjilu = $data->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $data->render());
        $data = $data->items();
        foreach($data as $key => $val){
            if($val['status'] == 1){
                $data[$key]['status'] = '<i class="fa fa-check text-success" data-container="body" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="'.Catfish::lang('Approved').'"></i>';
            }
            else{
                $tishi = Catfish::lang('Under review...');
                if(strtotime($val['xiugai']) - time() > 259200){
                    $tishi = Catfish::lang('Did not pass');
                }
                $data[$key]['status'] = '<i class="fa fa-times text-black-50" data-container="body" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="'.$tishi.'"></i>';
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
        Catfish::allot('utouxiang', $touxiang);
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
                    echo Catfish::lang('Email has been used');
                    exit();
                }
                $shouji = Catfish::getPost('shouji');
                if(!empty($shouji)){
                    $re = Catfish::db('users')->where('shouji',$shouji)->where('id', '<>',$uid)->field('id')->find();
                    if(!empty($re)){
                        echo Catfish::lang('Mobile number is already in use');
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
    public function vipmember()
    {
        $this->checkUser();
        if(Catfish::isPost(20)){
            $huiyuanleixing = Catfish::getPost('huiyuanleixing');
            if(empty($huiyuanleixing)){
                echo Catfish::lang('You must select the membership type');
                exit();
            }
            $huiyuanqixian = Catfish::getPost('huiyuanqixian');
            if($huiyuanleixing != 3 && !preg_match("/^[1-9][0-9]*$/", $huiyuanqixian)){
                echo Catfish::lang('The membership period must be an integer greater than 0');
                exit();
            }
            $zhifufangshi = Catfish::getPost('zhifufangshi');
            if(empty($zhifufangshi)){
                echo Catfish::lang('Payment method must be selected');
                exit();
            }
            $vipsetting = Catfish::get('vipsettings');
            if(!empty($vipsetting)){
                $vipsetting = unserialize($vipsetting);
            }
            else{
                echo Catfish::lang('It is not currently allowed to purchase VIP members');
                exit();
            }
            $uid = Catfish::getSession('user_id');
            $user = Catfish::db('users')->where('id',$uid)->field('utype,vipend,viptype,jifen,jinbi')->find();
            if($user['utype'] == 15 && $user['viptype'] == 3){
                echo Catfish::lang('You are already a permanent VIP member, no need to renew');
                exit();
            }
            if($user['utype'] < 6){
                echo Catfish::lang('The administrator cannot be changed to a VIP member');
                exit();
            }
            $nowtime = time();
            $viped = strtotime($user['vipend']);
            if($viped > $nowtime){
                $start = $viped;
            }
            else{
                $start = $nowtime;
            }
            $end = $start;
            if($zhifufangshi == 1){
                $xuyaoluntanbi = 0;
                if($huiyuanleixing == 1){
                    $per = intval($vipsetting['monthvipcoins']);
                    if($per <= 0){
                        echo Catfish::lang('It is not currently allowed to purchase VIP members');
                        exit();
                    }
                    $xuyaoluntanbi = $per * $huiyuanqixian;
                    $end = strtotime("+{$huiyuanqixian} months", $start);
                }
                elseif($huiyuanleixing == 2){
                    $per = intval($vipsetting['yearvipcoins']);
                    if($per <= 0){
                        echo Catfish::lang('It is not currently allowed to purchase VIP members');
                        exit();
                    }
                    $xuyaoluntanbi = $per * $huiyuanqixian;
                    $end = strtotime("+{$huiyuanqixian} years", $start);
                }
                elseif($huiyuanleixing == 3){
                    $per = intval($vipsetting['permanentvipcoins']);
                    if($per <= 0){
                        echo Catfish::lang('It is not currently allowed to purchase VIP members');
                        exit();
                    }
                    $xuyaoluntanbi = $per;
                }
                if($xuyaoluntanbi > 0){
                    if($user['jinbi'] < $xuyaoluntanbi){
                        echo Catfish::lang('You have insufficient forum coins');
                        exit();
                    }
                    else{
                        $vipend = date("Y-m-d H:i:s", $end);
                        Catfish::dbStartTrans();
                        try{
                            Catfish::db('users')->where('id',$uid)->update([
                                'utype' => 15,
                                'vipend' => $vipend,
                                'viptype' => $huiyuanleixing,
                                'jinbi' => Catfish::dbRaw('jinbi-'.$xuyaoluntanbi)
                            ]);
                            Catfish::db('coin_bill')->insert([
                                'uid' => $uid,
                                'zengjian' => - $xuyaoluntanbi,
                                'booktime' => Catfish::now(),
                                'miaoshu' => Catfish::lang('Pay VIP membership fee')
                            ]);
                            Catfish::dbCommit();
                            Catfish::setSession('user_type',15);
                            echo 'ok';
                            exit();
                        } catch (\Exception $e) {
                            Catfish::dbRollback();
                            Catfish::setSession('user_type',20);
                            echo Catfish::lang('The operation failed, please try again later');
                            exit();
                        }
                    }
                }
                else{
                    echo Catfish::lang('It is not currently allowed to purchase VIP members');
                    exit();
                }
            }
            elseif($zhifufangshi == 2){
                $xuyaojifen = 0;
                if($huiyuanleixing == 1){
                    $per = intval($vipsetting['monthvippoints']);
                    if($per <= 0){
                        echo Catfish::lang('It is not currently allowed to purchase VIP members');
                        exit();
                    }
                    $xuyaojifen = $per * $huiyuanqixian;
                    $end = strtotime("+{$huiyuanqixian} months", $start);
                }
                elseif($huiyuanleixing == 2){
                    $per = intval($vipsetting['yearvippoints']);
                    if($per <= 0){
                        echo Catfish::lang('It is not currently allowed to purchase VIP members');
                        exit();
                    }
                    $xuyaojifen = $per * $huiyuanqixian;
                    $end = strtotime("+{$huiyuanqixian} years", $start);
                }
                elseif($huiyuanleixing == 3){
                    $per = intval($vipsetting['permanentvippoints']);
                    if($per <= 0){
                        echo Catfish::lang('It is not currently allowed to purchase VIP members');
                        exit();
                    }
                    $xuyaojifen = $per;
                }
                if($xuyaojifen > 0){
                    if($user['jifen'] < $xuyaojifen){
                        echo Catfish::lang('You don\'t have enough points');
                        exit();
                    }
                    else{
                        $vipend = date("Y-m-d H:i:s", $end);
                        Catfish::dbStartTrans();
                        try{
                            Catfish::db('users')->where('id',$uid)->update([
                                'utype' => 15,
                                'vipend' => $vipend,
                                'viptype' => $huiyuanleixing,
                                'jifen' => Catfish::dbRaw('jifen-'.$xuyaojifen)
                            ]);
                            Catfish::db('points_book')->insert([
                                'uid' => $uid,
                                'zengjian' => - $xuyaojifen,
                                'booktime' => Catfish::now(),
                                'miaoshu' => Catfish::lang('Pay VIP membership fee')
                            ]);
                            Catfish::dbCommit();
                            Catfish::setSession('user_type',15);
                            echo 'ok';
                            exit();
                        } catch (\Exception $e) {
                            Catfish::dbRollback();
                            Catfish::setSession('user_type',20);
                            echo Catfish::lang('The operation failed, please try again later');
                            exit();
                        }
                    }
                }
                else{
                    echo Catfish::lang('It is not currently allowed to purchase VIP members');
                    exit();
                }
            }
            else{
                echo Catfish::lang('Payment method must be selected');
                exit();
            }
        }
        $jianyuuser = Catfish::db('users')->where('id',Catfish::getSession('user_id'))->field('utype,vipend,viptype,jifen,jinbi')->find();
        if($jianyuuser['utype'] == 15){
            $jianyuuser['showvip'] = 1;
            if($jianyuuser['viptype'] == 3){
                $jianyuuser['vipend'] = Catfish::lang('Permanent');
                $jianyuuser['viptype'] = Catfish::lang('Permanent member');
            }
            else{
                $jianyuuser['viptype'] = Catfish::lang('Term membership');
            }
            if(strtotime($jianyuuser['vipend']) > time()){
                $jianyuuser['expired'] = 0;
            }
            else{
                $jianyuuser['expired'] = 1;
            }
        }
        else{
            $jianyuuser['showvip'] = 0;
            $jianyuuser['expired'] = 0;
            $jianyuuser['vipend'] = Catfish::lang('Permanent');
            $jianyuuser['viptype'] = Catfish::lang('General user');
        }
        Catfish::allot('jianyuvip', $jianyuuser);
        $vipsetting = Catfish::get('vipsettings');
        if(!empty($vipsetting)){
            $vipsetting = unserialize($vipsetting);
        }
        else{
            $vipsetting = [
                'monthvipcoins' => '',
                'yearvipcoins' => '',
                'permanentvipcoins' => '',
                'allowpointsvip' => 0,
                'monthvippoints' => '',
                'yearvippoints' => '',
                'permanentvippoints' => '',
            ];
        }
        Catfish::allot('catfishcms', $vipsetting);
        $jumpto = '';
        if(Catfish::hasGet('jumpto')){
            $jumpto = Catfish::getGet('jumpto');
        }
        Catfish::allot('jumpto', $jumpto);
        return $this->show(Catfish::lang('VIP member'), 'vipmember');
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
        $cachezongjilu = 'user_forummainpost_'.md5($sidstr.'_'.serialize($query)).'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = $catfish->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20,$zongjilu,[
                'query' => $query
            ]);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
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
    public function unreviewedmainposts()
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
        $cachezongjilu = 'user_unreviewedmainposts_'.md5($sidstr).'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie','id,sid,fabushijian,biaoti,review,yuedu,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.sid','in',$sidstr)
            ->where('tie.status','=',1)
            ->where('tie.review','=',0)
            ->order('tie.id desc')
            ->paginate(20,$zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        $typeidnm = $this->gettypeidname();
        foreach($catfish as $key => $val){
            $catfish[$key]['tietype'] = $typeidnm[$val['tietype']];
            $catfish[$key]['banzhu'] = $sidarr[$val['sid']];
        }
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('mtype', Catfish::getSession('mtype'));
        return $this->show(Catfish::lang('Unreviewed main posts'), 'unreviewedmainposts');
    }
    public function delforummainpost()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $uid = Catfish::getSession('user_id');
            $tmp = Catfish::db('tie')->where('id',$id)->field('uid,sid,fabushijian,tietype,shipin,tu')->find();
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
            $ttname = Catfish::db('tietype')->where('id',$tmp['tietype'])->field('bieming')->find();
            $ttname = 'tj' . $ttname['bieming'];
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
            $nrtmp = Catfish::db('tienr')->where('tid',$id)->field('zhengwen,fujian')->find();
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
            $params = [
                'id' => $id,
                'uid' => $tmp['uid'],
                'tu' => $tmp['tu'],
                'shipin' => $tmp['shipin'],
                'fujian' => $nrtmp['fujian']
            ];
            $this->plantHook('deleteMainPost', $params);
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
        $cachezongjilu = 'user_followingupsection_'.md5($sidstr).'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie_comments','id,uid,sid,createtime,status,content')
            ->view('tie_comm_ontact','tid','tie_comm_ontact.cid=tie_comments.id', 'LEFT')
            ->view('users','nicheng','users.id=tie_comments.uid', 'LEFT')
            ->where('tie_comments.sid','in',$sidstr)
            ->order('tie_comments.xiugai desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        foreach($catfish as $key => $val){
            $catfish[$key]['banzhu'] = $sidarr[$val['sid']];
        }
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('mtype', Catfish::getSession('mtype'));
        return $this->show(Catfish::lang('Following-up of the section'), 'followingupsection');
    }
    public function unreviewedfollowposts()
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
        $cachezongjilu = 'user_unreviewedfollowposts_'.md5($sidstr).'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie_comments','id,uid,sid,createtime,status,content')
            ->view('tie_comm_ontact','tid','tie_comm_ontact.cid=tie_comments.id', 'LEFT')
            ->view('users','nicheng','users.id=tie_comments.uid', 'LEFT')
            ->where('tie_comments.sid','in',$sidstr)
            ->where('tie_comments.status','=',0)
            ->order('tie_comments.xiugai desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        foreach($catfish as $key => $val){
            $catfish[$key]['banzhu'] = $sidarr[$val['sid']];
        }
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('mtype', Catfish::getSession('mtype'));
        return $this->show(Catfish::lang('Unreviewed follow posts'), 'unreviewedfollowposts');
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
        $cachezongjilu = 'user_toppost_'.md5($sidstr).'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie','id,sid,fabushijian,biaoti,review,yuedu,istop,recommended,jingpin,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.sid','in',$sidstr)
            ->where('tie.istop','=',1)
            ->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
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
        $cachezongjilu = 'user_recommendedpost_'.md5($sidstr).'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie','id,sid,fabushijian,biaoti,review,yuedu,istop,recommended,jingpin,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.sid','in',$sidstr)
            ->where('tie.recommended','=',1)
            ->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
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
        $cachezongjilu = 'user_finepost_'.md5($sidstr).'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie','id,sid,fabushijian,biaoti,review,yuedu,istop,recommended,jingpin,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.sid','in',$sidstr)
            ->where('tie.jingpin','=',1)
            ->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
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
        $lianxifangshi = Catfish::get('jifenlianxifangshi');
        Catfish::allot('lianxifangshi', $lianxifangshi);
        $duihuan = 0;
        $forum = Catfish::getForum();
        if(in_array($forum['jifenbi'], [1,3])){
            $duihuan = 1;
        }
        Catfish::allot('duihuan', $duihuan);
        Catfish::allot('openpay', Catfish::get('openpay'));
        return $this->show(Catfish::lang('How to earn points'), 'earnpoints');
    }
    public function bitojf()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $jinbi = Catfish::db('users')->where('id', $uid)->field('jinbi')->find();
        if(Catfish::isPost(20)){
            $data = $this->bitojfPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                if(!preg_match("/^[1-9][0-9]*$/", $data['luntanbi'])){
                    echo Catfish::lang('The number of forum coins must be an integer');
                    exit();
                }
                $luntanbi = intval($data['luntanbi']);
                if($luntanbi <= 0){
                    echo Catfish::lang('The number of forum coins must be greater than 0');
                    exit();
                }
                $recod = Catfish::db('users')->where('id',$uid)->field('password,randomcode')->find();
                if($recod['password'] != md5($data['password'].$recod['randomcode']))
                {
                    echo Catfish::lang('Password is wrong');
                    exit();
                }
                if($jinbi['jinbi'] < $luntanbi){
                    echo Catfish::lang('You have insufficient forum coins');
                    exit();
                }
                $forum = Catfish::getForum();
                if(!in_array($forum['jifenbi'], [1,3])){
                    echo Catfish::lang('Your operation is illegal');
                    exit();
                }
                $jinbijifen = Catfish::get('jinbijifenduihuan');
                $jifenshu = intval($luntanbi * $jinbijifen);
                $now = Catfish::now();
                Catfish::dbStartTrans();
                try{
                    Catfish::db('users')
                        ->where('id', $uid)
                        ->update([
                            'jifen' => Catfish::dbRaw('jifen+' . $jifenshu),
                            'jinbi' => Catfish::dbRaw('jinbi-' . $luntanbi)
                        ]);
                    if($luntanbi > 0){
                        Catfish::db('points_book')->insert([
                            'uid' => $uid,
                            'zengjian' => $jifenshu,
                            'booktime' => $now,
                            'miaoshu' => Catfish::lang('Exchange forum coins for points')
                        ]);
                        Catfish::db('coin_bill')->insert([
                            'uid' => $uid,
                            'zengjian' => - $luntanbi,
                            'booktime' => $now,
                            'miaoshu' => Catfish::lang('Exchange forum coins for points')
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
        $duihuan = 0;
        $forum = Catfish::getForum();
        if(in_array($forum['jifenbi'], [1,3])){
            $duihuan = 1;
        }
        Catfish::allot('duihuan', $duihuan);
        Catfish::allot('jinbi', $jinbi['jinbi']);
        $jinbijifen = Catfish::get('jinbijifenduihuan');
        Catfish::allot('duihuanshu', $jinbijifen);
        $maxjf = intval($jinbi['jinbi'] * $jinbijifen);
        Catfish::allot('maxjf', $maxjf);
        return $this->show(Catfish::lang('Exchange forum coins for points'), 'earnpoints');
    }
    public function pointsbill()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $cachezongjilu = 'user_pointsbill_'.$uid.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $data = Catfish::db('points_book')->where('uid', $uid)->field('id,zengjian,booktime,miaoshu')->order('id desc')->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $data->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
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
    public function myforumcoin()
    {
        $this->checkUser();
        $jinbi = Catfish::db('users')->where('id', Catfish::getSession('user_id'))->field('jinbi')->find();
        Catfish::allot('jinbi', $jinbi['jinbi']);
        $jinbiduihuan = Catfish::get('jinbiduihuan');
        if(empty($jinbiduihuan)){
            $jinbiduihuan = 0;
        }
        Catfish::allot('jinbiduihuan', $jinbiduihuan);
        $lianxifangshi = Catfish::get('jinbilianxifangshi');
        Catfish::allot('lianxifangshi', $lianxifangshi);
        $duihuan = 0;
        $forum = Catfish::getForum();
        if(in_array($forum['jifenbi'], [2,3])){
            $duihuan = 1;
        }
        Catfish::allot('duihuan', $duihuan);
        Catfish::allot('openpay', Catfish::get('openpay'));
        return $this->show(Catfish::lang('My forum coin'), 'myforumcoin');
    }
    public function jftobi()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $jifen = Catfish::db('users')->where('id', $uid)->field('jifen')->find();
        if(Catfish::isPost(20)){
            $data = $this->bitojfPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                if(!preg_match("/^[1-9][0-9]*$/", $data['luntanbi'])){
                    echo Catfish::lang('The number of forum coins must be an integer');
                    exit();
                }
                $luntanbi = intval($data['luntanbi']);
                if($luntanbi <= 0){
                    echo Catfish::lang('The number of forum coins must be greater than 0');
                    exit();
                }
                $recod = Catfish::db('users')->where('id',$uid)->field('password,randomcode')->find();
                if($recod['password'] != md5($data['password'].$recod['randomcode']))
                {
                    echo Catfish::lang('Password is wrong');
                    exit();
                }
                $jinbijifen = Catfish::get('jinbijifenduihuan');
                $jifenshu = intval($luntanbi * $jinbijifen);
                if($jifen['jifen'] < $jifenshu){
                    echo Catfish::lang('You don\'t have enough points');
                    exit();
                }
                $forum = Catfish::getForum();
                if(!in_array($forum['jifenbi'], [2,3])){
                    echo Catfish::lang('Your operation is illegal');
                    exit();
                }
                $now = Catfish::now();
                Catfish::dbStartTrans();
                try{
                    Catfish::db('users')
                        ->where('id', $uid)
                        ->update([
                            'jifen' => Catfish::dbRaw('jifen-' . $jifenshu),
                            'jinbi' => Catfish::dbRaw('jinbi+' . $luntanbi)
                        ]);
                    if($luntanbi > 0){
                        Catfish::db('points_book')->insert([
                            'uid' => $uid,
                            'zengjian' => - $jifenshu,
                            'booktime' => $now,
                            'miaoshu' => Catfish::lang('Exchange points into forum coins')
                        ]);
                        Catfish::db('coin_bill')->insert([
                            'uid' => $uid,
                            'zengjian' => $luntanbi,
                            'booktime' => $now,
                            'miaoshu' => Catfish::lang('Exchange points into forum coins')
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
        $duihuan = 0;
        $forum = Catfish::getForum();
        if(in_array($forum['jifenbi'], [2,3])){
            $duihuan = 1;
        }
        Catfish::allot('duihuan', $duihuan);
        Catfish::allot('jifen', $jifen['jifen']);
        $jinbijifen = Catfish::get('jinbijifenduihuan');
        Catfish::allot('duihuanshu', $jinbijifen);
        $maxjb = floor($jifen['jifen'] / $jinbijifen);
        Catfish::allot('maxjb', $maxjb);
        return $this->show(Catfish::lang('Exchange points into forum coins'), 'myforumcoin');
    }
    public function forumcoinbill()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $cachezongjilu = 'user_forumcoinbill_'.$uid.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $data = Catfish::db('coin_bill')->where('uid', $uid)->field('id,zengjian,booktime,miaoshu')->order('id desc')->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $data->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $data->render());
        $datarr = $data->items();
        foreach($datarr as $key => $val){
            if($val['zengjian'] > 0){
                $datarr[$key]['zengjian'] = '+' . $val['zengjian'];
            }
        }
        Catfish::allot('data', $datarr);
        return $this->show(Catfish::lang('Forum coin bill'), 'forumcoinbill');
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
                'checkfirst' => 0,
                'checksecond' => 0,
                'checkthird' => 0,
                'checkfourth' => 0,
                'checkfifth' => 0,
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
                    $rank = Catfish::get('qiandaopaiming');
                    $paiming = 1;
                    if(!empty($qiandao)){
                        $rank = unserialize($rank);
                        if($rank['date'] != date("Y-m-d")){
                            $rank = ['date' => $today, 'rank' => 1];
                        }
                        else{
                            $paiming = $rank['rank'] + 1;
                            $rank['rank'] = $paiming;
                        }
                    }
                    else{
                        $rank = ['date' => $today, 'rank' => 1];
                    }
                    Catfish::set('qiandaopaiming', serialize($rank));
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
                        if(isset($qiandao['checkfirst'])){
                            switch($paiming){
                                case 1:
                                    $jifen += intval($qiandao['checkfirst']);
                                    break;
                                case 2:
                                    $jifen += intval($qiandao['checksecond']);
                                    break;
                                case 3:
                                    $jifen += intval($qiandao['checkthird']);
                                    break;
                                case 4:
                                    $jifen += intval($qiandao['checkfourth']);
                                    break;
                                case 5:
                                    $jifen += intval($qiandao['checkfifth']);
                                    break;
                            }
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
                        $statistics = Catfish::db('sign_in_statistics')->where('uid',$uid)->field('id')->find();
                        if(empty($statistics)){
                            Catfish::db('sign_in_statistics')->insert([
                                'uid' => $uid,
                                'qiandaoshijian' => date("Y-m-d H:i:s"),
                                'leijiqiandao' => 1,
                                'leijijiangli' => $jifen,
                                'jinrijiangli' => $jifen,
                                'lianxu' => $lianxu
                            ]);
                        }
                        else{
                            Catfish::db('sign_in_statistics')->where('uid',$uid)->update([
                                'qiandaoshijian' => date("Y-m-d H:i:s"),
                                'leijiqiandao' => Catfish::dbRaw('leijiqiandao+1'),
                                'leijijiangli' => Catfish::dbRaw('leijijiangli+' . $jifen),
                                'jinrijiangli' => $jifen,
                                'lianxu' => $lianxu
                            ]);
                        }
                    }
                    Catfish::clearCache('qiandao');
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
    public function mycollection()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $cachezongjilu = 'user_mycollection_'.$uid.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie_favorites','id,tid,createtime')
            ->view('tie','biaoti,annex,review,status','tie.id=tie_favorites.tid')
            ->where('tie_favorites.uid',$uid)
            ->order('tie_favorites.id desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('My collection'), 'mycollection');
    }
    public function delmycollection()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $uid = Catfish::getSession('user_id');
            $favorites = Catfish::db('tie_favorites')
                ->where('id', $id)
                ->where('uid', $uid)
                ->field('tid')
                ->find();
            if(empty($favorites)){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            Catfish::dbStartTrans();
            try{
                Catfish::db('tie_favorites')
                    ->where('id', $id)
                    ->where('uid', $uid)
                    ->delete();
                Catfish::db('tie')
                    ->where('id', $favorites['tid'])
                    ->update([
                        'shoucang' => Catfish::dbRaw('shoucang-1')
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
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function likedposts()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $cachezongjilu = 'user_likedposts_'.$uid.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie_zan','id,tid,accesstime')
            ->view('tie','biaoti,annex,review,status','tie.id=tie_zan.tid')
            ->where('tie_zan.uid',$uid)
            ->order('tie_zan.id desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Liked posts'), 'likedposts');
    }
    public function dellikedposts()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $uid = Catfish::getSession('user_id');
            $favorites = Catfish::db('tie_zan')
                ->where('id', $id)
                ->where('uid', $uid)
                ->field('tid')
                ->find();
            if(empty($favorites)){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            Catfish::dbStartTrans();
            try{
                Catfish::db('tie_zan')
                    ->where('id', $id)
                    ->where('uid', $uid)
                    ->delete();
                Catfish::db('tie')
                    ->where('id', $favorites['tid'])
                    ->update([
                        'zan' => Catfish::dbRaw('zan-1')
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
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function dislikedpost()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $cachezongjilu = 'user_dislikedpost_'.$uid.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie_cai','id,tid,accesstime')
            ->view('tie','biaoti,annex,review,status','tie.id=tie_cai.tid')
            ->where('tie_cai.uid',$uid)
            ->order('tie_cai.id desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Disliked post'), 'dislikedpost');
    }
    public function deldislikedpost()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $uid = Catfish::getSession('user_id');
            $favorites = Catfish::db('tie_cai')
                ->where('id', $id)
                ->where('uid', $uid)
                ->field('tid')
                ->find();
            if(empty($favorites)){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            Catfish::dbStartTrans();
            try{
                Catfish::db('tie_cai')
                    ->where('id', $id)
                    ->where('uid', $uid)
                    ->delete();
                Catfish::db('tie')
                    ->where('id', $favorites['tid'])
                    ->update([
                        'cai' => Catfish::dbRaw('cai-1')
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
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function likedfollow()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $cachezongjilu = 'user_likedfollow_'.$uid.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('gentie_zan','id,accesstime')
            ->view('tie_comments','status,content','tie_comments.id=gentie_zan.cid')
            ->view('tie_comm_ontact','tid','tie_comm_ontact.cid=tie_comments.id')
            ->where('gentie_zan.uid',$uid)
            ->order('gentie_zan.id desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Liked follow'), 'likedfollow');
    }
    public function dellikedfollow()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $uid = Catfish::getSession('user_id');
            $favorites = Catfish::db('gentie_zan')
                ->where('id', $id)
                ->where('uid', $uid)
                ->field('cid')
                ->find();
            if(empty($favorites)){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            Catfish::dbStartTrans();
            try{
                Catfish::db('gentie_zan')
                    ->where('id', $id)
                    ->where('uid', $uid)
                    ->delete();
                Catfish::db('tie_comments')
                    ->where('id', $favorites['cid'])
                    ->update([
                        'zan' => Catfish::dbRaw('zan-1')
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
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function dislikedfollow()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $cachezongjilu = 'user_dislikedfollow_'.$uid.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('gentie_cai','id,accesstime')
            ->view('tie_comments','status,content','tie_comments.id=gentie_cai.cid')
            ->view('tie_comm_ontact','tid','tie_comm_ontact.cid=tie_comments.id')
            ->where('gentie_cai.uid',$uid)
            ->order('gentie_cai.id desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Disliked follow'), 'dislikedfollow');
    }
    public function deldislikedfollow()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $uid = Catfish::getSession('user_id');
            $favorites = Catfish::db('gentie_cai')
                ->where('id', $id)
                ->where('uid', $uid)
                ->field('cid')
                ->find();
            if(empty($favorites)){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            Catfish::dbStartTrans();
            try{
                Catfish::db('gentie_cai')
                    ->where('id', $id)
                    ->where('uid', $uid)
                    ->delete();
                Catfish::db('tie_comments')
                    ->where('id', $favorites['cid'])
                    ->update([
                        'cai' => Catfish::dbRaw('cai-1')
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
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function pointspaidposts()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $cachezongjilu = 'user_pointspaidposts_'.$uid.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie_jifen','id,tid,paytime')
            ->view('tie','biaoti,annex,review,status','tie.id=tie_jifen.tid')
            ->where('tie_jifen.uid',$uid)
            ->order('tie_jifen.id desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        foreach($catfish as $key => $val){
            if($val['paytime'] == '2000-01-01 00:00:00'){
                $catfish[$key]['paytime'] = Catfish::lang('Nnknown');
            }
        }
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Points paid posts'), 'pointspaidposts');
    }
    public function postsvisited()
    {
        $this->checkUser();
        $uid = Catfish::getSession('user_id');
        $cachezongjilu = 'user_postsvisited_'.$uid.'_zongjilu';
        $zongjilu = Catfish::getCache($cachezongjilu);
        $catfish = Catfish::view('tie_access','id,tid,accesstime')
            ->view('tie','biaoti,annex,review,status','tie.id=tie_access.tid')
            ->where('tie_access.uid',$uid)
            ->order('tie_access.id desc')
            ->paginate(20, $zongjilu);
        if($zongjilu === false){
            $zongjilu = $catfish->total();
            Catfish::setCache($cachezongjilu,$zongjilu,$this->time);
        }
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Posts visited'), 'postsvisited');
    }
    public function plugin()
    {
        $this->checkUser();
        $name = $this->untoup(Catfish::getParam('name'));
        $func = $this->untoup(Catfish::getParam('func'));
        $plugin = $this->untoup(Catfish::getParam('plugin'));
        $theme = $this->untoup(Catfish::getParam('theme'));
        $alias = urldecode(Catfish::getParam('alias'));
        $theme = ($theme == '_theme') ? '' : $theme;
        $params = [
            'plugin' => $plugin,
            'name' => $name,
            'alias' => $alias,
            'function' => $func,
            'template' => $theme,
        ];
        $lang = Catfish::detectLang();
        if(empty($theme)){
            $langPath = ROOT_PATH.'plugins/'.$plugin.'/lang/'.$lang.'.php';
        }
        else{
            $langPath = ROOT_PATH.'public/theme/'.$plugin.'/theme/lang/'.$lang.'.php';
        }
        if(is_file($langPath)){
            Catfish::loadLang($langPath);
        }
        $ufplugin = ucfirst($plugin);
        $html = '';
        if(Catfish::isPost(20)){
            $post = Catfish::getPost();
            if(isset($post['verification'])){
                unset($post['verification']);
            }
            if(empty($theme)){
                Catfish::execHook('plugin\\' . $plugin . '\\' . $ufplugin, $func . 'Post', $post);
            }
            else{
                Catfish::execHook('theme\\' . $plugin . '\\' . $ufplugin, $func . 'Post', $post);
            }
            if(isset($post['result'])){
                echo $post['result'];
                exit();
            }
        }
        if(empty($theme)){
            Catfish::execHook('plugin\\' . $plugin . '\\' . $ufplugin, $func, $params);
        }
        else{
            Catfish::execHook('theme\\' . $plugin . '\\' . $ufplugin, $func, $params);
        }
        if(isset($params['html'])){
            $html = $params['html'];
        }
        Catfish::allot('plugin', $html);
        return $this->show($alias, $name);
    }
    public function uploadvideo()
    {
        if(Catfish::isPost(20)){
            $tid = intval(Catfish::getPost('tid'));
            $ovdo = '';
            if($tid > 0){
                $tmp = Catfish::db('tie')->where('id',$tid)->field('uid,video,shipin')->find();
                if($tmp['uid'] != Catfish::getSession('user_id')){
                    $result = [
                        'result' => 'error',
                        'message' => Catfish::lang('Your operation is illegal')
                    ];
                    return json($result);
                }
                if($tmp['video'] == 1){
                    $ovdo = $tmp['shipin'];
                }
            }
            $file = request()->file(Catfish::getPost('file'));
            if($file){
                $validate = [
                    'ext' => 'mp4,ogg,webm'
                ];
                $info = $file->validate($validate)->move(ROOT_PATH . 'data' . DS . 'video');
                if($info){
                    $name = $file->getInfo('name');
                    $crtname = str_replace('\\','/',$info->getSaveName());
                    $crtpath = 'data/video/'.$crtname;
                    if($tid > 0){
                        Catfish::dbStartTrans();
                        try{
                            Catfish::db('tie')
                                ->where('id', $tid)
                                ->update([
                                    'video' => 1,
                                    'shipin' => $crtpath
                                ]);
                            Catfish::db('tienr')
                                ->where('tid', $tid)
                                ->update([
                                    'shipinming' => $name
                                ]);
                            Catfish::dbCommit();
                        } catch (\Exception $e) {
                            Catfish::dbRollback();
                            $result = [
                                'result' => 'error',
                                'message' => Catfish::lang('The operation failed, please try again later')
                            ];
                            return json($result);
                        }
                        if(!empty($ovdo) && Catfish::isDataPath($ovdo)){
                            $path = ROOT_PATH . str_replace(['/','\\'], DS, $ovdo);
                            if(is_file($path)){
                                @unlink($path);
                            }
                        }
                    }
                    $params = [
                        'shipin' => $crtpath
                    ];
                    $this->plantHook('uploadVideo', $params);
                    $result = [
                        'result' => 'ok',
                        'name' => $name,
                        'path' =>$crtpath,
                        'message' => ''
                    ];
                    return json($result);
                }
                else{
                    $result = [
                        'result' => 'error',
                        'message' => Catfish::lang('Upload failed') . ': ' . $file->getError()
                    ];
                    return json($result);
                }
            }
            else{
                $result = [
                    'result' => 'error',
                    'message' => Catfish::lang('No uploaded files')
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
    public function delvideo()
    {
        if(Catfish::isPost(20)){
            $path = Catfish::getPost('path');
            $tid = intval(Catfish::getPost('tid'));
            if($tid > 0){
                $tmp = Catfish::db('tie')->where('id',$tid)->field('uid,shipin')->find();
                if($tmp['uid'] != Catfish::getSession('user_id') || $tmp['shipin'] != $path){
                    echo Catfish::lang('Your operation is illegal');
                    exit();
                }
                $ovdo = $tmp['shipin'];
                Catfish::dbStartTrans();
                try{
                    Catfish::db('tie')
                        ->where('id', $tid)
                        ->update([
                            'video' => 0,
                            'shipin' => ''
                        ]);
                    Catfish::db('tienr')
                        ->where('tid', $tid)
                        ->update([
                            'shipinming' => ''
                        ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                if(!empty($ovdo) && Catfish::isDataPath($ovdo)){
                    $path = ROOT_PATH . str_replace(['/','\\'], DS, $ovdo);
                    if(is_file($path)){
                        @unlink($path);
                    }
                }
                $params = [
                    'shipin' => $ovdo
                ];
                $this->plantHook('deleteVideo', $params);
                echo 'ok';
                exit();
            }
            else{
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function manamymainpost()
    {
        if(Catfish::isPost(20)){
            $id = intval(Catfish::getPost('id'));
            $uid = Catfish::getSession('user_id');
            $tmp = Catfish::db('tie')->where('id',$id)->field('uid')->find();
            if($tmp['uid'] != $uid){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $chkarr = ['isclose'];
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
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                Catfish::removeCache('post_'.$id);
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
}