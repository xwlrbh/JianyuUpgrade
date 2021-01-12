ALTER TABLE `jianyu_forum` ADD `jinbi` SMALLINT( 2 ) NOT NULL DEFAULT '0' AFTER `jifendj` ;
ALTER TABLE `jianyu_forum` ADD `jinbidj` SMALLINT( 2 ) NOT NULL DEFAULT '1' AFTER `jinbi` ;
ALTER TABLE `jianyu_tie` ADD `jinbileixing` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `jifen` ;
ALTER TABLE `jianyu_tie` ADD `jinbi` INT( 11 ) NOT NULL DEFAULT '0' AFTER `jinbileixing` ;
ALTER TABLE `jianyu_tie` ADD `zhifufangshi` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `jinbi` ;
ALTER TABLE `jianyu_forum` ADD `jifenbi` SMALLINT( 2 ) NOT NULL DEFAULT '0' AFTER `shipinkan` ;