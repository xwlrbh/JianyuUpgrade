ALTER TABLE `jianyu_msort` ADD `islink` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `image` ;
ALTER TABLE `jianyu_msort` ADD `linkurl` VARCHAR( 300 ) NOT NULL DEFAULT '' AFTER `islink` ;