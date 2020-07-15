ALTER TABLE `jianyu_chengzhang` ADD `jifen` INT( 11 ) NOT NULL DEFAULT '0' AFTER `chengzhang` ;
ALTER TABLE `jianyu_forum` ADD `jifen` SMALLINT( 2 ) NOT NULL DEFAULT '0' AFTER `fpreaudit` ;
ALTER TABLE `jianyu_forum` ADD `jifendj` SMALLINT( 2 ) NOT NULL DEFAULT '1' AFTER `jifen` ;
ALTER TABLE `jianyu_tie` ADD `jifen` INT( 11 ) NOT NULL DEFAULT '0' AFTER `pinglun` ;
CREATE TABLE `jianyu_tie_jifen` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(11) unsigned DEFAULT 0,
  `uid` int(11) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `utid` (`uid`,`tid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
CREATE TABLE `jianyu_tie_access` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(11) unsigned DEFAULT 0,
  `uid` int(11) unsigned DEFAULT 0,
  `accesstime` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
ALTER TABLE `jianyu_tie_zan` ADD `accesstime` DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00' AFTER `uid` ;
ALTER TABLE `jianyu_tie_cai` ADD `accesstime` DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00' AFTER `uid` ;
ALTER TABLE `jianyu_gentie_zan` ADD `accesstime` DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00' AFTER `uid` ;
ALTER TABLE `jianyu_gentie_cai` ADD `accesstime` DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00' AFTER `uid` ;
ALTER TABLE `jianyu_tie` ADD `jifenleixing` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `pinglun` ;
CREATE TABLE `jianyu_points_book` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned DEFAULT 0,
  `zengjian` int(11) DEFAULT 0,
  `booktime` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `miaoshu` varchar(100) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `booktime` (`booktime`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
CREATE TABLE `jianyu_coin_bill` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned DEFAULT 0,
  `zengjian` int(11) DEFAULT 0,
  `booktime` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `miaoshu` varchar(100) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
CREATE TABLE `jianyu_sign_in` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned DEFAULT 0,
  `qiandao` date DEFAULT '2000-01-01',
  `lianxu` int(11) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `qiandao` (`qiandao`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;