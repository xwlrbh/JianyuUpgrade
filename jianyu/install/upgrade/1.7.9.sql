ALTER TABLE `jianyu_forum` ADD `preaudit` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `mingan` ;
ALTER TABLE `jianyu_forum` ADD `fpreaudit` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `preaudit` ;