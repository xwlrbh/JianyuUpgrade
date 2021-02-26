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
class Signup extends Jsonapi
{
    public function signup($param)
    {
        if(empty($param['username']) || empty($param['password']) || empty($param['email'])){
            $err = $this->createError('706', 'Incomplete registration information', 'Registration failed');
            $this->addError($err);
        }
        elseif(!filter_var($param['email'], FILTER_VALIDATE_EMAIL)){
            $err = $this->createError('707', 'The e-mail format is incorrect', 'Registration failed');
            $this->addError($err);
        }
        else{
            $user = Catfish::db('users')->where('yonghu',$param['username'])->whereOr('email',$param['email'])->field('id,yonghu,email')->select();
            if(!empty($user)){
                foreach($user as $key => $val){
                    if(strtolower($val['yonghu']) == strtolower($param['username'])){
                        $err = $this->createError('704', 'Username already exists', 'Registration failed');
                        $this->addError($err);
                        break;
                    }
                    if(strtolower($val['email']) == strtolower($param['email'])){
                        $err = $this->createError('705', 'Email already exists', 'Registration failed');
                        $this->addError($err);
                        break;
                    }
                }
            }
            else{
                $create_date = Catfish::now();
                $rmd = md5($create_date . '_' . rand());
                $nicheng = substr(md5($rmd),0,6);
                $reid = Catfish::db('users')->insertGetId([
                    'yonghu' => $param['username'],
                    'password' => md5($param['password'].$rmd),
                    'nicheng' => $nicheng,
                    'email' => $param['email'],
                    'createtime' => $create_date,
                    'randomcode' => $rmd
                ]);
                Catfish::db('users_tongji')->insert([
                    'uid' => $reid
                ]);
                Catfish::db('users_info')->insert([
                    'uid' => $reid
                ]);
                $users = intval(Catfish::get('users'));
                $users ++;
                Catfish::set('users', $users);
                Catfish::tongji('zhuce');
                $data = $this->createData('signup', $reid, [
                    'id' => $reid,
                    'nickname' => $nicheng,
                    'createtime' => $create_date
                ]);
                $this->addData($data);
            }
        }
        return $this->outJsonApi();
    }
}