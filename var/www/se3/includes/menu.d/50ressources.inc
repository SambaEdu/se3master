<?php


   /**
   * Menu Ressources et partages
  
   * @Version $Id: 50ressources.inc 2951 2008-05-04 19:38:54Z plouf $
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Equipe TICE crdp de Caen
   
   * @Note: 

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: 50ressources.inc
   * @Repertoire: includes/menu.d/ 
   */  
  
  


require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');


    $liens1=array(array(
        gettext("Gestion des partages"),"profs",1,
        gettext("R&#233;pertoires Classes"),"partages/rep_classes.php","se3_is_admin|annu_is_admin",1,
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
