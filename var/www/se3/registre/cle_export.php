<?php
		
   /**
   
   * Gestion des cles pour clients Windows (mise a jour des cles)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: cle_export.php

  */	




require "include.inc.php";
connexion();
$act=$_GET['action'];
if (!$act) { $act=$_POST['action'];}


switch($act) {
	
	default:
	include "entete.inc.php";
	include "ldap.inc.php";
	include "ihm.inc.php";
	
	require_once ("lang.inc.php");
	bindtextdomain('se3-registre',"/var/www/se3/locale");
	textdomain ('se3-registre');
	if (ldap_get_right("computers_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

	$_SESSION["pageaide"]="Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";
	
	echo "<a href=\"cle_export.php?action=export\">".gettext("Exporter mes cl&#233s ?")."</a>";
	break;

	case "export":
	$content_dir = '/tmp/';
	$fichier_mod_xml = $content_dir . "rules.xml";
	if (file_exists($fichier_mod_xml)) unlink($fichier_mod_xml);

	$get= fopen ($fichier_mod_xml, "w+");
	
	$ligne="<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<se3mod>\n<nom>SE3</nom>\n<version>V 0.1</version>\n<categories>\n";
	fputs($get,$ligne);
 	$query1="SELECT categorie from corresp group by categorie";
 	$resultat1 = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
 	
	while ($row = mysqli_fetch_array($resultat1)) {
		$ligne="<categorie nom=\"$row[0]\">\n<regles>\n<Regle ClasseObjet=\"INFO\">\n<OS>252</OS>\n<Intitule>g&#233;n&#233;ral</Intitule>\n<Composant>LIGNE</Composant>\n<ValeurSiCoche/>\n<ValeurSiDecoche/>\n<Commentaire/>\n </Regle>\n";
    	fputs($get,$ligne);
		$query2="SELECT sscat from corresp where categorie='$row[0]' group by sscat";
		$resultat2 = mysqli_query($GLOBALS["___mysqli_ston"], $query2);
		
		while ($row2 = mysqli_fetch_array($resultat2)) {
 			if ($row2[0]) {
				$ligne="<Regle ClasseObjet=\"INFO\">\n<OS>252</OS>\n<Intitule>\"$row2[0]\"</Intitule>\n<Composant>LIGNE</Composant>\n <ValeurSiCoche/>\n <ValeurSiDecoche/>\n<Commentaire/>\n</Regle>\n";
				fputs($get,$ligne);
                $ajoutquery=" and sscat=\"$row2[0]\" ";
 			} else  { $ajoutquery= " and sscat=\"\" "; }
		
			$query3="SELECT Intitule,chemin,OS,type,genre,valeur,antidote,comment from corresp where categorie='$row[0]' ".$ajoutquery." order by type,genre,OS,valeur";
			$resultat3 = mysqli_query($GLOBALS["___mysqli_ston"], $query3);
			while ($row3=mysqli_fetch_array($resultat3)) {
 				$cheminpascomp=$row3['chemin'];
 				$chemin=explode("\\",$row3['chemin']);
 				$j=count($chemin)-1;
 				$cheminpascomp=$chemin[0];
 				for ($i=1;$i<$j;$i++) {
 					$cheminpascomp=$cheminpascomp."\\".$chemin[$i];
 				}
 				$variable=$chemin[$j];

 				$ligne="<Regle ClasseObjet=\"REGISTRE\">\n<OS>".$row3['OS']."</OS>\n<Chemin>reg:///$cheminpascomp</Chemin>\n<Intitule>".$row3['Intitule']."</Intitule>\n";
 				fputs($get,$ligne);
				if ($row3['type']=="restrict") { $type="restrict" ;} else {$type="config"; }
    			$ligne="<Composant>$type</Composant>\n<Variable type=\"".$row3['genre']."\">$variable</Variable>\n";
    			fputs($get,$ligne);
    			if (isset($row3['valeur'])) {$ligne="<ValeurSiCoche>".$row3['valeur']."</ValeurSiCoche>\n"; } else {$ligne="<ValeurSiCoche/>\n";}
    			fputs($get,$ligne);
    			if (isset($row3['antidote'])) {$ligne="<ValeurSiDecoche>".$row3['antidote']."</ValeurSiDecoche>\n";} else {$ligne="<ValeurSiDeCoche/>\n";}
    			fputs($get,$ligne);
    			if (isset($row3['comment'])) {$ligne="<commentaire>".$row3['comment']."</commentaire>\n";} else {$ligne="<commentaire/>\n";}
    			fputs($get,$ligne); 
				$ligne="</Regle>\n";
				fputs($get,$ligne); 
 			}
		}


 		$ligne="</regles>\n</categorie>\n";
        fputs($get,$ligne); 
	}
	$ligne="</categories>\n</se3mod>\n";
    fputs($get,$ligne); 
    fclose($get);
	$get= fopen ($fichier_mod_xml, "r");
	header("Content-type: application/force-download");
	header("Content-Length: ".filesize($fichier_mod_xml));
	header("Content-Disposition: attachment; filename=rules.xml");
	readfile($fichier_mod_xml);
	if (file_exists($fichier_mod_xml)) unlink($fichier_mod_xml);
	((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);

	include "entete.inc.php";
	include "ldap.inc.php";
	include "ihm.inc.php";
	
	require_once ("lang.inc.php");
	bindtextdomain('se3-registre',"/var/www/se3/locale");
	textdomain ('se3-registre');
	if (ldap_get_right("computers_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

	$_SESSION["pageaide"]="Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";

	break;
}
retour();

include("pdp.inc.php");
?>
