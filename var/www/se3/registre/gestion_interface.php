<?php

   /**
   
   * Gestion des cles pour clients Windows (page d'import-export)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: gestion_interface.php

  */	



require "include.inc.php";
$action=$_GET['action'];
$cat=$_GET['cat'];
$sscat=$_GET['sscat'];
if (!$cat) { $cat=$HTTP_COOKIE_VARS["Categorie"]; }
if ($cat) {
	setcookie ("Categorie", "", time() - 3600);
	setcookie("Categorie",$cat,time()+3600);
}

if ($cat=="tout") {
	setcookie ("Categorie", "", time() - 3600);
	$cat="";
	$sscat="";
}


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

if (ldap_get_right("computers_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");
$_SESSION["pageaide"]="Gestion_des_clients_windowsNG#Description_du_processus_de_configuration_du_registre_Windows";

//require ("functions.inc.php");
$testniveau=getintlevel();
$afficheniveau=afficheniveau($testniveau);

if ($action == "delall")
{
	connexion ();
	$deleteSQL = "delete from corresp;";
	mysqli_query($GLOBALS["___mysqli_ston"], $deleteSQL);
	((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
	echo "Suppression cl&#233s ok!\n";
}

if ($action == "delallmod" or $action == "delall")
{
	connexion ();
	$deleteSQL = "delete from modele;";
	mysqli_query($GLOBALS["___mysqli_ston"], $deleteSQL);
	((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
	if ($action != "delall")
		echo "Suppression groupes de cl&#233s ok!\n";
}

if ($testniveau) {
	echo "<h1>".gettext("Administration de l'interface de cl&#233s")."</h1>\n";
	echo "<h3>".gettext("Gestion des cl&#233s :")."</h3>";
	echo "<a href=\"cle-maj.php?action=maj\">".gettext("Effectuer la mise a jour de la base de cl&#233s ?")."</a><br>";
	echo "<a href=\"affiche_cle.php\">".gettext("Editer les cl&#233s ?")."</a><br>";
	echo "<a href=\"gestion_interface.php?action=delall\" onclick=\"return getconfirm();\">".gettext("Supprimer toutes les cl&#233s ?")."</a><br>";

	if ($testniveau>1) {
		echo "<form method=\"post\" enctype=\"multipart/form-data\" action=\"cle-maj.php\">";
		echo "<BR>".gettext("Incorporer le fichier de cl&#233s suivant  (format xml) :");
		echo "<BR><input type=\"file\" name=\"fichier\" size=\"30\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"file\" />";
		echo "<input type=\"submit\" name=\"upload\" value=\"Incorporer \">";

		echo "</form>";
		echo "<a href=\"cle_export.php?action=export\">".gettext("Exporter mes cl&#233s ?")."</a></p></p>";
	}
	if ($testniveau>2) {
		echo "<form method=\"post\" enctype=\"multipart/form-data\" action=\"import_reg.php\">";
		echo "<BR>".gettext("Importer un fichier de cl&#233s au format .reg");
		echo "<BR><input type=\"file\" name=\"fichier\" size=\"30\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"file\" />";
		echo "<input type=\"submit\" name=\"upload\" value=\"Incorporer \">";
		echo "</form>";
	}

	echo "<h3>".gettext("Gestion des groupes de cl&#233s :")." </h3>";
    echo "<a href=\"mod_maj.php?action=maj\">".gettext("Effectuer la mise &#224 jour des groupes de cl&#233s ?")."</a><br>";
	echo "<a href=\"affiche_modele.php\">".gettext("Editer les groupes de cl&#233s ?")."</a><br>";
	echo "<a href=\"gestion_interface.php?action=delallmod\" onclick=\"return getconfirm();\">".gettext("Supprimer tous les groupes de cl&#233s?")."</a><br>";
	if ($testniveau>1) {

		echo "<BR>".gettext("Incorporer le fichier de groupes de cl&#233s suivant (format xml) :")." <form method=\"post\" enctype=\"multipart/form-data\" action=\"mod_maj.php\">";
		echo "<BR><input type=\"file\" name=\"fichier\" size=\"30\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"file\" />";
		echo "<input type=\"submit\" name=\"upload\" value=\"Incorporer\">";
		echo "</form>";
		echo "<a href=\"mod_export.php?action=export\">".gettext("Exporter mes groupes de cl&#233s ?")."</a>";
	}
}

// echo $testniveau;
# pied de page
include ("pdp.inc.php");
?>
