<?php

   /**
   
   * Edition des cles
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Cedric Bellegarde
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: edit_cle_grp.php

  */	


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');

if (ldap_get_right("computers_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

	$_SESSION["pageaide"]="Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";


require "include.inc.php";

$cle=$_GET['cle'];
$modele=$_GET['modele'];
$state=$_GET['state'];
$choix=$_GET['choix'];
$antidote=$_GET['antidote'];

$checkedA="";
$checkedI="";
$checkedD="";

connexion();
echo "<title>".gettext("Edition d'une restriction")."</title>";

if ($choix != "") {
	$deleteSQL = "delete from modele where `cle`='$cle' and `mod`='$modele'";
	if ($choix == "Active") {
		$addSQL = "Insert into modele values ('', '$cle', '$modele', '1')";
		mysqli_query($GLOBALS["___mysqli_ston"], $deleteSQL);
		mysqli_query($GLOBALS["___mysqli_ston"], $addSQL);
	}
	else if ($choix == "Inactive") {
		$addSQL = "Insert into modele values ('', '$cle', '$modele', '0')";
		mysqli_query($GLOBALS["___mysqli_ston"], $deleteSQL);
		mysqli_query($GLOBALS["___mysqli_ston"], $addSQL);
	}
	else {
		mysqli_query($GLOBALS["___mysqli_ston"], $deleteSQL);
	}
	?>
		<script language="JavaScript" type="text/javascript">
			opener.location.reload(true);
			self.close();
		</script>
	<?php
}
else {
	if ($state == "1") $checkedA="checked";
	if ($state == "0") $checkedI="checked";
	if ($state == "-1") $checkedD="checked";

	$query="Select valeur, type, Intitule, antidote from corresp where CleID='$cle'";
	$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
	$row = mysqli_fetch_row($result);
	echo "$row[2] :\n";
	echo "<form method=get action=\"edit_cle_grp.php\">\n";
	echo "<br/>\n";
	echo "<input type=\"radio\" name=\"choix\" value=\"Active\" $checkedA>Active<br/>\n";
	if ( "$row[1]" == "config" ) { echo "<input type=\"radio\" name=\"choix\" value=\"Inactive\" $checkedI>Inactive<br/>\n"; }
	echo "<input type=\"radio\" name=\"choix\" value=\"Non configur&eacute;e\" $checkedD>Non configur&eacute;e<br/>";
	echo "<input type=\"hidden\" name=\"cle\" value=\"$cle\">";
	echo "<input type=\"hidden\" name=\"modele\" value=\"$modele\">";

	echo "<br/><input type=\"submit\" value=\"Valider\">";
	echo "</form>\n";
}
((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);

include("pdp.inc.php");

?>
