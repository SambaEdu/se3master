-- phpMyAdmin SQL Dump
-- version 2.6.2-Debian-3sarge1
-- http://www.phpmyadmin.net
-- 
-- Serveur: localhost
-- Généré le : Jeudi 10 Novembre 2005 à 06:46
-- Version du serveur: 4.1.11
-- Version de PHP: 4.3.10-16
-- 
-- Base de données: `se3db`
-- 

-- --------------------------------------------------------

-- 
-- Structure de la table `quotas`
-- 

DROP TABLE IF EXISTS `quotas`;
CREATE TABLE `quotas` (
  `type` char(1) default NULL,
  `nom` varchar(20) default NULL,
  `quotasoft` mediumint(9) default NULL,
  `quotahard` mediumint(9) default NULL,
  `partition` varchar(10) default NULL
) ENGINE=MyISAM;

-- 
-- Contenu de la table `quotas`
-- 

