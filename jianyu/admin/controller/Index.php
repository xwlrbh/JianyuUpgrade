<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://jianyuluntan.com All rights reserved.
 */
namespace app\admin\controller;
use catfishcms\Catfish;
class Index extends CatfishCMS
{
    public function index()
    {
        $this->checkUser();
        $zhutie = 0;
        $gentie = 0;
        $msort = Catfish::db('msort')
            ->field('id,zhutie,gentie')
            ->select();
        foreach($msort as $val){
            $zhutie += $val['zhutie'];
            $gentie += $val['gentie'];
        }
        Catfish::allot('zhutie', $zhutie);
        Catfish::allot('gentie', $gentie);
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
        Catfish::allot('jintian', $jintian);
        $ltie = Catfish::db('tie')->field('id,fabushijian,biaoti')->order('id desc')->limit(30)->select();
        foreach($ltie as $key => $val){
            $ltie[$key]['href'] = Catfish::url('index/Index/post', ['find' => $val['id']]);
        }
        Catfish::allot('tie', $ltie);
        $lgentie = Catfish::db('tie_comments')->field('createtime,content')->order('id desc')->limit(30)->select();
        Catfish::allot('lgentie', $lgentie);
        $conf = Catfish::getConfig('jianyu');
        Catfish::allot('xitong', $conf);
        Catfish::allot('users', Catfish::get('users'));
        Catfish::allot('jianyuver', Catfish::getConfig('jianyu.version'));
        return $this->show(Catfish::lang('Welcome'));
    }
    public function latestv()
    {
        if(Catfish::isPost(5)){
            return $this->bbnp();
        }
        return '';
    }
    public function mainpost()
    {
        $this->checkUser();
        $guanjianzi = Catfish::getGet('guanjianzi');
        if($guanjianzi === false){
            $guanjianzi = '';
        }
        $yonghuming = Catfish::getGet('yonghuming');
        if($yonghuming === false){
            $yonghuming = '';
        }
        $query = [];
        $catfish = Catfish::view('tie','id,fabushijian,biaoti,review,yuedu,fstop,fsrecommended,jingpin,tietype,annex')
            ->view('users','yonghu,nicheng,touxiang','users.id=tie.uid');
        if($guanjianzi != ''){
            $catfish = $catfish->where('tie.biaoti','like','%'.$guanjianzi.'%');
            $query['guanjianzi'] = $guanjianzi;
        }
        if($yonghuming != ''){
            $catfish = $catfish->where('users.yonghu','=',$yonghuming);
            $query['yonghuming'] = $yonghuming;
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
        }
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Main post'), 5, 'mainpost');
    }
    public function manamainpost()
    {
        if(Catfish::isPost(5)){
            $chkarr = ['review', 'fstop', 'fsrecommended', 'jingpin'];
            $id = intval(Catfish::getPost('id'));
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
                    if($opt == 'fstop'){
                        if($chk == 1){
                            Catfish::db('tie_fstop')->insert([
                                'tid' => $id
                            ]);
                        }
                        elseif($chk == 0){
                            Catfish::db('tie_fstop')
                                ->where('tid',$id)
                                ->delete();
                        }
                    }
                    if($opt == 'fsrecommended'){
                        if($chk == 1){
                            Catfish::db('tie_fstuijian')->insert([
                                'tid' => $id
                            ]);
                        }
                        elseif($chk == 0){
                            Catfish::db('tie_fstuijian')
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
                Catfish::clearCache('shouye_zhiding_tuijian');
                Catfish::clearCache('shouye');
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
    public function delmainpost()
    {
        if(Catfish::isPost(5)){
            $id = Catfish::getPost('id');
            $tmp = Catfish::db('tie')->where('id',$id)->field('uid,sid,fabushijian,tietype')->find();
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
    public function followpost()
    {
        $this->checkUser();
        $guanjianzi = Catfish::getGet('guanjianzi');
        if($guanjianzi === false){
            $guanjianzi = '';
        }
        $yonghuming = Catfish::getGet('yonghuming');
        if($yonghuming === false){
            $yonghuming = '';
        }
        $query = [];
        $catfish = Catfish::view('tie_comments','id,uid,createtime,status,content')
            ->view('tie_comm_ontact','tid','tie_comm_ontact.cid=tie_comments.id', 'LEFT')
            ->view('users','yonghu,nicheng','users.id=tie_comments.uid', 'LEFT');
        if($guanjianzi != ''){
            $catfish = $catfish->where('tie_comments.content','like','%'.$guanjianzi.'%');
            $query['guanjianzi'] = $guanjianzi;
        }
        if($yonghuming != ''){
            $catfish = $catfish->where('users.yonghu','=',$yonghuming);
            $query['yonghuming'] = $yonghuming;
        }
        $catfish = $catfish->order('tie_comments.xiugai desc')
            ->paginate(20,false,[
                'query' => $query
            ]);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Follow post'), 5, 'followpost');
    }
    public function manafollowpost()
    {
        if(Catfish::isPost(5)){
            $chkarr = ['status'];
            $id = intval(Catfish::getPost('id'));
            $tid = intval(Catfish::getPost('tid'));
            $nicheng = Catfish::getPost('nicheng');
            $content = Catfish::getPost('content');
            $content = str_replace(['&lt;', '&gt;'], ['<', '>'], $content);
            $createtime = Catfish::getPost('createtime');
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
        if(Catfish::isPost(5)){
            $cid = intval(Catfish::getPost('id'));
            $mtie = Catfish::db('tie_comm_ontact')->where('cid',$cid)->field('tid,uid')->find();
            $tid = $mtie['tid'];
            $getsort = Catfish::db('tie_comments')->where('id', $cid)->field('sid,createtime')->find();
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
                    ->where('id', $getsort['sid'])
                    ->update([
                        'gentie' => Catfish::dbRaw('gentie-1')
                    ]);
                Catfish::db('tongji')
                    ->where('riqi', date("Y-m-d", strtotime($getsort['createtime'])))
                    ->update([
                        'gentie' => Catfish::dbRaw('gentie-1')
                    ]);
                Catfish::db('users')
                    ->where('id', $mtie['uid'])
                    ->update([
                        'pinglun' => Catfish::dbRaw('pinglun-1')
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
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function newclassification()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $data = $this->newclassificationPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                if(strpos($data['sname'], ',') !== false){
                    echo Catfish::lang('The name of the section cannot contain a comma');
                    exit();
                }
                $ismodule = Catfish::getPost('ismodule') == 'on' ? 1 : 0;
                $islink = Catfish::getPost('islink') == 'on' ? 1 : 0;
                $linkurl = trim(Catfish::getPost('linkurl'));
                if($islink == 1){
                    if(empty($linkurl)){
                        echo Catfish::lang('Link address must be filled');
                        exit();
                    }
                    elseif(substr($linkurl, 0, 4) != 'http'){
                        echo Catfish::lang('Link address format error');
                        exit();
                    }
                    $ismodule = 0;
                }
                $image = '';
                $file = request()->file('image');
                if($file){
                    $validate = [
                        'ext' => 'jpg,png,gif,jpeg'
                    ];
                    $info = $file->validate($validate)->move(ROOT_PATH . 'data' . DS . 'uploads');
                    if($info){
                        $image = 'data/uploads/'.str_replace('\\','/',$info->getSaveName());
                    }else{
                        echo Catfish::lang('Section image upload failed') . ': ' . $file->getError();
                        exit();
                    }
                }
                $ismenu = Catfish::getPost('ismenu') == 'on' ? 1 : 0;
                $subclasses = Catfish::getPost('subclasses') == 'on' ? 1 : 0;
                $virtual = Catfish::getPost('virtual') == 'on' ? 1 : 0;
                $re = Catfish::db('msort')->insert([
                    'sname' => $data['sname'],
                    'bieming' => Catfish::getPost('bieming'),
                    'guanjianzi' => str_replace('，', ', ', Catfish::getPost('guanjianzi')),
                    'description' => Catfish::getPost('description'),
                    'ismenu' => $ismenu,
                    'virtual' => $virtual,
                    'icon' => Catfish::getPost('icon', false),
                    'image' => $image,
                    'islink' => $islink,
                    'linkurl' => $linkurl,
                    'ismodule' => $ismodule,
                    'subclasses' => $subclasses,
                    'parentid' => Catfish::getPost('parentid')
                ]);
                if($re == 1){
                    Catfish::clearCache('fenlei_id_name');
                    Catfish::clearCache('sortcache');
                    Catfish::removeCache('sort_id_sname_virtual_parentid');
                    echo 'ok';
                }
                else{
                    echo Catfish::lang('Failure to submit');
                }
                exit();
            }
        }
        Catfish::allot('fenlei', Catfish::getSort('msort', 'id,sname,parentid', '&nbsp;&nbsp;&nbsp;&nbsp;', ['islink', 0]));
        return $this->show(Catfish::lang('New section'), 3, 'newclassification', '', true);
    }
    public function createdclassification()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $this->order('msort');
            Catfish::clearCache('caidan');
            echo 'ok';
            exit();
        }
        $fenlei = Catfish::getSort('msort','id,sname,bieming,ismenu,islink,linkurl,ismodule,parentid,listorder','&#12288;', '', 'listorder asc');
        Catfish::allot('fenlei', $fenlei);
        return $this->show(Catfish::lang('Existing section'), 3, 'createdclassification');
    }
    public function manamm()
    {
        if(Catfish::isPost(3)){
            $chkarr = ['ismenu', 'ismodule'];
            $id = intval(Catfish::getPost('id'));
            $chk = intval(Catfish::getPost('chk'));
            if($chk > 1){
                $chk = 1;
            }
            $opt = Catfish::getPost('opt');
            if(in_array($opt, $chkarr)){
                Catfish::db('msort')->where('id',$id)->update([
                    $opt => $chk
                ]);
                Catfish::clearCache('caidan');
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
    public function delclassification()
    {
        if(Catfish::isPost(3)){
            $id = Catfish::getPost('id');
            $re = Catfish::db('tie')->where('sid',$id)->find();
            if(!empty($re)){
                echo Catfish::lang('This section is not empty and cannot be deleted. Please transfer the section first or clear the section before proceeding');
                exit();
            }
            $re = Catfish::db('msort')->where('id',$id)->field('parentid')->find();
            Catfish::dbStartTrans();
            try{
                Catfish::db('msort')
                    ->where('parentid', $id)
                    ->update(['parentid' => $re['parentid']]);
                Catfish::db('msort')
                    ->where('id',$id)
                    ->delete();
                Catfish::dbCommit();
            } catch (\Exception $e) {
                Catfish::dbRollback();
                echo Catfish::lang('The operation failed, please try again later');
                exit();
            }
            Catfish::clearCache('fenlei_id_name');
            Catfish::clearCache('sortcache');
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function modifyclassification()
    {
        $this->checkUser();
        if(Catfish::hasPost('c')){
            $sid = Catfish::getGet('c');
        }
        elseif(Catfish::hasGet('c')){
            $sid = Catfish::getGet('c');
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
        if(Catfish::isPost(3)){
            $data = $this->newclassificationPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $ismodule = Catfish::getPost('ismodule') == 'on' ? 1 : 0;
                $islink = Catfish::getPost('islink') == 'on' ? 1 : 0;
                $linkurl = trim(Catfish::getPost('linkurl'));
                if($islink == 1){
                    if(empty($linkurl)){
                        echo Catfish::lang('Link address must be filled');
                        exit();
                    }
                    elseif(substr($linkurl, 0, 4) != 'http'){
                        echo Catfish::lang('Link address format error');
                        exit();
                    }
                    $hasplink = Catfish::db('msort')->where('parentid',$sid)->field('id')->find();
                    if(!empty($hasplink)){
                        echo Catfish::lang('Can\'t change already used section to link');
                        exit();
                    }
                    $hastie = Catfish::db('tie')->where('sid',$sid)->field('id')->find();
                    if(!empty($hastie)){
                        echo Catfish::lang('Can\'t change already used section to link');
                        exit();
                    }
                    $ismodule = 0;
                }
                else{
                    $linkurl = '';
                }
                $oimage = Catfish::db('msort')->where('id',$sid)->field('image')->find();
                $oimage = $oimage['image'];
                $image = '';
                $file = request()->file('image');
                if($file){
                    $validate = [
                        'ext' => 'jpg,png,gif,jpeg'
                    ];
                    $info = $file->validate($validate)->move(ROOT_PATH . 'data' . DS . 'uploads');
                    if($info){
                        $image = 'data/uploads/'.str_replace('\\','/',$info->getSaveName());
                    }else{
                        echo Catfish::lang('Section image upload failed') . ': ' . $file->getError();
                        exit();
                    }
                }
                if(!empty($image)){
                    if($image != $oimage){
                        if(!empty($oimage) && Catfish::isDataPath($oimage)){
                            @unlink(ROOT_PATH . str_replace('/', DS, $oimage));
                        }
                    }
                }
                else{
                    $image = $oimage;
                }
                $ismenu = Catfish::getPost('ismenu') == 'on' ? 1 : 0;
                $subclasses = Catfish::getPost('subclasses') == 'on' ? 1 : 0;
                $virtual = Catfish::getPost('virtual') == 'on' ? 1 : 0;
                $re = Catfish::db('msort')->where('id', $sid)->update([
                    'sname' => $data['sname'],
                    'bieming' => Catfish::getPost('bieming'),
                    'guanjianzi' => str_replace('，', ',', Catfish::getPost('guanjianzi')),
                    'description' => Catfish::getPost('description'),
                    'ismenu' => $ismenu,
                    'virtual' => $virtual,
                    'icon' => Catfish::getPost('icon', false),
                    'image' => $image,
                    'islink' => $islink,
                    'linkurl' => $linkurl,
                    'ismodule' => $ismodule,
                    'subclasses' => $subclasses,
                    'parentid' => Catfish::getPost('parentid')
                ]);
                if($re == 1){
                    Catfish::clearCache('fenlei_id_name');
                    Catfish::clearCache('sortcache');
                    Catfish::removeCache('sort_id_sname_virtual_parentid');
                    echo 'ok';
                }
                else{
                    echo Catfish::lang('Failure to submit');
                }
                exit();
            }
        }
        $re = Catfish::db('msort')->where('id',$sid)->find();
        Catfish::allot('sort', $re);
        Catfish::allot('fenlei', Catfish::getSortNoSelf('msort', $sid, 'id,sname,parentid', '&nbsp;&nbsp;&nbsp;&nbsp;', ['islink', 0]));
        return $this->show(Catfish::lang('Modify section'), 3, 'createdclassification', '', true);
    }
    public function transferclassification()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $data = $this->transferclassificationPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                if($data['osid'] == 0){
                    echo Catfish::lang('Transfer out section must be selected');
                    exit();
                }
                if($data['nsid'] == 0){
                    echo Catfish::lang('Transfer to the section must be selected');
                    exit();
                }
                if($data['osid'] == $data['nsid']){
                    echo Catfish::lang('The transferred section cannot be the same as the transferred section');
                    exit();
                }
                $osidtj = Catfish::db('msort')->where('id', $data['osid'])->field('zhutie,gentie,tjoriginal,tjreprint')->find();
                Catfish::dbStartTrans();
                try{
                    Catfish::db('tie')
                        ->where('sid', $data['osid'])
                        ->update(['sid' => $data['nsid']]);
                    Catfish::db('tie_comments')
                        ->where('sid', $data['osid'])
                        ->update(['sid' => $data['nsid']]);
                    Catfish::db('tie_top')
                        ->where('sid', $data['osid'])
                        ->update(['sid' => $data['nsid']]);
                    Catfish::db('tie_tuijian')
                        ->where('sid', $data['osid'])
                        ->update(['sid' => $data['nsid']]);
                    Catfish::db('msort')
                        ->where('id', $data['nsid'])
                        ->update([
                            'zhutie' => Catfish::dbRaw('zhutie+'.$osidtj['zhutie']),
                            'gentie' => Catfish::dbRaw('gentie+'.$osidtj['gentie']),
                            'tjoriginal' => Catfish::dbRaw('tjoriginal+'.$osidtj['tjoriginal']),
                            'tjreprint' => Catfish::dbRaw('tjreprint+'.$osidtj['tjreprint'])
                        ]);
                    Catfish::db('msort')
                        ->where('id', $data['osid'])
                        ->update([
                            'zhutie' => Catfish::dbRaw('zhutie-'.$osidtj['zhutie']),
                            'gentie' => Catfish::dbRaw('gentie-'.$osidtj['gentie']),
                            'tjoriginal' => Catfish::dbRaw('tjoriginal-'.$osidtj['tjoriginal']),
                            'tjreprint' => Catfish::dbRaw('tjreprint-'.$osidtj['tjreprint'])
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
        $fenlei = Catfish::getSort('msort', 'id,sname,parentid', '&nbsp;&nbsp;&nbsp;&nbsp;', ['islink', 0]);
        Catfish::allot('fenlei', $fenlei);
        return $this->show(Catfish::lang('Transfer section'), 3, 'transferclassification');
    }
    public function generaluser()
    {
        $this->checkUser();
        $utp = intval(Catfish::getSession('user_type'));
        $yonghuming = Catfish::getGet('yonghuming');
        if($yonghuming === false){
            $yonghuming = '';
        }
        $query = [];
        $catfish = Catfish::db('users')
            ->where('id', '>', 1)
            ->where('utype', '>', $utp);
        if($yonghuming != ''){
            $catfish = $catfish->where('yonghu','=',$yonghuming);
            $query['yonghuming'] = $yonghuming;
        }
        $catfish = $catfish->field('id,yonghu,nicheng,email,shouji,touxiang,qianming,status,utype,mtype')
            ->order('id desc')
            ->paginate(20,false,[
                'query' => $query
            ]);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('dengji', Catfish::getSession('user_type'));
        return $this->show(Catfish::lang('All users'), 5, 'generaluser');
    }
    public function clearcache()
    {
        $this->checkUser();
        if(Catfish::isPost(5)){
            try{
                Catfish::clearCache();
                echo 'ok';
            } catch (\Exception $e) {
                echo Catfish::lang('The operation failed, please try again later');
            }
            exit();
        }
        return $this->show(Catfish::lang('Clear cache'), 5, 'clearcache');
    }
    public function manauser()
    {
        if(Catfish::isPost(3)){
            $chkarr = ['status'];
            $id = intval(Catfish::getPost('id'));
            $chk = intval(Catfish::getPost('chk'));
            if($chk > 1){
                $chk = 1;
            }
            $opt = Catfish::getPost('opt');
            if(in_array($opt, $chkarr)){
                $utp = intval(Catfish::getSession('user_type'));
                $reu = Catfish::db('users')->where('id',$id)->field('utype')->find();
                if($utp >= intval($reu['utype'])){
                    echo Catfish::lang('Your operation is illegal');
                    exit();
                }
                else{
                    Catfish::db('users')->where('id',$id)->update([
                        $opt => $chk
                    ]);
                    echo 'ok';
                }
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
    public function setgroups()
    {
        if(Catfish::isPost(3)){
            $utp = intval(Catfish::getSession('user_type'));
            $totp = intval(Catfish::getPost('gup'));
            $id = intval(Catfish::getPost('id'));
            if($utp >= $totp){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            else{
                $reu = Catfish::db('users')->where('id',$id)->field('utype')->find();
                if($utp >= intval($reu['utype'])){
                    echo Catfish::lang('Your operation is illegal');
                    exit();
                }
                else{
                    Catfish::db('users')->where('id',$id)->update([
                        'utype' => $totp
                    ]);
                    echo 'ok';
                    exit();
                }
            }
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function setbzgroups()
    {
        if(Catfish::isPost(5)){
            $totp = intval(Catfish::getPost('gup'));
            $id = intval(Catfish::getPost('id'));
            Catfish::db('users')->where('id',$id)->update([
                'mtype' => $totp
            ]);
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function moderator()
    {
        $this->checkUser();
        $catfish = Catfish::db('users')
            ->where('id', '>', 1)
            ->where('mtype', '>', 0)
            ->field('id,yonghu,nicheng,email,shouji,touxiang,qianming,status,utype,mtype')
            ->order('id desc')
            ->paginate(20);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        $idstr = '';
        $uidarr = [];
        foreach($catfish as $key => $val){
            $idstr .= empty($idstr) ? $val['id'] : ',' . $val['id'];
            $uidarr[$val['id']] = '';
        }
        $modsec = Catfish::view('mod_sec_ontact','uid,mtype')
            ->view('msort','sname','msort.id=mod_sec_ontact.sid')
            ->where('mod_sec_ontact.uid','in',$idstr)
            ->select();
        foreach($modsec as $key => $val){
            $bkm = '';
            switch($val['mtype']){
                case 5:
                    $bkm = Catfish::lang('Intern moderator');
                    break;
                case 10:
                    $bkm = Catfish::lang('Secondary moderator');
                    break;
                case 15:
                    $bkm = Catfish::lang('Moderator');
                    break;
            }
            $uidarr[$val['uid']] .= empty($uidarr[$val['uid']]) ? $val['sname'].'&nbsp;[&nbsp;'.$bkm.'&nbsp;]' : ',&nbsp;' . $val['sname'].'&nbsp;[&nbsp;'.$bkm.'&nbsp;]';
        }
        foreach($catfish as $key => $val){
            $catfish[$key]['bankuai'] = $uidarr[$val['id']];
        }
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('dengji', Catfish::getSession('user_type'));
        $fenlei = Catfish::getSort('msort', 'id,sname,parentid', '&nbsp;&nbsp;&nbsp;&nbsp;', ['islink', 0]);
        Catfish::allot('fenlei', $fenlei);
        return $this->show(Catfish::lang('Moderator'), 5, 'moderator');
    }
    public function setmoderator()
    {
        if(Catfish::isPost(5)){
            $uid = intval(Catfish::getPost('uid'));
            $sid = Catfish::getPost('sid');
            $reu = Catfish::db('users')->where('id',$uid)->field('mtype')->find();
            if($reu['mtype'] == 0){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            $sarr = explode(',', $sid);
            $data = [];
            if(is_array($sarr) && count($sarr) > 0){
                foreach($sarr as $key => $val){
                    if(strpos($val,':') !== false){
                        $sval = explode(':', $val);
                        $data[] = [
                            'sid' => intval($sval[0]),
                            'uid' => $uid,
                            'mtype' => intval($sval[1])
                        ];
                    }
                }
            }
            Catfish::dbStartTrans();
            try{
                Catfish::db('mod_sec_ontact')
                    ->where('uid', $uid)
                    ->delete();
                if(count($data) > 0){
                    Catfish::db('mod_sec_ontact')->insertAll($data);
                }
                Catfish::dbCommit();
            } catch (\Exception $e) {
                Catfish::dbRollback();
                echo Catfish::lang('The operation failed, please try again later');
                exit();
            }
            Catfish::removeCache('moderator_'.$uid);
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function administrator()
    {
        $this->checkUser();
        $utp = intval(Catfish::getSession('user_type'));
        $catfish = Catfish::db('users')
            ->where('id', '>', 1)
            ->where('utype',['>',$utp],['<',6])
            ->field('id,yonghu,nicheng,email,shouji,touxiang,qianming,status,utype')
            ->order('id desc')
            ->paginate(20);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('dengji', Catfish::getSession('user_type'));
        return $this->show(Catfish::lang('Administrator'), 3, 'administrator');
    }
    public function manaadmin()
    {
        if(Catfish::isPost(1)){
            $chkarr = ['status'];
            $id = intval(Catfish::getPost('id'));
            $chk = intval(Catfish::getPost('chk'));
            if($chk > 1){
                $chk = 1;
            }
            $opt = Catfish::getPost('opt');
            if(in_array($opt, $chkarr)){
                $utp = intval(Catfish::getSession('user_type'));
                $reu = Catfish::db('users')->where('id',$id)->field('utype')->find();
                if($utp >= intval($reu['utype'])){
                    echo Catfish::lang('Your operation is illegal');
                    exit();
                }
                else{
                    Catfish::db('users')->where('id',$id)->update([
                        $opt => $chk
                    ]);
                    echo 'ok';
                }
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
    public function setadmingroups()
    {
        if(Catfish::isPost(1)){
            $utp = intval(Catfish::getSession('user_type'));
            $totp = intval(Catfish::getPost('gup'));
            $id = intval(Catfish::getPost('id'));
            if($utp >= $totp){
                echo Catfish::lang('Your operation is illegal');
                exit();
            }
            else{
                $reu = Catfish::db('users')->where('id',$id)->field('utype')->find();
                if($utp >= intval($reu['utype'])){
                    echo Catfish::lang('Your operation is illegal');
                    exit();
                }
                else{
                    Catfish::db('users')->where('id',$id)->update([
                        'utype' => $totp
                    ]);
                    echo 'ok';
                    exit();
                }
            }
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function websitesettings()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $data = $this->websitesettingsPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $captcha = Catfish::getPost('captcha') == 'on' ? 1 : 0;
                $rewrite = Catfish::getPost('rewrite') == 'on' ? 1 : 0;
                $regvery = Catfish::getPost('regvery') == 'on' ? 1 : 0;
                $spareoption = Catfish::db('options')->where('option_name','spare')->field('option_value')->find();
                $spare = $spareoption['option_value'];
                if(empty($spare)){
                    $spare = [];
                }
                else{
                    $spare = unserialize($spare);
                }
                $spare['notfollow'] = Catfish::getPost('notfollow') == 'on' ? 1 : 0;
                Catfish::dbStartTrans();
                try{
                    Catfish::db('options')
                        ->where('option_name', 'title')
                        ->update([
                            'option_value' => $data['title']
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'subtitle')
                        ->update([
                            'option_value' => Catfish::getPost('subtitle')
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'keyword')
                        ->update([
                            'option_value' => Catfish::getPost('keyword')
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'description')
                        ->update([
                            'option_value' => Catfish::getPost('description')
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'record')
                        ->update([
                            'option_value' => Catfish::getPost('record', false)
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'serial')
                        ->update([
                            'option_value' => Catfish::getPost('serial')
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'statistics')
                        ->update([
                            'option_value' => serialize(Catfish::getPost('statistics', false, false))
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'domain')
                        ->update([
                            'option_value' => $data['domain']
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'logo')
                        ->update([
                            'option_value' => Catfish::getPost('logo')
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'captcha')
                        ->update([
                            'option_value' => $captcha
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'rewrite')
                        ->update([
                            'option_value' => $rewrite
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'icon')
                        ->update([
                            'option_value' => Catfish::getPost('icon')
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'filtername')
                        ->update([
                            'option_value' => Catfish::getPost('filtername')
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'regvery')
                        ->update([
                            'option_value' => $regvery
                        ]);
                    Catfish::db('options')
                        ->where('option_name', 'spare')
                        ->update([
                            'option_value' => serialize($spare),
                            'autoload' => 1
                        ]);
                    Catfish::dbCommit();
                } catch (\Exception $e) {
                    Catfish::dbRollback();
                    echo Catfish::lang('The operation failed, please try again later');
                    exit();
                }
                Catfish::removeCache('options');
                Catfish::removeCache('jianyu_options_regvery');
                echo 'ok';
                exit();
            }
        }
        $jianyu = Catfish::db('options')->where('id','<',24)->field('option_name,option_value')->select();
        $jianyuItem = [];
        foreach($jianyu as $key => $val){
            if($val['option_name'] == 'statistics'){
                $jianyuItem[$val['option_name']] = unserialize($val['option_value']);
            }
            elseif($val['option_name'] == 'record'){
                $jianyuItem[$val['option_name']] = str_replace('"', '\'', $val['option_value']);
            }
            elseif($val['option_name'] == 'spare'){
                if(!empty($val['option_value'])){
                    $sval = unserialize($val['option_value']);
                    foreach($sval as $spkey => $spval){
                        $jianyuItem[$spkey] = $spval;
                    }
                }
            }
            else{
                $jianyuItem[$val['option_name']] = $val['option_value'];
            }
        }
        Catfish::allot('jianyuItem', $jianyuItem);
        return $this->show(Catfish::lang('Website settings'), 3, 'websitesettings', '', true);
    }
    public function forumsettings()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $geshi = Catfish::getPost('geshi');
            $geshi = Catfish::toComma($geshi, true);
            $kzleixing = '';
            if(Catfish::hasPost('kzleixing')){
                $kzleixing = Catfish::getPost('kzleixing');
            }
            Catfish::db('forum')
                ->where('id', 1)
                ->update([
                    'fujian' => Catfish::getPost('fujian'),
                    'fujiandj' => Catfish::getPost('fujiandj'),
                    'fujiandwn' => Catfish::getPost('fujiandwn'),
                    'tiezi' => Catfish::getPost('tiezi'),
                    'tupian' => Catfish::getPost('tupian'),
                    'tupiandj' => Catfish::getPost('tupiandj'),
                    'lianjie' => Catfish::getPost('lianjie'),
                    'lianjiedj' => Catfish::getPost('lianjiedj'),
                    'yanzhengzt' => $this->bound(intval(Catfish::getPost('yanzhengzt')), 0),
                    'yanzhenggt' => $this->bound(intval(Catfish::getPost('yanzhenggt')), 0),
                    'shichangzt' => Catfish::getPost('shichangzt'),
                    'shichanggt' => Catfish::getPost('shichanggt'),
                    'geshi' => $geshi,
                    'mingan' => Catfish::getPost('mingan'),
                    'preaudit' => Catfish::getPost('preaudit'),
                    'fpreaudit' => Catfish::getPost('fpreaudit'),
                    'jifen' => Catfish::getPost('jifen'),
                    'jifendj' => Catfish::getPost('jifendj'),
                    'kzleixing' => $kzleixing
                ]);
            $tietype = Catfish::db('tietype')->where('id',3)->field('id')->find();
            if(empty($tietype) && $kzleixing != ''){
                $bieming = 'kz' . strtolower(substr(md5(time()), 0, 6));
                Catfish::db('tietype')->insert([
                    'id' => 3,
                    'tpname' => $kzleixing,
                    'bieming' => $bieming
                ]);
                Catfish::dbExecute("ALTER TABLE `".Catfish::prefix()."users_tongji` ADD `tj{$bieming}` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0';");
                Catfish::dbExecute("ALTER TABLE `".Catfish::prefix()."msort` ADD `tj{$bieming}` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0';");
            }
            else{
                Catfish::db('tietype')->where('id',3)->update([
                    'tpname' => $kzleixing
                ]);
            }
            Catfish::removeCache('tie_type');
            Catfish::removeCache('forumsettings');
            echo 'ok';
            exit();
        }
        $forum = Catfish::db('forum')->where('id',1)->field('fujian,fujiandj,fujiandwn,tiezi,tupian,tupiandj,lianjie,lianjiedj,yanzhengzt,yanzhenggt,shichangzt,shichanggt,geshi,mingan,preaudit,fpreaudit,jifen,jifendj,kzleixing')->find();
        Catfish::allot('forum', $forum);
        $extend = Catfish::iszero(Catfish::remind()) ? 0 : 1;
        Catfish::allot('extend', $extend);
        $dengji = Catfish::db('dengji')->field('id,jibie,djname')->order('jibie asc')->select();
        foreach($dengji as $key => $val){
            if(!empty($val['djname'])){
                $dengji[$key]['djname'] = Catfish::lang($val['djname']);
            }
        }
        Catfish::allot('dengji', $dengji);
        return $this->show(Catfish::lang('Forum settings'), 3, 'forumsettings');
    }
    public function levelsetting()
    {
        $this->checkUser();
        $dengji = Catfish::db('dengji')->field('id,jibie,djname,chengzhang')->order('jibie asc')->select();
        foreach($dengji as $key => $val){
            if(!empty($val['djname'])){
                $dengji[$key]['djname'] = Catfish::lang($val['djname']);
            }
        }
        Catfish::allot('dengji', $dengji);
        return $this->show(Catfish::lang('Level setting'), 3, 'levelsetting');
    }
    public function increaselevel()
    {
        if(Catfish::isPost(3)){
            Catfish::db('dengji')->insert([
                'jibie' => intval(Catfish::getPost('xh'))
            ]);
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function reducelevel()
    {
        if(Catfish::isPost(3)){
            Catfish::db('dengji')->where('jibie', intval(Catfish::getPost('xh')))->delete();
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function djmiaoshu()
    {
        if(Catfish::isPost(3)){
            Catfish::db('dengji')->where('jibie', intval(Catfish::getPost('xh')))->update([
                'djname' => Catfish::getPost('zhi')
            ]);
            Catfish::removeCache('dengji_id_name');
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function djzhi()
    {
        if(Catfish::isPost(3)){
            Catfish::db('dengji')->where('jibie', intval(Catfish::getPost('xh')))->update([
                'chengzhang' => intval(Catfish::getPost('zhi'))
            ]);
            Catfish::removeCache('dengji_jibie_chengzhang');
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function growthsetting()
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
        $growth = Catfish::db('chengzhang')->field('id,czname,chengzhang,jifen')->select();
        $xh = 1;
        foreach($growth as $key => $val){
            $growth[$key]['xh'] = $xh++;
            $growth[$key]['czname'] = $mj[$val['czname']];
        }
        Catfish::allot('growth', $growth);
        return $this->show(Catfish::lang('Growth setting'), 3, 'growthsetting');
    }
    public function czzhi()
    {
        if(Catfish::isPost(3)){
            Catfish::db('chengzhang')->where('id', intval(Catfish::getPost('id')))->update([
                'chengzhang' => intval(Catfish::getPost('zhi'))
            ]);
            Catfish::removeCache('growingup');
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function jfzhi()
    {
        if(Catfish::isPost(3)){
            Catfish::db('chengzhang')->where('id', intval(Catfish::getPost('id')))->update([
                'jifen' => intval(Catfish::getPost('zhi'))
            ]);
            Catfish::removeCache('growingup');
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function uploadimage()
    {
        if(Catfish::isPost(3)){
            $file = request()->file('file');
            $validate = [
                'ext' => 'jpg,png,gif,jpeg'
            ];
            $info = $file->validate($validate)->move(ROOT_PATH . 'data' . DS . 'uploads');
            if($info){
                echo 'data/uploads/'.str_replace('\\','/',$info->getSaveName());
            }else{
                echo $file->getError();
            }
        }
        exit();
    }
    public function uploadIco()
    {
        if(Catfish::isPost(3)){
            $file = request()->file('file');
            $validate = [
                'ext' => 'ico'
            ];
            $info = $file->validate($validate)->move(ROOT_PATH . 'data' . DS . 'uploads');
            if($info){
                echo 'data/uploads/'.str_replace('\\','/',$info->getSaveName());
            }else{
                echo $file->getError();
            }
        }
        exit();
    }
    public function themeswitching()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $template = Catfish::getPost('theme');
            Catfish::set('template', $template);
            Catfish::removeCache('options');
            $params = [
                'original' => $this->template,
                'target' => $template
            ];
            $this->themeHook('closeTheme', $params, $this->template);
            $params = [
                'original' => $this->template,
                'target' => $template
            ];
            $this->themeHook('openTheme', $params, $template);
            echo 'ok';
            exit();
        }
        $current = Catfish::get('template');
        $jianyuThemes = [];
        $domain = Catfish::domain();
        $dir = glob(ROOT_PATH.'public/theme/*',GLOB_ONLYDIR);
        foreach($dir as $key => $val){
            $tmpdir = basename($val);
            $url = $domain.'public/common/images/screenshot.jpg';
            $path = ROOT_PATH.'public/theme/'.$tmpdir.'/screenshot.jpg';
            if(is_file($path)){
                $url = $domain.'public/theme/'.$tmpdir.'/screenshot.jpg';
            }
            if($tmpdir == $current){
                array_unshift($jianyuThemes,[
                    'name' => $tmpdir,
                    'url' => $url,
                    'open' => 1
                ]);
            }
            else{
                array_push($jianyuThemes,[
                    'name' => $tmpdir,
                    'url' => $url,
                    'open' => 0
                ]);
            }
        }
        Catfish::allot('jianyuThemes', $jianyuThemes);
        return $this->show(Catfish::lang('Theme switching'), 3, 'themeswitching');
    }
    public function themesetting()
    {
        $this->checkUser();
        $langPath = ROOT_PATH.'public/theme/'.$this->template.'/theme/lang/'.Catfish::detectLang().'.php';
        if(is_file($langPath)){
            Catfish::loadLang($langPath);
        }
        if(Catfish::isPost(3,false)){
            $params = Catfish::getPost();
            $this->themeHook('themeSettingPost', $params);
        }
        $params = [
            'template' => $this->template,
            'html' => ''
        ];
        $this->themeHook('themeSetting', $params);
        Catfish::allot('themeSetting', $params['html']);
        return $this->show(Catfish::lang('Theme setting'), 3, 'themesetting');
    }
    public function pluginlist()
    {
        $this->checkUser();
        $prompt = '';
        if(Catfish::isPost(1)){
            $file = request()->file('file');
            if($file->checkExt('zip') === true){
                $tempdatadir = ROOT_PATH . 'data' . DS . 'plugin';
                $this->delFolder($tempdatadir);
                $info = $file->move($tempdatadir, false);
                if($info){
                    $pluginFile = $tempdatadir . DS . $info->getSaveName();
                    $tempdir = ROOT_PATH . 'data' . DS . 'temp' . DS . 'plugin';
                    if(!is_dir($tempdir)){
                        mkdir($tempdir, 0777, true);
                    }
                    $this->delFolder($tempdir);
                    if(is_file($pluginFile)){
                        try{
                            $zip = new \ZipArchive();
                            if($zip->open($pluginFile, \ZipArchive::OVERWRITE || \ZIPARCHIVE::CREATE) === true){
                                $zip->extractTo($tempdir);
                                $zip->close();
                                $this->movePlugin($tempdir);
                            }
                            else{
                                $prompt = Catfish::lang('The uploaded zip file is not available');
                            }
                        }
                        catch(\Exception $e){
                            $prompt = Catfish::lang('Upload failed');
                        }
                        @unlink($pluginFile);
                        $this->delFolder($tempdir);
                    }
                }else{
                    $prompt = $file->getError();
                }
            }
            else{
                $prompt =  Catfish::lang('Please upload the zip file');
            }
        }
        Catfish::allot('prompt', $prompt);
        $data = [];
        $dir = glob(ROOT_PATH.'plugins/*',GLOB_ONLYDIR);
        foreach($dir as $key => $val){
            $pluginBaseName = basename($val);
            $pluginLang = ROOT_PATH.'plugins'.DS.$pluginBaseName.DS.'lang'.DS.Catfish::detectLang().'.php';
            if(is_file($pluginLang)){
                Catfish::loadLang($pluginLang);
            }
            $pluginFile = ROOT_PATH.'plugins'.DS.$pluginBaseName.DS.ucfirst($pluginBaseName).'.php';
            if(!is_file($pluginFile)){
                continue;
            }
            $pluginContent = file_get_contents($pluginFile);
            $pluginName = '';
            if(preg_match("/(插件名|Plugin Name)\s*(：|:)(.*)/i", $pluginContent ,$matches))
            {
                if(isset($matches[3])){
                    $pluginName = trim($matches[3]);
                    if(!empty($pluginName)){
                        $pluginName = Catfish::lang($pluginName);
                    }
                }
            }
            $pluginDesc = '';
            if(preg_match("/(描述|Description)\s*(：|:)(.*)/i", $pluginContent ,$matches))
            {
                if(isset($matches[3])){
                    $pluginDesc = trim($matches[3]);
                    if(!empty($pluginDesc)){
                        $pluginDesc = Catfish::lang($pluginDesc);
                    }
                }
            }
            $pluginAuth = '';
            if(preg_match("/(作者|Author)\s*(：|:)(.*)/i", $pluginContent ,$matches))
            {
                if(isset($matches[3])){
                    $pluginAuth = trim($matches[3]);
                }
            }
            $pluginVers = '';
            if(preg_match("/(版本|Version)\s*(：|:)(.*)/i", $pluginContent ,$matches))
            {
                if(isset($matches[3])){
                    $pluginVers = trim($matches[3]);
                }
            }
            $pluginUri = '';
            if(preg_match("/(插件网址|插件網址|Plugin URI|Plugin URL)\s*(：|:)(.*)/i", $pluginContent ,$matches))
            {
                if(isset($matches[3])){
                    $pluginUri = trim($matches[3]);
                }
            }
            $data[] = [
                'plugin' => $pluginBaseName,
                'name' => $pluginName,
                'description' => $pluginDesc,
                'author' => $pluginAuth,
                'version' => $pluginVers,
                'pluginUrl' => $pluginUri
            ];
        }
        $pluginsOpened = Catfish::get('plugins_opened');
        if(empty($pluginsOpened)){
            $pluginsOpened = [];
        }
        else{
            $pluginsOpened = unserialize($pluginsOpened);
        }
        foreach($data as $dkey => $dval){
            if(in_array($dval['plugin'], $pluginsOpened)){
                $data[$dkey]['open'] = 1;
            }
            else{
                $data[$dkey]['open'] = 0;
            }
        }
        Catfish::allot('jianyuluntan', $data);
        return $this->show(Catfish::lang('Plugin list'), 1, 'pluginlist');
    }
    public function manaplugin()
    {
        if(Catfish::isPost(1)){
            $plugin = trim(Catfish::getPost('plugin'));
            $chk = intval(Catfish::getPost('chk'));
            if($chk > 0){
                $chk = true;
            }
            else{
                $chk = false;
            }
            $this->openClosePlugin($plugin, $chk);
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function delplugin()
    {
        if(Catfish::isPost(1)){
            $plugin = trim(Catfish::getPost('plugin'));
            $pluginPath = ROOT_PATH.'plugins'.DS.$plugin;
            if(is_dir($pluginPath)){
                $this->openClosePlugin($plugin, false);
                $this->deleteFolder($pluginPath);
            }
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
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
        $authority = 100;
        if(empty($theme)){
            $pluginFile = ROOT_PATH.'plugins'.DS.$plugin.DS.ucfirst($ufplugin).'.php';
        }
        else{
            $pluginFile = ROOT_PATH.'public' . DS . 'theme' . DS . $plugin . DS . ucfirst($ufplugin) .'.php';
        }
        if(is_file($pluginFile)){
            $pluginContent = file_get_contents($pluginFile);
            if(preg_match("/(权限|Authority)\s*(：|:)(.*)/i", $pluginContent ,$matches)){
                if(isset($matches[3])){
                    $authorityv = intval(trim($matches[3]));
                    if($authorityv > 0){
                        $authority = $authorityv;
                        if(Catfish::getSession('user_type') > $authorityv){
                            $html = Catfish::lang('You have insufficient permissions');
                        }
                    }
                }
            }
        }
        else{
            $html = Catfish::lang('The plugin file is missing');
        }
        if(empty($html)){
            if(Catfish::isPost($authority)){
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
        }
        Catfish::allot('plugin', $html);
        return $this->show($alias, $authority, $name);
    }
    public function alipay()
    {
        $this->checkUser();
        if(Catfish::isPost(1)){
            $data = $this->alipayPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $method = Catfish::getPost('signaturemethod');
                $applicationpublickeyname = Catfish::getPost('applicationpublickeyname');
                $applicationpublickeypath = Catfish::getPost('applicationpublickeypath');
                $alipaypublickeyname = Catfish::getPost('alipaypublickeyname');
                $alipaypublickeypath = Catfish::getPost('alipaypublickeypath');
                $alipayrootname = Catfish::getPost('alipayrootname');
                $alipayrootpath = Catfish::getPost('alipayrootpath');
                $publickey = Catfish::getPost('alipaypublic', false);
                if($method == 'certificate'){
                    if(empty($applicationpublickeyname) || empty($applicationpublickeypath)){
                        echo Catfish::lang('Application public key certificate must be uploaded');
                        exit();
                    }
                    if(empty($alipaypublickeyname) || empty($alipaypublickeypath)){
                        echo Catfish::lang('Alipay public key certificate must be uploaded');
                        exit();
                    }
                    if(empty($alipayrootname) || empty($alipayrootpath)){
                        echo Catfish::lang('Alipay root certificate must be uploaded');
                        exit();
                    }
                }
                elseif($method == 'publickey'){
                    if(empty($publickey)){
                        echo Catfish::lang('Alipay public key must be filled in');
                        exit();
                    }
                }
                $alipay = [
                    'appid' => $data['appid'],
                    'merchantuid' => $data['merchantuid'],
                    'privatekey' => $data['privatekey'],
                    'signaturemethod' => $method,
                    'apppublickeyname' => $applicationpublickeyname,
                    'apppublickeypath' => $applicationpublickeypath,
                    'alipaypublickeyname' => $alipaypublickeyname,
                    'alipaypublickeypath' => $alipaypublickeypath,
                    'alipayrootname' => $alipayrootname,
                    'alipayrootpath' => $alipayrootpath,
                    'publickey' => $publickey
                ];
                Catfish::set('alipay', serialize($alipay));
                echo 'ok';
                exit();
            }
        }
        $alipay = Catfish::get('alipay');
        if(!empty($alipay)){
            $alipay = unserialize($alipay);
        }
        else{
            $alipay = [
                'appid' => '',
                'merchantuid' => '',
                'privatekey' => '',
                'signaturemethod' => '',
                'apppublickeyname' => '',
                'apppublickeypath' => '',
                'alipaypublickeyname' => '',
                'alipaypublickeypath' => '',
                'alipayrootname' => '',
                'alipayrootpath' => '',
                'publickey' => ''
            ];
        }
        Catfish::allot('alipay', $alipay);
        return $this->show(Catfish::lang('Payment configuration') . ' - ' . Catfish::lang('Alipay'), 1, 'alipay');
    }
    public function uploadcertificate()
    {
        if(Catfish::isPost(1)){
            $file = request()->file(Catfish::getPost('file'));
            if($file){
                $validate = [
                    'ext' => 'crt'
                ];
                $info = $file->validate($validate)->move(ROOT_PATH . 'data' . DS . 'crt', false);
                if($info){
                    $crtname = str_replace('\\','/',$info->getSaveName());
                    $crtpath = 'data/crt/'.$crtname;
                    $result = [
                        'result' => 'ok',
                        'name' => $crtname,
                        'path' =>$crtpath,
                        'message' => ''
                    ];
                    return json($result);
                }else{
                    $result = [
                        'result' => 'error',
                        'message' => Catfish::lang('Certificate upload failed') . ': ' . $file->getError()
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
        $result = [
            'result' => 'error',
            'message' => Catfish::lang('Your operation is illegal')
        ];
        return json($result);
    }
    public function wechat()
    {
        $this->checkUser();
        if(Catfish::isPost(1)){
            $data = $this->wechatPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $wechat = [
                    'appid' => $data['appid'],
                    'merchantuid' => $data['merchantuid'],
                    'privatekey' => $data['privatekey'],
                    'paymethod' => 'NATIVE',
                    'openid' => '',
                    'certname' => '',
                    'certpath' => '',
                    'keyname' => '',
                    'keypath' => ''
                ];
                Catfish::set('wechat', serialize($wechat));
                echo 'ok';
                exit();
            }
        }
        $wechat = Catfish::get('wechat');
        if(!empty($wechat)){
            $wechat = unserialize($wechat);
        }
        else{
            $wechat = [
                'appid' => '',
                'merchantuid' => '',
                'privatekey' => '',
                'paymethod' => 'NATIVE',
                'openid' => '',
                'certname' => '',
                'certpath' => '',
                'keyname' => '',
                'keypath' => ''
            ];
        }
        Catfish::allot('wechat', $wechat);
        return $this->show(Catfish::lang('Payment configuration') . ' - ' . Catfish::lang('WeChat'), 1, 'wechat');
    }
    public function _empty()
    {
        Catfish::toError();
    }
    private function bbnp()
    {
        return Catfish::bbn();
    }
    public function addfriendshiplink()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $data = $this->addfriendshiplinkPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $shouye = 0;
                if(Catfish::getPost('shouye') == 'on'){
                    $shouye = 1;
                }
                Catfish::db('links')->insert([
                    'dizhi' => $data['dizhi'],
                    'mingcheng' => $data['mingcheng'],
                    'tubiao' => Catfish::getPost('tubiao'),
                    'target' => Catfish::getPost('target'),
                    'miaoshu' => Catfish::getPost('miaoshu'),
                    'shouye' => $shouye
                ]);
                echo 'ok';
                exit();
            }
        }
        return $this->show(Catfish::lang('Add a friendship link'), 3, 'addfriendshiplink', '', true);
    }
    public function alllinks()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $this->order('links');
            Catfish::removeCache('youlian');
            echo 'ok';
            exit();
        }
        $youlian = Catfish::db('links')
            ->field('id,dizhi,mingcheng,tubiao,miaoshu,shouye,status,listorder')
            ->order('listorder asc')
            ->select();
        foreach($youlian as $key => $val){
            if(!empty($val['tubiao'])){
                $youlian[$key]['tubiao'] = Catfish::domain() . $val['tubiao'];
            }
        }
        Catfish::allot('youlian', $youlian);
        return $this->show(Catfish::lang('All links'), 3, 'alllinks');
    }
    public function manalink()
    {
        if(Catfish::isPost(3)){
            $chkarr = ['shouye', 'status'];
            $id = intval(Catfish::getPost('id'));
            $chk = intval(Catfish::getPost('chk'));
            if($chk > 1){
                $chk = 1;
            }
            $opt = Catfish::getPost('opt');
            if(in_array($opt, $chkarr)){
                Catfish::db('links')->where('id',$id)->update([
                    $opt => $chk
                ]);
                Catfish::removeCache('youlian');
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
    public function dellink()
    {
        if(Catfish::isPost(3)){
            $id = Catfish::getPost('id');
            $link = Catfish::db('links')->where('id', $id)->find();
            Catfish::db('links')->where('id', $id)->delete();
            if(!empty($link['tubiao']) && Catfish::isDataPath($link['tubiao'])){
                @unlink(ROOT_PATH . str_replace('/', DS, $link['tubiao']));
            }
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function modifylink()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $data = $this->addfriendshiplinkPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $shouye = 0;
                if(Catfish::getPost('shouye') == 'on'){
                    $shouye = 1;
                }
                Catfish::db('links')->where('id', Catfish::getPost('id'))->update([
                    'dizhi' => $data['dizhi'],
                    'mingcheng' => $data['mingcheng'],
                    'tubiao' => Catfish::getPost('tubiao'),
                    'target' => Catfish::getPost('target'),
                    'miaoshu' => Catfish::getPost('miaoshu'),
                    'shouye' => $shouye
                ]);
                echo 'ok';
                exit();
            }
        }
        $id = Catfish::getGet('c');
        $link = Catfish::db('links')->where('id',$id)->find();
        Catfish::allot('link', $link);
        return $this->show(Catfish::lang('Modify the friendship link'), 3, 'modifylink', '', true);
    }
    public function dbbackup()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $dbnm = Catfish::getConfig('database.database');
            $dbPrefix = Catfish::getConfig('database.prefix');
            $prefixlen = strlen($dbPrefix);
            $bkstr = '';
            $sql = "SHOW TABLES FROM {$dbnm} LIKE '{$dbPrefix}%'";
            $renm = Catfish::dbExecute($sql);
            foreach($renm as $nmval){
                reset($nmval);
                $tbnm = current($nmval);
                $onlynm = substr($tbnm, $prefixlen);
                $sql = 'SHOW COLUMNS FROM `'.$tbnm.'`';
                $re = Catfish::dbExecute($sql);
                $field = '';
                foreach($re as $val){
                    if(empty($field)){
                        $field = '`'.$val['Field'].'`';
                    }
                    else{
                        $field .= ', `'.$val['Field'].'`';
                    }
                }
                $tmp = '';
                $all = Catfish::db($onlynm)->select();
                if(is_array($all) && count($all) > 0){
                    $i = 0;
                    foreach((array)$all as $rec){
                        $str = '';
                        foreach($rec as $key => $srec){
                            if(empty($str)){
                                $str = $this->strint($srec);
                            }
                            else{
                                $str .= ', '.$this->strint($srec);
                            }
                        }
                        if(empty($tmp)){
                            $tmp .= '('.$str.')';
                        }
                        else{
                            $tmp .= ',('.$str.')';
                        }
                        $i ++ ;
                        if($i > 50){
                            $this->semiinsert($tbnm, $field, $tmp, $bkstr);
                            $tmp = '';
                            $i = 0;
                        }
                    }
                    if(!empty($tmp)){
                        $this->semiinsert($tbnm, $field, $tmp, $bkstr);
                    }
                }
            }
            $bkstr = '-- 剑鱼论坛数据库备份' . PHP_EOL . '-- 生成日期：' . date('Y-m-d H: i: s') . PHP_EOL . '-- Table prefix: ' . $dbPrefix . PHP_EOL . $bkstr;
            $bkpath = date('Ymd');
            $bkname = date('Y-m-d_H-i-s') . '_' . md5(Catfish::getRandom() . ' ' . time() . ' ' . rand());
            $bk = ROOT_PATH . 'data' . DS . 'dbbackup';
            Catfish::addIndex($bk, true);
            $bk = $bk . DS . $bkpath;
            Catfish::addIndex($bk, true);
            $sqlf = $bkname.'.jyb';
            file_put_contents($bk.DS.$sqlf, gzcompress($bkstr));
            $dbrec = Catfish::get('dbbackup');
            $recpath = $bkpath . '/' . $sqlf;
            if(empty($dbrec)){
                $dbrec = $recpath;
            }
            else{
                if(strpos($dbrec,$recpath) === false){
                    $dbrec .= ','.$recpath;
                }
            }
            Catfish::set('dbbackup', $dbrec);
            echo 'ok';
            exit();
        }
        Catfish::allot('dbbackup',$this->showdbbackup());
        return $this->show(Catfish::lang('Database backup'), 3, 'dbbackup');
    }
    public function deldbbackup()
    {
        if(Catfish::isPost(3)){
            $fn = Catfish::getPost('fn');
            if(strpos($fn, '..') === false){
                $dbrec = ',' . Catfish::get('dbbackup');
                $dbrec = str_replace(',' . $fn, '', $dbrec);
                $dbrec = empty($dbrec) ? '' : substr($dbrec, 1);
                Catfish::set('dbbackup', $dbrec);
                $this->deletefile('data/dbbackup/' . $fn);
                echo 'ok';
            }
            else{
                echo Catfish::lang('Error');
            }
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function redbbackup()
    {
        if(Catfish::isPost(3)){
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $file = ROOT_PATH . 'data' . DS . 'dbbackup' . DS . str_replace('/', DS, Catfish::getPost('fn'));
            echo $this->restoredb($file);
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function uploadrestore()
    {
        $this->checkUser();
        $prompt = '';
        if(Catfish::isPost(3)){
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $file = request()->file('file');
            if($file->checkExt('jyb') === true){
                $rem = $this->restoredb($file->getPathname());
                if($rem == 'ok'){
                    $prompt = Catfish::lang('The database has been restored');
                }
                else{
                    $prompt = $rem;
                }
            }
            else{
                $prompt = Catfish::lang('Please select the correct backup file');
            }
        }
        Catfish::allot('dbbackup',$this->showdbbackup());
        Catfish::allot('dbprompt',$prompt);
        return $this->show(Catfish::lang('Database backup'), 3, 'dbbackup', '', false, 'dbbackup');
    }
    public function remind()
    {
        if(Catfish::isPost(5)){
            echo Catfish::remind();
            exit();
        }
    }
    public function homepagetop()
    {
        $this->checkUser();
        $zdstr = '';
        $zhiding = Catfish::db('tie_fstop')->field('tid')->select();
        foreach($zhiding as $key => $val){
            $zdstr .= empty($zdstr) ? $val['tid'] : ',' . $val['tid'];
        }
        $catfish = Catfish::view('tie','id,fabushijian,biaoti,review,yuedu,fstop,fsrecommended,jingpin,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.id','in',$zdstr)
            ->where('tie.fstop','=',1)
            ->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        $typeidnm = $this->gettypeidname();
        foreach($catfish as $key => $val){
            $catfish[$key]['tietype'] = $typeidnm[$val['tietype']];
        }
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Home page top'), 5, 'homepagetop');
    }
    public function homerecommendation()
    {
        $this->checkUser();
        $tjstr = '';
        $tuijian = Catfish::db('tie_fstuijian')->field('tid')->select();
        foreach($tuijian as $key => $val){
            $tjstr .= empty($tjstr) ? $val['tid'] : ',' . $val['tid'];
        }
        $catfish = Catfish::view('tie','id,fabushijian,biaoti,review,yuedu,fstop,fsrecommended,jingpin,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.id','in',$tjstr)
            ->where('tie.fsrecommended','=',1)
            ->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        $typeidnm = $this->gettypeidname();
        foreach($catfish as $key => $val){
            $catfish[$key]['tietype'] = $typeidnm[$val['tietype']];
        }
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Home recommendation'), 5, 'homerecommendation');
    }
    public function bbn()
    {
        if(Catfish::isPost(5)){
            echo $this->bbnp();
        }
        exit();
    }
    public function finepost()
    {
        $this->checkUser();
        $catfish = Catfish::view('tie','id,fabushijian,biaoti,review,yuedu,fstop,fsrecommended,jingpin,tietype,annex')
            ->view('users','nicheng,touxiang','users.id=tie.uid')
            ->where('tie.jingpin','=',1)
            ->where('tie.status','=',1)
            ->order('tie.id desc')
            ->paginate(20);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        $typeidnm = $this->gettypeidname();
        foreach($catfish as $key => $val){
            $catfish[$key]['tietype'] = $typeidnm[$val['tietype']];
        }
        Catfish::allot('catfishcms', $catfish);
        return $this->show(Catfish::lang('Fine post'), 5, 'finepost');
    }
    public function smtpsettings()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $data = $this->smtpsettingsPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $auth = Catfish::getPost('auth') == 'on' ? true : false;
                $estis = serialize([
                    'host' => $data['host'],
                    'port' => $data['port'],
                    'user' => $data['user'],
                    'password' => $data['password'],
                    'secure' => Catfish::getPost('secure'),
                    'auth' => $auth
                ]);
                Catfish::set('emailsettings', $estis);
                echo 'ok';
                exit();
            }
        }
        $estis = Catfish::get('emailsettings');
        if($estis != false){
            $estis = unserialize($estis);
        }
        $ceshi = 1;
        if($estis == false){
            $estis = [
                'host' => '',
                'port' => 25,
                'user' => '',
                'password' => '',
                'secure' => 'tls',
                'auth' => true
            ];
            $ceshi = 0;
        }
        Catfish::allot('jianyuItem', $estis);
        Catfish::allot('ceshi', $ceshi);
        return $this->show(Catfish::lang('SMTP settings'), 3, 'smtpsettings', '', true);
    }
    public function csmail()
    {
        if(Catfish::isPost(3)){
            $estis = unserialize(Catfish::get('emailsettings'));
            if(Catfish::sendmail($estis['user'], '', Catfish::lang('Test mail'), Catfish::lang('This is a test email'))){
                echo 'ok';
            }
            else{
                echo Catfish::lang('Test mail failed to send');
            }
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function systemupgrade()
    {
        $this->checkUser();
        $conf = Catfish::getConfig('jianyu');
        $version = $conf['version'];
        $lastv = $this->bbnp();
        Catfish::set('systemupgrade_currentversion', $version);
        if(version_compare($version, $lastv) >= 0){
            $needupgrade = 0;
        }
        else{
            $needupgrade = 1;
        }
        $sjbdz = Catfish::sjbdz();
        $au = isset($sjbdz['au']) ? $sjbdz['au'] : 0;
        $directly = 0;
        $directlystr = '';
        $address = [];
        if(isset($sjbdz['address'])){
            if(isset($sjbdz['address']['directly']) && !empty($sjbdz['address']['directly'])){
                $directlystr = $sjbdz['address']['directly'];
            }
            Catfish::set('systemupgrade_directly', $directlystr);
            if(isset($sjbdz['address']['manually']) && !empty($sjbdz['address']['manually'])){
                $tmp_addr = explode(',', $sjbdz['address']['manually']);
                foreach($tmp_addr as $val){
                    array_push($address, $val);
                }
            }
            if(isset($sjbdz['address']['official']) && !empty($sjbdz['address']['official'])){
                $tmp_addr = explode(',', $sjbdz['address']['official']);
                foreach($tmp_addr as $val){
                    array_push($address, $val);
                }
            }
        }
        if(!empty($directlystr) && $au == 1){
            $directly = 1;
        }
        Catfish::allot('needupgrade', $needupgrade);
        Catfish::allot('directly', $directly);
        Catfish::allot('address', $address);
        return $this->show(Catfish::lang('System Upgrade'), 1, 'systemupgrade');
    }
    public function upgradepackage()
    {
        if(Catfish::isPost(1)){
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $package = ROOT_PATH . 'data' . DS . 'package';
            if(is_dir($package)){
                $this->delFolder($package);
            }
            $file = request()->file('file');
            $validate = [
                'ext' => 'zip'
            ];
            $info = $file->validate($validate)->move($package, false);
            if($info){
                Catfish::set('upgradepackagefilename', $info->getSaveName());
                echo 'ok';
            }else{
                echo $file->getError();
            }
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function upgrading()
    {
        if(Catfish::isPost(1)){
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $tempdir = ROOT_PATH . 'data' . DS . 'temp';
            $auto = Catfish::getPost('auto');
            if($auto == 1){
                $tempfolder = $tempdir . DS . 'autoupgrade';
            }
            else{
                $tempfolder = $tempdir . DS . 'upgrade';
            }
            if(!is_dir($tempfolder)){
                mkdir($tempfolder, 0777, true);
            }
            $upgradingfile = ROOT_PATH . 'data' . DS . 'package' . DS . Catfish::get('upgradepackagefilename');
            if(is_file($upgradingfile)){
                if(function_exists('disk_free_space')){
                    $needspace = filesize($upgradingfile) * 5;
                    if($needspace > disk_free_space($tempfolder)){
                        echo Catfish::lang('Not enough space');
                        exit();
                    }
                }
                Catfish::clearCache();
                try{
                    $zip = new \ZipArchive();
                    if($zip->open($upgradingfile, \ZipArchive::OVERWRITE || \ZIPARCHIVE::CREATE) === true){
                        $zip->extractTo($tempfolder);
                        $zip->close();
                        $this->upgradFile($tempfolder);
                        @unlink($upgradingfile);
                        $this->delFolder($tempfolder);
                        $this->upgradedb();
                        Catfish::curl(Catfish::domain());
                        echo 'ok';
                    }
                    else{
                        echo Catfish::lang('Upgrade package is not available');
                    }
                }
                catch(\Exception $e){
                    echo Catfish::lang('Upgrade unsuccessful');
                }
            }
            else{
                echo Catfish::lang('Upgrade package not found');
            }
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    private function upgradedb()
    {
        $upgradedbfile = ROOT_PATH . 'jianyu' . DS . 'install' . DS . 'upgrade';
        $sqlfiles = glob($upgradedbfile . DS . '*.sql');
        if(count($sqlfiles) > 0){
            $currentversion = Catfish::get('systemupgrade_currentversion');
            foreach($sqlfiles as $file){
                $ver = basename($file, '.sql');
                if(version_compare($ver, $currentversion) > 0){
                    $sql = Catfish::fgc($file);
                    $sql = str_replace([" `catfish_", " `jianyu_"], " `" . Catfish::prefix(), $sql);
                    $sql = str_replace("\r", "\n", $sql);
                    $sqlarr = explode(";\n", $sql);
                    foreach ($sqlarr as $item) {
                        $item = trim($item);
                        if(empty($item)) continue;
                        try{
                            Catfish::dbExecute($item);
                        }
                        catch(\Exception $e){
                            continue;
                        }
                    }
                }
                @unlink($file);
            }
        }
    }
    public function remotepackage()
    {
        if(Catfish::isPost(1)){
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $directly = Catfish::get('systemupgrade_directly');
            $directlyarr = explode(',', $directly);
            if(count($directlyarr) > 1){
                $key = rand(0, count($directlyarr) - 1);
                $directly = $directlyarr[$key];
            }
            $path = ROOT_PATH . 'data' . DS . 'package';
            if(is_dir($path)){
                $this->delFolder($path);
            }
            if(!is_dir($path)){
                mkdir($path, 0777, true);
            }
            $file = $path . DS . 'jianyu.zip';
            Catfish::set('upgradepackagefilename', 'jianyu.zip');
            Catfish::getFile($directly, $file);
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    private function upgradFile($folder)
    {
        $cfolder = 1;
        while($cfolder == 1){
            $farr = glob($folder . DS . '*', GLOB_ONLYDIR);
            $cfolder = count($farr);
            if($cfolder == 1){
                $folder = $farr[0];
            }
            else{
                break;
            }
        }
        $this->recurseCopy($folder, ROOT_PATH);
    }
    public function getmainpost()
    {
        if(Catfish::isPost(5)){
            $post = Catfish::db('tienr')->where('tid', Catfish::getPost('id'))->field('zhengwen')->find();
            return $post['zhengwen'];
        }
        return '';
    }
    public function userpoints()
    {
        $this->checkUser();
        $utp = intval(Catfish::getSession('user_type'));
        $yonghuming = Catfish::getGet('yonghuming');
        if($yonghuming === false){
            $yonghuming = '';
        }
        $query = [];
        $catfish = Catfish::db('users')
            ->where('id', '>', 1)
            ->where('utype', '>', $utp);
        if($yonghuming != ''){
            $catfish = $catfish->where('yonghu','=',$yonghuming);
            $query['yonghuming'] = $yonghuming;
        }
        $catfish = $catfish->field('id,yonghu,nicheng,email,jifen')
            ->order('id desc')
            ->paginate(20,false,[
                'query' => $query
            ]);
        Catfish::allot('pages', $catfish->render());
        $catfish = $catfish->items();
        Catfish::allot('catfishcms', $catfish);
        Catfish::allot('dengji', Catfish::getSession('user_type'));
        return $this->show(Catfish::lang('User points'), 1, 'userpoints');
    }
    public function increasepoints()
    {
        if(Catfish::isPost(1)){
            $data = $this->increasepointsPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                Catfish::db('users')
                    ->where('id', Catfish::getPost('uid'))
                    ->update([
                        'jifen' => Catfish::dbRaw('jifen+'.$data['increase'])
                    ]);
                if($data['increase'] != 0){
                    Catfish::db('points_book')->insert([
                        'uid' => Catfish::getPost('uid'),
                        'zengjian' => $data['increase'],
                        'booktime' => Catfish::now(),
                        'miaoshu' => Catfish::lang('Administrator changes')
                    ]);
                }
                echo 'ok';
                exit();
            }
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function decreasepoints()
    {
        if(Catfish::isPost(1)){
            $data = $this->decreasepointsPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $uid = Catfish::getPost('uid');
                $jifen = Catfish::db('users')->where('id', $uid)->field('jifen')->find();
                if($jifen['jifen'] < $data['decrease']){
                    echo Catfish::lang('The reduced number of points cannot be greater than the existing number of points');
                    exit();
                }
                Catfish::db('users')
                    ->where('id', $uid)
                    ->update([
                        'jifen' => Catfish::dbRaw('jifen-'.$data['decrease'])
                    ]);
                if($data['decrease'] != 0){
                    Catfish::db('points_book')->insert([
                        'uid' => $uid,
                        'zengjian' => - $data['decrease'],
                        'booktime' => Catfish::now(),
                        'miaoshu' => Catfish::lang('Administrator changes')
                    ]);
                }
                echo 'ok';
                exit();
            }
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
    public function redemptionpoints()
    {
        $this->checkUser();
        if(Catfish::isPost(1)){
            $data = $this->redemptionpointsPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                if($data['jifen'] < 1){
                    echo Catfish::lang('The set points cannot be less than 1');
                    exit();
                }
                Catfish::set('jifenduihuan', intval($data['jifen']));
                echo 'ok';
                exit();
            }
        }
        $jifen = Catfish::get('jifenduihuan');
        if($jifen === false){
            $jifen = '';
        }
        Catfish::allot('catfishcms', $jifen);
        Catfish::allot('openpay', Catfish::get('openpay'));
        return $this->show(Catfish::lang('Redemption of points'), 1, 'redemptionpoints');
    }
    public function checkinsettings()
    {
        $this->checkUser();
        if(Catfish::isPost(3)){
            $data = $this->checkinsettingsPost();
            if(!is_array($data)){
                echo $data;
                exit();
            }
            else{
                $qiandao = [
                    'checkin' => intval($data['checkin']),
                    'checkincontinu' => intval($data['checkincontinu']),
                    'checkinthreedays' => intval($data['checkinthreedays']),
                    'checkinweek' => intval($data['checkinweek']),
                    'checkintwoweek' => intval($data['checkintwoweek']),
                    'checkinmonth' => intval($data['checkinmonth']),
                    'checkintwomonth' => intval($data['checkintwomonth']),
                    'checkinthreemonth' => intval($data['checkinthreemonth']),
                    'checkinhalfyear' => intval($data['checkinhalfyear']),
                    'checkinyear' => intval($data['checkinyear'])
                ];
                Catfish::set('qiandaojifen', serialize($qiandao));
                echo 'ok';
                exit();
            }
        }
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
        return $this->show(Catfish::lang('Check-in settings'), 3, 'checkinsettings');
    }
    public function delimage()
    {
        if(Catfish::isPost(3)){
            $id = Catfish::getPost('id');
            $tmp = Catfish::db('msort')->where('id',$id)->field('image')->find();
            Catfish::db('msort')
                ->where('id', $id)
                ->update([
                    'image' => ''
                ]);
            if(Catfish::isDataPath($tmp['image'])){
                @unlink(ROOT_PATH . str_replace('/', DS, $tmp['image']));
            }
            echo 'ok';
            exit();
        }
        else{
            echo Catfish::lang('Your operation is illegal');
            exit();
        }
    }
}