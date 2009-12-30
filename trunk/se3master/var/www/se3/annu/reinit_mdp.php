<?php

   /**
   
   * Reinitialisation/Modification des mots de passe
   * @Version $Id: infomdp.php 4187 2009-06-19 09:22:12Z gnumdk $ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: reinit_mdp.php

  */	



require ("entete.inc.php");
require ("ihm.inc.php");

require("config.inc.php");
require("ldap.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-infos',"/var/www/se3/locale");
textdomain ('se3-infos');

// aide en ligne
$_SESSION["pageaide"]="Annuaire";


if (is_admin("annu_is_admin",$login)!="Y") {
	die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");
}

echo "<h1>".gettext("R&#233;initialisation/modification de mots de passe")."</h1>\n";

//==========================================================
// Phase de choix des parametres
if(!isset($_POST['is_posted'])) {
	//echo "<center>\n";
	echo "<form action=\"".$_SERVER['PHP_SELF']."\" enctype='multipart/form-data' method=\"post\">\n";

	echo "<p>Cette page est destin&#233;e &#224; r&#233;initialiser/modifier les mots de passe par lots.</p>\n";
	echo "<p>Vous pouvez&nbsp;:</p>\n";
	echo "<table summary='Choix du mode'>\n";
	echo "<tr>\n";
	echo "<td valign='top'><input type='radio' name='reinit_mode' id='reinit_mode_naissance' value='naissance' onchange='teste_radio()' checked /></td><td><label for='reinit_mode_naissance'> r&#233;initialiser les mots de passe &#224; la date de naissance pour un ou des groupes.</label></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td valign='top'><input type='radio' name='reinit_mode' id='reinit_mode_alea' value='alea' onchange='teste_radio()' /></td><td><label for='reinit_mode_alea'> modifier al&#233;atoirement les mots de passe pour un ou des groupes.</label></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td valign='top'><input type='radio' name='reinit_mode' id='reinit_mode_csv' value='csv' onchange='teste_radio()' /></td><td><label for='reinit_mode_csv'> imposer les mots de passe d'apr&#232;s un fichier CSV au format&nbsp;: <b>LOGIN;MOTDEPASSE;</b></label><br />\n";
	echo "Le point-virgule en fin de ligne est recommand&#233; pour &#233;viter des blagues avec les fins de lignes DO$/Unix.<br />\n";
	echo "Fichier CSV&nbsp;: <input type='file' name='fich_csv_reinit' onfocus=\"document.getElementById('reinit_mode_csv').checked=true; teste_radio();\" />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";


	// Etablissement des listes des groupes disponibles
	$list_groups=search_groups("(&(cn=*) $filter )");
	// Etablissement des sous listes de groupes :
	$j =0; $k =0;
	$m = 0; $n=0;
	for ($loop=0; $loop < count ($list_groups) ; $loop++) {
		// Classe
		if ( ereg ("Classe_", $list_groups[$loop]["cn"]) ) {
			$classe[$j]["cn"] = $list_groups[$loop]["cn"];
			$classe[$j]["description"] = $list_groups[$loop]["description"];
			$j++;
		}
		// Equipe
		elseif ( ereg ("Equipe_", $list_groups[$loop]["cn"]) ) {
			$equipe[$k]["cn"] = $list_groups[$loop]["cn"];
			$equipe[$k]["description"] = $list_groups[$loop]["description"];
			$k++;
		}
		// Matiere
		elseif ( ereg ("Matiere_", $list_groups[$loop]["cn"]) ) {
			$matiere[$n]["cn"] = $list_groups[$loop]["cn"];
			$matiere[$n]["description"] = $list_groups[$loop]["description"];
			$n++;
		}
		// Autres
		elseif (!ereg ("^overfill", $list_groups[$loop]["cn"]) && !ereg ("^lcs-users", $list_groups[$loop]["cn"]) &&
		//!ereg ("^admins", $list_groups[$loop]["cn"]) &&
		!ereg ("Cours_", $list_groups[$loop]["cn"]) &&
		!ereg ("^system", $list_groups[$loop]["cn"]) &&
		!ereg ("^slis", $list_groups[$loop]["cn"]) &&
		!ereg ("^machines", $list_groups[$loop]["cn"])) {
			$autres[$m]["cn"] = $list_groups[$loop]["cn"];
			$autres[$m]["description"] = $list_groups[$loop]["description"];
			$m++;
		}
	}

	echo "<div id='div_choix_groupes'>\n";
	echo "<p>Choisissez les groupes auxquels appliquer le traitement&nbsp;:</p>\n";
	echo "<table border='0' cellspacing='10' summary='Choix des groupes'>\n";
	echo "<thead>\n";
	echo "<tr>\n";
	echo "<td>".gettext("Classes")."</td>\n";
	echo "<td>".gettext("Equipes")."</td>\n";
	echo "<td>".gettext("Mati&#232;res")."</td>\n";
	echo "<td>".gettext("Autres")."</td>\n";
	echo "</tr>\n";
	echo "</thead>\n";

	echo "<tbody>\n";
	echo "<tr>\n";
	echo "<td valign='top'>\n";
	echo "<select name= \"classe_gr[]\" size=\"8\" multiple=\"multiple\">\n";
	for ($loop=0; $loop < count ($classe) ; $loop++) {
		echo "<option value=".$classe[$loop]["cn"].">".$classe[$loop]["cn"]."</option>\n";
	}
	echo "</select>\n";
	echo "</td>\n";

	echo "<td valign=\"top\">\n";
	echo "<select name= \"equipe_gr[]\" size=\"8\" multiple=\"multiple\">\n";
	for ($loop=0; $loop < count ($equipe) ; $loop++) {
		echo "<option value=".$equipe[$loop]["cn"].">".$equipe[$loop]["cn"]."</option>\n";
	}
	echo "</select>\n";
	echo "</td>\n";
	
	echo "<td valign=\"top\">\n";
	echo "<select name= \"matiere_gr[]\"  size=\"8\" multiple=\"multiple\">\n";
	for ($loop=0; $loop < count ($matiere) ; $loop++) {
		echo "<option value=".$matiere[$loop]["cn"].">".$matiere[$loop]["cn"]."</option>\n";
	}
	echo "</select>\n";
	echo "</td>\n";
	
	echo "<td valign=\"top\">";
	echo "<select name=\"autres_gr[]\" size=\"8\" multiple=\"multiple\">";
	for ($loop=0; $loop < count ($autres) ; $loop++) {
		echo "<option value=".$autres[$loop]["cn"].">".$autres[$loop]["cn"]."</option>\n";
	}
	echo "</select>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<div id=\"attribution\" align='center'>\n";
	echo "<input type=\"hidden\" name=\"is_posted\" value=\"1\">\n";
	echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
	echo "<input type=\"reset\" value=\"".gettext("R&#233;initialiser")."\">\n";
	echo "</div>\n";

	echo "</div>\n";

	echo "<div id=\"div_validation\" align='center'>\n";
	echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
	echo "</div>\n";

	echo "</form>\n";

	echo "<script type='text/javascript'>
	function teste_radio() {
		if(document.getElementById('reinit_mode_naissance').checked==true) {
			document.getElementById('div_choix_groupes').style.display='';
			document.getElementById('div_validation').style.display='none';
		}

		if(document.getElementById('reinit_mode_alea').checked==true) {
			document.getElementById('div_choix_groupes').style.display='';
			document.getElementById('div_validation').style.display='none';
		}

		if(document.getElementById('reinit_mode_csv').checked==true) {
			document.getElementById('div_choix_groupes').style.display='none';
			document.getElementById('div_validation').style.display='';
		}
	}

	teste_radio();
</script>\n";

	//echo "</center>\n";
	include ("pdp.inc.php");
	die();
}
//==========================================================

// Phase de traitement

//echo "<center>\n";

$reinit_mode=isset($_POST['reinit_mode']) ? $_POST['reinit_mode'] : NULL;

if(!isset($reinit_mode)) {
	echo "<p style='color:red'>Le mode de r&#233;initialisation/changement des mots de passe n'a pas &#233;t&#233; choisi.</p>";
	echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a></p>";

	include ("pdp.inc.php");
	die();
}

$tab_reinit_mode=array('naissance','alea','csv');
if(!in_array($reinit_mode, $tab_reinit_mode)) {
	echo "<p style='color:red'>Le mode de r&#233;initialisation/changement des mots de passe choisi est invalide.</p>";
	echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a></p>";

	include ("pdp.inc.php");
	die();
}


if($reinit_mode=='csv') {
	if($_FILES["fich_csv_reinit"]["name"]=="") {
		echo "<p style='color:red;'><b>ERREUR:</b> Aucun fichier n'a &#233;t&#233; fourni!</p>\n";
		echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a>.</p>\n";
		include ("pdp.inc.php");
		exit();
	}

	$tmp_csv_file=$HTTP_POST_FILES['fich_csv_reinit']['tmp_name'];
	$csv_file=$HTTP_POST_FILES['fich_csv_reinit']['name'];
	$size_ecsv_file=$HTTP_POST_FILES['fich_csv_reinit']['size'];

	if(($csv_file!='')&&($tmp_csv_file=='')) {
		echo "<p>L'upload du fichier <span style='color:red;'>$csv_file</span> a semble-t-il &eacute;chou&eacute;.</p>";

		$upload_max_filesize=ini_get('upload_max_filesize');
		$post_max_size=ini_get('post_max_size');

		echo "<p>Il se peut que le fichier fourni ait &eacute;t&eacute; trop volumineux.<br />PHP est actuellement param&eacute;tr&eacute; avec:<br />\n";
		echo "</p>\n";
		echo "<blockquote>\n";
		echo "<span style='color:blue;'>upload_max_filesize</span>=<span style='color:green;'>".$upload_max_filesize."</span><br />\n";
		echo "<span style='color:blue;'>post_max_size</span>=<span style='color:green;'>".$post_max_size."</span><br />\n";
		echo "</blockquote>\n";
		echo "<p>\n";
		echo "Si ces valeurs sont insuffisantes pour vos fichiers XML, il est possible de modifier les valeurs limites dans <span style='color:green;'>/etc/php5/apache2/php.ini</span>\n";
		echo "</p>\n";

		echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a>.</p>\n";
		include ("pdp.inc.php");
		exit();
	}

    $dossier_tmp="/var/lib/se3/import_comptes";
	$dest_file="$dossier_tmp/fichier_csv_reinit_mdp.csv";
	if(file_exists($dest_file)){
		unlink($dest_file);
	}

	if(is_uploaded_file($tmp_csv_file)){
		$source_file=stripslashes("$tmp_csv_file");
		$res_copy=copy("$source_file" , "$dest_file");

		echo "<h4>".gettext("Modification des mots de passe d'apr&#232;s le fichier CSV fourni")."</h4>\n";

		echo "<pre class='listing'>";
		system ("/usr/bin/sudo /usr/share/se3/sbin/se3_reinit_mdp.sh 'csv=$dest_file' 'nettoyage'");
		echo "</pre>\n";
		echo "<hr />\n";

	}
}
else {

	$option="";
	if($reinit_mode=='alea') {
		$option=" 'alea'";
	}

	// Liste des groupes a traiter
	$classe_gr=isset($_POST['classe_gr']) ? $_POST['classe_gr'] : array();
	$equipe_gr=isset($_POST['equipe_gr']) ? $_POST['equipe_gr'] : array();
	$matiere_gr=isset($_POST['matiere_gr']) ? $_POST['matiere_gr'] : array();
	$autres_gr=isset($_POST['autres_gr']) ? $_POST['autres_gr'] : array();

	// On lance l'operation pour chaque groupe demande !!!

	if (count($classe_gr) ) {
		foreach ($classe_gr as $grp){
			if($reinit_mode=='alea') {
				echo "<h4>".gettext("Modification des mots de passe (<i>pour une valeur al&#233;atoire</i>) pour les membres du groupe ".$grp."&nbsp;:")."</h4>\n";
			}
			else {
				echo "<h4>".gettext("R&#233;initialisation des mots de passe &#224; la date de naissance pour les membres du groupe ".$grp."&nbsp;:")."</h4>\n";
			}
			echo "<pre class='listing'>";
			system ("/usr/bin/sudo /usr/share/se3/sbin/se3_reinit_mdp.sh $grp $option");
			echo "</pre>\n";
			echo "<hr />\n";
		}
	}

	if (count($equipe_gr) ) {
		foreach ($equipe_gr as $grp){
			if($reinit_mode=='alea') {
				echo "<h4>".gettext("Modification des mots de passe (<i>pour une valeur al&#233;atoire</i>) pour les membres du groupe ".$grp."&nbsp;:")."</h4>\n";
			}
			else {
				echo "<h4>".gettext("R&#233;initialisation des mots de passe &#224; la date de naissance pour les membres du groupe ".$grp."&nbsp;:")."</h4>\n";
			}
			echo "<pre class='listing'>";
			system ("/usr/bin/sudo /usr/share/se3/sbin/se3_reinit_mdp.sh $grp $option");
			echo "</pre>\n";
			echo "<hr />\n";
		}
	}


	if (count($matiere_gr) ) {
		foreach ($matiere_gr as $grp){
			if($reinit_mode=='alea') {
				echo "<h4>".gettext("Modification des mots de passe (<i>pour une valeur al&#233;atoire</i>) pour les membres du groupe ".$grp."&nbsp;:")."</h4>\n";
			}
			else {
				echo "<h4>".gettext("R&#233;initialisation des mots de passe &#224; la date de naissance pour les membres du groupe ".$grp."&nbsp;:")."</h4>\n";
			}
			echo "<pre class='listing'>";
			system ("/usr/bin/sudo /usr/share/se3/sbin/se3_reinit_mdp.sh $grp $option");
			echo "</pre>\n";
			echo "<hr />\n";
		}
	}


	if (count($autres_gr) ) {
		foreach ($autres_gr as $grp){
			if($reinit_mode=='alea') {
				echo "<h4>".gettext("Modification des mots de passe (<i>pour une valeur al&#233;atoire</i>) pour les membres du groupe ".$grp."&nbsp;:")."</h4>\n";
			}
			else {
				echo "<h4>".gettext("R&#233;initialisation des mots de passe &#224; la date de naissance pour les membres du groupe ".$grp."&nbsp;:")."</h4>\n";
			}
			echo "<pre class='listing'>";
			system ("/usr/bin/sudo /usr/share/se3/sbin/se3_reinit_mdp.sh $grp $option");
			echo "</pre>\n";
			echo "<hr />\n";
		}
	}
}
//echo "</center>\n";

include ("pdp.inc.php");

?>

