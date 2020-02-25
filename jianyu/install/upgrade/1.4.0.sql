ALTER TABLE `catfish_forum` ADD `yanzhengzt` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `lianjiedj` ,
ADD `yanzhenggt` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `yanzhengzt` ,
ADD `shichangzt` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `yanzhenggt` ,
ADD `shichanggt` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `shichangzt` ;