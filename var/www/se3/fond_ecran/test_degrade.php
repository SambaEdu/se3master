<?php	

/**

* Interface de gestion du fond d'ecran
* @Version $Id$ 


* @Projet LCS / SambaEdu 

* @auteurs  Stephane Boireau

* @Licence Distribue selon les termes de la licence GPL

* @note 

*/

/**

* @Repertoire: fond_ecran
* file: test_degrade.php

*/	

	include "entete.inc.php";
	include "ldap.inc.php";
	include "ihm.inc.php";

	require_once("lang.inc.php");
	bindtextdomain('se3-fond',"/var/www/se3/locale");
	textdomain ('se3-fond');

	// Chemin a recuperer par la suite depuis MySQL (ou depuis un fichier texte)
	//$chemin_param_fond="/usr/share/se3/etc/fonds_ecran";
	$chemin_param_fond="/etc/se3/fonds_ecran";
	//$dossier_upload_images="/var/remote_adm";
	$chemin_scripts="/usr/share/se3/scripts";
	$chemin_tmp_img_png="/var/www/se3/Admin/fonds_ecran";
	$chemin_html_tmp_img_png="../Admin/fonds_ecran";

	echo "<h1>".gettext("Test de d&#233;grad&#233;")."</h1>\n";

	if (is_admin("se3_is_admin",$login)!="Y") {
		echo "<p>".gettext("Vous n'&#234;tes pas autoris&#233; &#224; acc&#233;der &#224; cette page.")."</p>\n";
		include ("pdp.inc.php");
		exit();
	}

	$titre=gettext("Aide en ligne");
	$texte=gettext("
		Vous &#234;tes administrateur du serveur SE3.<br>
		Cette page ne fait qu'afficher une image d'un d&#233;grad&#233; test g&#233;n&#233;r&#233;e d'apr&#232;s les param&#232;tres de couleurs et de dimensions pass&#233;s en param&#232;tres.<br>
	");
	mkhelp($titre,$texte);


	if((!isset($_POST['couleur1']))||(!isset($_POST['couleur2']))||(!isset($_POST['hauteur']))||(!isset($_POST['largeur']))||(!isset($_POST['groupe']))){
		echo "<p><b>".gettext("ERREUR").":</b> ".gettext("Une des variable n'est pas renseign&#233;e").".</p>\n";
		include ("pdp.inc.php");
		exit();
	}

	// Recuperation des variables:
	$couleur1=$_POST['couleur1'];
	$couleur2=$_POST['couleur2'];
	$hauteur=$_POST['hauteur'];
	$largeur=$_POST['largeur'];
	$groupe=$_POST['groupe'];

	if((strlen(preg_replace("/[0-9]/","",$hauteur))!=0)||(strlen(preg_replace("/[0-9]/","",$largeur))!=0)){
		echo "<p><b>".gettext("ERREUR").":</b> ".gettext("Les dimensions ne sont pas correctes").".</p>\n";
		include ("pdp.inc.php");
		exit();
	}

	echo "<h2>".gettext("Param&#232;tres pour")." $groupe</h2>\n";

	echo "<table border=\"1\">\n";

	echo "<tr style=\"font-weight:bold; text-align:center;\">\n";
	echo "<td>".gettext("Largeur")."</td>\n";
	echo "<td>".gettext("Hauteur")."</td>\n";
	echo "<td>".gettext("Couleur")." 1</td>\n";
	echo "<td>".gettext("Couleur")." 2</td>\n";
	echo "</tr>\n";

	echo "<tr style=\"text-align:center;\">\n";
	echo "<td>$largeur</td>\n";
	echo "<td>$hauteur</td>\n";
	echo "<td>$couleur1</td>\n";
	echo "<td>$couleur2</td>\n";
	echo "</tr>\n";

	echo "</table>\n";

	echo "<h2>".gettext("Image g&#233;n&#233;r&#233;e")."</h2>\n";

	if(!file_exists($chemin_tmp_img_png)){
		mkdir($chemin_tmp_img_png);
	}

	exec("/usr/bin/convert -size ".$largeur."x".$hauteur." gradient:$couleur1-$couleur2 $chemin_tmp_img_png/$groupe.png");

	echo "<p><img src=\"$chemin_html_tmp_img_png/$groupe.png\" width=\"$largeur\" height=\"$hauteur\"></p>\n";

	//Fin de page:
	include ("pdp.inc.php");
?>
