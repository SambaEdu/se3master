<?php


   /**
   
   * Constitution des groupes parcs
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: create_parc.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

if (is_admin("se3_is_admin",$login)=="Y") {
	$_SESSION["pageaide"]="Annuaire";

	echo "<h1>".gettext("Annuaire")."</h1>";

	$filter=isset($_GET['filter']) ? $_GET['filter'] : "";

	if (!isset($filter)) {
		echo "<p>Vous n'avez pas sp&#233;cifi&#233; de groupe.</p>\n";
	}
	else{
		echo "<h3>Cr&#233;ation d'un dossier de template</h3>\n";

		echo "<p>Cr&#233;ation d'un dossier de template pour le groupe $filter</p>\n";
// Ajout strtolower pour passer le nom du template en minuscules		
		$filter = strtolower($filter);

		exec ("/bin/bash /usr/share/se3/scripts/createtemplateparc.sh \"$filter\" \"groupe\"",$retour);

		if(isset($retour)){
			for($i=0;$i<count($retour);$i++){
				echo "\$retour[$i]=$retour[$i]<br />\n";
			}
		}
	}
}

include ("pdp.inc.php");
?>
