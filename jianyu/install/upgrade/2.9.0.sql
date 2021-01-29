ALTER TABLE `jianyu_users` ADD `vipend` DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00' AFTER `utype` ;
ALTER TABLE `jianyu_users` ADD `viptype` SMALLINT( 2 ) NOT NULL DEFAULT '0' AFTER `vipend` ;
ALTER TABLE `jianyu_forum` ADD `huiyuan` SMALLINT( 2 ) NOT NULL DEFAULT '0' AFTER `jinbidj` ;
ALTER TABLE `jianyu_forum` ADD `huiyuandj` SMALLINT( 2 ) NOT NULL DEFAULT '1' AFTER `huiyuan` ;
ALTER TABLE `jianyu_forum` ADD `huiyuanmianfu` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `jifenbi` ;
ALTER TABLE `jianyu_tie` ADD `huiyuanleixing` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `jinbi` ;