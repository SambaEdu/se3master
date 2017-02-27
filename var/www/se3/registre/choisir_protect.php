<?php

   /**
   
   * Gestion des cles pour clients Windows (affiche les modeles a appliquer aux templates)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: choisir_protect.php

  */	



require "include.inc.php";
connexion();
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');

if (ldap_get_right("computers_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

$_SESSION["pageaide"]="Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";


$testniveau=getintlevel();

if ($testniveau==1) { $affiche="non"; } else { $affiche="oui"; }
echo "<h1>".gettext("Gestion des templates")."  (".afficheniveau($testniveau).")</h1>";
$template=$_POST['salles'];
$mod=$_POST['mod'];
if (!$template) { $template=$_GET['salles'];}

if ($template) { 

	$query4="DELETE FROM restrictions WHERE groupe='$template';";
	mysqli_query($GLOBALS["___mysqli_ston"], $query4);

	applique_modele($mod,$template,"oui");
 		
	echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_restrictions.php?salles=$template\"></HEAD>";

} else {
	echo gettext("Choisir un template &#224 modifier")."<br>";
  	$handle=opendir('/home/templates');
        while ($file = readdir($handle)) {
        	if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser') {
                	echo "<a href=\"choisir_protect.php?salles=$file\">$file</a><br>";
		}
	}
}

include("pdp.inc.php");

?>
