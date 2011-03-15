<?php

/**

* Upload de fond d'ecran personnalise

* @Version $Id$

* @Projet LCS / SambaEdu 

* @auteurs Stephane Boireau

* @Licence Distribue selon les termes de la licence GPL

* @note 

*/

/**

* @Repertoire: fond_ecran/
* file: fond_perso.php

*/	


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-fond',"/var/www/se3/locale");
textdomain ('se3-fonds');

//aide
$_SESSION["pageaide"]="Ressources_et_partages";


if ((is_admin("se3_is_admin",$login)=="Y") or
(ldap_get_right("fond_can_change",$login)=="Y")) {
	// Initialisation
	$dossier_upload_images="/var/lib/se3/fonds_ecran";
	//$chemin_scripts="/usr/share/se3/scripts"; // mkwall_perso.sh
	$chemin_scripts="/usr/share/se3/sbin";      // mkwall.sh

	$chemin_www_fonds_courants="Admin/fonds_ecran/courant";
	$dossier_www_fonds_courants="/var/www/se3/".$chemin_www_fonds_courants;

	//debug_var();

	echo "<h1>".gettext("Personnalisation du fond d'&#233;cran")."</h1>";

	if(!file_exists($dossier_www_fonds_courants)) {
		mkdir($dossier_www_fonds_courants);
	}

	$cible=isset($_POST['cible']) ? $_POST['cible'] : $login;
	$cible=preg_replace("/[^A-Za-z0-9\._-]/","",$cible);

	//if(!isset($_POST['image'])) {
	if(!isset($_POST['is_posted'])) {
		echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\" name=\"form1\" enctype=\"multipart/form-data\">\n";

		if(is_admin("se3_is_admin",$login)=="Y") {
			// Choix d'un utilisateur ou d'un groupe
			echo "<p>Vous avez la possibilit&#233; de choisir une image &#224; ins&#233;rer au centre du fond d'&#233;cran de l'utilisateur de votre choix.</p>\n";

			echo "<p>\n";
			//echo "<input type='radio' id='type_utilisateur' name='type' value='utilisateur' /><label for='type_utilisateur'> Utilisateur</label><br />\n";
			//echo "<input type='radio' id='type_groupe' name='type' value='groupe' /><label for='type_groupe'> Groupe</label><br />\n";
			echo "Login de l'utilisateur&nbsp;: <input type='text' name='cible' value='$cible' /><br />\n";
			echo "</p>\n";
		}
		else {
			echo "<p>Vous avez la possibilit&#233; de choisir une image &#224; ins&#233;rer au centre de votre fond d'&#233;cran.</p>\n";

			if(file_exists($dossier_www_fonds_courants."/".$login)) {
				echo "<p><a href='$chemin_www_fonds_courants/$login.jpg' target='_blank'>Votre fond d'&#233;cran actuel</a></p>\n";
			}
		}

		echo "<p>".gettext("Image").": <input type=\"file\" name=\"image\" enctype=\"multipart/form-data\" /><br />\n";
	
		echo "<input type=\"hidden\" name=\"is_posted\" value=\"y\">\n";

		echo "<input type=\"submit\" name=\"bouton_choix\" value=\"".gettext("Valider")."\"></p>\n";

		echo "</form>\n";

		echo "<p><br /></p>\n";
		echo "<p><i>NOTES&nbsp;</i>: ";
		echo "<span style='color:red'>A FAIRE: Possibilit&#233; de param&#233;trer les dimensions de l'insertion d'image (<i>avec valeur max pour conserver le nom_pr&#233;nom,...</i>)</span></p>\n";

	}
	else {
		if($cible=='') {
			echo "<p><span style='color:red'>La cible choisie n'est pas valide";
			if(isset($POST['cible'])) {echo "&nbsp;: ".$POST['cible'];}
			echo "</span>";
			echo "</p>\n";

			echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a></p>\n";
			include ("pdp.inc.php");
			die();
		}

		$tmp_image=$HTTP_POST_FILES['image']['tmp_name'];
		$image=$HTTP_POST_FILES['image']['name'];
		$size_image=$HTTP_POST_FILES['image']['size'];

		$cible=isset($_POST['cible']) ? $_POST['cible'] : $login;

		// Retrouver le groupe d'appartenance
		// Inutile... la recherche est faite dans mkwall_perso.sh ou mkwall.sh

		/*
		echo "\$tmp_image=$tmp_image<br />";
		echo "\$image=$image<br />";
		echo "\$size_image=$size_image<br />";
		*/

		if(is_uploaded_file($tmp_image)){
			$dest_file="$dossier_upload_images/tmp_$cible.jpg";
			$source_file=stripslashes("$tmp_image");
			$res_copy=copy("$source_file" , "$dest_file");
			if(!$res_copy) {
				echo "<p style='color:red'>Erreur lors de la copie de l'image.</p>\n";
				echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a></p>\n";
				include ("pdp.inc.php");
				die();
			}
		}
		else {
			echo "<p style='color:red'>Erreur lors de l'upload de l'image.<br />Cela peut se produire avec une image trop volumineuse.</p>\n";
			include ("pdp.inc.php");
			die();
		}
		echo "<p>Le fichier a ete uploade et copie.</p>\n";

		echo "<p>Lancement du traitement...<br />\n";
		//exec("/usr/bin/sudo $chemin_scripts/mkwall_perso.sh $cible");
		exec("/usr/bin/sudo $chemin_scripts/mkwall.sh $cible",$retour);

		foreach($retour as $key => $value) {
			//echo "<span style='color:green'>\$retour[$key]=$value</span><br />";
			echo "<span style='color:green; margin-left: 3em;'>$value</span><br />";
		}

		//echo "$dossier_www_fonds_courants/$cible.jpg<br />";

		/*
		// Les tests d'existence echouent
		// peut-etre parce que ce sont des liens symboliques ?

		if(file_exists($dossier_www_fonds_courants."/".$cible."jpg")) {
			if(is_admin("se3_is_admin",$login)=="Y") {
				echo "<p>Le nouveau fond d'&#233;cran de '$cible'&nbsp;:<br /><a href='../$chemin_www_fonds_courants/$cible.jpg' target='_blank'><img src='../$chemin_www_fonds_courants/$cible.jpg' /></a></p>\n";
			}
			else {
				echo "<p>Votre nouveau fond d'&#233;cran&nbsp;:<br /><a href='../$chemin_www_fonds_courants/$cible.jpg' target='_blank'><img src='../$chemin_www_fonds_courants/$cible.jpg' /></a></p>\n";
			}
		}

		if(file_exists("/var/se3/Docs/media/fonds_ecran/".$cible."jpg")) {
			echo "file_exists(\"/var/se3/Docs/media/fonds_ecran/\".$cible.\"jpg\"<br />";
		}
		*/

		if(is_admin("se3_is_admin",$login)=="Y") {
			echo "<p>Le nouveau fond d'&#233;cran du compte '<b>$cible</b>'&nbsp;:<br /><span style='margin-left: 3em;'><a href='../$chemin_www_fonds_courants/$cible.jpg' target='_blank'><img src='../$chemin_www_fonds_courants/$cible.jpg' /></a></span></p>\n";
		}
		else {
			echo "<p>Votre nouveau fond d'&#233;cran&nbsp;:<br /><span style='margin-left: 3em;'><a href='../$chemin_www_fonds_courants/$cible.jpg' target='_blank'><img src='../$chemin_www_fonds_courants/$cible.jpg' /></a></span></p>\n";
		}

		echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a></p>\n";
	}

} // Fin if is_admin
include ("pdp.inc.php");
?>
