-- phpMyAdmin SQL Dump
-- version 3.2.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 25, 2010 at 03:32 
-- Server version: 5.0.51
-- PHP Version: 5.3.0



/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `brownie`
--

-- --------------------------------------------------------

--
-- Table structure for table `brw_files`
--

DROP TABLE IF EXISTS `brw_files`;
CREATE TABLE IF NOT EXISTS `brw_files` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `model` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category_code` char(10) NOT NULL,
  `order` int(10) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `category_code` (`category_code`)
) TYPE=MyISAM;

--
-- Dumping data for table `brw_files`
--


-- --------------------------------------------------------

--
-- Table structure for table `brw_groups`
--

DROP TABLE IF EXISTS `brw_groups`;
CREATE TABLE IF NOT EXISTS `brw_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

--
-- Dumping data for table `brw_groups`
--


-- --------------------------------------------------------

--
-- Table structure for table `brw_images`
--

DROP TABLE IF EXISTS `brw_images`;
CREATE TABLE IF NOT EXISTS `brw_images` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `extension` char(5) NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `model` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category_code` char(10) NOT NULL,
  `order` int(10) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `category_code` (`category_code`)
) TYPE=MyISAM;

--
-- Dumping data for table `brw_images`
--


-- --------------------------------------------------------

--
-- Table structure for table `brw_models`
--

DROP TABLE IF EXISTS `brw_models`;
CREATE TABLE IF NOT EXISTS `brw_models` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `model` varchar(255) NOT NULL,
  `seccion` varchar(255) NOT NULL,
  `orden` int(10) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

--
-- Dumping data for table `brw_models`
--


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
) TYPE=MyISAM AUTO_INCREMENT=1 ;

--
-- Dumping data for table `brw_permissions`
--


-- --------------------------------------------------------

--
-- Table structure for table `brw_users`
--

DROP TABLE IF EXISTS `brw_users`;
CREATE TABLE IF NOT EXISTS `brw_users` (
  `id` int(5) unsigned NOT NULL auto_increment,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `brw_group_id` int(10) NOT NULL,
  `root` tinyint(1) NOT NULL default '0',
  `last_login` datetime NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) TYPE=MyISAM  AUTO_INCREMENT=4 ;

--
-- Dumping data for table `brw_users`
--

