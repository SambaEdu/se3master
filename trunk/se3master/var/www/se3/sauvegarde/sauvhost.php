<?php


   /**
   
   * Permet configurer la sauvegarde (Backuppc)
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: sauvegarde
   * file: savhost.php

  */	


   include "entete.inc.php";   
   require ("config.inc.php");
   require ("ldap.inc.php");
   require ("ihm.inc.php");
   include ("fonction_backup.inc.php");

   require_once("lang.inc.php");
   bindtextdomain('sauvegarde',"/var/www/se3/locale");
   textdomain ('sauvegarde');


// Verifie les droits
if (is_admin("system_is_admin",$login)=="Y") {

	$HostServer=$_GET['HostServer'];
	$TypeServer=$_GET['TypeServer'];
	
	// verifie la coherence entre le fichier hosts et la presence du fichier machine.pl
	HostCoherence();


	echo "<P><h1>".gettext("Gestion des sauvegardes")."</h1></P>";
	echo "<br><br>";

/************* Suppression ***************************************************/
if ($_GET['action']=="del") {
	echo"<br><br>";
	echo "<form method=\"get\" action=\"sauvhost.php\" >";
	echo"<table align=center width=\"60%\" border=1 cellspacing=\"0\" cellpadding=\"0\">\n";
	if (GetTypeServer($HostServer)!="Archive") {
		echo"<tr><td colspan=\"3\" bgcolor=#E0E0E0 align=\"center\">".gettext("Machine")." $HostServer ".gettext("&#224; supprimer de la sauvegarde")."</td></tr>";
		echo"<tr><td>";
	  	echo gettext("D&#233;sactiver cette machine de la sauvegarde");
	  	echo"</td><td>";
	  	echo"<input type=radio name=\"supp\" value=\"0\">";
	  	echo"</td><td>&nbsp;<u onmouseover=\"return escape".gettext("('Vous permet de d&#233;sactiver une sauvegarde.<br>Vous ne perdez pas les sauvegardes existantes.<br>Vous obtiendrez un bouton bleu sur l\'interface de gestion des sauvegardes en face de la machine d&#233;sactiv&#233;e. <br>Pour la r&#233;activer il suffit de cliquer dessus.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u> &nbsp;</td></tr>\n";
		echo"<tr><td>";
		echo gettext("Supprimer cette machine ainsi que les sauvegardes existantes pour cette machine");
		echo"</td><td>";
		echo"<input type=radio name=\"supp\" value=\"1\" >";
		echo"</td><td>&nbsp;<u onmouseover=\"return escape".gettext("('Attention : vous permet de supprimer la machine de la sauvegarde, ainsi que les sauvegardes existantes.<br>Il ne restera plus rien apr&#232;s cette action.<br><br>Si vous supprimez une archive, cela ne supprime pas les archives existantes.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td>";
	} else {
		$TypeServer="Archive";
	  
	  	echo"<tr><td colspan=\"3\" bgcolor=#E0E0E0 align=\"center\">".gettext("Archive")." $HostServer ".gettext("&#224; supprimer de la sauvegarde")."</td></tr>";
 	  	echo"<tr><td>";
	  	echo gettext("Supprimer cette archive, sans supprimer les archives existantes.");
	  	echo"</td><td>";
	  	echo"<input type=radio name=\"supp\" value=\"1\" >";
	  	echo"</td><td>&nbsp;<u onmouseover=\"return escape".gettext("('Supprime l'archive. Ne supprime pas les archives d&#233;j&#224; faites.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>&nbsp;</td>";
	}

	
	echo "</tr></table>";
	echo"<br>";
	echo"<input name=HostServer type=hidden value=\"$HostServer\">";
	echo"<input name=TypeServer type=hidden value=\"$TypeServer\">";
	echo"<input name=action type=hidden value=del2>";
	echo "<center><input name=\"formsauv\" type=\"submit\"  value=\"".gettext("Valider")."\">";
	echo "</center></form>\n";
			    
	require ("pdp.inc.php");
	exit;
}

if($_GET['action']=="del2") {
	if($_GET['supp']=="1") { // On detruit tout
		$rep = "/etc/backuppc/";
		$file = $rep.$HostServer.".pl";
		if (file_exists($file)) { // On d&#233;truit le fichier de conf de cette machine
			@unlink($file);
		}
		if (HostExist($HostServer)) {
			DeleteHost($HostServer);
			reloadBackuPpc();
		}

		DeleteRep($HostServer);
		if ($TypeServer=="Archive") {
			echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=sauv.php\">";
			exit;
		}	
  	}
  
  	if($_GET['supp']=="0") { // On d&#233;sactive
		Desactive($HostServer,-1);
		reloadBackuPpc();
	}
}	

if ($_GET['action'] == "active") {
	Desactive($HostServer,"1");
	reloadBackuPpc();
}

/***********************************************************************************/

echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\">\n";
echo "<tr><td>".gettext("Ajouter une nouvelle sauvegarde")."</td>\n";
echo "<td align=\"center\" <u onmouseover=\"return escape".gettext("('Ajouter une nouvelle machine dans la sauvegarde')\"><a href=new_host.php>Ajouter")."</a></u>";
echo "</td></tr>\n";
echo "</table>\n";
echo "<br><br>";

     

$i="0";
$dir = "/etc/backuppc";
if(is_dir($dir)) {
	if ($liste = opendir($dir)) {
		while (($file = readdir($liste)) != false) {
			if ((preg_match("/.pl$/",$file)) and ($file != "config.pl")) {
				$Host = substr ("$file",0,-3);
				
				// recherche le type de sauvegarde
				if (GetTypeServer($Host) != "Archive") {
				   if ($i == "0") {
					echo "
					<table align=center width=\"80%\" border=0 cellspacing=\"0\" cellpadding=\"0\">
        				<tr><td><H3>".gettext("Machines sauvegard&#233;es")."</H3></td></tr>
					</table><br>";
					echo "<table align=center width=\"80%\" border=1 cellspacing=\"0\" cellpadding=\"0\">";
					echo "<tr><td colspan=\"6\" bgcolor=#E0E0E0 align=\"center\">".gettext("Machines sauvegard&#233;es")."</td></tr>\n";
				   }
				$i="1";   
				echo "<tr";
				  // verifie si tout est ok
				  if (HostExist($Host) == "true") {
				  	if (EtatDesactive($Host) == "true") { $im = "info.png"; } else { $im = "recovery.png"; }
				  } else { $im="critical.png"; }
				  echo "><td><a href=\"modif_host.php?HostServer=$Host\">$Host</a></td><td align=center>";
				  echo "<u onmouseover=\"return escape".gettext("('Si le bouton est vert, tout est normal. Cliquez dessus pour &#233;ventuellement modifier la sauvegarde.<bR>Si le bouton est bleu la sauvegarde a &#233;t&#233; d&#233;sactiv&#233;e. Cliquer sur le bouton pour la r&#233;activer.<br>Si le bouton est rouge, il y a un probl&#232;me.')")."\">";				  
				  if ($im == "info.png") { echo "<a href=\"sauvhost.php?HostServer=$Host&action=active\">"; } else {
				    echo "<a href=\"modif_host.php?HostServer=$Host\">"; }
				  echo "<img style=\"border: 0px solid;\"src=\"../elements/images/$im\">";
				  if ($im == "info.png") { echo "</a>"; } 
				  echo "</u></td><td align=center><u onmouseover=\"return escape".gettext("('Permet de voir l\'&#233;tat des  sauvegardes de cette machine')")."\"><a href=../backuppc/index.cgi?host=$Host><img style=\"border: 0px solid;\"src=\"../elements/images/zoom.png\"></a></u></td><td align=center><u onmouseover=\"return escape".gettext("('Parcourir les sauvegardes et les restaurer.')")."\"><a href=../backuppc/index.cgi?action=browse&host=$Host><img style=\"border: 0px solid;\"src=\"../elements/images/logrotate.png\"></a></u></td><td align=center><u onmouseover=\"return escape".gettext("('Permet de d&#233;sactiver ou de supprimer la sauvegarde de cette machine')")."\"><a href=\"sauvhost.php?HostServer=$Host&action=del\"><img style=\"border: 0px solid;\"src=\"../elements/images/edittrash.png\"></a></u></td>";
				  echo "<td align=center><u onmouseover=\"return escape".gettext("('Aide &#224; la configuration de la machine &#224; sauvegarder.')")."\"><a href=conf_host.php?HostServer=$Host><img style=\"border: 0px solid;\"src=\"../elements/images/system-help.png\"></a></u></td>";
				  
				  echo "</tr>";
				}  
			}	
		}
//	closedir($dir);
	}
}

echo "</table>";
echo "<br><br>";

require ("pdp.inc.php");

}
?>
