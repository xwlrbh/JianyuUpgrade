CREATE TABLE `jianyu_sign_in_statistics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned DEFAULT 0,
  `qiandaoshijian` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `leijiqiandao` int(11) unsigned DEFAULT 0,
  `leijijiangli` int(11) unsigned DEFAULT 0,
  `jinrijiangli` int(11) unsigned DEFAULT 0,
  `lianxu` int(11) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `qiandaoshijian` (`qiandaoshijian`),
  KEY `leijiqiandao` (`leijiqiandao`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;