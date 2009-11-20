<?php


   /**
   
   * Retourne des informations sur le systeme (nbr de compte - memoire dispo)
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note Utilise le script /usr/share/se3/sbin/infose.sh
   
   */

   /**

   * @Repertoire: /
   * file: infose.php

  */	




require ("entete.inc.php");
require ("ihm.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');

if (is_admin("system_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");


//aide 
$_SESSION["pageaide"]="Informations_syst%C3%A8me#Informations_g.C3.A9n.C3.A9rales";

system ("/usr/share/se3/scripts/infose.sh \"$peopleRdn\" \"$groupsRdn\" \"$ldap_base_dn\" $path2smbconf");
require ("pdp.inc.php");
?>
