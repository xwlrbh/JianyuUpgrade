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
class Onepost extends Jsonapi
{
    private $time = 1200;
    public function getData($param)
    {
        $find = $param['post'];
        $cachename = 'api_post_'.$find;
        $post = Catfish::getCache($cachename);
        if($post === false){
            $post = Catfish::view('tie','id,uid,sid,fabushijian,xiugai,biaoti,zhaiyao,pinglunshu,yuedu,zan,cai,shoucang,jingpin,shipin')
                ->view('tienr','laiyuan,zhengwen,fujian','tienr.tid=tie.id')
                ->view('users','nicheng,touxiang','users.id=tie.uid')
                ->where('tie.id','=',$find)
                ->where('tie.status','=',1)
                ->where('tie.review','=',1)
                ->find();
            Catfish::tagCache('api_post')->set($cachename,$post,$this->time);
        }
        if(empty($post)){
            $err = $this->createError('603', 'Post does not exist', 'No post found');
            $this->addError($err);
        }
        else{
            $author = $this->createData('people', $post['uid'], [
                'nickname' => $post['nicheng'],
                'avatar' => Catfish::domain() . 'data/avatar/' . $post['touxiang']
            ]);
            $data = $this->createData('posts', $post['id'], [
                'title' => $post['biaoti'],
                'body' => $this->filterContent($post['zhengwen']),
                'created' => $post['fabushijian'],
                'updated' => $post['xiugai'],
                'comments' => $post['pinglunshu'],
                'reading' => $post['yuedu'],
                'like' => $post['zan'],
                'dislike' => $post['cai'],
                'keep' => $post['shoucang'],
                'fine' => $post['jingpin'],
                'video' => Catfish::domain() . $post['shipin'],
                'source' => $post['laiyuan'],
                'annex' => $post['fujian']
            ], [
                'self' => Catfish::domain() . 'post/' . $find
            ], [
                'author' => [
                    'data' => $author
                ]
            ]);
            $this->addData($data);
        }
        return $this->outJsonApi();
    }
}