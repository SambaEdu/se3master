<?php


   /**
   
   * Gestion du professeur remplacant
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Schwarz
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note
   * @sudo /usr/share/se3/scripts/remplacant.pl
   */

   /**

   * @Repertoire: annu
   * file: rempl.php
   */



   
require ("config.inc.php");
#include "functions.inc.php";

include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');
 
 // Aide
 $_SESSION["pageaide"]="Annuaire";

 echo "<h1>".gettext("Annuaire")."</h1>";

aff_trailer ("8");
if (is_admin("Annu_is_admin",$login)=="Y") {
	$rpl=$_POST['remplacant'];
  	echo" <B>$rpl</B>".gettext(" r&#233;cup&#232;re les appartenances suivantes :")." <BR><BR>";
  
  	for($i=0;$i<200;$i++) {
    		$GPRLDAP="GRPE".$i;
    		$LDAP=$_POST["$GPRLDAP"];
    		if ($LDAP <>"") {
    			$cpt++;
    			system("sudo /usr/share/se3/scripts/remplacant.pl $rpl $LDAP");
    		}
  	}
  
} else {
	echo "<div class=error_msg>".gettext("Cette application, n&#233;cessite les droits d'administrateur du serveur SambaEdu !")."</div>";
}

include ("pdp.inc.php");

?>
