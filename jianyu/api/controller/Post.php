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
class Post extends Jsonapi
{
    private $time = 1200;
    public function add($param, $uid)
    {
        $subSort = [];
        $fenleiarr = $this->getSortCache();
        foreach($fenleiarr as $key => $val){
            if($val['disabled'] == 0){
                $subSort[] = $val['id'];
            }
        }
        if(!in_array($param['section'], $subSort)){
            $err = $this->createError('712', 'Section not allowed', 'Section does not match');
            $this->addError($err);
        }
        else{
            if(!in_array($param['type'], [1,2])){
                $err = $this->createError('711', 'The post type can only be 1 or 2', 'Type does not match');
                $this->addError($err);
            }
            else{
                $forum = $this->myforumpost($uid);
                if(!$this->checkIllegal($param['content'], $forum['mingan'])){
                    $err = $this->createError('718', 'Posting content contains content that is not allowed', 'Failed to post');
                    $this->addError($err);
                }
                else{
                    $now = Catfish::now();
                    $zhengwen = $param['content'];
                    $zhaiyao = mb_substr($zhengwen, 0, 300);
                    if(mb_strlen($zhengwen) > 300){
                        $zhaiyao .= '...';
                    }
                    $chengzhang = Catfish::getGrowing();
                    $ttname = Catfish::db('tietype')->where('id',$param['type'])->field('bieming')->find();
                    $ttname = 'tj' . $ttname['bieming'];
                    $tus = '';
                    $tuArr = explode('<img ',$zhengwen);
                    array_shift($tuArr);
                    if(count($tuArr) > 0){
                        $domain = Catfish::domain();
                        foreach($tuArr as $key => $val){
                            $val = strstr($val, '>', true);
                            preg_match('/src="(.*?)"/i',str_replace("'",'"',$val),$tusrc);
                            if(strpos($tusrc[1], $domain) !== false){
                                $tus .= empty($tus) ? $tusrc[1] : ',' . $tusrc[1];
                            }
                        }
                    }
                    Catfish::dbStartTrans();
                    try{
                        $reid = Catfish::db('tie')->insertGetId([
                            'uid' => $uid,
                            'sid' => $param['section'],
                            'guanjianzi' => '',
                            'fabushijian' => $now,
                            'biaoti' => $param['title'],
                            'zhaiyao' => $zhaiyao,
                            'ordertime' => $now,
                            'tietype' => $param['type'],
                            'tu' => $tus
                        ]);
                        Catfish::db('tienr')->insert([
                            'tid' => $reid,
                            'zhengwen' => $zhengwen
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
                            ->where('uid', $uid)
                            ->update([
                                $ttname => Catfish::dbRaw($ttname.'+1')
                            ]);
                        Catfish::db('tietype')
                            ->where('id', $param['type'])
                            ->update([
                                'tongji' => Catfish::dbRaw('tongji+1')
                            ]);
                        Catfish::db('msort')
                            ->where('id', $param['section'])
                            ->update([
                                'zhutie' => Catfish::dbRaw('zhutie+1'),
                                $ttname => Catfish::dbRaw($ttname.'+1')
                            ]);
                        Catfish::db('users_tongji_'.date('Ym'))
                            ->where('uid', $uid)
                            ->update([
                                'yuefatie' => Catfish::dbRaw('yuefatie+1')
                            ]);
                        Catfish::dbCommit();
                        Catfish::tongji('zhutie');
                        $data = $this->createData('post', $reid, [
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
        $subSort = [];
        $fenleiarr = $this->getSortCache();
        foreach($fenleiarr as $key => $val){
            if($val['disabled'] == 0){
                $subSort[] = $val['id'];
            }
        }
        if(!in_array($param['section'], $subSort)){
            $err = $this->createError('712', 'Section not allowed', 'Section does not match');
            $this->addError($err);
        }
        else{
            if(!in_array($param['type'], [1,2])){
                $err = $this->createError('711', 'The post type can only be 1 or 2', 'Type does not match');
                $this->addError($err);
            }
            else{
                $tie = Catfish::db('tie')->where('id',$id)->field('id,uid,sid,biaoti,tietype')->find();
                if($tie['uid'] != $uid){
                    $err = $this->createError('720', 'No operation authority', 'Operation not allowed');
                    $this->addError($err);
                }
                else{
                    $forum = $this->myforumpost($uid);
                    if(!$this->checkIllegal($param['content'], $forum['mingan'])){
                        $err = $this->createError('718', 'Posting content contains content that is not allowed', 'Failed to post');
                        $this->addError($err);
                    }
                    else{
                        $zhengwen = $param['content'];
                        $zhaiyao = mb_substr($zhengwen, 0, 300);
                        if(mb_strlen($zhengwen) > 300){
                            $zhaiyao .= '...';
                        }
                        $tieyptb = Catfish::db('tietype')->field('id, bieming')->select();
                        $nttname = '';
                        $ottname = '';
                        foreach($tieyptb as $key => $val){
                            if($val['id'] == $param['type']){
                                $nttname = 'tj' . $val['bieming'];
                            }
                            if($val['id'] == $tie['tietype']){
                                $ottname = 'tj' . $val['bieming'];
                            }
                        }
                        $tus = '';
                        $tuArr = explode('<img ',$zhengwen);
                        array_shift($tuArr);
                        if(count($tuArr) > 0){
                            $domain = Catfish::domain();
                            foreach($tuArr as $key => $val){
                                $val = strstr($val, '>', true);
                                preg_match('/src="(.*?)"/i',str_replace("'",'"',$val),$tusrc);
                                if(strpos($tusrc[1], $domain) !== false){
                                    $tus .= empty($tus) ? $tusrc[1] : ',' . $tusrc[1];
                                }
                            }
                        }
                        $tietop = Catfish::db('tie_top')->where('tid', $id)->field('id,sid')->find();
                        $tietuijian = Catfish::db('tie_tuijian')->where('tid', $id)->field('id,sid')->find();
                        Catfish::dbStartTrans();
                        try{
                            Catfish::db('tie')->where('id', $id)->update([
                                'sid' => $param['section'],
                                'biaoti' => $param['title'],
                                'zhaiyao' => $zhaiyao,
                                'tietype' => $param['type'],
                                'tu' => $tus
                            ]);
                            Catfish::db('tienr')->where('tid', $id)->update([
                                'zhengwen' => $zhengwen
                            ]);
                            if($tie['tietype'] != $param['type']){
                                Catfish::db('tietype')
                                    ->where('id', $tie['tietype'])
                                    ->update([
                                        'tongji' => Catfish::dbRaw('tongji-1')
                                    ]);
                                Catfish::db('tietype')
                                    ->where('id', $param['type'])
                                    ->update([
                                        'tongji' => Catfish::dbRaw('tongji+1')
                                    ]);
                            }
                            if($ottname != $nttname){
                                Catfish::db('users_tongji')
                                    ->where('uid', $uid)
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
                                ->where('id', $param['section'])
                                ->update([
                                    $nttname => Catfish::dbRaw($nttname.'+1')
                                ]);
                            if(!empty($tietop) && $tietop['sid'] != $param['section']){
                                Catfish::db('tie_top')
                                    ->where('id', $tietop['id'])
                                    ->update([
                                        'sid' => $param['section']
                                    ]);
                            }
                            if(!empty($tietuijian) && $tietuijian['sid'] != $param['section']){
                                Catfish::db('tie_tuijian')
                                    ->where('id', $tietuijian['id'])
                                    ->update([
                                        'sid' => $param['section']
                                    ]);
                            }
                            Catfish::dbCommit();
                            $data = $this->createData('post', $id, [
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
        }
        return $this->outJsonApi();
    }
    public function delete($param, $uid, $id)
    {
        $tie = Catfish::db('tie')->where('id',$id)->field('id,uid,sid,fabushijian,tietype,shipin,tu')->find();
        if($tie['uid'] != $uid){
            $err = $this->createError('720', 'No operation authority', 'Operation not allowed');
            $this->addError($err);
        }
        else{
            if(isset($param['method']) && $param['method'] == 'remove'){
                $ttname = Catfish::db('tietype')->where('id',$tie['tietype'])->field('bieming')->find();
                $ttname = 'tj' . $ttname['bieming'];
                $tcstr = '';
                $gentieshu = 0;
                $tcontact = Catfish::db('tie_comm_ontact')->where('tid',$id)->field('cid')->select();
                foreach((array)$tcontact as $key => $val){
                    $tcstr .= empty($tcstr) ? $val['cid'] : ',' . $val['cid'];
                    $gentieshu ++;
                }
                $yue = date('Ym', strtotime($tie['fabushijian']));
                $tbnm = Catfish::prefix().'users_tongji_'.$yue;
                $istb = Catfish::hastable($tbnm);
                $nrtmp = Catfish::db('tienr')->where('tid',$id)->field('fujian')->find();
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
                        ->where('id', $uid)
                        ->update([
                            'fatie' => Catfish::dbRaw('fatie-1')
                        ]);
                    Catfish::db('users_tongji')
                        ->where('uid', $uid)
                        ->update([
                            $ttname => Catfish::dbRaw($ttname.'-1')
                        ]);
                    Catfish::db('tietype')
                        ->where('id', $tie['tietype'])
                        ->update([
                            'tongji' => Catfish::dbRaw('tongji-1')
                        ]);
                    Catfish::db('msort')
                        ->where('id', $tie['sid'])
                        ->update([
                            'zhutie' => Catfish::dbRaw('zhutie-1'),
                            'gentie' => Catfish::dbRaw('gentie-'.$gentieshu),
                            $ttname => Catfish::dbRaw($ttname.'-1')
                        ]);
                    Catfish::db('tongji')
                        ->where('riqi', date("Y-m-d", strtotime($tie['fabushijian'])))
                        ->update([
                            'zhutie' => Catfish::dbRaw('zhutie-1')
                        ]);
                    if($istb == true){
                        Catfish::db('users_tongji_'.$yue)
                            ->where('uid', $uid)
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
                    $params = [
                        'id' => $id,
                        'uid' => $tie['uid'],
                        'tu' => $tie['tu'],
                        'shipin' => $tie['shipin'],
                        'fujian' => $nrtmp['fujian']
                    ];
                    $this->plantHook('deleteMainPost', $params);
                    $data = $this->createData('post', $id, [
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
                Catfish::db('tie')
                    ->where('id', $id)
                    ->update([
                        'status' => 0,
                        'recoverytime' => Catfish::now()
                    ]);
                $data = $this->createData('post', $id, [
                    'result' => 'ok'
                ]);
                $this->addData($data);
            }
        }
        return $this->outJsonApi();
    }
    public function patch($param, $uid, $id)
    {
        $tie = Catfish::db('tie')->where('id',$id)->field('id,uid')->find();
        if($param['method'] == 'like'){
            if($tie['uid'] == $uid){
                $err = $this->createError('731', 'Can not like yourself', 'Operation not allowed');
                $this->addError($err);
            }
            else{
                $hasrec = Catfish::db('tie_zan')->where('tid', $id)->where('uid', $uid)->field('id')->find();
                if(empty($hasrec)){
                    Catfish::dbStartTrans();
                    try{
                        Catfish::db('tie')
                            ->where('id', $id)
                            ->update([
                                'zan' => Catfish::dbRaw('zan+1')
                            ]);
                        Catfish::db('tie_zan')->insert([
                            'tid' => $id,
                            'uid' => $uid,
                            'accesstime' => Catfish::now()
                        ]);
                        Catfish::dbCommit();
                        $data = $this->createData('post', $id, [
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
            if($tie['uid'] == $uid){
                $err = $this->createError('733', 'Can not dislike yourself', 'Operation not allowed');
                $this->addError($err);
            }
            else{
                $hasrec = Catfish::db('tie_cai')->where('tid', $id)->where('uid', $uid)->field('id')->find();
                if(empty($hasrec)){
                    Catfish::dbStartTrans();
                    try{
                        Catfish::db('tie')
                            ->where('id', $id)
                            ->update([
                                'cai' => Catfish::dbRaw('cai+1')
                            ]);
                        Catfish::db('tie_cai')->insert([
                            'tid' => $id,
                            'uid' => $uid,
                            'accesstime' => Catfish::now()
                        ]);
                        Catfish::dbCommit();
                        $data = $this->createData('post', $id, [
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
        elseif($param['method'] == 'keep'){
            if($tie['uid'] == $uid){
                $err = $this->createError('735', 'Can not collect yourself', 'Operation not allowed');
                $this->addError($err);
            }
            else{
                $hasrec = Catfish::db('tie_favorites')->where('tid', $id)->where('uid', $uid)->field('id')->find();
                if(empty($hasrec)){
                    $now = Catfish::now();
                    Catfish::dbStartTrans();
                    try{
                        Catfish::db('tie_favorites')->insert([
                            'uid' => $uid,
                            'tid' => $id,
                            'createtime' => $now
                        ]);
                        Catfish::db('tie')
                            ->where('id', $id)
                            ->update([
                                'shoucang' => Catfish::dbRaw('shoucang+1'),
                                'cangtime' => $now
                            ]);
                        Catfish::dbCommit();
                        $data = $this->createData('post', $id, [
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
                    $err = $this->createError('736', 'Already collected', 'Operation not allowed');
                    $this->addError($err);
                }
            }
        }
        else{
            $err = $this->createError('730', 'Operation instructions are not clear', 'Operation not allowed');
            $this->addError($err);
        }
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