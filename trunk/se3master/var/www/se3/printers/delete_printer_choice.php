<?php


   /**
   
   * Choix entre supression d'un parc ou complete
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Patrice Andre <h.barca@free.fr>
   * @auteurs Carip-Academie de Lyon

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: printers/
   * file: delete_printer_choice.php

  */	



//Affichage du menu de suppression

include "entete.inc.php";
include "ihm.inc.php";     // pour is_admin()
include "printers.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-printers',"/var/www/se3/locale");
textdomain ('se3-printers');

//aide
$_SESSION["pageaide"]="Imprimantes";

if (is_admin("se3_is_admin",$login)=="Y") {
	//Selection de la suppression: supprimer une ou plusieurs imprimante(s) d'un parc seulement, ou integralement 
  	echo "<H1>".gettext("S&#233lection du mode de suppression")."</H1>";
  	echo "<FORM ACTION=\"delete_printer.php\" method=\"post\">\n";
  	echo "<P>".gettext("Que souhaitez-vous ?")."</P>";
  	echo "<INPUT TYPE=\"radio\" NAME=\"choix\" VALUE=\"option1\" CHECKED>".gettext("Supprimer des imprimantes d'un parc seulement")."<BR><BR>";
  	echo "<INPUT TYPE=\"radio\" NAME=\"choix\" VALUE=\"option2\">".gettext("Supprimer d&#233finitivement des imprimantes (<B>CHOIX DANGEREUX</B>)")."<BR><BR>";
  	echo "<P><INPUT TYPE=\"submit\" VALUE=\"".gettext("Valider")."\"\n></P>";
  	echo "</FORM>\n";
}

include ("pdp.inc.php");
?>
