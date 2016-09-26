<?php

   /**
   * Menu Gestion des parcs
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Equipe TICE crdp de Caen
   
   * @Note: 

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: 60parcs.inc
   * @Repertoire: includes/menu.d/ 
   */  
  
require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');

    $liens1=array(array(

        gettext("Gestion des parcs"),"computers_is_admin|parc_can_manage|parc_can_view",1,
		gettext("Liste des parcs"),"parcs/show_parc.php","computers_is_admin",1,
		gettext("Action"),"parcs/action_parc.php","computers_is_admin|parc_can_manage|parc_can_view",1,
		gettext("Programmer"),"parcs/wolstop_station.php?action=timing","computers_is_admin",1,
      	gettext("Recherche"),"parcs/cherche_machine.php","computers_is_admin",1,
        gettext("Ajout et cr&#233;ation"),"parcs/create_parc.php","computers_is_admin",1,
        gettext("Suppression"),"parcs/delete_parc.php","computers_is_admin",1,
        gettext("D&#233;l&#233;gation"),"parcs/delegate_parc.php","computers_is_admin",3,
        ));
	$liens=array_merge($liens,$liens1);
?>
