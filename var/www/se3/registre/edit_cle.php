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
   * file: edit_cle.php

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
$template=$_GET['template'];
$state=$_GET['state'];
$value=$_GET['value'];
$choix=$_GET['choix'];
$antidote=$_GET['antidote'];

$checkedA="";
$checkedI="";
$checkedD="";

connexion();
echo "<title>".gettext("Edition d'une restriction")."</title>";

if ($choix != "") {
	$deleteSQL = "delete from restrictions where cleID='$cle' and groupe='$template'";
	if ($choix == "Active") {
		$addSQL = "Insert into restrictions values ('', '$cle', '$template', '$value', '0')";
		mysqli_query($GLOBALS["___mysqli_ston"], $deleteSQL);
		mysqli_query($GLOBALS["___mysqli_ston"], $addSQL);
	}
	else if ($choix == "Inactive") {
		$addSQL = "Insert into restrictions values ('', '$cle', '$template', '$antidote')";
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
//	if ($state == "-1") $checkedD="checked";

	$query="Select valeur, type, Intitule, antidote from corresp where cleID='$cle'";
	$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
	$row = mysqli_fetch_row($result);
	if ($value == "") $value = $row[0];
	echo "$row[2]:\n";
	echo "<form method=get action=\"edit_cle.php\">\n";
	echo "<br/>\n";
	echo "<input type=\"radio\" name=\"choix\" id='choix_active' value=\"Active\" $checkedA><label for='choix_active'>Active</label><br/>\n";
	if ($row[1] != "config")
		echo "<input type=\"radio\" name=\"choix\" id='choix_inactive' value=\"Inactive\" $checkedI><label for='choix_inactive'>Inactive</label><br/>\n";
//	echo "<input type=\"radio\" name=\"choix\" id='choix_nc' value=\"Non configur&eacute;e\" $checkedD><label for='choix_nc'>Non configur&eacute;e</label><br/>";
	if ($row[1] == "config")
		echo "<br/>Valeur: <input type=\"text\" value=\"$value\" name=\"value\" size=\"40\"><br/>";
	else
		echo "<input type=\"hidden\" name=\"value\" value=\"$row[0]\">";

	echo "<input type=\"hidden\" name=\"cle\" value=\"$cle\">";
	echo "<input type=\"hidden\" name=\"template\" value=\"$template\">";
	echo "<input type=\"hidden\" name=\"antidote\" value=\"$row[3]\">";

}
((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);

include("pdp.inc.php");

?>
