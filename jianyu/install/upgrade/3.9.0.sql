CREATE TABLE `jianyu_slides` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `gid` int(11) unsigned DEFAULT 0,
  `mingcheng` varchar(255) DEFAULT '',
  `tupian` varchar(255) DEFAULT '',
  `lianjie` varchar(255) DEFAULT '',
  `miaoshu` varchar(255) DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `listorder` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `gid` (`gid`),
  KEY `listorder` (`listorder`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `jianyu_slides_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `zuming` varchar(255) DEFAULT '',
  `width` int(11) DEFAULT '1920',
  `height` int(11) DEFAULT '700',
  `listorder` int(11) NOT NULL DEFAULT 0,
  `description` text,
  PRIMARY KEY (`id`),
  KEY `listorder` (`listorder`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;