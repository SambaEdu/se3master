-- --------------------------------------------------------
--$id$--
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

ALTER TABLE `params` ADD UNIQUE (name);
ALTER TABLE `devoirs` ADD nom_devoir VARCHAR( 50 ) NOT NULL DEFAULT 'devoir' AFTER id_devoir;

INSERT INTO `params` VALUES ('', 'slis_url', '', 0, 'Url du Slis (par defaut celle du webmail', 1);
INSERT INTO `params` VALUES ('', 'infobul_activ', '1', 0, 'Activation des info-bulles', 1);
INSERT INTO `params` VALUES ('', 'bpcmedia', '0', 0, 'Media de sauvegarde pour backuppc', 5);
INSERT INTO `params` VALUES ('', 'backuppc', '1', 0, 'Active backuppc de l''interface', 5);
INSERT INTO `params` VALUES ('', 'inventaire', '1', 0, 'Désactive l''inventaire', 6);
INSERT INTO `params` VALUES ('', 'antivirus', '1', 0, 'Désactive l''anti-virus', 6);
INSERT INTO `params` VALUES ('', 'affiche_etat', '1', 0, 'Affiche la page d''état au lancement de l''interface', 6);

DROP TABLE IF EXISTS `actionse3`;
CREATE TABLE actionse3 (
  action varchar(30) NOT NULL default '',
  parc varchar(50) NOT NULL default '',
  jour varchar(30) NOT NULL default '',
  heure time NOT NULL default '00:00:00',
  UNIQUE KEY parc (parc,jour,heure)
) TYPE=MyISAM ;


CREATE TABLE `appli_se3` (
  `categorie` varchar(100) NOT NULL default '',
  `script` varchar(100) NOT NULL default '',
  `executable` varchar(100) NOT NULL default '',
  `nom` varchar(100) NOT NULL default '',
  `valide` int(11) NOT NULL default '0',
  PRIMARY KEY  (`nom`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `appli_se3` VALUES ('reseau', 'putty.bat',
'putty-0.58-installer.exe', 'putty', 0);
INSERT INTO `appli_se3` VALUES ('base', 'adobe-reader.bat', '7',
'adobe-reader', 0);
INSERT INTO `appli_se3` VALUES ('bureautique', 'officeop2.bat',
'openofficeorg20.msi', 'OpenOffice2', 0);
INSERT INTO `appli_se3` VALUES ('base', 'sun-jre506.bat',
'jre-1_5_0_06-windows-i586-p.exe', 'Java jre-1_5_0_06', 0);
INSERT INTO `appli_se3` VALUES ('bureautique', 'truc.bat', '', 'tru', 0);
INSERT INTO `appli_se3` VALUES ('graphisme', 'xnview.bat', '', 'xnview', 0);
INSERT INTO `appli_se3` VALUES ('bureautique', 'openoffice.bat', '',
'openoffice1.5', 0);
