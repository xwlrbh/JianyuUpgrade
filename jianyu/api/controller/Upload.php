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
class Upload extends Jsonapi
{
    public function avatar($uid)
    {
        $path = substr(md5($uid), 0, 2);
        $filename = 'u_'.$uid.'.png';
        $file = request()->file('avatar');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $info = $file->validate($validate)->move(ROOT_PATH . 'data' . DS . 'avatar' . DS . $path, $filename);
        if($info){
            $tx = $path . '/' . $filename;
            $touxiang = Catfish::db('users')->where('id',$uid)->field('touxiang')->find();
            Catfish::db('users')
                ->where('id', $uid)
                ->update([
                    'touxiang' => $tx
                ]);
            if(!empty($touxiang['touxiang']) && $touxiang['touxiang'] != $tx){
                $avpath = ROOT_PATH . 'data' . DS . 'avatar' . DS . str_replace('/', DS, $touxiang['touxiang']);
                if(is_file($avpath)){
                    @unlink($avpath);
                }
            }
            $data = $this->createData('avatar', $uid, [
                'avatar' => Catfish::domain() . 'data/avatar/' . $tx
            ]);
            $this->addData($data);
        }
        else{
            $err = $this->createError('713', 'Avatar upload failed: ' . $file->getError(), 'Upload failed');
            $this->addError($err);
        }
        return $this->outJsonApi();
    }
    public function image($uid)
    {
        $file = request()->file('image');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $info = $file->validate($validate)->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            $position = 'data/uploads/'.str_replace('\\','/',$info->getSaveName());
            $data = $this->createData('image', $uid, [
                'image' => Catfish::domain().$position
            ]);
            $this->addData($data);
        }else{
            $err = $this->createError('714', 'Image upload failed: ' . $file->getError(), 'Upload failed');
            $this->addError($err);
        }
        return $this->outJsonApi();
    }
}