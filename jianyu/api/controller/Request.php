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
class Request
{
    public function getRequest($param)
    {
        $redata = [];
        if(isset($param['post'])){
            if($param['post'] == 'all'){
                $apiObj = new Allpost();
                $redata = $apiObj->getData($param);
            }
            elseif(preg_match('/^\d+$/', $param['post'])){
                if(isset($param['discuss'])){
                    $apiObj = new Discuss();
                    $redata = $apiObj->getData($param);
                }
                else{
                    $apiObj = new Onepost();
                    $redata = $apiObj->getData($param);
                }
            }
        }
        elseif(isset($param['section'])){
            if($param['section'] == 'all'){
                $apiObj = new Section();
                $redata = $apiObj->getSection($param);
            }
            elseif(preg_match('/^\d+$/', $param['section'])){
                $apiObj = new Section();
                $redata = $apiObj->getData($param);
            }
        }
        elseif(isset($param['get'])){
            $vtoken = new Jwttoken();
            $verify = $vtoken->verifyJwt(Catfish::getHeader('Authorization'));
            if($verify['approved'] === true){
                $apiObj = new User();
                if($param['get'] == 'user'){
                    $redata = $apiObj->getData($param, $verify['data']);
                }
                elseif($param['get'] == 'posts'){
                    $redata = $apiObj->getPost($param, $verify['data']);
                }
                elseif($param['get'] == 'discuss'){
                    $redata = $apiObj->getComment($param, $verify['data']);
                }
                else{
                    $redata = $apiObj->getError($param);
                }
            }
            else{
                $redata = $verify['data'];
            }
        }
        else{
            $apiObj = new Errorapi();
            $redata = $apiObj->getData('600', 'No content found', 'Return empty');
        }
        Catfish::jsonApi($redata);
    }
    public function postRequest($param)
    {
        $redata = [];
        if($param['data'][0]['type'] == 'login'){
            $apiObj = new Login();
            $redata = $apiObj->getResults($param['data'][0]['attributes']);
        }
        elseif($param['data'][0]['type'] == 'signup'){
            $apiObj = new Signup();
            $redata = $apiObj->signup($param['data'][0]['attributes']);
        }
        elseif(in_array($param['data'][0]['type'], ['post', 'discuss'])){
            $vtoken = new Jwttoken();
            $verify = $vtoken->verifyJwt(Catfish::getHeader('Authorization'));
            if($verify['approved'] === true){
                $action = 'app\api\controller\\' . ucfirst($param['data'][0]['type']);
                $actobj = new $action();
                $redata = $actobj->add($param['data'][0]['attributes'], $verify['data']);
            }
            else{
                $redata = $verify['data'];
            }
        }
        else{
            $apiObj = new Errorapi();
            $redata = $apiObj->getData('700', 'Request not allowed', 'Request failed');
        }
        Catfish::jsonApi($redata);
    }
    public function putRequest($param)
    {
        if(in_array($param['data'][0]['type'], ['post', 'user', 'discuss'])){
            $vtoken = new Jwttoken();
            $verify = $vtoken->verifyJwt(Catfish::getHeader('Authorization'));
            if($verify['approved'] === true){
                $action = 'app\api\controller\\' . ucfirst($param['data'][0]['type']);
                $actobj = new $action();
                $redata = $actobj->modify($param['data'][0]['attributes'], $verify['data'], $param['data'][0]['id']);
            }
            else{
                $redata = $verify['data'];
            }
        }
        else{
            $apiObj = new Errorapi();
            $redata = $apiObj->getData('700', 'Request not allowed', 'Request failed');
        }
        Catfish::jsonApi($redata);
    }
    public function patchRequest($param)
    {
        if(in_array($param['data'][0]['type'], ['post', 'discuss'])){
            $vtoken = new Jwttoken();
            $verify = $vtoken->verifyJwt(Catfish::getHeader('Authorization'));
            if($verify['approved'] === true){
                $action = 'app\api\controller\\' . ucfirst($param['data'][0]['type']);
                $actobj = new $action();
                $dparam = [];
                if(isset($param['data'][0]['attributes'])){
                    $dparam = $param['data'][0]['attributes'];
                }
                $redata = $actobj->patch($dparam, $verify['data'], $param['data'][0]['id']);
            }
            else{
                $redata = $verify['data'];
            }
        }
        else{
            $apiObj = new Errorapi();
            $redata = $apiObj->getData('700', 'Request not allowed', 'Request failed');
        }
        Catfish::jsonApi($redata);
    }
    public function deleteRequest($param)
    {
        if(in_array($param['data'][0]['type'], ['post', 'discuss'])){
            $vtoken = new Jwttoken();
            $verify = $vtoken->verifyJwt(Catfish::getHeader('Authorization'));
            if($verify['approved'] === true){
                $action = 'app\api\controller\\' . ucfirst($param['data'][0]['type']);
                $actobj = new $action();
                $dparam = [];
                if(isset($param['data'][0]['attributes'])){
                    $dparam = $param['data'][0]['attributes'];
                }
                $redata = $actobj->delete($dparam, $verify['data'], $param['data'][0]['id']);
            }
            else{
                $redata = $verify['data'];
            }
        }
        else{
            $apiObj = new Errorapi();
            $redata = $apiObj->getData('700', 'Request not allowed', 'Request failed');
        }
        Catfish::jsonApi($redata);
    }
    public function upload($param)
    {
        $redata = [];
        $vtoken = new Jwttoken();
        $verify = $vtoken->verifyJwt(Catfish::getHeader('Authorization'));
        if($verify['approved'] === true){
            $actobj = new Upload();
            if($param['upload'] == 'avatar'){
                $redata = $actobj->avatar($verify['data']);
            }
            elseif($param['upload'] == 'image'){
                $redata = $actobj->image($verify['data']);
            }
        }
        else{
            $redata = $verify['data'];
        }
        Catfish::jsonApi($redata);
    }
}