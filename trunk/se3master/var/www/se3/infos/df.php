<?php

   /**
   
   * Affiche l'espace utilise sur le disque par repertoire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: df.php

  */	

require ("entete.inc.php");
require ("ihm.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');


//aide 
$_SESSION["pageaide"]="Informations_syst&#232;me#Espace_disque";

if (is_admin("system_is_admin",$login)!="Y")
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");
system ("/usr/share/se3/sbin/df.sh");

require ("pdp.inc.php");
?>
