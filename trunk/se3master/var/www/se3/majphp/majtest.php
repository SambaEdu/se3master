<?php


   /**
   
   * Mise a jour de SambaEdu3 
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs « wawa »  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: majphp 
   * file: majtest.php

  */	


require("entete.inc.php");

//aide
$_SESSION["pageaide"]="Prise_en_main#Mettre_.C3.A0_jour_le_serveur";

if (ldap_get_right("se3_is_admin",$login)!="Y")
	die ("<HTML><BODY>".gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

$action=$_GET['action'];
	
if ($action == "majse3") {
	$info_1 = gettext("Mise &#224; jour lanc&#233;e, ne fermez pas cette fen&#234;tre avant que le script ne soit termin&#233;. vous recevrez un mail r&#233;capitulatif de tout ce qui sera effectu&#233;...");
	echo $info_1;

	system('sleep 1; /usr/bin/sudo /usr/share/se3/scripts/se3-upgrade.sh');
}
else {
	echo "<BR><BR>";
	echo "<H3>Mise &#224; jour de SE3</H3>\n";
	echo "Pour mettre &#224; jour votre version de SE3 depuis l'interface web, il vous suffit de cliquer sur le bouton ci-dessous<BR>\n";
	echo "<FORM action=\"majtest.php?action=majse3 \"method=\"post\"><CENTER><INPUT type='submit' VALUE='Lancer la mise &#224; jour'></CENTER></FORM>\n";
}
# pied de page
include ("pdp.inc.php");

?>
