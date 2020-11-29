ALTER TABLE `jianyu_msort` CHANGE `icon` `icon` VARCHAR( 700 ) DEFAULT '';
ALTER TABLE `jianyu_msort` ADD `icons` VARCHAR( 500 ) NOT NULL DEFAULT '' AFTER `icon` ;