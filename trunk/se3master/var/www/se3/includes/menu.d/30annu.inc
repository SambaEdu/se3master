<?php

   /**
   * Menu Annuaire
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Equipe TICE crdp de Caen
   
   * @Note: 

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: 30annu.inc
   * @Repertoire: includes/menu.d/ 
   */  
  
  

	require_once("lang.inc.php");
	require_once("lang.inc.php");
	bindtextdomain('se3-core',"/var/www/se3/locale");
	textdomain ('se3-core');


    $liens1=array(array(
        gettext("Annuaire"),"",1,
        gettext("Acc&#232;s &#224; l'annuaire"),"annu/annu.php","annu_can_read|Annu_is_admin",1,
        gettext("Modifier mon compte"),"annu/mod_entry.php","",1,
        gettext("Mon mot de passe"),"annu/mod_pwd.php","",1,
        gettext("Voir ma fiche"),"annu/me.php","",1,
        gettext("Mon espace personnel"),"individuel.php","",1,
	gettext("Administration LDAP"),"annu/indexannu.php","se3_is_admin",3,
	gettext("Nettoyage des comptes"),"annu/ldap_cleaner.php","se3_is_admin",3,
        ));
	$liens=array_merge($liens,$liens1);
?>
