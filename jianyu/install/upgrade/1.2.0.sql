CREATE TABLE `catfish_banned` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned DEFAULT 0,
  `zhutie` tinyint(1) NOT NULL DEFAULT 0,
  `zhutietime` datetime DEFAULT '2000-01-01 00:00:00',
  `zhutieliyou` varchar(500) DEFAULT '',
  `gentie` tinyint(1) NOT NULL DEFAULT 0,
  `gentietime` datetime DEFAULT '2000-01-01 00:00:00',
  `gentieliyou` varchar(500) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
