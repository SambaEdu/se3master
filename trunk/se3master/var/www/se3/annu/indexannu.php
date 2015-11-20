<?php


   /**
   
   * @Version $Id: indexcle.php 4206 2009-06-22 11:31:33Z gnumdk $ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: indexcle.php

  */	



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');

if ((is_admin("computers_is_admin",$login)!="Y") or (is_admin("parc_can_manage",$login)!="Y"))
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");
	$_SESSION["pageaide"]="Annuaire#Administration_LDAP";

echo "<font color=\"orange\"><center>\n";
echo "<img src=\"../elements/images/dialog-warning.png\">\n";
echo gettext("Attention, les pages suivantes sont &#224; utiliser avec prudence!");
echo "<img src=\"../elements/images/dialog-warning.png\">\n";
echo "</center></font><br><br>\n";

//echo "<a href=\"../yala/index.html\">".gettext("Explorateur LDAP")."</a><br>\n";
$se3_pam=exec("dpkg -l|grep 'ii  se3-pla'|wc -l");
if ($se3_pam) {
	echo "<a href=\"../phpldapadmin/\">".gettext("phpldapadmin : Explorateur LDAP")."</a><br>\n";
}

echo "<a href=\"export_ldif.php\">".gettext("Export LDAP")."</a><br>\n";
echo "<a href=\"import_ldif.php\">".gettext("Import LDAP")."</a><br>\n";
echo "<a href=\"replica.php\">".gettext("R&#233;plica LDAP")."</a><br>\n";

include("pdp.inc.php");
?>
