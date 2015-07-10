CREATE DATABASE family_organizer;

USE family_organizer;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` int(1) NOT NULL DEFAULT '1',
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `group_id` varchar(255) NOT NULL,
  `password_hash` text NOT NULL,
  `api_key` varchar(32) NOT NULL,  
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
);

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
  `items` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
 
CREATE TABLE IF NOT EXISTS `user_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` int(1) NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL,
  `items_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `items_id` (`items_id`)
);
 
ALTER TABLE  `user_items` ADD FOREIGN KEY (  `user_id` ) REFERENCES  `family_organizer`.`users` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;
 
ALTER TABLE  `user_items` ADD FOREIGN KEY (  `items_id` ) REFERENCES  `family_organizer`.`items` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;