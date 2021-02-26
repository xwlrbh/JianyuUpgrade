<?php
/**
 * Project: 剑鱼论坛 - Forum system developed by catfish cms.
 * Producer: catfish(鲶鱼) cms [ http://www.catfish-cms.com ]
 * Author: A.J <804644245@qq.com>
 * License: Catfish CMS License ( http://www.catfish-cms.com/licenses/ccl )
 * Copyright: http://jianyuluntan.com All rights reserved.
 */
namespace app\api\controller;
class Errorapi extends Jsonapi
{
    public function getData($code, $detail, $title = null, $source = null)
    {
        $err = $this->createError($code, $detail, $title, $source);
        $this->addError($err);
        return $this->outJsonApi();
    }
}