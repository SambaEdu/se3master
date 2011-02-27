<?php



   /**
   
   * Action sur une machine (arret - start)
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  sandrine dangreville matice creteil 2005

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: parcs/
   * file: action_machine.php

  */	



include "entete.inc.php";
require_once "ldap.inc.php";
require_once "ihm.inc.php";
require_once "fonc_outils.inc.php";

// Internationnalisation
require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');



//*****************connexion bdd*******************
// $authlink = @mysql_connect($dbhost,$dbuser,$dbpass);
// @mysql_select_db($dbname) or die("Impossible de se connecter &#224; la base $dbname.");
     
//***************Definition des droits de lecture  et aide en ligne

// Verifie les droits
if ((is_admin("computers_is_admin",$login)=="Y") or (is_admin("parc_can_view",$login)=="Y") or (is_admin("parc_can_manage",$login)=="Y") or (is_admin("inventaire_can_read",$login)=="Y")) {

	//aide
	$_SESSION["pageaide"]="Gestion_des_parcs#Action_sur_parcs";

} else {
	exit; 
}

//*****************cas des parcs delegues***********************************/
if ((is_admin("computers_is_admin",$login)=="N") and ((is_admin("parc_can_view",$login)=="Y") or (is_admin("parc_can_manage",$login)=="Y"))) { 
	echo "<h3>".gettext("Votre d&#233l&#233gation a &#233t&#233 prise en compte pour l'affichage de cette page.")."</h3>"; $acces_restreint=1;

	$list_delegate=list_parc_delegate($login);
	echo "<ul>";
	foreach ($list_delegate as $info_parc_delegate) {
		echo "<li>$info_parc_delegate</li>";
	}
	echo "</ul>";
}

      
/************************* Declaration des variables ************************************/
//action peut etre shutdown ou wol
$action=$_GET['action'];
$machine=$_GET['machine'];
$retour=$_GET['retour'];
$parc=$_GET['parc'];
$file=$_GET['file'];

if ($acces_restreint)  {  if ((!this_parc_delegate($login,$parc,"manage")) and (!this_parc_delegate($login,$parc,"view"))) { exit; } }

switch ($action) {
	case "shutdown":
	if (($parc)  and ($parc<>"SELECTIONNER")) {
		echo "<HEAD>";
		echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"2; URL=$retour.php?parc=$parc&action_poste=check&action=choix_time \">";
		echo "</HEAD>";
		
		echo "<h1>Arr&#234;t de(s) machine(s)</h1>";
		echo gettext("Commandes prises en compte !")."<br>";
		$commandes=start_poste("shutdown", $machine);
		echo "<h3>".gettext("Arret lanc&#233 pour le poste")." $machine. ".gettext("(Ne concerne que les machines XP/2000)")."</h3>";
		echo "<font color=#FF0000>".gettext("Un temps d'attente de plus une minute est n&#233c&#233ssaire pour voir le r&#233sultat dans l'interface")."</font><br>";
		echo "<br><center>";
		echo "<a href=\"$retour.php?parc=$parc&action=check \">".gettext("Retour")."</a>"; 
		echo "</center>";
	} else { echo gettext("Vous devez choisir un parc"); }

	break;

	
	case "wol":
	if (($parc)  and ($parc<>"SELECTIONNER")) {
		echo "<HEAD>";
		echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"2; URL=$retour.php?parc=$parc&action_poste=check&action=choix_time \">";
		echo "</HEAD>";

		echo "<h1>D&#233;marrage de(s) machine(s)</h1>";

		echo gettext("Commandes prises en compte !")."<br>";

		echo "<h3>".gettext("D&#233;marrage effectu&#233 pour le poste")." $machine. ".gettext("(Ne concerne que les machines equip&#233es du syst&#232me 'wake on lan')")."</h3>";
		$commandes=start_poste("wol", $machine);
		echo "<font color=#FF0000>".gettext("Un temps d'attente de plus une minute est n&#233c&#233ssaire pour voir le r&#233sultat dans l'interface")."</font><br>";
		
		echo "<br><center>";
		echo "<a href=\"$retour.php?parc=$parc&action_poste=check \">".gettext("Retour")."</a>";  
		echo "</center>";
	} else { 
		echo gettext("Vous devez choisir un parc");
	}

	break;



	case "ts":
	$get= fopen ($file, "r");
	header("Content-type: application/force-download");
	header("Content-Length: ".filesize($file));
	header("Content-Disposition: attachment; filename=$machine.rdp");
	readfile($file);
	break;

}

include("pdp.inc.php");
?>

