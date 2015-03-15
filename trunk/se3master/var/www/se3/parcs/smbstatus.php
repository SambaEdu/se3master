<?php


   /**
   
   * affiche l'etat des connexions
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe Tice academie de Caen
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs  oluve  olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs  wawa   olivier.lecluse@crdp.ac-caen.fr

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
require_once ("fonc_parc.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');


//aide
$_SESSION["pageaide"]="Informations_syst%C3%A8me#Connexions_actives";

if (is_admin("system_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

$smb_login = smbstatus();
	
echo "<H1>".gettext("Connexions aux ressources samba")."</H1>\n";
echo "<H3>".gettext("Il y a "). count($smb_login). gettext(" connexions en cours")."</H3>";

// Si on a des connexions
if (count($smb_login)>0) {

		echo "<TABLE WIDTH=90% BORDER=1 ALIGN=center>\n";
		echo "<TR><TD class='menuheader'>".gettext("Identifiant")."</TD>";
        	echo "<TD class='menuheader'>".gettext("Machine")."</TD>";
        	echo "<TD class='menuheader'>".gettext("Adresse IP")."</TD></TR>\n";



foreach($smb_login as $nom => $val) {
    	echo"<TR>";
    	echo "<TD><a href=\"show_histo.php?selectionne=3&user=".$val['login']."\">".$val['login']."</a></TD>";
    	echo "<TD><a href=\"show_histo.php?selectionne=2&mpenc=$nom\">$nom</a></TD>";
    	echo "<TD><a href=\"show_histo.php?selectionne=1&ipaddr=".$val['ip']."\">".$val['ip']."</a></TD>";
    	echo"</TR>";
}
}
echo "</TABLE>\n";

require ("pdp.inc.php");
?>
