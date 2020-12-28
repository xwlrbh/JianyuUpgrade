ALTER TABLE `jianyu_forum` ADD `shipin` SMALLINT( 2 ) NOT NULL DEFAULT '0' AFTER `kzleixing` ;
ALTER TABLE `jianyu_forum` ADD `shipindj` SMALLINT( 2 ) NOT NULL DEFAULT '1' AFTER `shipin` ;
ALTER TABLE `jianyu_forum` ADD `shipinkan` SMALLINT( 2 ) NOT NULL DEFAULT '0' AFTER `shipindj` ;
ALTER TABLE `jianyu_tie` ADD `video` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `annex` ;
ALTER TABLE `jianyu_tie` ADD `shipin` VARCHAR( 500 ) NOT NULL DEFAULT '' AFTER `video` ;
ALTER TABLE `jianyu_tienr` ADD `shipinming` VARCHAR( 200 ) NOT NULL DEFAULT '' AFTER `fjsize` ;