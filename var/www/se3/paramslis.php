<?php

   /**
   
   * Gestion de l'icone de slis dans la barre du haut  
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Franck Molle franck.molle@ac-rouen.fr (keyser)

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: paramslis.php
   */



require("entete.inc.php");

//Verification existence utilisateur dans l'annuaire
require("config.inc.php");


//permet l'autehtification is_admin
require("ihm.inc.php");

// Langues
require_once("lang.inc.php");
bindtextdomain('se3-core',"/var/www/se3/locale");
textdomain ('se3-core');

//aide
$_SESSION["pageaide"]="Table_des_mati%C3%A8res";
//AUTHENTIFICATION
if (is_admin("se3_is_admin",$login)!="Y")
   die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");


if ($_POST['config']==""||$_POST['config']=="init") {
	
	echo "<H1>Interface du Slis</H1>\n";
	echo "<br>";
	echo "<A href=\"https://$slisip:1098\" target=\"_blank\">".gettext("Acc&#232;s &#224; l'interface d'administration du Slis")."</A><br><br>\n";
	echo gettext("Param&#233;trage de l'icone Slis dans la barre du menu");
	echo "&nbsp;";
	echo "<u onmouseover=\"return escape".gettext("('Indiquer ici l\&#039;url sur laquelle vous voulez faire pointer l\&#039;icone slis se trouvant dans la barre du haut. Le fait de mettre une valeur vide fera disparaitre l\&#039;icone')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>\n";
	
	echo "<br><br>";	
	
	echo "<form action=\"paramslis.php\" method=\"post\">\n";
	echo gettext("Page cible de l'icone : ");
	echo "<INPUT TYPE=\"TEXT\" NAME=\"new_slis_url\" value='$slis_url' size=35>";
	echo "<input type=\"hidden\" name=\"config\" value=\"action\">";	
	echo "<input type=\"submit\" value=\"".gettext("Modifier")."\">";
	echo "</form>";
} else {
	echo "<H1>Interface du Slis</H1>\n";
	$new_slis_url=$_POST['new_slis_url'];
	$update_query="UPDATE params SET value='$new_slis_url' WHERE name='slis_url'";
	$result_update=mysql_query($update_query);

	echo "<br><br><center>\n";
	if ($result_update) {
		print gettext("Modification du param&#232;tre ")."<EM><FONT color=\"red\">".slis_url."</FONT></EM><br>";
// 		. gettext("de ")."<STRONG>".$r["value"]."</STRONG>".gettext(" en ")."<STRONG>".$$formname."</STRONG>"."<BR>\n";
	} else {
		print gettext("oops: la requete ") . "<STRONG>$update_query</STRONG>" . gettext(" a provoqu&#233; une erreur");
	}	
	echo "<br>";	
	echo gettext("L'icone du slis dans la barre du haut pointe d&#233;sormais sur")." <b>$new_slis_url</b><br>";	
	echo "<A href=\"paramslis.php\">".gettext("Modifier &#224; nouveau")."</A><br>";
	echo "<SCRIPT LANGUAGE=JavaScript>";
	echo "setTimeout('top.location.href=\"index.html\"',\"3000\")";
	echo "</SCRIPT>";
	echo "</center>";
}


require ("pdp.inc.php");
?>
