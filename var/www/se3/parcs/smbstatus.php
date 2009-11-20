<?php


   /**
   
   * affiche l'etat des connexions
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe Tice academie de Caen
   * @auteurs « jLCF >:> » jean-luc.chretien@tice.ac-caen.fr
   * @auteurs « oluve » olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs « wawa »  olivier.lecluse@crdp.ac-caen.fr

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note modifie par jean navarro - Carip Lyon introduction du choix de l'ordre de tri de l'affichage des connexions
   
   */

   /**

   * @Repertoire: parcs/
   * file: smbstatus.php
   */		


require ("entete.inc.php");
require ("ihm.inc.php");

// Internationnalisation
require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');

$tri=$_GET['tri'];

//aide
$_SESSION["pageaide"]="Gestion_des_parcs#Connexions_samba";

if (is_admin("system_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

if (($tri=="") OR (($tri != 0) AND ($tri != 2)) ) $tri=2; // tri par ip par defaut
// modif du tri 
// /usr/bin/smbstatus -S| awk 'NF>6 {print $2,$5,$6}'|sort -u +2
// le +POS de la fin donne le rang de la variable de tri (0,1,2...)
if ("$smbversion" == "samba3") {
       	exec ("/usr/bin/smbstatus -b | grep -v root | grep -v nobody | awk 'NF>4 {print $2,$4,$5}' | sort -u",$out); 
} elseif ($tri == 0) {
	exec ("/usr/bin/smbstatus -S | grep -v root | grep -v nobody | awk 'NF>6 {print $2,$5,$6}' | sort -u",$out); 
} else  {
	exec ("/usr/bin/smbstatus -S | grep -v root | grep -v nobody | awk 'NF>6 {print $2,$5,$6}' | sort -u +2",$out); 
}
	
echo "<H1>".gettext("Connexions aux ressources samba")."</H1>\n";
echo "<H3>".gettext("Il y a "). count($out). gettext(" connexions en cours")."</H3>";

// Si on a des connexions
if (count($out)>0) {
	//introduction lien pour le tri
	if ("$smbversion" !== "samba3") {
		echo "<TABLE WIDTH=90% BORDER=1 ALIGN=center>\n";
		echo "<TR><TD class='menuheader'><a href=\"".$_SERVER["SCRIPT_NAME"]."?tri=0\" title=\"".gettext("Trier par Identifiant")."\">".gettext("Identifiant")."</a></TD>";
        	echo "<TD class='menuheader'>".gettext("Machine")."</TD>";
        	echo "<TD class='menuheader'><a href=\"".$_SERVER["SCRIPT_NAME"]."?tri=2\" title=\"".gettext("Trier par Adresse IP")."\">".gettext("Adresse IP")."</a></TD></TR>\n";
	} else {
		echo "<TABLE WIDTH=90% BORDER=1 ALIGN=center>\n";
		echo "<TR><TD class='menuheader'>".gettext("Identifiant")."</TD>";
        	echo "<TD class='menuheader'>".gettext("Machine")."</TD>";
        	echo "<TD class='menuheader'>".gettext("Adresse IP")."</TD></TR>\n";
	}
}
//fin modif 

for ($i = 0; $i < count($out) ; $i++) {
	$test=explode(" ",$out[$i]);
    	$test[2]=strtr($test[2],"()","  ");
    	$test[2]=trim($test[2]);
    	echo"<TR>";
    	echo "<TD><a href=\"show_histo.php?selectionne=3&user=$test[0]\">$test[0]</a></TD>";
    	echo "<TD><a href=\"show_histo.php?selectionne=2&mpenc=$test[1]\">$test[1]</a></TD>";
    	echo "<TD><a href=\"show_histo.php?selectionne=1&ipaddr=$test[2]\">$test[2]</a></TD>";
    	echo"</TR>";
}
echo "</TABLE>\n";

require ("pdp.inc.php");
?>
