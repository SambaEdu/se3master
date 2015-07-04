# phpMyAdmin MySQL-Dump
# version 2.2.3
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Serveur: localhost
# Genere le : Lundi 22 Juillet 2002 a 15:10
# Version du serveur: 3.23.49
# Version de PHP: 4.1.2
# Base de donnees: `se3db`
# --------------------------------------------------------

#
## $Id$ ## 
#

#
# Structure de la table `connexions`
#


CREATE TABLE IF NOT EXISTS connexions (
  id bigint(20) unsigned NOT NULL auto_increment,
  username varchar(20) NOT NULL default '',
  ip_address varchar(15) NOT NULL default '',
  netbios_name varchar(15) NOT NULL default '',
  logintime datetime NOT NULL default '0000-00-00 00:00:00',
  logouttime datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  UNIQUE KEY id_2 (id),
  KEY id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Contenu de la table `connexions`
#

# --------------------------------------------------------

#
# Structure de la table `params`
#

CREATE TABLE IF NOT EXISTS params (
  id smallint(5) unsigned NOT NULL auto_increment,
  name varchar(50) NOT NULL default '',
  value varchar(100) NOT NULL default '',
  srv_id smallint(4) NOT NULL default '0',
  descr varchar(50) NOT NULL default '',
  cat tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY id (id),
  UNIQUE KEY name (name),
  KEY id_2 (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Contenu de la table `params`
#

INSERT INTO params VALUES (1, 'urlse3', '', 0, 'Adresse de l\'interface SambaEdu3', 1);
INSERT INTO params VALUES (2, 'lang', 'fr', 0, 'Langue', 1);
INSERT INTO params VALUES (3, 'ldap_server', 'localhost', 0, 'Adresse du serveur LDAP', 2);
INSERT INTO params VALUES (4, 'ldap_port', '389', 0, 'Port LDAP', 2);
INSERT INTO params VALUES (5, 'ldap_base_dn', 'dc=sambaedu,dc=net', 0, 'Dn de base de l\'annuaire', 2);
INSERT INTO params VALUES (6, 'adminRdn', 'cn=admin', 0, 'dn Relatif de l\'administrateur de l\'annuaire', 2);
INSERT INTO params VALUES (7, 'adminPw', '', 0, 'Mot de passe d\'administrateur de l\'annuaire', 2);
INSERT INTO params VALUES (8, 'peopleRdn', 'ou=People', 0, 'dn Relatif de la branche People', 2);
INSERT INTO params VALUES (9, 'groupsRdn', 'ou=Groups', 0, 'dn Relatif de la branche Groups', 2);
INSERT INTO params VALUES (10, 'rightsRdn', 'ou=Rights', 0, 'dn Relatif de la branche Droits', 2);
INSERT INTO params VALUES (11, 'parcsRdn', 'ou=Parcs', 0, 'dn Relatif de la branche Parcs', 2);
INSERT INTO params VALUES (12, 'computersRdn', 'ou=Computers', 0, 'dn Relatif de la branche Machines', 2);
INSERT INTO params VALUES (13, 'path_to_wwwse3', '', 0, 'Chemin d\'installation de l\'interface SambaEdu3', 3);
INSERT INTO params VALUES (14, 'lcsIp', '', 0, 'Adresse du serveur Lcs (optionnel)', 1);
INSERT INTO params VALUES (15, 'domain', '', 0, 'Nom du domaine DNS', 1);
INSERT INTO params VALUES (16, 'path2UserSkel', '/etc/skel/user', 0, 'Chemin vers le modèle d\'utilisateurs', 3);
INSERT INTO params VALUES (17, 'path2BatFiles', '/home/netlogon', 0, 'Chemin vers les scripts de login', 3);
INSERT INTO params VALUES (18, 'path2Templates', '/home/templates', 0, 'Chemin vers le dossier des modèles', 3);
INSERT INTO params VALUES (19, 'path2smbconf', '/etc/samba/smb.conf', 0, 'Chemin vers smb.conf', 3);
INSERT INTO params VALUES (20, 'path2slapdconf', '', 0, 'Chemin vers slapd.conf', 3);
INSERT INTO params VALUES (21, 'path2ldapconf', '', 0, 'Chemin vers ldap.conf', 3);
INSERT INTO params VALUES (22, 'path2pamldapconf', '', 0, 'Chemin vers pam_ldap.conf', 3);
INSERT INTO params VALUES (23, 'path2nssldapconf', '', 0, 'Chemin vers fichier de conf nss', 3);
INSERT INTO params VALUES (24, 'path2ldapsecret', '/etc/ldap.secret', 0, 'Chemin vers fichier mdp ldap', 3);
INSERT INTO params VALUES (25, 'serv_samba', '', 0, 'Script de démarrage samba', 3);
INSERT INTO params VALUES (26, 'serv_apache', '', 0, 'Script de démarrage apache', 3);
INSERT INTO params VALUES (27, 'serv_slapd', '', 0, 'Script de démarrage slapd', 3);
INSERT INTO params VALUES (28, 'serv_nscd', '', 0, 'Script de démarrage nscd', 3);
INSERT INTO params VALUES (29, 'defaultgid', '5005', 0, 'gidNumber des nouveaux users', 1);
INSERT INTO params VALUES (30, 'majnbr', '#MAJNBR#', 0, 'Indice de mise a jour', 4);
INSERT INTO params VALUES (31, 'version', '#VERSION#', 0, 'Numero de version', 4);
INSERT INTO params VALUES (32, 'autologon', '1', 0, 'Login automatique sur l\'interface', 1);
INSERT INTO params VALUES (33, 'uidPolicy', 4, 0, 'Type de login', 1);
INSERT INTO params VALUES (34, 'yala_bind', 0, 0, 'Droits sur l\'annuaire pour YALA', 2);
INSERT INTO params VALUES ('', 'defaultshell', '/bin/bash', 0, 'Shell par défaut', 1);
INSERT INTO params VALUES ('', 'pwdPolicy', '0', 0, 'Politique de mot de passe', 1);

# Sauvegarde

INSERT INTO params VALUES( '', 'melsavadmin', 'mail@serveur', '0', 'Mail Administrateur de sauvegarde', '5');
INSERT INTO params VALUES( '', 'savlevel', '0', '0', 'Niveau de sauvegarde', '5');
INSERT INTO params VALUES( '', 'savbandnbr', '0', '0', 'Compteur de bande', '5');
INSERT INTO params VALUES( '', 'savdevice', '/dev/st0', '0', 'Périphérique de sauvegarde', '5');
INSERT INTO params VALUES( '', 'savhome', '1', '0', 'Etat de la sauvegarde de /home', '5');
INSERT INTO params VALUES( '', 'savse3', '0', '0', 'Etat de la sauvegarde de /var/se3','5');
INSERT INTO params VALUES( '', 'savsuspend', '1', '0', 'Mise en attente de la sauvegarde', '5');

# param de la sauvegarde auto
INSERT INTO params VALUES( '', 'svgsyst_cnsv_hebdo', '0', '0', 'Archivage sauvegarde hebdomadaire', '5');
INSERT INTO params VALUES( '', 'svgsyst_varlibsamba', '0', '0', 'sauvegarde de /var/lib/samba ou juste secret.tdb ','5');
INSERT INTO params VALUES( '', 'svgsyst_aclvarse3', '1', '0', 'Sauvegarde acl sur /var/se3', '5');

# Gep.cgi

INSERT INTO `params` ( `id` , `name` , `value` , `srv_id` , `descr` , `cat` ) 
VALUES (
 '', 'debug', '2', '0', 'Debug Gep.cgi', '4'
);

# Mise a jour
INSERT INTO `params` ( `id` , `name` , `value` , `srv_id` , `descr` , `cat` )
VALUES (
'', 'urlmaj', 'http://wawadeb.crdp.ac-caen.fr/majse3', '0', 'Adresse des scripts de mise à jour', '1'
);
INSERT INTO `params` ( `id` , `name` , `value` , `srv_id` , `descr` , `cat` )
VALUES (
'', 'ftpmaj', 'ftp://wawadeb.crdp.ac-caen.fr/pub/sambaedu', '0', 'Adresse de téléchargement des mises à jour', '1'
);
INSERT INTO `params` (`name`,`value`,`descr`,`cat`) VALUES ("defaultintlevel","3","Niveau d'interface par défaut","1");
INSERT INTO `params` (`name`,`value`,`descr`,`cat`) VALUES ("majzinbr","0","Indice de Mise a jour DLL VBS","4");
INSERT INTO `params` (`name`,`value`,`descr`,`cat`) VALUES ("xppass","","Mot de passe Administrateur sur 2000/XP","4");
INSERT INTO `params` (`name`,`value`,`descr`,`cat`) VALUES ("ntpserv", "ntp.ac-creteil.fr", "Serveur de temps", "1");
INSERT INTO `params` (`name`,`value`,`descr`,`cat`) VALUES ("printersRdn", "ou=Printers", "dn Relatif de la branche Imprimantes", "2");
INSERT INTO `params` (`name`,`value`,`descr`,`cat`) VALUES ("trashRdn", "ou=Trash", "dn Relatif de la branche Corbeille", "2");
INSERT INTO `params` (`name`,`value`,`descr`,`cat`) VALUES ('slisip', '', 'Adresse du serveur Slis (optionnel)', '1');
INSERT INTO `params` VALUES ('', 'slis_url', '', 0, 'Url du Slis (par defaut celle du webmail)', 1);
INSERT INTO `params` VALUES ('', 'infobul_activ', '1', 0, 'Activation des info-bulles', 1);
INSERT INTO `params` VALUES ('', 'inventaire', '0', 0, 'Désactive l''inventaire', 6);
INSERT INTO `params` VALUES ('', 'antivirus', '0', 0, 'Désactive l''anti-virus', 6);
INSERT INTO `params` VALUES ('', 'affiche_etat', '1', 0, 'Affiche la page d''état au lancement de l''interface', 6);
INSERT INTO `params` VALUES  ('', 'type_Equipe_Matiere', 'posixGroup', 0, 'posixGroup ou groupOfNames', 4);
INSERT INTO params VALUES ('', 'quota_warn_home', '0', '0', 'Avertissement pour dépassement de quota sur /home', '6');
INSERT INTO params VALUES ('', 'quota_warn_varse3', '0', '0', 'Avertissement pour dépassement de quota sur /var/se3', '6');
INSERT INTO `params` VALUES ('', 'se3_domain', '', 0, 'Nom du domaine samba', 4);
INSERT INTO `params` VALUES ('', 'netbios_name', '', 0, 'Nom netbios du serveur', 4);
INSERT INTO `params` VALUES ('', 'se3ip', '', 0, 'Adresse IP du serveur', 4);
INSERT INTO `params` VALUES ('', 'se3mask', '', 0, 'masque sous reseau du serveur', 4);
INSERT INTO `params` VALUES ('', 'ecard', '', 0, 'nom de la carte ethernet du serveur', 4);
INSERT INTO `params` VALUES ('', 'corbeille', '0', 0, 'Etat activation de la corbeille', 4);
INSERT INTO `params` VALUES ('', 'hide_logon', '1', 0, 'Visibilite script de login ou non', 4);
INSERT INTO `params` VALUES ('', 'localmenu', '1', 0, 'menu demarrer en local ou distant', 4);
# --------------------------------------------------------

#
# Structure de la table `sessions`
#

CREATE TABLE IF NOT EXISTS sessions (
  id smallint(5) unsigned NOT NULL auto_increment,
  sess varchar(20) NOT NULL default '',
  mdp varchar(20) NOT NULL default '',
  login varchar(20) NOT NULL default '',
  help tinyint(4) default NULL,
  intlevel tinyint(4) default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY id_2 (id,sess),
  KEY id (id,sess)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure de la table `categories`
#

CREATE TABLE IF NOT EXISTS categories (
  catID int(11) NOT NULL default '0',
  IntCat varchar(100) NOT NULL default '',
  CleID tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (catID,CleID),
  UNIQUE KEY `IntCat` (`IntCat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Contenu de la table `categories`
#

INSERT INTO categories VALUES (100, 'Utilisateurs', 1);
INSERT INTO categories VALUES (200, 'Ordinateurs', 2);
# --------------------------------------------------------

#
# Structure de la table `configuration`
#

CREATE TABLE IF NOT EXISTS configuration (
  cheminvbsse3 varchar(50) NOT NULL default '',
  cheminreseau varchar(100) NOT NULL default ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure de la table `corresp`
#

CREATE TABLE IF NOT EXISTS corresp (
  CleID int(11) NOT NULL auto_increment,
  Intitule varchar(100) NOT NULL default '',
  valeur varchar(100) NOT NULL default '',
  antidote varchar(200) default NULL,
  genre varchar(30) NOT NULL default '',
  categorie varchar(60) default '',
  sscat varchar(100) default '',
  OS varchar(200) NOT NULL default '98',
  chemin varchar(150) NOT NULL default '',
  comment longtext,
  type varchar(20) NOT NULL default 'restrict',
  PRIMARY KEY  (chemin),
  UNIQUE KEY CleID (CleID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


#
# Structure de la table `modele`
#

CREATE TABLE IF NOT EXISTS modele (
  modID int(11) NOT NULL auto_increment,
  cle int(11) NOT NULL default '0',
  `mod` varchar(30) NOT NULL default 'fullrestrict',
  etat tinyint(4) default '1',
  PRIMARY KEY  (cle,`mod`),
  UNIQUE KEY modID (modID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


#
# Structure de la table `restrictions`
#

CREATE TABLE IF NOT EXISTS restrictions (
  resID int(11) NOT NULL auto_increment,
  cleID int(11) NOT NULL default '0',
  groupe varchar(100) NOT NULL default '',
  valeur varchar(255) NOT NULL default '',
  PRIMARY KEY  (cleID,groupe),
  UNIQUE KEY resID (resID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


#
# Structure de la table `devoirs`
#

CREATE TABLE IF NOT EXISTS devoirs (
  id smallint(6) NOT NULL auto_increment,
  id_prof varchar(50) NOT NULL default '',
  id_devoir varchar(50) NOT NULL default '',
  nom_devoir varchar(50) NOT NULL default 'devoir',
  date_distrib date NOT NULL default '0000-00-00',
  date_recup date NOT NULL default '0000-00-00',
  description varchar(255) NOT NULL default '',
  liste_distrib text NOT NULL,
  liste_retard text NOT NULL,
  etat char(1) NOT NULL default 'D',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `alertes` (
  `ID` bigint(20) NOT NULL auto_increment,
  `NAME` varchar(100) NOT NULL default '',
  `MAIL` varchar(100) NOT NULL default '',
  `Q_ALERT` varchar(200) NOT NULL default '',
  `VALUE` varchar(100) NOT NULL default '',
  `CHOIX` varchar(40) NOT NULL default '',
  `TEXT` varchar(250) NOT NULL default '',
  `AFFICHAGE` tinyint(4) NOT NULL default '0',
  `VARIABLE` varchar(50) NOT NULL default '',
  `PREDEF` tinyint(4) NOT NULL default '0',
  `MENU` varchar(50) NOT NULL default '',
  `ACTIVE` tinyint(4) NOT NULL default '0',
  `SCRIPT` varchar(255) NOT NULL default '',
  `PARC` varchar(50) NOT NULL default '',
  `FREQUENCE` varchar(20) NOT NULL default '',
  `PERIODE_SCRIPT` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `MAIL_FREQUENCE` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `ID` (`ID`),
  UNIQUE KEY `NAME` (`NAME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 
-- Contenu de la table `alertes`
-- 

INSERT INTO `alertes` (`ID`, `NAME`, `MAIL`, `Q_ALERT`, `VALUE`, `CHOIX`, `TEXT`, `AFFICHAGE`, `VARIABLE`, `PREDEF`, `MENU`, `ACTIVE`, `SCRIPT`, `PARC`, `FREQUENCE`, `PERIODE_SCRIPT`) VALUES (51, 'Surveille apachese (909)', 'se3_is_admin', '', '', '', 'Test si l''interface d''administration marche', 1, '1', 1, '', 0, 'check_http -H localhost -p 909', '', '900', '2007-01-12 17:35:02');
INSERT INTO `alertes` (`ID`, `NAME`, `MAIL`, `Q_ALERT`, `VALUE`, `CHOIX`, `TEXT`, `AFFICHAGE`, `VARIABLE`, `PREDEF`, `MENU`, `ACTIVE`, `SCRIPT`, `PARC`, `FREQUENCE`, `PERIODE_SCRIPT`) VALUES (52, 'Test swap', 'se3_is_admin', '', '', '', 'Test si le serveur swap � plus de 80%', 1, '', 1, '', 0, 'check_swap -w 80%', '', '3600', '2007-01-12 15:27:03');
INSERT INTO `alertes` (`ID`, `NAME`, `MAIL`, `Q_ALERT`, `VALUE`, `CHOIX`, `TEXT`, `AFFICHAGE`, `VARIABLE`, `PREDEF`, `MENU`, `ACTIVE`, `SCRIPT`, `PARC`, `FREQUENCE`, `PERIODE_SCRIPT`) VALUES (53, 'Mises � jour', 'se3_is_admin', '', '', '', 'Test les mises � jour de s�curit� disponibles', 1, '0', 1, '', 0, 'check_debian_packages --timeout=60', '', '302400', '2007-01-12 17:11:12');
INSERT INTO `alertes` (`ID`, `NAME`, `MAIL`, `Q_ALERT`, `VALUE`, `CHOIX`, `TEXT`, `AFFICHAGE`, `VARIABLE`, `PREDEF`, `MENU`, `ACTIVE`, `SCRIPT`, `PARC`, `FREQUENCE`, `PERIODE_SCRIPT`) VALUES (50, 'Etat des disques', 'se3_is_admin', '', '', '', 'Espace libre sur les disques', 1, '0', 1, '', 0, 'check_disk -w 5% -c 3% -x /dev/shm -t 10 -e', '', '900', '2007-01-12 17:30:01');
INSERT INTO `alertes` (`ID`, `NAME`, `MAIL`, `Q_ALERT`, `VALUE`, `CHOIX`, `TEXT`, `AFFICHAGE`, `VARIABLE`, `PREDEF`, `MENU`, `ACTIVE`, `SCRIPT`, `PARC`, `FREQUENCE`, `PERIODE_SCRIPT`) VALUES ('NULL', 'UPS', 'se3_is_admin', '', '', '', 'Recevoir les alertes de l\'onduleur', 0, '', 1, '', 0, '', '', '900', 'NULL');

INSERT IGNORE INTO `alertes` (`ID`, `NAME`, `MAIL`, `Q_ALERT`, `VALUE`, `CHOIX`, `TEXT`, `AFFICHAGE`, `VARIABLE`, `PREDEF`, `MENU`, `ACTIVE`, `SCRIPT`, `PARC`, `FREQUENCE`, `PERIODE_SCRIPT`) VALUES ('NULL', 'close maintenance', 'se3_is_admin', '', '', '', 'Fermeture d\\''une demande de maintenance', 0, 'close_maintenance', 1, '', 0, '', '', '900', '2007-04-26 18:57:12');
INSERT IGNORE INTO `alertes` (`ID`, `NAME`, `MAIL`, `Q_ALERT`, `VALUE`, `CHOIX`, `TEXT`, `AFFICHAGE`, `VARIABLE`, `PREDEF`, `MENU`, `ACTIVE`, `SCRIPT`, `PARC`, `FREQUENCE`, `PERIODE_SCRIPT`) VALUES ('NULL', 'new maintenance', 'se3_is_admin', '', '', '', 'Ouverture d''une demande de maintenance', 0, 'new_maintenance', 1, '', 0, '', '', '900', '0000-00-00 00:00:00');
INSERT IGNORE INTO `alertes` (`ID`, `NAME`, `MAIL`, `Q_ALERT`, `VALUE`, `CHOIX`, `TEXT`, `AFFICHAGE`, `VARIABLE`, `PREDEF`, `MENU`, `ACTIVE`, `SCRIPT`, `PARC`, `FREQUENCE`, `PERIODE_SCRIPT`) VALUES ('NULL', 'change maintenance', 'se3_is_admin', '', '', '', 'Changement d''une demande de maintenance', 0, 'change_maintenance', 1, '', 0, '', '', '900', '0000-00-00 00:00:00');



CREATE TABLE IF NOT EXISTS delegation (
  ID int(11) NOT NULL auto_increment,
  login varchar(40) NOT NULL default '',
  parc varchar(40) NOT NULL default '',
  niveau varchar(20) NOT NULL default '',
  PRIMARY KEY  (login,parc),
  UNIQUE KEY ID (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS actionse3 (
  action varchar(30) NOT NULL default '',
  parc varchar(50) NOT NULL default '',
  jour varchar(30) NOT NULL default '',
  heure time NOT NULL default '00:00:00',
  UNIQUE KEY parc (parc,jour,heure)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 
-- Structure de la table `quotas`
-- 

CREATE TABLE IF NOT EXISTS `quotas` (
  `type` char(1) default NULL,
  `nom` varchar(255) default NULL,
  `quotasoft` mediumint(9) default NULL,
  `quotahard` mediumint(9) default NULL,
  `partition` varchar(10) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
