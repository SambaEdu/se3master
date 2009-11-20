<?php


   /**
   
   * Verifie le fonctionnement de CUPS
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Patrice Andre <h.barca@free.fr>
   * @auteurs Carip-Academie de Lyon

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: printers/
   * file: server_CUPS.php

  */	



// Etat du serveur d'impression CUPS

include "entete.inc.php";
include "ihm.inc.php";     // pour is_admin()

require_once ("lang.inc.php");
bindtextdomain('se3-printers',"/var/www/se3/locale");
textdomain ('se3-printers');

//aide
$_SESSION["pageaide"]="Imprimantes";

if (is_admin("se3_is_admin",$login)=="Y") { 
	echo "<H1>".gettext("Serveur CUPS")."</H1>";
	echo gettext("Serveur actif : ");
	$status=exec("LC_ALL=C /usr/bin/lpstat -r");
	if ($status=="scheduler is running") 
   		echo "<FONT COLOR=\"green\">".gettext("OUI")."</FONT>";
	else
    		echo "<FONT COLOR=\"red\">".gettext("NON")."</FONT>";
}       
        
include "pdp.inc.php";
?> 
