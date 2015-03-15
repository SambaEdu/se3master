<?php


   /**
   
   * Permet configurer l'onduleur
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   * @sudo  /usr/share/se3/scripts/ups.sh  
   */

   /**

   * @Repertoire: ups
   * file: ups.php

  */	




// loading libs and init
include ("entete.inc.php");
include ("ldap.inc.php");
include ("ihm.inc.php");


require_once("ups.commun.php");

//list ($idpers, $login)= isauth();
//if ($idpers == "0")    header("Location:$urlauth");


bindtextdomain ('se3-ups', "/var/www/se3/locale");
textdomain ('se3-ups');

//aide 
$_SESSION["pageaide"]="Gestion_de_l\'onduleur#Onduleur_unique_sur_le_serveur_Se3";

//Si on a le droit de se connecter
if ($is_admin = is_admin("se3_is_admin",$login)=="Y") {

echo "<H1>".gettext("Gestion de l'onduleur")."</H1>";

// ###################### Variables ##############################//

$lien = "ups.php";
$xmlfile = "/var/www/se3/ups/ups.xml";
$conffile = "/etc/nut/ups.conf";

$pmarque=$_POST['pmarque'];
if ($pmarque==''){$pmarque=$_GET['pmarque'];}
$pversion=$_POST['pversion'];
if ($pversion==''){$pversion=$_GET['pversion'];}
$pdriver=$_POST['pdriver'];
if ($pdriver==''){$pdriver=$_GET['pdriver'];}
$pcable=$_POST['pcable'];
if ($pcable==''){$pcable=$_GET['pcable'];}
$pport=$_POST['pport'];
if ($pport==''){$pport=$_GET['pport'];}
$ptype=$_POST['ptype'];
if ($ptype==''){$ptype=$_GET['ptype'];}

$action=$_POST['action'];
if ($action=='')($action=$_GET['action']);

$filiation = array();
$lselect = array();
$marqueOk=false;
$versionOk=false;

//############################# Delete ##########################################//

if ($action=="Configurer") {
        $fp=fopen("/etc/nut/upsd.stop","w+");
        fputs($fp,"stop");
        fclose($fp);
	exec ("/usr/bin/sudo /usr/share/se3/scripts/ups.sh");
}


//########################### IP Master #########################################//

if ($_POST['slave']=="yes") {
	if ($_POST['ipslave']!="") {
    		$ok=1;$i=1;
    		// split ipslave
    		$chaine=preg_split("/;/",$_POST['ipslave']);
    		foreach($chaine as $resultat){
     			// verifie l ip
     			if (!is_string($resultat)) {$ok = 0;}
     			$ip_long = ip2long($resultat);
     			$ip_revers = long2ip($ip_long);
     			if($resultat != $ip_revers) {$ok=0;}
     			if($i=="1") {
				$ip1=$resultat;
				$upsd_var = "ACL machine1 $ip1/32\nACCEPT machine1\n";
			}
     			if($i=="2") {
				$ip2=$resultat;
				$upsd_var = "ACL machine2 $ip2/32\n".$upsd_var."ACCEPT machine2\n";
			}
     			if($i=="3") {
				$ip3=$resultat;
				$upsd_var = "ACL machine3 $ip3/32\n".$upsd_var."ACCEPT machine3\n";
			}
     			if($i=="4") {
				$ip4=$resultat;
				$upsd_var = "ACL machine4 $ip4/32\n".$upsd_var."ACCEPT machine4\n";
			}
     			if($i=="5") {
				$ip5=$resultat;
				$upsd_var = "ACL machine5 $ip5/32\n".$upsd_var."ACCEPT machine5\n";
			}
    			$i++;
  		}
		$upsd_var = "ACL all 0.0.0.0/0\nACL localhost 127.0.0.1/32\n".$upsd_var."ACCEPT localhost\nREJECT all\n";
  		if ($ok=="1") {
			// On cree ipslave
			$fp=fopen("/etc/nut/ipslave","w+");
			fputs($fp,$ipslave);
			fclose($fp);
			// On cree upsd.conf
			$fp=fopen("/etc/nut/upsd.conf","w+");
			fputs($fp,$upsd_var);
			fclose($fp);

			exec ("/usr/bin/sudo /usr/share/se3/scripts/ups.sh");
			echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"2; URL=ups.php\">";
  		} else {
			$action="Avancer";
  		}	
	}  
}

//######################## Creation du fichier ####################################//

if ($pcable!='' && $pversion!='' &&  $pmarque!='' && $pport!='' && $pcable!='' && $pdriver!=''){
	if ($pport=="1") {$pport="/dev/ttyS0";}
	if ($pport=="2") {$pport="/dev/ttyS1";}
	if ($pport=="3") {$pport="/dev/usb/hiddev0";}
	$texte = "[myups]\n";
	if($pport=="/dev/usb/hiddev0") {$pdriver="usbhid-ups";}
	$texte .= "driver = $pdriver\n";
	$texte .= "port = $pport\n";
	if($pcable!="0") {$texte .= "cable = $pcable\n";}
	$texte .= "desc = $pmarque $pversion\n";
	if($pdriver=="genericups") {$texte .= "upstype = $ptype\n";}
	$texte .= "# marque = $pmarque\n";
	$texte .= "# version = $pversion\n";
	$fp=fopen("/etc/nut/ups.conf","w+");
	fputs($fp,$texte);
	fclose($fp);

	$upsd_var = "ACL all 0.0.0.0/0\nACL localhost 127.0.0.1/32\n".$upsd_var."ACCEPT localhost\nREJECT all\n";

	$fp=fopen("/etc/nut/upsd.conf","w+");
	fputs($fp,$upsd_var);
	fclose($fp);

	$fp=fopen("/etc/nut/hosts.conf","w+");
	$hosts_var = "MONITOR myups@localhost \"Local UPS\"\n";
	fputs($fp,$hosts_var);
	fclose($fp);

	$fp=fopen("/etc/nut/upsd.users","w+");
	$users_var = "[monuser]\npassword = GwawaKaN\nallowfrom = localhost\nupsmon master\n";
	fputs($fp,$users_var);
	fclose($fp);

	$fp=fopen("/etc/nut/upsmon.conf","w+");
	$upsmon_var = "MONITOR myups@localhost 1 monuser GwawaKaN master\nMINSUPPLIES 1\nSHUTDOWNCMD \"/sbin/shutdown -h +1\"\nPOLLFREQ 5\nPOLLFREQALERT 5\nHOSTSYNC 15\nDEADTIME 15\nPOWERDOWNFLAG /etc/killpower\nRBWARNTIME 43200\nNOCOMMWARNTIME 300\nFINALDELAY 5\nNOTIFYCMD /usr/share/se3/sbin/mail_alertes_ups.sh\nNOTIFYFLAG ONBATT SYSLOG+EXEC\n";
	fputs($fp,$upsmon_var);
	fclose($fp);

	echo gettext("Veuillez patienter ...!");
	echo "<br>";
	echo "<a href=ups.php?action=Configurer>Configurer</a>";
	
	/**
	*  /usr/share/se3/scripts/ups.sh
	*/
	exec ("/usr/bin/sudo /usr/share/se3/scripts/ups.sh");
	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"3; URL=ups.php\">";
	exit;
}   


//######################## Avance ################################################//

if ($action=="Avancer") {
	if($ipslave=="") {	
		if(file_exists("/etc/nut/ipslave")) {
			$fp=fopen("/etc/nut/ipslave","r");
			while ($str=fgets($fp,50)) {
				$ip = $str;
	   			$ipslave .= $ip;
	    		}
		}	
  	}	

	$text =	"<br><br>";
	$text .= "<form action=\"ups.php\" name=\"change\" method=\"post\" />";
	$text .= gettext("Indiquer l'adresse IP du serveur aliment&#233; par cet onduleur, et qui n'a pas de c&#224;ble s&#233;rie. Si plusieurs serveurs, s&#233;parer les adresses IP par un point virgule.\n");
	if($ok=="0") { 
		$text .= "<br><br><font color='red'>";
		$text .= gettext("Erreur sur l'adresse IP\n");
		$text .= "</font>";
	}
	$text .= "<br><table>\n";
	$text .= "<tr><td>";
	$text .= "<input type=\"text\" name=\"ipslave\" value=\"$ipslave\" size=\"40\" />";
	$text .= "</td><td>";
	$text .= "<input type=\"hidden\" name=\"slave\" value=\"yes\" />";
	$text .= "<input type=\"submit\" name=\"action\" value=";
	$text .=  gettext("Valider\n");
	$text .=  ">";
	$text .= "</td></tr></table><br>\n";
	$text .=  "<i>";
	$text .=  gettext("Cette option permet d'onduler plusieurs serveurs (LCS - SLIS - SE3)  avec un seul onduleur.\n");
	$text .=  "<br>";
	$text .= gettext("Ne pas oublier d'onduler le switch ;-)\n");
	$text .= "<br>";
	$text .= gettext("La machine jouant le r&#244;le de serveur doit rester en marche tout le temps.\n");
	$text .= "</i>";
	
        echo "<div style='no-border; height: 75%'>\n";
	print "$text\n";
        echo "</div>\n";

        require ("pdp.inc.php");
	exit;
}

//#################### Verif si un onduleur existe deja ######################//

if(file_exists("/etc/nut/ups.conf") || file_exists("/etc/nut/upsmon.conf")) {
	$text .= "<br><br><CENTER>";
	if (file_exists("/etc/nut/ups.conf")) {
		$text = affichage_ups("myups@127.0.0.1");
	} else {
	        $ip=exec("cat /etc/nut/ipmaster");	
		$text = affichage_ups("myups@$ip");
	}

	$text .= "<br><br>";

	$text .= "<table><tr>";
	$text .= "<td><form action=\"ups.php\" name=\"codexml\" method=\"post\">";
	$text .= "<input type=\"submit\" name=\"action\" value=\"Configurer\" />";
	$text .= "</form>";
	$text .= "</td>";
	if( ! file_exists("/etc/nut/ipmaster")) {
	  $text .= "<td>";
	  $text .= "<form action=\"ups.php\" name=\"codexml_2\" method=\"post\">";
	  $text .= "<input type=\"submit\" name=\"action\" value=\"Avancer\" />";
	  $text .= "</form>";
	  $text .= "</td>";
	}
	$text .= "</tr>";
	$text .= "</table></center>";

	$titre =gettext("UPS\n");

        echo "<div style='no-border; height: 75%'>\n";
	print "$text\n";
        echo "</div>\n";

        require ("pdp.inc.php");
	exit;
}

//######################## Parser ##################################################//

If (!($fp = fopen($xmlfile , "r"))) {die("Impossible d'ouvrir le fichier XML");}

if ($pcable=='' or $pversion=='' or $pmarque==''){
	$xml_parser = xml_parser_create();
  	if ($pmarque==''){
    		xml_set_element_handler($xml_parser, "debutElement0", "finElement");
    		xml_set_character_data_handler($xml_parser, "characterData0");
    	} elseif ($pversion=='') {
    		xml_set_element_handler($xml_parser, "debutElement1", "finElement");
    		xml_set_character_data_handler($xml_parser, "characterData1");
    	}  else {
    		xml_set_element_handler($xml_parser, "debutElement2", "finElement");
    		xml_set_character_data_handler($xml_parser, "characterData2");
    	}

  	while ($data = fread($fp, 4096)) {
      		if (!xml_parse($xml_parser, $data, feof($fp))) {
          		die(sprintf("erreur XML : %s &#224; la ligne %d",
               		xml_error_string(xml_get_error_code($xml_parser)),
               		xml_get_current_line_number($xml_parser)));
      		}
  	}
  	xml_parser_free($xml_parser);
}

//########################### Affichage ################################################//
 
$urlmark=urlencode($pmarque);
$urlversion=urlencode($pversion);
$urlcable=urlencode($pcable);

$text = "<br><a href=$lien?pmarque=$urlmark>$pmarque</a>";
if ($pversion!=''){$text .= " --> <a href=$lien?pmarque=$urlmark&amp;pversion=$urlversion>$pversion</a> ";}
if ($pport!=''){$text .=  " --> <a href=$lien?pmarque=$urlmark&amp;pversion=$urlversion&amp;pport=$pport>Port $pport</a>";}
if ($pcable!=''){$text .= " --> <a href=$lien?pmarque=$urlmark&amp;pversion=$urlversion&amp;pport=$pport&amp;pdriver=$pdriver&amp;ptype=$ptype&amp;pcable=$urlcable>$pcable</a>";}

$text .= "<form action=\"$lien\" name=\"codexml\" method=\"post\">";
if ($pmarque=='') {
	$text .= "<SELECT NAME='pmarque' onchange=submit()>\r\n";
  	foreach ( $lselect as $sel_element ){
    		$text .= "<option value='$sel_element'>$sel_element</option>\r\n";
    	}
  	$text .= "</SELECT>\r\n";
} elseif ($pversion=='') {
	$text .= "<INPUT TYPE='hidden' NAME='pmarque' VALUE='$pmarque'/>\r\n";
  	$text .= "<SELECT NAME='pversion' onchange=submit()>\r\n";
  	foreach ( $lselect as $sel_element ){
    		$text .= "<option value='$sel_element'>$sel_element</option>\r\n";
    	}
  	$text .= "</SELECT>\r\n";
} elseif ($pcable=='') {
	$text .= "<INPUT TYPE='hidden' NAME='pmarque' VALUE='$pmarque' />\r\n";
	$text .= "<INPUT TYPE='hidden' NAME='pversion' VALUE='$pversion' />\r\n";
  	$text .= "<INPUT TYPE='hidden' NAME='pdriver' VALUE='$pdriver' />\r\n";
  	$text .= "<INPUT TYPE='hidden' NAME='pport' VALUE='$pport'/>\r\n";
  	$text .= "<INPUT TYPE='hidden' NAME='ptype' VALUE='$ptype' />\r\n";
  	$taille = count ($lselect);
    	if ($taille > "1") {
      		$text .= "<SELECT NAME='pcable' onchange=submit()>\r\n";
      		foreach ( $lselect as $sel_element ){
        		$text .= "<option value='$sel_element'>$sel_element</option>\r\n";
      		}
      		$text .= "</SELECT>\r\n";
    	}
   	if ($taille == "1") {
		$pcable=$lselect[0];
  		$text .= "<INPUT TYPE='hidden' NAME='pcable' VALUE='$pcable'/>\r\n";
   	}
	if ($taille == "0") {
		$pcable=$lselect[0];
		$text .= "<INPUT TYPE='hidden' NAME='pcable' VALUE='0'/>\r\n";
	}	
} else {
	$text .= "<INPUT TYPE='hidden' NAME='pmarque' VALUE='$pmarque'/>\r\n";
  	$text .= "<INPUT TYPE='hidden' NAME='pversion' VALUE='$pversion' />\r\n";
  	$text .= "<INPUT TYPE='hidden' NAME='pdriver' VALUE='$pdriver' />\r\n";
  	$text .= "<INPUT TYPE='hidden' NAME='pport' VALUE='$pport' />\r\n";
  	$text .= "<INPUT TYPE='hidden' NAME='pcable' VALUE='$pcable' />\r\n";
  	$text .= "<INPUT TYPE='hidden' NAME='ptype' VALUE='$ptype' />\r\n";
}


if ($pmarque!='' && $pversion!='' && $pport=='') {
	$text .= "<INPUT TYPE='hidden' NAME='pmarque' VALUE='$pmarque' />\r\n";
  	$text .= "<INPUT TYPE='hidden' NAME='pversion' VALUE='$pversion' />\r\n";
  	$text .= "<INPUT TYPE='hidden' NAME='pdriver' VALUE='$pdriver' />\r\n";
 	$text .= "<INPUT TYPE='hidden' NAME='ptype' VALUE='$ptype' />\r\n";
	$text .= "<SELECT NAME='pport' onchange=submit()>\r\n";
        $text .= "<option value='1'>Port serie 1 (ttyS0)</option>\r\n";
        $text .= "<option value='2'>Port serie 2 (ttys1)</option>\r\n";
        $text .= "<option value='3'>USB </option>\r\n";
	$text .= "</select>\r\n";
} else {
	$text .=  "$selectStr\r\n";
}

$text .= "<input type=\"submit\" name=\"action\" value=";
$text .= gettext ("Valider\n");
$text .= "></form>\n";

 
if($pmarque=='') {
//	$text .= "<br><br>";
	$text .= "<br><a href=slave_ups.php>";
	$text .= gettext(" Installer comme esclave\n");
	$text .= "</a>";
	$text .= "<u onmouseover=\"return escape".gettext("('Installer comme esclave, permet de partager un onduleur dont le c&#224;ble est branch&#233; sur une autre machine.<br>Vous devez simplement indiquer l\'adresse IP de cette machine.<br><br><b>Attention :</b> ne pas oublier d\'onduler aussi le switch.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
}
$titre =gettext("UPS\n");
echo "<div style='no-border;height: 75%'>\n";
print "$text\n";
echo "</div>\n";
} else
        echo "$html<div class=alert_msg>".gettext("Cette fonctionnalit\xe9, n\xe
9cessite les droits d'administrateur du serveur Se3 !")."</div>";


require ("pdp.inc.php");

?>

