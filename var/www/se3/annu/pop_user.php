<?php


   /**
   
   * Expedie une popup a un utilisateur
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Peter
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: pop_user.php
   */






include "entete.inc.php";
require_once ("lang.inc.php");
include "ihm.inc.php"; 
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');


if ((is_admin("annu_can_read",$login)=="Y") || (is_admin("Annu_is_admin",$login)=="Y") || (is_admin("savajon_is_admin",$login)=="Y"))  {
	// Aide
	$_SESSION["pageaide"]="Gestion_des_parcs#Envoi_d.27un_popup";

	echo "<h1>".gettext("Annuaire")."</h1>\n";
 
	$uid=escapeshellarg($_GET['uid']);
	$connect=`smbstatus -u $uid|grep $uid`;

	if (empty($connect)) {
	        echo "<H2>Pop Down :-)</H2><P>";
	    	echo "<br>";
	    	echo "<br>";
	    	echo "<small>".gettext("L'envoi de Pop Up &#224;")." <b>$uid </b> ".gettext("n'est pas possible")." <br>";
	    	echo gettext("car")." <b>$uid </b> ".gettext("n'a pas de session ouverte actuellement.")."</small>";
	} else {
	        echo "<H2>".gettext("Pop Up")."</H2><P>";
		echo "<small>";
		echo "<B><H3>".gettext("Message du Pop Up pour")." $uid:</H3></B><BR>";
		echo "<form action=\"respop_user.php\" method=\"post\">";
		echo "<textarea cols=60 rows=5 name=\"message\" maxlength=\"1200\"></textarea>";
		echo "<br><br>";
		echo "<input type=hidden name=\"uid\" value=\"".$uid."\">\n";
		echo "<br>";
		echo "<input type=\"submit\" value=\"".gettext("Envoyer le Pop Up")."\">";
		echo "</form>";
	}

}
include ("pdp.inc.php");
?>
