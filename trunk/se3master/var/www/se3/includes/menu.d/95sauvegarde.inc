<?php

   /**
   * Menu Sauvegarde
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Equipe TICE crdp de Caen
   
   * @Note: 

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: 95sauvegarde.inc
   * @Repertoire: includes/menu.d/ 
   */  
 

require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');

//sauvegarde sur bande active
if (($savbandactiv==1) && ($backuppc==1)) {
$liens1=array(array(
        gettext("Sauvegarde"),"system_is_admin",3,
	gettext("Configuration"),"sauvegarde/sauv.php","system_is_admin",3,
	gettext("Machines"),"sauvegarde/sauvhost.php","system_is_admin",3,
	gettext("Etat des sauvegardes"),"/backuppc/","system_is_admin",3,
        // gettext("Param&#232;tres sauvegarde Bande"),"edit_params.php?cat=5","se3_is_admin", 3,
        gettext("Sauvegarde sur bande"),"infos/savstatus.php","system_is_admin", 3,
        ));
} elseif (($savbandactiv==0) && ($backuppc==1)) {	
$liens1=array(array(
        gettext("Sauvegarde"),"system_is_admin",3,
	gettext("Configuration"),"sauvegarde/sauv.php","system_is_admin",3,
	gettext("Machines"),"sauvegarde/sauvhost.php","system_is_admin",3,
	gettext("Etat des sauvegardes"),"/backuppc/","system_is_admin",3,
        ));
} elseif (($savbandactiv==1) && ($backuppc==0)) {
$liens1=array(array(
        gettext("Sauvegarde"),"system_is_admin",3,
	gettext("Sauvegarde sur bande"),"infos/savstatus.php","system_is_admin", 3,
        ));
}	
$liens=array_merge($liens,$liens1);
?>
