-- create database if it does not already exist
CREATE DATABASE IF NOT EXISTS `vacinfo` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
use `vacinfo`;

-- hold individual user ban info
CREATE TABLE `users` (
 -- steam id64s are currently 17 digits long
 `steamid64` BIGINT unsigned NOT NULL,
 -- there are currently 72 games which use VAC
 `banCount` TINYINT unsigned NOT NULL DEFAULT 0,
 `gameCount` TINYINT unsigned NOT NULL DEFAULT 0,
 `communityBan` BOOLEAN NOT NULL DEFAULT 0,
 `economyBan` BOOLEAN NOT NULL DEFAULT 0,
 -- statistically impossible to be greater than 2
 `stepsRemoved` TINYINT unsigned,
 PRIMARY KEY (`steamid64`)
);
