ALTER TABLE `jianyu_msort` ADD `preaudit` SMALLINT( 2 ) NOT NULL DEFAULT '0' AFTER `jibie` ;
ALTER TABLE `jianyu_msort` ADD `fpreaudit` SMALLINT( 2 ) NOT NULL DEFAULT '0' AFTER `preaudit` ;