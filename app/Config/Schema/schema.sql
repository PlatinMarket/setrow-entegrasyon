DROP TABLE IF EXISTS `access_tokens`;

CREATE TABLE `access_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(40) NOT NULL DEFAULT '',
  `customer_id` int(11) NOT NULL,
  `lifetime` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

INSERT INTO access_tokens VALUES (3,'285ccac022335ee75da9ba9d63e54ca6cb3db2cf',1,-1,'2015-07-06 19:44:13');



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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

INSERT INTO customers VALUES (1,'bayanpazar.com','5c5392fa-18ef-11e5-88b9-000c29212a08','Burak Doğan','burak@platinmarket.com',1,0,'2015-07-02 13:59:10','2015-07-02 15:46:19');
INSERT INTO customers VALUES (2,'bayanpazar.com','5c5392fa-18ef-11e5-88b9-000c29212a08','Burak Doğan','burak@platinmarket.com',0,0,'2015-07-06 19:33:59','2015-07-06 19:33:59');
INSERT INTO customers VALUES (3,'bayanpazar.com','5c5392fa-18ef-11e5-88b9-000c29212a08','Burak Doğan','burak@platinmarket.com',0,0,'2015-07-06 19:34:06','2015-07-06 19:34:06');



DROP TABLE IF EXISTS `filters`;

CREATE TABLE `filters` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(45) NOT NULL DEFAULT '',
  `query` text NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO filters VALUES (1,'test global','',NULL,NULL,NULL);
INSERT INTO filters VALUES (2,'test cust','',1,NULL,NULL);



DROP TABLE IF EXISTS `refresh_tokens`;

CREATE TABLE `refresh_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(40) NOT NULL DEFAULT '',
  `customer_id` int(11) NOT NULL,
  `lifetime` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

INSERT INTO refresh_tokens VALUES (3,'1f9c4ef44b5d7f87b447a762c5f6876d60abe4fa',1,1206900,'2015-07-06 19:44:13');



DROP TABLE IF EXISTS `setrow`;

CREATE TABLE `setrow` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `api_key` varchar(50) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

INSERT INTO setrow VALUES (1,1,'ueoJuCbU7tVkN0lhRBNx343TqxbgbofB612DVXXxdQrQZ3ertg','2015-07-02 15:39:58','2015-07-02 15:46:19');



