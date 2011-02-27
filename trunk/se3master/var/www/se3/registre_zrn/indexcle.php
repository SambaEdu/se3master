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
if (test_bdd_registre()==false) {
	exit;
} else {
	if (test_zorn_tools()==false) {
		exit; 
	}
}


echo "<h1>".gettext("Gestion des templates")."</h1>";
echo gettext("Chosir un template ");
echo "<u onmouseover=\"return escape".gettext("('Choisir un template correspond &#224; un groupe de machine, un groupe de personnes. Dans ce menu, vous pouvez visualiser les protections des clients windows de votre parc en leur attribuant des groupes de cl&#233;s. Selon le niveau de s&#233;curit&#233; que vous souhaitez, choisissez un des groupes des cl&#233;s qui va vous &#234;tre propos&#233;. Attention, vous pouvez<font color=#FF0000> uniquement enlever des restrictions </font> ou faire des r&#233;glages sur les cl&#233;s de configuration ( changer votre page de d&#233;marrage pour Internet Explorer, par exemple), seuls les administrateurs r&#233;seau peuvent ajouter des restrictions. <font color=#FF0000>Soyez tr&#232;s prudent avec ce menu !!</font>. Faites-vous aidez par votre administrateur r&#233;seau au d&#233;but.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>";

echo "<br><br>";
$handle=opendir('/home/templates');

$filesArray=array();           
while ($file = readdir($handle)) {
	$filesArray[] = $file;
}
sort($filesArray);
$count = count($filesArray);

for ($i = 0; $i < $count; $i++) {
	$file=$filesArray[$i];
	if ($testniveau < "3") {
		if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser') {
			if ((this_parc_delegate($login,$file,'manage')) or (is_admin("computers_is_admin",$login)=="Y")) { echo "<a href=\"choisir_protect.php?salles=$file\">$file</a><br>"; $test_affiche++;}
                }
	}
	else if ($testniveau >= "3") {
		if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser') {
			if ((this_parc_delegate($login,$file,'manage')) or (is_admin("computers_is_admin",$login)=="Y")) { echo "<a href=\"affiche_restrictions.php?salles=$file&cat=tout\">$file </a><br>"; $test_affiche++;}
               	}  
	}
}
		
closedir($handle);
if ($test_affiche=0) { echo gettext("Vous n'avez pas de droit sur ce template. "); }
   
include("pdp.inc.php");
?>
