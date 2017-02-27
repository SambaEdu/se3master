<?php

   /**
   
   * Permet de mettre en place des alertes (supervison)
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: alertes 
   * file: config_alertes_system.php
   */


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
//require "dbconfig.inc.php";
include_once "config.inc.php";
if ($inventaire=="1") { // Si inventaire on on inclu le fichier de conf
    include_once "dbconfig.inc.php";  
}
//***************D�inition des droits de lecture  et aide en ligne

if (is_admin("computers_is_admin",$login)=="Y")  {
  //aide 
  $_SESSION["pageaide"]="L\'interface_web_administrateur#Gestion_des_alertes";
} else { exit; }

/************************* Declaration des variables ************************************/
$ID=$_GET["ID"];
$action=$_GET["action"];

//echo "testaction $action";
$right=$_GET["droit"];
if (!($right)) $right="computers_is_admin";
$nom_alert=$_GET["nom_alert"];
$name_alert=$_GET["name_alert"];
$text_alert=$_GET["text_alert"];
$mail_alert=$_GET["mail_alert"];
$active_alert=$_GET["active_alert"];
$parc_alert=$_GET["parc_alert"];
$parc_frequence=$_GET["parc_frequence"];
$frequence_mail_=$_GET['frequence_mail'];


// Cree ou modifier une alerte de type systeme
if ($action=="new") {

	$authlink = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost, $dbuser, $dbpass));
	@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $dbname)) or die("Impossible de se connecter &#224; la base $dbname.");

	// SI ID n'est pas vide alors on est en modification
	if($ID != "") {
		$query_info="SELECT * FROM alertes WHERE ID='$ID';";
		$result_info=mysqli_query($authlink, $query_info);
		$row = mysqli_fetch_array($result_info);
	}
	
	echo "<H1>".gettext("Gestion des alertes ")."</H1>\n";
	echo "<form action=\"alertes.php\" method=get>";

	echo "<CENTER>";
	echo "<table border=1>\n";
	
	echo "<TR>\n";
	  echo "<TD class=\"menuheader\" height=\"30\" align=center colspan=\"3\">".gettext("Nouvelle alerte "). $row['NAME'] ." </TD>\n";
	echo "</TR>\n";
	
	echo "<tr>\n";
	  echo "<td class=\"menuheader\">".gettext("Nom")."</td>\n";
	  echo "<td><input type=\"text\" name=\"name_alert\" value=\"".$row['NAME']."\" size=\"30\" /></td>\n";
	  echo "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<B>Le nom :</B> indiquer ici un nom court, sans caract&#232;re particulier.')")."\"><img name=\"action_image5\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u></TD>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	  echo "<td class=\"menuheader\">".gettext("Commentaire")."</td>\n";
	  echo "<td><input type=\"text\" name=\"text_alert\" value=\"".$row['TEXT']."\" size=\"30\" /></td>\n";
	  echo "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<B>Le commentaire :</B> pas trop long, il donne une id&#233;e de l\'alerte. Il correspond au texte qui s\'affiche sur l\'interface.')")."\"><img name=\"action_image6\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u></TD>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	  echo "<td class=\"menuheader\">Mail</td>\n";
	  echo "<td><select name=\"mail_alert\" size=\"1\">\n<option";
	  if($row['MAIL']=="computers_is_admin") {echo " selected ";}
	  echo ">computers_is_admin</option>\n<option";
	  if($row['MAIL']=="se3_is_admin") {echo " selected ";}
	  echo ">se3_is_admin</option>\n<option";
	  if($row['MAIL']=="lcs_is_admin") {echo " selected ";}
	  echo ">lcs_is_admin</option>\n<option";
	  if($row['MAIL']=="maintenance_can_write") {echo " selected ";}
	  echo ">maintenance_can_write</option>\n</select></td>\n";
	  echo "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<B>La mail :</B> correspond au groupe (dans le sens droit de se3) qui va recevoir les messages')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u></TD>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	  echo "<td class=\"menuheader\">".gettext("Script")."</td>\n";
	  echo "<td><input type=\"text\" name=\"script_alert\" value=\"".$row['SCRIPT']."\" size=\"30\" /></td>\n";
	  echo "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<B>Le script :</B> est l\'executable qui sera lanc&#233; par le daemon, et qui doit renvoyer le r&#233;sultat du test. Il peut &#234;tre suivi par des options. Il doit exister dans le r&#233;pertoire pr&#233;vu.<br>Les scripts sont des scripts de type nagios.<br>Pour plus d\'informations lire la documentation.')")."\"><img name=\"action_image3\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u></TD>\n";
	echo "</tr>\n";
	if ($row['FREQUENCE']=="") { $row['FREQUENCE']="1s"; }	
	echo "<tr>\n";
	  echo "<td class=\"menuheader\">".gettext("Fr&#233;quence")."</td>\n";
	  echo "<td><select name=\"frequence_alert\" size=\"1\">\n<option value=\"900\"";
	  if($row['FREQUENCE']=="900") {echo " selected ";}
	  echo ">Toutes les 15 mn</option>\n<option value=\"1800\"";
	  if($row['FREQUENCE']=="1800") {echo " selected ";}
	  echo ">Toutes les 30mn</option>\n<option value=\"3600\"";
	  if($row['FREQUENCE']=="3600") {echo " selected ";}
	  echo ">Toutes les heures</option>\n<option value=\"14400\"";
	  if($row['FREQUENCE']=="14400") {echo " selected ";}
	  echo ">Toutes les 4 heures</option>\n<option value=\"43200\"";
	  if($row['FREQUENCE']=="43200") {echo " selected ";}
	  echo ">Toutes les nuits</option>\n<option value=\"302400\"";
	  if($row['FREQUENCE']=="302400") {echo " selected ";}
	  echo ">1 fois par semaine (WE)</option>\n";
	  echo "</select></td>\n";
	  echo "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<B>La fr&#233;quence :</B> vous pouvez indiquer la fr&#233;quence avec laquelle ce script sera execut&#233;. <br><b>Attention :</b> Ne pas prendre le risque de surcharger le serveur en indiquant une fr&#233;quence trop courte.')")."\"><img name=\"action_image4\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u></TD>\n";
	  
	echo "</tr>\n";

	// frequence des mails
	if ($row['MAIL_FREQUENCE']=="") { $row['MAIL_FREQUENCE']="0"; }	
	echo "<tr>\n";
	  echo "<td class=\"menuheader\">".gettext("Fr&#233;quence des mails")."</td>\n";
	  echo "<td><select name=\"frequence_mail\" size=\"1\">\n";
	  echo "<option value=\"0\"";
	  if($row['MAIL_FREQUENCE']=="0") {echo " selected ";}
	  echo ">Une seule fois</option>\n";
	  echo "<option value=\"1\"";
	  if($row['MAIL_FREQUENCE']=="1") {echo " selected ";}
	  echo ">A chaque test (voir fr&#233;quence)</option>";
	  echo "</select></td>\n";
	  echo "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<B>La fr&#233;quence des mails :</B> Vous pouvez choisir la fr&#233;quence avec laquelle les messages vous seront exp&#233;di&#233;s par mail.<br>Par d&#233;faut une seule fois au moment ou l\'alerte est d&#233;tect&#233;e.<br>Sinon &#224; chaque test, donc en m&#234;me temps que la fr&#233;quence des tests<br>Attention de ne pas mettre une fr&#233;quence  trop courte, au risque de bombarder votre boite mail.<br>Les mails d\'alerte ne sont envoy&#233;s qu\'en cas de probl&#232;me.')")."\"><img name=\"action_image4\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u></TD>\n";
	  
	echo "</tr>\n";
	echo "<tr>\n";
	  echo "<td class=\"menuheader\">".gettext("Alerte active")."</td>\n";
	  echo "<td><select name=\"active_alert\" size=\"1\">\n<option value=1";
	  if ($row['ACTIVE']==1) { echo " selected";}
	  echo ">".gettext("Oui")."</option>\n<option value=0";
	  if ($row['ACTIVE']==0) { echo " selected";} 
	  echo ">".gettext("Non")."</option>\n</select></td>\n";
	  echo "<TD align=\"center\"><u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<B>Actives : </B> permet d\'activer (oui) ou de d&#233;sactiver (non) l\'alerte sans la supprimer. ')")."\"><img name=\"action_image7\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u></TD>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	  echo "<td colspan=3  align=center><input type=\"submit\" value=\"".gettext("Valider")."\" />\n";
	  echo "<INPUT value=\"mod2\" name=\"action\" type=\"hidden\">\n";
	  echo "<INPUT value=\"$ID\" name=\"ID\" type=\"hidden\"></td>\n";
	echo "</tr>\n";
	
	echo "</table>\n";
	echo "</CENTER>";

	echo "</form>\n";
}


include("pdp.inc.php");
?>
