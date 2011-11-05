<?php

   /**
   * Menu Clients Windows
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Equipe TICE crdp de Caen
   
   * @Note: 

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: 70wondoz.inc
   * @Repertoire: includes/menu.d/ 
   */  
  
	require_once("lang.inc.php");
	bindtextdomain('se3-core',"/var/www/se3/locale");
	textdomain ('se3-core');


	// Si le menu fond d'ecran est active, on ajoute la ligne correspondante:
	if ($menu_fond_ecran=="1") {
		$liens1=array(array(
		gettext("Clients Windows"),"computers_is_admin|parc_can_manage",1,
		gettext("Gestion des cl&#233;s"),"registre/gestion_interface.php","computers_is_admin",1,
		gettext("Attribution des cl&#233;s"),"registre/indexcle.php","computers_is_admin|parc_can_manage",1,
		gettext("Test des restrictions"),"registre/testreg.php","computers_is_admin",2,
		gettext("D&#233;ploiement"),"deploy/accueil.php","computers_is_admin",2,
		gettext("Navigateurs et mails"),"mozilla_profiles/profile.php","computers_is_admin",2,
		gettext("Fonds d'&#233;cran"),"fond_ecran/fonds.php","se3_is_admin",2
		));
	}
	else{
		$liens1=array(array(
		gettext("Clients Windows"),"computers_is_admin|parc_can_manage",1,
		gettext("Gestion des cl&#233;s"),"registre/gestion_interface.php","computers_is_admin",1,
		gettext("Attribution des cl&#233;s"),"registre/indexcle.php","computers_is_admin|parc_can_manage",1,
		gettext("Test des restrictions"),"registre/testreg.php","computers_is_admin",2,
		gettext("D&#233;ploiement"),"deploy/accueil.php","computers_is_admin",2,
		gettext("Navigateurs et mails"),"mozilla_profiles/profile.php","computers_is_admin",2
		));
	}
	$liens=array_merge($liens,$liens1);
?>
