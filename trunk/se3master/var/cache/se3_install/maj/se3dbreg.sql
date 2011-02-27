#
# Structure de la table `categories`
#

DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  catID int(11) NOT NULL default '0',
  IntCat varchar(100) NOT NULL default '',
  CleID tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (catID,CleID)
) TYPE=MyISAM;

#
# Contenu de la table `categories`
#

INSERT INTO categories VALUES (100, 'Utilisateurs', 1);
INSERT INTO categories VALUES (200, 'Ordinateurs', 2);
# --------------------------------------------------------

#
# Structure de la table `configuration`
#

DROP TABLE IF EXISTS configuration;
CREATE TABLE configuration (
  cheminvbsse3 varchar(50) NOT NULL default '',
  cheminreseau varchar(100) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `configuration`
#

# --------------------------------------------------------

#
# Structure de la table `corresp`
#

DROP TABLE IF EXISTS corresp;
CREATE TABLE corresp (
  CleID tinyint(4) NOT NULL auto_increment,
  Intitule varchar(100) NOT NULL default '',
  valeur varchar(100) NOT NULL default '',
  antidote varchar(30) default NULL,
  genre varchar(30) NOT NULL default '',
  OS varchar(20) NOT NULL default '98',
  chemin varchar(150) NOT NULL default '',
  comment longtext,
  type varchar(20) NOT NULL default 'restrict',
  PRIMARY KEY  (chemin),
  UNIQUE KEY CleID (CleID)
) TYPE=MyISAM;

#
# Contenu de la table `corresp`
#

INSERT INTO corresp VALUES (18, 'Page de démarrage d\'Internet Explorer', 'www.ac-creteil.fr', 'www.ac-creteil.fr', 'REG_SZ', 'TOUS', 'HKEY_CURRENT_USER\\Software\\Microsoft\\Internet Explorer\\Main\\Start Page', 'Sandrine Dangreville', 'config');
INSERT INTO corresp VALUES (19, 'Don\'t show last user', '1', 'SUPPR', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Policies\\System\\DontDisplayLastUserName', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (3, 'Pas de menu fichier', '1', '0', 'REG_DWORD', 'Type9x', 'HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoFileMenu', 'test de commentaire ok\r\n\r\nre', 'restrict');
INSERT INTO corresp VALUES (17, 'Affichage note pré-login', 'Message perso bandeau fenêtre', 'SUPPR', 'REG_DWORD', 'Type9x', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Winlogon\\LegalNoticeCaption', 'J. BREYAULT', 'config');
INSERT INTO corresp VALUES (20, 'Affiche message sur fenêtre login', 'Message personnalisé', 'SUPPR', 'REG_SZ', 'TypeXP', 'HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows NT\\CurrentVersion\\Winlogon\\LogonPrompt', 'J. BREYAULT', 'config');
INSERT INTO corresp VALUES (22, 'Empêche changement MDP', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\System\\DisableChangePassword', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (23, 'Pas de panneau de config', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoControlPanel', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (24, 'Pas de map de lecteurs reseau', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoNetConnectDisconnect', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (25, 'Efface swap au shutdown', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Session Manager\\Memory Management\\ClearPageFileAtShutdown', 'J. BREYAULT', 'config');
INSERT INTO corresp VALUES (26, 'Affiche logoff sur menu démarrer', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\ForceStartMenuLogoff', 'J. BREYAULT', 'config');
INSERT INTO corresp VALUES (27, 'Empêche accés icônes près horloge', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoTrayItemsDisplay', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (28, 'Empêche tollbars sur barre des tâches', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoToolbarsOnTaskbar', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (29, 'Bloque position barre des tâches', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\LockTaskbar', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (30, 'Force menu démarrer classique', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoSimpleStartMenu', 'J. BREYAULT', 'config');
INSERT INTO corresp VALUES (31, 'Pas de restauration - clé1', '1', 'SUPPR', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\SOFTWARE\\Policies\\Microsoft\\Windows NT\\SystemRestore\\DisableConfig', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (32, 'Pas de restauration - clé2', '1', 'SUPPR', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\SOFTWARE\\Policies\\Microsoft\\Windows NT\\SystemRestore\\DisableSR', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (33, 'Autoreboot après crash', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\CrashControl\\AutoReboot', 'J. BREYAULT', 'config');
INSERT INTO corresp VALUES (34, 'Login après resume', '1', '0', 'REG_DWORD', 'TypeXP', 'HKEY_LOCAL_MACHINE\\Software\\Policies\\Microsoft\\Windows\\System\\Power\\PromptPasswordOnResume', 'J. BREYAULT', 'config');
INSERT INTO corresp VALUES (35, 'Empêche clic droit sur bureau', '1', '0', 'REG_DWORD', 'TOUS', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoViewContextMenu', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (36, 'Cacher une machine sur le réseau', '1', 'SUPPR', 'REG_DWORD', 'TOUS', 'HKEY_LOCAL_MACHINE\\System\\CurrentControlSet\\Services\\LanmanServer\\Parameters\\Hidden', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (37, 'Efface fichiers internet temporaires', '0', '1', 'REG_DWORD', 'TOUS', 'HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Persistent', 'J. BREYAULT', 'config');
INSERT INTO corresp VALUES (38, 'Pas de windows update', '0', 'SUPPR', 'REG_DWORD', 'TOUS', 'HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoWindowsUpdate', 'J. BREYAULT', 'restrict');
INSERT INTO corresp VALUES (39, 'Pas enregistrement MDP internet', '1', 'SUPPR', 'REG_DWORD', 'TOUS', 'HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Internet Settings', 'J. BREYAULT', 'config');
INSERT INTO corresp VALUES (40, 'Masque l\'icône Voisinage Réseau', '00000001', '00000000', 'REG_DWORD', 'Type9x', 'HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoNetHood', 'Olivier.S', 'restrict');
INSERT INTO corresp VALUES (41, 'Masque la commande "Executer" du menu démarrer', '00000001', '00000000', 'REG_DWORD', 'Type9x', 'HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoRun', 'Olivier.S', 'restrict');
INSERT INTO corresp VALUES (42, 'Masquage : panneau de configuration + imprimante', '00000001', '00000000', 'REG_DWORD', 'Type9x', 'HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoSetFolders', 'Masquage du Panneau de Configuration et du dossier Imprimantes dans le poste de travail (j\'ai n\'ai pas essayer de rajouter ce dossier, mais au cas ou cela fonctionne...) Olivier.S', 'restrict');
INSERT INTO corresp VALUES (43, 'Empèche la  suppression des imprimantes', '00000001', '00000000', 'REG_DWORD', 'Type9x', 'HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoDeletePrinter', 'Olivier.S', 'restrict');
INSERT INTO corresp VALUES (46, 'Empèche l\'ajout d\'imprimantes', '1', '0', 'REG_DWORD', 'Type9x', 'HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\NoAddPrinter', 'Olivier.S', 'restrict');
# --------------------------------------------------------

#
# Structure de la table `modele`
#

DROP TABLE IF EXISTS modele;
CREATE TABLE modele (
  modID int(10) NOT NULL auto_increment,
  cle tinyint(4) NOT NULL default '0',
  mod varchar(30) NOT NULL default 'fullrestrict',
  etat tinyint(4) default '1',
  PRIMARY KEY  (cle,mod),
  UNIQUE KEY modID (modID)
) TYPE=MyISAM;

#
# Contenu de la table `modele`
#

INSERT INTO modele VALUES (1, 18, 'norestrict', 0);
INSERT INTO modele VALUES (2, 18, 'fullrestrict', 1);

# --------------------------------------------------------

#
# Structure de la table `restrictions`
#

DROP TABLE IF EXISTS restrictions;
CREATE TABLE restrictions (
  resID tinyint(4) NOT NULL auto_increment,
  cleID int(11) NOT NULL default '0',
  groupe varchar(100) NOT NULL default '',
  valeur varchar(100) NOT NULL default '',
  PRIMARY KEY  (cleID,groupe),
  UNIQUE KEY resID (resID)
) TYPE=MyISAM;

#
# Contenu de la table `restrictions`
#



