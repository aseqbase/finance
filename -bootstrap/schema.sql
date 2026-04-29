-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: %%DATABASE%%:3306
-- Generation Time: Jan 07, 2025 at 03:39 PM
-- Server version: 10.5.27-MariaDB
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `%%DATABASE%%`
--
CREATE DATABASE IF NOT EXISTS `%%DATABASE%%` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `%%DATABASE%%`;

--
-- Table structure for table `%%PREFIX%%Finance_Invoice`
--

CREATE TABLE IF NOT EXISTS `%%PREFIX%%Finance_Invoice` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `UserId` int(11) DEFAULT NULL,
  `Name` tinytext DEFAULT NULL,
  `Title` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Relation` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Transactions` longtext DEFAULT NULL,
  `Source` tinytext DEFAULT NULL,
  `SourceData` longtext DEFAULT NULL,
  `Amount` float DEFAULT NULL,
  `Currency` tinytext DEFAULT NULL,
  `Platform` tinytext DEFAULT NULL,
  `Destination` tinytext DEFAULT NULL,
  `DestinationData` longtext DEFAULT NULL,
  `Access` int(11) DEFAULT 0,
  `Status` tinytext DEFAULT NULL,
  `CreateTime` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdateTime` datetime NOT NULL DEFAULT current_timestamp(),
  `MetaData` longtext DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Table structure for table `%%PREFIX%%Finance_Account`
--

CREATE TABLE IF NOT EXISTS `%%PREFIX%%Finance_Account` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Relation` tinytext DEFAULT NULL,
  `RelationId` int(11) DEFAULT NULL,
  `SourceId` int(11) DEFAULT NULL,
  `DestinationId` int(11) DEFAULT NULL,
  `Platform` tinytext DEFAULT NULL,
  `Transaction` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `Amount` float DEFAULT 0,
  `Currency` tinytext DEFAULT NULL,
  `Status` tinytext DEFAULT NULL,
  `Description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `CreateTime` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdateTime` datetime NOT NULL DEFAULT current_timestamp(),
  `MetaData` longtext DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
