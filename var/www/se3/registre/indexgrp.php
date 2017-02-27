<?php


   /**
   
   * Gestion des cles pour clients Windows (affichage des templates vu dans /home/templates ,lien vers choisirprotect ou vers affiche_restrictions en fonction du niveau)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: indexcle.php

  */	



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');

if ((is_admin("computers_is_admin",$login)!="Y") or (is_admin("parc_can_manage",$login)!="Y"))
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");
	$_SESSION["pageaide"]="Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";

$testniveau=getintlevel();
require "include.inc.php";

connexion();

echo "<h1>".gettext("Gestion des groupes de cl&#233s")."</h1>\n";
$query="SELECT `mod` FROM modele GROUP BY `mod`;";
$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
if (mysqli_num_rows($resultat) != 0) {
	while ($row = mysqli_fetch_array($resultat)) {
		echo "<a href=\"affiche_modele.php?modele=$row[0]\">".$row[0]."</a><br/>";
	}
}
else
	echo "<a href=\"mod_maj.php?action=maj\">".gettext("Effectuer la mise &#224 jour des groupes de cl&#233s ?")."</a><br>";
include("pdp.inc.php");
?>
