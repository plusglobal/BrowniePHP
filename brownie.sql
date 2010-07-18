-- phpMyAdmin SQL Dump
-- version 3.2.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 17, 2010 at 10:10 
-- Server version: 5.0.51
-- PHP Version: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `acltest`
--

-- --------------------------------------------------------

--
-- Table structure for table `brw_files`
--

DROP TABLE IF EXISTS `brw_files`;
CREATE TABLE IF NOT EXISTS `brw_files` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `model` varchar(255) collate utf8_unicode_ci NOT NULL,
  `description` varchar(255) collate utf8_unicode_ci NOT NULL,
  `category_code` char(10) collate utf8_unicode_ci NOT NULL,
  `order` int(10) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `category_code` (`category_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `brw_groups`
--

DROP TABLE IF EXISTS `brw_groups`;
CREATE TABLE IF NOT EXISTS `brw_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent_id` bigint(20) unsigned default NULL,
  `lft` bigint(20) unsigned default NULL,
  `rght` bigint(20) unsigned default NULL,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1011 ;

-- --------------------------------------------------------

--
-- Table structure for table `brw_images`
--

DROP TABLE IF EXISTS `brw_images`;
CREATE TABLE IF NOT EXISTS `brw_images` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `model` varchar(255) collate utf8_unicode_ci NOT NULL,
  `description` varchar(255) collate utf8_unicode_ci NOT NULL,
  `category_code` char(10) collate utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `category_code` (`category_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `brw_models`
--

DROP TABLE IF EXISTS `brw_models`;
CREATE TABLE IF NOT EXISTS `brw_models` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `model` varchar(255) collate utf8_unicode_ci NOT NULL,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `parent_id` bigint(20) default NULL,
  `lft` bigint(20) default NULL,
  `rght` bigint(20) default NULL,
  `orden` int(10) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `brw_permissions`
--

DROP TABLE IF EXISTS `brw_permissions`;
CREATE TABLE IF NOT EXISTS `brw_permissions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `brw_model_id` int(10) unsigned NOT NULL,
  `brw_group_id` int(10) unsigned NOT NULL,
  `view` tinyint(1) NOT NULL,
  `edit` tinyint(1) NOT NULL,
  `delete` tinyint(1) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `brw_users`
--

DROP TABLE IF EXISTS `brw_users`;
CREATE TABLE IF NOT EXISTS `brw_users` (
  `id` int(5) unsigned NOT NULL auto_increment,
  `email` varchar(50) collate utf8_unicode_ci NOT NULL,
  `password` varchar(255) collate utf8_unicode_ci NOT NULL,
  `brw_group_id` int(10) NOT NULL,
  `last_login` datetime NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;
