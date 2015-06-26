DROP TABLE IF EXISTS `access_tokens`;

CREATE TABLE `access_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(40) NOT NULL DEFAULT '',
  `customer_id` int(11) NOT NULL,
  `lifetime` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

INSERT INTO access_tokens VALUES (1,'4a54b0e206e3ae2616f2e688784f353bf3bc43a0',1,3600,'2015-06-27 01:50:14');



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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

INSERT INTO customers VALUES (1,'bayanpazar.com','5c5392fa-18ef-11e5-88b9-000c29212a08','Burak Doğan','burak@platinmarket.com',0,0,'2015-06-27 01:50:14','2015-06-27 01:50:14');



DROP TABLE IF EXISTS `refresh_tokens`;

CREATE TABLE `refresh_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(40) NOT NULL DEFAULT '',
  `customer_id` int(11) NOT NULL,
  `lifetime` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

INSERT INTO refresh_tokens VALUES (1,'702917b6a93309572258e9a2d343e5cdf3a3d696',1,1206900,'2015-06-27 01:50:14');



DROP TABLE IF EXISTS `setrow`;

CREATE TABLE `setrow` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `api_key` varchar(50) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




