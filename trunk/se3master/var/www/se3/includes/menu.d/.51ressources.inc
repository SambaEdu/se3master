<?php

   /**
   * Menu Ressources et partages
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Equipe TICE crdp de Caen
   
   * @Note: Ce menu est identique a 50ressources mais utilise les anciennes pages dans le cas de presence de serveur esclave.

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: 51ressources.inc
   * @Repertoire: includes/menu.d/ 
   */  
  
require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');


    $liens1=array(array(
        gettext("Ressources et partages"),"profs",1,
        gettext("Liste r&#233;pertoires Classes"),"partages/liste_folders_classes.php","se3_is_admin",1,
        gettext("Cr&#233;ation r&#233;pertoires Classes"),"partages/create_folders_classes.php","se3_is_admin",1,
        gettext("Rafra&#238;chissement des Classes"),"partages/synchro_folders_classes.php","se3_is_admin",1,
        gettext("Suppression r&#233;pertoires Classes"),"partages/delete_folders_classes.php","se3_is_admin",1,
        gettext("Cr&#233;ation de partages"),"partages/create_share.php","se3_is_admin",2,
        gettext("Suppression de partages"),"partages/delete_share.php","se3_is_admin",2,
	gettext("Droits sur fichiers"),"acls/acls.php","se3_is_admin",3,
        gettext("Distribution documents"),"echanges/distribuer.php","profs",1,
        gettext("R&#233;cup&#233;ration devoirs"),"echanges/recuperer.php","profs",1,
	gettext("Gestion des devoirs"),"echanges/gerer.php","profs",1,
	gettext("Envoi de corrig&#233;s"),"echanges/corriger.php","profs",1,
	gettext("Dossier &#233;change"),"dossier_echange/dossier_echange.php","echange_can_administrate",1

	  ));
	  $liens=array_merge($liens,$liens1);
?>
