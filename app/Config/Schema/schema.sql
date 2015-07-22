DROP TABLE IF EXISTS `access_tokens`;

CREATE TABLE `access_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(40) NOT NULL DEFAULT '',
  `customer_id` int(11) NOT NULL,
  `lifetime` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;

INSERT INTO access_tokens VALUES (24,'5761fe937b3fb0ddbc782a0411fd076ade8104cf',2,3600,'2015-07-14 15:34:39');
INSERT INTO access_tokens VALUES (25,'95379cf64a9a273c109ff7b8860711b244668ca9',1,3600,'2015-07-22 15:41:57');



DROP TABLE IF EXISTS `bad_members`;

CREATE TABLE `bad_members` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `member_mapper_id` int(11) NOT NULL,
  `reason` varchar(200) NOT NULL DEFAULT '',
  `member_ID` int(11) NOT NULL,
  `member_EMAIL` varchar(100) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




DROP TABLE IF EXISTS `bad_products`;

CREATE TABLE `bad_products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `filter_id` int(11) NOT NULL,
  `reason` varchar(200) NOT NULL DEFAULT '',
  `pro_ID` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




DROP TABLE IF EXISTS `customers`;

CREATE TABLE `customers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(100) NOT NULL DEFAULT '',
  `uuid` varchar(45) NOT NULL DEFAULT '',
  `name` varchar(70) DEFAULT '',
  `mail` varchar(70) DEFAULT NULL,
  `is_installed` tinyint(1) NOT NULL DEFAULT '0',
  `is_develop` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO customers VALUES (1,'bayanpazar.com','5c5392fa-18ef-11e5-88b9-000c29212a08','Burak Doğan','burak@platinmarket.com',1,1,'2015-07-02 13:59:10','2015-07-02 15:46:19');
INSERT INTO customers VALUES (2,'reform','721e8418-0b90-11e5-8982-000c29212a08','Burak Doğan','burak@platinmarket.com',1,1,'2015-07-14 15:34:39','2015-07-14 15:34:57');



DROP TABLE IF EXISTS `filters`;

CREATE TABLE `filters` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(45) NOT NULL DEFAULT '',
  `remote` varchar(45) DEFAULT NULL,
  `query` text NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

INSERT INTO filters VALUES (1,'Bayan Kullanıcılar','Member','a:1:{s:17:\"Member.member_SEX\";i:2;}',NULL);
INSERT INTO filters VALUES (2,'Erkek Kullanıcılar','Member','a:1:{s:17:\"Member.member_SEX\";i:1;}',NULL);
INSERT INTO filters VALUES (3,'Tüm Kullanıcılar','Member','a:0:{}',NULL);
INSERT INTO filters VALUES (4,'Tüm Ürünler','Product','a:0:{}',NULL);



DROP TABLE IF EXISTS `member_mappers`;

CREATE TABLE `member_mappers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `grupid` int(11) NOT NULL,
  `filter_id` int(11) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;

INSERT INTO member_mappers VALUES (24,1,24089,3,'2015-07-08 10:55:20','2015-07-14 16:30:47');
INSERT INTO member_mappers VALUES (25,2,7303,3,'2015-07-14 15:35:25','2015-07-14 15:35:25');



DROP TABLE IF EXISTS `refresh_tokens`;

CREATE TABLE `refresh_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(40) NOT NULL DEFAULT '',
  `customer_id` int(11) NOT NULL,
  `lifetime` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;

INSERT INTO refresh_tokens VALUES (24,'8a1e220eed5dd4c51ac0d8600c7ca85cc63d4d69',2,1206900,'2015-07-14 15:34:39');
INSERT INTO refresh_tokens VALUES (25,'29ef3cf3abf640b3ed791acb1738f8d43c37d658',1,1206900,'2015-07-22 15:41:57');



DROP TABLE IF EXISTS `setrow`;

CREATE TABLE `setrow` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `api_key` varchar(50) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO setrow VALUES (1,1,'ueoJuCbU7tVkN0lhRBNx343TqxbgbofB612DVXXxdQrQZ3ertg','2015-07-02 15:39:58','2015-07-14 16:30:47');
INSERT INTO setrow VALUES (2,2,'ueoJuCbU7tVkN0lhRBNx343TqxbgbofB612DVXXxdQrQZ3ertg','2015-07-14 15:34:57','2015-07-14 15:35:25');



DROP TABLE IF EXISTS `sync_config`;

CREATE TABLE `sync_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `period` int(11) NOT NULL DEFAULT '1',
  `customer_id` int(11) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO sync_config VALUES (1,1,5,1,'2015-07-07 23:15:03','2015-07-14 16:30:47');
INSERT INTO sync_config VALUES (2,1,1,2,'2015-07-14 15:34:39','2015-07-14 15:35:25');



DROP TABLE IF EXISTS `sync_track`;

CREATE TABLE `sync_track` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `alias` varchar(45) NOT NULL DEFAULT '',
  `last_created` datetime DEFAULT NULL,
  `last_modified` datetime DEFAULT NULL,
  `last_try` datetime DEFAULT NULL,
  `last_success` datetime DEFAULT NULL,
  `last_error` datetime DEFAULT NULL,
  `last_message` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO sync_track VALUES (1,2,'Member:Filter:3:Mapper:25','1970-01-01 00:00:01','1970-01-01 00:00:01','1970-01-01 00:00:01','1970-01-01 00:00:01','2015-07-14 15:56:16','Reform Api error. Customer Not Found.');
INSERT INTO sync_track VALUES (2,1,'Member:Filter:3:Mapper:24','1970-01-01 00:00:01','1970-01-01 00:00:01','2015-07-14 15:56:16','1970-01-01 00:00:01','1970-01-01 00:00:01','');



