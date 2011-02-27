<?php


   /**
   
   * Affiche la page de selection des drivers
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Patrice Andre <h.barca@free.fr>
   * @auteurs Carip-Academie de Lyon

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: printers/
   * file: cups_driver.php

  */	



include "entete.inc.php";
include "ihm.inc.php";     // pour is_admin()

require_once ("lang.inc.php");
bindtextdomain('se3-printers',"/var/www/se3/locale");
textdomain ('se3-printers');

//aide
$_SESSION["pageaide"]="Imprimantes";

if (is_admin("se3_is_admin",$login)=="Y") {
	echo "<h1>".gettext("Pilote CUPS")."</H1>";
	echo "<H3>".gettext("S&#233lectionnez un pilote dans la liste")."</H3>";

	// Retourne le nombre de pilotes
	$nb_drivers=exec("lpinfo -m | wc -l");
	// Retourne les pilotes
	$return=exec ("lpinfo -m",$all_drivers);

	// Affichage du filtre sur constructeur        
	if (!isset($filtre)) {
    		echo "<P>".gettext("Nom d'utilisateur:")." </P>";
    		echo "<FORM ACTION=\"cups_driver.php\" METHOD=\"post\">";
    		echo "<INPUT TYPE=\"text\" NAME=\"filtre\" VALUE=\"$filtre\" SIZE=\"20\">";
    		echo "<INPUT TYPE=\"hidden\" NAME=\"info_imprimante\" VALUE=\"$info_imprimante\">";
    		echo "<INPUT TYPE=\"hidden\" NAME=\"uri_imprimante\" VALUE=\"$uri_imprimante\">";
    		echo "<INPUT TYPE=\"hidden\" NAME=\"nom_imprimante\" VALUE=\"$nom_imprimante\">";
    		echo "<INPUT TYPE=\"hidden\" NAME=\"info_imprimante\" VALUE=\"$lieu_imprimante\">";
    		echo "<INPUT TYPE=\"hidden\" NAME=\"protocole\" VALUE=\"$protocole\">";
    		echo "<INPUT TYPE=\"submit\" VALUE=\"Filtrer\">";
    		echo "</FORM>";
	}

	echo "<FORM ACTION=\"config_printer.php\" METHOD=\"post\">";
	echo "<SELECT NAME=\"driver_name\" SIZE=\"15\" MULTIPLE>";
	for ($i=0;$i<$nb_drivers;$i++) {   
    		if ( !isset($filtre) || ( ($fabricant[$i]==$filtre) ) ) {
        		echo "<OPTION VALUE=\"$all_drivers[$i]\">$all_drivers[$i]";
        		echo "</OPTION>";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"info_imprimante\" VALUE=\"$info_imprimante\">";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"uri_imprimante\" VALUE=\"$uri_imprimante\">";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"nom_imprimante\" VALUE=\"$nom_imprimante\">";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"info_imprimante\" VALUE=\"$lieu_imprimante\">";
        		echo "<INPUT TYPE=\"hidden\" NAME=\"protocole\" VALUE=\"$protocole\">";
        		echo "<BR>"; 
     		}
	}
	echo "</SELECT>";
}

include "pdp.inc.php";
?>
