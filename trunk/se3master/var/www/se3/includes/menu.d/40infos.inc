<?php


   /**
   * Menu Information systeme
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Equipe TICE crdp de Caen
   
   * @Note: 

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: 40infos.inc
   * @Repertoire: includes/menu.d/ 
   */  
  
  

	require_once("lang.inc.php");


	require_once("lang.inc.php");
	bindtextdomain('se3-core',"/var/www/se3/locale");
	textdomain ('se3-core');
	
	
	$liens1=array(array(
        gettext("Informations syst&#232;me"),"system_is_admin",1,
        gettext("Diagnostic"),"test.php","system_is_admin",1,
        gettext("Tableau de bord"),"sysmon/index.php","system_is_admin",1,
	gettext("Informations g&#233;n&#233;rales"),"infos/infose.php","system_is_admin",1,
	gettext("Connexions actives"),"parcs/smbstatus.php","computers_is_admin",1,
        gettext("Historique"),"parcs/show_histo.php","computers_is_admin",1,
        gettext("Espace disque"),"infos/df.php","system_is_admin",1,
        gettext("Occupation disque"),"infos/du.php","system_is_admin",1,
	gettext("Fixer des quotas"),"infos/quota_fixer.php","system_is_admin",2,
	gettext("Quotas effectifs"),"infos/quota_visu.php","system_is_admin",2,
	gettext("Correction de probl&#232;mes"),"infos/fix_se3.php","system_is_admin",2,
        ));    
	$liens=array_merge($liens,$liens1);
?>
