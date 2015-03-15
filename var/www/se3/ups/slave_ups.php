<?php


   /**
   
   * Permet configurer un onduleur esclave
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   * @sudo  /usr/share/se3/scripts/ups.sh  
   */

   /**

   * @Repertoire: ups
   * file: slave_ups.php

  */	



// loading libs and init
include ("entete.inc.php");
include ("ldap.inc.php");
include ("ihm.inc.php");

require_once "ups.commun.php";

  

// list ($idpers, $login)= isauth();
// if ($idpers == "0")    header("Location:$urlauth");


bindtextdomain ('se3-ups', "/var/www/se3/locale");
textdomain ('se3-ups');

//aide 
$_SESSION["pageaide"]="Gestion_de_l\'onduleur#Onduleur_pour_plusieurs_serveurs";

//Si on a le droit de se connecter
if ($is_admin = is_admin("Lcs_is_admin",$login)=="Y") {

 echo "<H1>".gettext("Gestion des onduleurs")."</H1>";
 
// ###################### Variables ##############################//

$lien = "ups.php";
$xmlfile = "/var/www/se3/ups/ups.xml";
$conffile = "/etc/nut/ups.conf";
$lang_ups_titre = "Configuration de l'onduleur: Esclave ";


//###############################################################################################//

$ipmaster=isset($_POST['ipmaster']) ? $_POST['ipmaster'] : "";

if ($ipmaster!="") { // Si on a recu une IP on la verifie
	$ok=1;
	if (!is_string($ipmaster)) {$ok = 0;}
	
	$ip_long = ip2long($ipmaster);
	$ip_revers = long2ip($ip_long);
	if($ipmaster != $ip_revers) {$ok=0;}
}

if($ok=="1") { // If IP is Ok
	$fp=fopen("/etc/nut/upsmon.conf","w+");
	$upsmon_var = "MONITOR myups@$ipmaster 1 monslave wawa slave\nMINSUPPLIES 1\nSHUTDOWNCMD \"/sbin/shutdown -h +1\"\nPOLLFREQ 5\nPOLLFREQALERT 5\nHOSTSYNC 15\nDEADTIME 15\nPOWERDONFLAG /etc/killpower\nRBWARNTIME 43200\nNOCOMMWARNTIME 300\nFINALDELAY 5\nNOTIFYCMD /usr/share/se3/sbin/mail_alertes_ups.sh\nNOTIFYFLAG ONBATT SYSLOG+EXEC\n";
        fputs($fp,$upsmon_var);
	fclose($fp);


	$fp=fopen("/etc/nut/ipmaster","w+");	
        fputs($fp,$ipmaster);
	fclose($fp);

	$fp=fopen("/etc/nut/hosts.conf","w+");	
	$hosts_var = "MONITOR myups@".$ipmaster." \"UPS slave\"";
        fputs($fp,$hosts_var);
	fclose($fp);
	
	echo gettext("Veuillez patienter ...!");
	echo "<br>";
	echo "<a href=ups.php?action=Configurer>Configurer</a>";
	exec ("/usr/bin/sudo /usr/share/se3/scripts/ups.sh");
	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"1; URL=ups.php\">";
	exit;
} else {  // Si pas encore d'IP
	$text = "<br>";
	$text .= "<form action=\"slave_ups.php\" name=\"change\" method=\"post\" />";
	$text .= gettext("Indiquer l'adresse IP du serveur \"maitre\" qui est connect&#233; &#224; l'onduleur via le c&#224;ble s&#233;rie.");
	if($ok=="0") {$text .= "<br><br><font color='red'>";
	$text .= gettext("Erreur : Adresse IP non correcte.");
	$text .=  "</font>";}
	$text .= "<br><table>\n";
	$text .= "<tr>";
	$text .= "<td>";
	$text .= "<input type=\"text\" name=\"ipmaster\" value=\"$ipmaster\" />";
	$text .= "</td><td>";
	$text .= "<input type=\"submit\" name=\"action\" value=";
	$text .= gettext("Valider");
	$text .=  ">";
	$text .= "</td></tr></table><br>\n";
}


$titre =gettext("UPS");
echo "<div style='no-border; height: 75%'>\n";
print "$text\n";
echo "</div>\n";

} else
        echo "$html<div class=alert_msg>".gettext("Cette fonctionnalit\xe9, n\xe
9cessite les droits d'administrateur du serveur Se3 !")."</div>";
require ("pdp.inc.php");


?>
