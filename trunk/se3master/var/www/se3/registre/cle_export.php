<?php
		
   /**
   
   * Gestion des cles pour clients Windows (mise a jour des cles)
   * @Version $Id: cle_export.php 2949 2008-05-04 18:45:49Z plouf $ 
   
  
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
	$ligne="<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?><se3mod><nom>SE3</nom><version>V 0.1</version><categories>";
 	$query1="SELECT categorie from corresp group by categorie";
 	$resultat1 = mysql_query($query1);
 	
	while ($row = mysql_fetch_array($resultat1)) {
		$ligne=$ligne."<categorie nom=\"$row[0]\"><regles><Regle ClasseObjet=\"INFO\"><OS>252</OS><Chemin/><Intitule>g&#233;n&#233;ral</Intitule><Composant>LIGNE</Composant><Variable/><ValeurSiCoche/><ValeurSiDecoche/><Commentaire/></Regle>";
		$query2="SELECT sscat from corresp where categorie='$row[0]' group by sscat";
		$resultat2 = mysql_query($query2);
		
		while ($row2 = mysql_fetch_array($resultat2)) {
 			if ($row2[0]) {
				$ligne=$ligne."<Regle ClasseObjet=\"INFO\"><OS>252</OS><Chemin/><Intitule>$row2[0]</Intitule><Composant>LIGNE</Composant><Variable/><ValeurSiCoche/><ValeurSiDecoche/><Commentaire/></Regle>";
				$ajoutquery=" and sscat=\"$row2[0]\" ";
 			} else  { $ajoutquery= " and sscat=\"\" "; }
		
			$query3="SELECT Intitule,chemin,OS,type,genre,valeur,antidote,comment from corresp where categorie='$row[0]' ".$ajoutquery;
			$resultat3 = mysql_query($query3);
			while ($row3=mysql_fetch_row($resultat3)) {
 				$cheminpascomp=$row3[1];
 				$chemin=explode("\\",$row3[1]);
 				$j=count($chemin)-1;
 				$cheminpascomp=$chemin[0];
 				for ($i=1;$i<$j;$i++) {
 					$cheminpascomp=$cheminpascomp."\\".$chemin[$i];
 				}
 				$variable=$chemin[$j];

 				$ligne=$ligne."<Regle ClasseObjet=\"REGISTRE\"><OS>$row3[2]</OS><Chemin>reg:///$cheminpascomp</Chemin><Intitule>$row3[0]</Intitule>";
				if ($row3[3]=="restrict") { $type="CHECKBOX" ;} else {$type="SELECT"; }
      				if (trim($row3[4])=="REG_SZ") {$genre="CHAINE" ;}
    				if (trim($row3[4])=="REG_DWORD") { $genre="DWORD"; }

    				$ligne=$ligne."<Composant>$type</Composant><Variable type=\"$genre\">$variable</Variable><ValeurSiCoche>$row3[5]</ValeurSiCoche><ValeurSiDecoche>$row3[6]</ValeurSiDecoche> ";
    				if ($row3[7]) {$ligne=$ligne."<commentaire>$row3[7]</commentaire>";} else { $ligne=$ligne."<commentaire/>";}
				$ligne=$ligne."</Regle>";
 			}
		}


 		$ligne=$ligne."</regles></categorie>";
	}
	$ligne=$ligne."</categories></se3mod>";
	$content_dir = '/tmp/';
	$fichier_mod_xml = $content_dir . "rules.xml";
	if (file_exists($fichier_mod_xml)) unlink($fichier_mod_xml);

	$get= fopen ($fichier_mod_xml, "w+");
	fputs($get,$ligne);
	fclose($get);
	$get= fopen ($fichier_mod_xml, "r");
	header("Content-type: application/force-download");
	header("Content-Length: ".filesize($fichier_mod_xml));
	header("Content-Disposition: attachment; filename=rules.xml");
	readfile($fichier_mod_xml);
	if (file_exists($fichier_mod_xml)) unlink($fichier_mod_xml);
	mysql_close();

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
