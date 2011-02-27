<?php

/* $Id$ */

/* =============================================
   Projet SE3 : Gestion des parc
   Distribué selon les termes de la licence GPL
   ============================================= */


@session_start();

include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
include "printers.inc.php";
include "fonc_inventaire.php";

include ("fonction_backup.inc.php");
   

include "fonc_outils.inc.php";
include_once "Sajax.php";

// include "conf_invent.inc.php";

include "fonc_js.inc.php";

/**********************************************************************************/
// $authlink_modif = @mysql_connect($dbhostinvent,$dbuserinvent,$dbpassinvent);
// @mysql_select_db($dbnameinvent) or die("Impossible de se connecter &#224; la base $dbnameinvent.");


/***********************************************************************************************/	

// initialise la table affichage si elle est vide 
// if(!isset($affichage)) { 
	$affichage = array(Parc => 0, Stations => 0, Imprimantes => 0, Maintenance => 0, Reseau => 0);
// }  

// initialise la table parc_all si elle est vide
// if (!isset($parc_all)) { 
	$parc_all = array();
  	$list_parcs=search_machines("objectclass=groupOfNames","parcs");
  	if ( count($list_parcs)>0) {
		for ($loopaf=0; $loopaf < count($list_parcs); $loopaf++) {
			$parc_af = $list_parcs[$loopaf]["cn"];
			$_SESSION["parc_all"][$parc_af] = 0;
		}
  	}
// }


/*
if($_GET['parc_m']) {
	if ($_GET['affichage']=="1") {
		foreach ($_SESSION['parc_all'] as $k => $v) { 
 			$_SESSION['parc_all'][$k] = 1;
		}
	// $_SESSION['affichage']['Stations']=	
  	} elseif ($_SESSION['affichage'][$parc_m]=="0") {
		$_SESSION['affichage'][$parc_m]="1";
	} elseif ($_SESSION['affichage'][$parc_m]=="1") {
		$_SESSION['affichage'][$parc_m]="0";
	} else {
		$_SESSION['affichage'][$parc_m]="1";
	}	
}	


if ($_SESSION['parc_all'][$parc_a]=="0") {
	$_SESSION['parc_all'][$parc_a]="1";
} elseif ($_SESSION['parc_all'][$parc_a]=="1") {
	$_SESSION['parc_all'][$parc_a]="0";
} else {
	$_SESSION['parc_all'][$parc_a]="1";
}	

 print_r	($_SESSION['parc_all']);
 print_r	($_SESSION['affichage']);
*/

/*
if (is_admin("computers_is_admin",$login)=="Y") {
$titre=gettext("Aide en ligne");
	$texte=gettext("
                Vous êtes administrateur du serveur SE3.<BR>
                 Avec le menu ci-dessous, vous pouvez consulter les informations sur les parcs de machines ou les machines.
	");
	mkhelp($titre,$texte);
*/


echo "<h1>".gettext("Interface SambaEdu")."</h1>\n";

echo "<table border=0 width=\"100%\">\n";
echo "<tr><td>\n";
echo "<table border=0 width=\"100%\">\n";

// Couleurs des lignes
$color_bg="#003399";

/****************** Parcs **************************************************/
/*	
$imgdep = "img_all_parc";
echo "<tr bgcolor=\"$color_bg\"><td><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/plus.png\" ID=\"".$imgdep."\" onClick=\"return_list('all_parc','all_parc','affiche')\"><font color=\"FFFFFF\">&nbsp;&nbsp;";
echo gettext("Parcs")."</font></td>\n";

// Créer un nouveau parc
echo "<td align=\"center\">";
*/


// Test
$i="0";
exec("ls /var/www/se3/isgse/objets.d/*.inc",$files,$return);
for ($i=0; $i< count($files); $i++) {
	$objet="";
	$taille_tableau="";
	include ($files[$i]);

	$affiche_obj=$$basesql_obj;


	// Si affichage autorisé
	if ($affiche_obj != "0") {

		if ($color_bg=="#003399") { $color_bg="#0066FF"; } else { $color_bg="#003399"; }
		$parc=$name_id;
		$imgdep = "img_".$name_id;
		echo "<tr bgcolor=\"$color_bg\"><td><IMG  style=\"border: 0px solid ;\" SRC=\"../elements/images/plus.png\" ALT=\"All\" ID=\"".$imgdep."\" onClick=\"return_list('$parc','$parc','affiche')\"></a><font color=\"FFFFFF\">&nbsp;&nbsp;";
		echo $name_obj."</font></td>\n\n";
	
		// Affichage des boutons de commande ici
		echo "<td align=\"center\">\n";
		$taille_tableau = count($objet);
		foreach($objet as $key => $valeur) {
			$imgdep = "img_".$objet[$key][3];

			echo "<span title=\"".$objet[$key][1]."\"><IMG width=\"15\" height=\"15\" style=\"border: 0px solid ;\" SRC=\"../elements/images/".$objet[$key][0]."\" ID=\"".$imgdep."\" onclick=\"".$objet[$key][2]."\"></span>\n";
			echo "&nbsp;&nbsp;\n";
		}
		echo "</td></tr>\n";

		echo "<tr><td colspan=\"2\">\n";
		echo "<table border=0 width=\"100%\" ID=\"$name_id\">\n";
		echo "</table>\n";
		echo "</td></tr>\n";
	}
}									

///////////////// FIN ///////////////////////////////////////////
echo "</table>\n";

echo "</td></tr>\n";
echo "</table>\n";

include ("pdp.inc.php");
?>
