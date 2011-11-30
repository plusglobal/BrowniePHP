CREATE TABLE IF NOT EXISTS `brw_files` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `model` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category_code` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `category_code` (`category_code`)
);

CREATE TABLE IF NOT EXISTS `brw_images` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `model` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category_code` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `category_code` (`category_code`)
);

CREATE TABLE IF NOT EXISTS `brw_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `email` varchar(250) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` datetime NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`email`)
);
