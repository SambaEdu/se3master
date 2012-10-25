<?php


   /**
   * Menu Configuration generale
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Equipe TICE crdp de Caen
   
   * @Note: 

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: 20config.inc
   * @Repertoire: includes/menu.d/ 
   */  
  
  

	require_once("lang.inc.php");
	bindtextdomain('se3-core',"/var/www/se3/locale");
	textdomain ('se3-core');


    if (file_exists("/var/www/se3/includes/dbconfig.inc.php")) { 
        $liens1=array(array(
        gettext("Configuration g&#233;n&#233;rale"),"se3_is_admin", 1,
        gettext("Param&#232;tres serveur"),"conf_params.php?cat=1","se3_is_admin", 1,
        gettext("Action serveur"),"action_serv.php","se3_is_admin", 1,
    	gettext("Modules"),"conf_modules.php","se3_is_admin", 2,
	gettext("Onduleur"),"ups/ups.php","system_is_admin", 1,
        gettext("Alertes"),"alertes/alertes.php","se3_is_admin", 3,
        ));
    }
    else {
        $liens1=array(array(
        gettext("Configuration g&#233;n&#233;rale"),"se3_is_admin", 1,
        gettext("Param&#232;tres serveur"),"conf_params.php?cat=1","se3_is_admin", 1,
        gettext("Action serveur"),"action_serv.php","se3_is_admin", 1,
    	gettext("Modules"),"conf_modules.php","se3_is_admin", 2,
	gettext("Onduleur"),"ups/ups.php","system_is_admin", 1,
        gettext("Alertes"),"alertes/alertes.php","se3_is_admin", 3,
        ));
    }
	$liens=array_merge($liens,$liens1);
?>
