-- DROP DATABASE IF EXISTS family_organizer;

CREATE DATABASE family_organizer;

USE family_organizer;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` int(1) NOT NULL DEFAULT '1',
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `group_id` varchar(255),
  `password_hash` varchar(255) NOT NULL,
  `api_key` varchar(32) NOT NULL,  
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
);

-- HOH is Head of household 
CREATE TABLE IF NOT EXISTS `groups` (
  `id` int (11) NOT NULL AUTO_INCREMENT,
  `hoh_email` varchar (255) NOT NULL,
  PRIMARY KEY(`id`) 
);

ALTER TABLE `groups` ADD FOREIGN KEY ( `hoh_email` ) REFERENCES `family_organizer`.`users` (`email`)
ON DELETE CASCADE on UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` int(1) NOT NULL DEFAULT '1',
  `item` varchar(255) NOT NULL,
  `description` varchar(255),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
 
CREATE TABLE IF NOT EXISTS `user_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` int(1) NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`)
);
 
ALTER TABLE  `user_items` ADD FOREIGN KEY (  `user_id` ) REFERENCES  `family_organizer`.`users` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;
 
ALTER TABLE  `user_items` ADD FOREIGN KEY (  `item_id` ) REFERENCES  `family_organizer`.`items` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;

CREATE TABLE IF NOT EXISTS `user_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` int(1) NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `group_id` (`group_id`)
);

ALTER TABLE  `user_groups` ADD FOREIGN KEY (  `user_id` ) REFERENCES  `family_organizer`.`users` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;
 
ALTER TABLE  `user_groups` ADD FOREIGN KEY (  `group_id` ) REFERENCES  `family_organizer`.`items` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;