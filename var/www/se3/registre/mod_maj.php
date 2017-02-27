<?php

   /**
   
   * Gestion des cles pour clients Windows (mise a jour des modeles)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: mod_maj.php

  */	



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
require "include.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');

echo "<h1>Importation des groupes de cl&#233s</h1>";
// connexion();

if (ldap_get_right("computers_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

//Aide
$_SESSION["pageaide"]="Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";

$act=$_GET['action'];
if (!$act) { $act=$_POST['action'];}

switch($act) {

	default:
	echo "<a href=\"mod_maj.php?action=maj\">".gettext("Effectuer la mise &#224; jour des restrictions ?")."</a>";
	break;

	case "file":
	if( isset($_POST['upload']) ) { // si formulaire soumis 
    		if (file_exists("/tmp/mod.gz")) unlink("/tmp/mod.gz");

    			$content_dir = '/tmp/'; // dossier ou sera deplace le fichier
    			$tmp_file = $_FILES['fichier']['tmp_name'];
    			if( !is_uploaded_file($tmp_file) ) {
        			exit(gettext("Le fichier est introuvable"));
    			}
    			// on verifie maintenant l'extension
    		$type_file = $_FILES['fichier']['type'];
    		if( !strstr($type_file, 'xml')) {
        		exit(gettext("Le fichier n'est pas un fichier xml"));
    		}
    		// on copie le fichier dans le dossier de destination
    		$name_file = $_FILES['fichier']['name'];

    		if( !move_uploaded_file($tmp_file, $content_dir . $name_file) ) {
        		exit(gettext("Impossible de copier le fichier dans")." $content_dir");
    		}
    		echo gettext("Le fichier a bien &#233;t&#233; upload&#233;")."<br>";
    		$fichier_xml = $content_dir . $name_file;
	}

	break;

	case "maj":
		$fichier_xml = "/usr/share/se3/data/grp.xml";

	break;
}

if (($fichier_xml)&&(!$retval_mod)) {
	
	
   	/**

	   * Fonctions Analyse le debut d'un fichier XML
	
	   * @Parametres 
	   * @Return  
   
   	*/
	function gestionnaire_debut($analyseur, $nom, $attribut) {
    		global $nb;
    		global $ligne;
   		$nb++;
    		if(sizeof($attribut)) {
      			foreach($attribut as $cle => $valeur) { $ligne=$ligne.$valeur."-:-"; }
    		}
  	}
  	
   	/**

	   * Fonctions Analyse la fin d'un fichier XML
	
	   * @Parametres 
	   * @Return  
   
   	*/
	function gestionnaire_fin($analyseur, $nom) {
    		global $nb;
    		global $ligne;
   	 	$nb--;
    		if ($nb<4) { $ligne=$ligne.";&;"; } else {$ligne=$ligne."--";}
  	}
  	
   	/**

	   * Fonctions Analyse le texte d'un fichier XML
	
	   * @Parametres 
	   * @Return  
   
   	*/
	function gestionnaire_texte($analyseur, $texte) {
    		global $nb;
    		global $ligne;
    		if ($nb>2) { $ligne= $ligne.$texte ; }
  	}
  
  	$nb = 0;
  	$analyseur_xml = xml_parser_create();
  	xml_set_element_handler($analyseur_xml,"gestionnaire_debut", "gestionnaire_fin");
  	xml_set_character_data_handler($analyseur_xml,"gestionnaire_texte");
  	if (!($id_fichier = fopen($fichier_xml, "r"))) {
    		die(gettext("Impossible d'ouvrir le fichier XML !"));
  	}

  	while ($donnee = fread($id_fichier, filesize($fichier_xml))) {
    		if (!xml_parse($analyseur_xml, $donnee, feof($id_fichier))) {
      			die(sprintf(gettext("Une erreur XML %s s'est produite &#224 la ligne %d et &#224 la colonne %d."),xml_error_string(xml_get_error_code($analyseur_xml)),xml_get_current_line_number($analyseur_xml),xml_get_current_column_number($analyseur_xml)));
    		}
  	}
  	xml_parser_free($analyseur_xml);
     	connexion();
    	$ligne=preg_replace("/(\r\n)|(\n)|(\r)/","",$ligne);

        //Passage UTF-8 donc plus necessaire
	//if (mb_detect_encoding($ligne,"UTF-8")) {
        //        $ligne=mb_convert_encoding($ligne,'ISO-8859-1','UTF-8');
	//}
    	
	$categorie=explode(";&;",$ligne);
    	for ($j;$j<count($categorie)+5;$j++) {
    		if ((preg_match("/-:-/",$categorie[$j]))) {
    			if ($nom) {
				echo " $modif ".gettext("cl&#233s modifi&#233es")." , $cree ".gettext("cl&#233s ajout&#233s")." , $ignore ".gettext("cl&#233s ignor&#233es pour le groupe de cl&#233s")." $nom";
			}
    			$nom="";
    			$cree=0;
    			$modif=0;
    			$ignore=0;
    			list($nom,$reste)=preg_split("/-:-/",$categorie[$j]);
    			list($cle,$valeur)=preg_split("/--/",$reste);
    			echo "<h2>$nom</h2>";
    		} else {
    			list($cle,$valeur)=preg_split("/--/",$categorie[$j]);
    		}
    		
		if ($cle) {
    			$cle=ajoutedoublebarre(trim($cle));
    			$query="SELECT `CleID` FROM `corresp` WHERE `chemin`='$cle'";
    			$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
    			if (mysqli_num_rows($resultat)) {
    				$row=mysqli_fetch_row($resultat);
    				$query2="SELECT `cle` FROM `modele` WHERE `mod`= '$nom' and `cle` = '$row[0]' ;";
    				$resultat2 = mysqli_query($GLOBALS["___mysqli_ston"], $query2);
    				if (mysqli_num_rows($resultat2) and ($nom)) {
       					$query1 = "UPDATE `modele` SET `etat` = '$valeur' WHERE `cle` = '$row[0]' AND `mod` = '$nom';";
      		 			$resultat1=mysqli_query($GLOBALS["___mysqli_ston"], $query1);
       					$modif++;
     				} else {
        				$query="INSERT INTO modele( `etat`, `cle`, `mod` ) VALUES ('$valeur','$row[0]','$nom');";
        				$insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        				$cree++;
     				}
    			} else {
    				echo gettext("Ignor&#233e : Cette cl&#233  n'existe pas:")." $cle <br>";
          			$ignore++;
    			}
    		}
    	}
    	
	if ($nom) {
    		echo " $modif ".gettext("cl&#233s modifi&#233es")." , $cree ".gettext("cl&#233s ajout&#233s")." , $ignore ".gettext("cl&#233s ignor&#233es pour le groupe de cl&#233s")." $nom";
    	}
	if ($fichier_xml != "/usr/share/se3/data/grp.xml")
		unlink($fichier_xml);
}

include("pdp.inc.php");
?>
