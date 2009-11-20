<?php

/**
   * Page qui permet de gerer les modules (installation - desactivation - mises a jour)
   * @Version $Id$
   
   * @Projet LCS-SE3
   * @auteurs Philippe Chadefaux
   * @Licence Distribue sous  la licence GPL
*/

/**	
	* @Repertoire /
	* file conf_modules.php
*/	


require ("entete.inc.php");
include ("fonc_outils.inc.php");

// require_once("lang.inc.php");
// bindtextdomain('se3-core',"/var/www/se3/locale");
// textdomain ('se3-core');


//aide 
$_SESSION["pageaide"]="Les modules";


if (ldap_get_right("se3_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

$module = "se3-".$_GET[varb];
// Mise a jour
if ($_GET[action] == "update") {
	echo "<h1>Modules optionnels</h1>";	
	system("/usr/bin/sudo /usr/share/se3/scripts/install_se3-module.sh $module");
	echo "<br><a href=\"conf_modules.php\">Retour &#224; l'interface de gestion des modules optionnels.</a>";
	exit;
}

// Change dans la base
if ($_GET[action] == "change") {

	echo "<H1>Modules optionnels</H1>";
	// Change dnas la table params
	$resultat=mysql_query("UPDATE params set value='$_GET[valeur]' where name='$_GET[varb]'");
	switch ($_GET[varb]) {
		case "backuppc":
			include ("fonction_backup.inc.php");
			stopBackupPc();
			break;
		case "savbandactiv":
			if ($_GET[valeur] == "1") {
				echo "Module $module activ&#233;.<br>\n";
			} else{
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;
		case "inventaire":
			if ($_GET[valeur] == "1") {
				echo "Module $module activ&#233;.<br>\n";
			} else{
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;
		// Conf antivirus
		case "antivirus":
			$clamav_actif = exec("dpkg -s se3-clamav | grep \"Status: install ok\" > /dev/null && echo 1");
			if(($_GET[valeur]=="1") && ($clamav_actif!="1")) { //paquet pas installe on l'installe
					system("/usr/bin/sudo /usr/share/se3/scripts/install_se3-module.sh se3-clamav");
					echo "Module $module activ&#233;.<br>\n";
			} else {
				$update_query = "UPDATE clamav_dirs SET frequency='none'";
				mysql_query($update_query);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;
		// Conf du dhcp
		case "dhcp":
			if($_GET[valeur]=="1") { //si on veut l'activer
				$STOP_START="start"; 
				$dhcp_actif = exec("dpkg -s se3-dhcp | grep \"Status: install ok\" > /dev/null && echo 1");
				if($dhcp_actif!="1") { //paquet pas installe on l'installe
					system("/usr/bin/sudo /usr/share/se3/scripts/install_se3-module.sh se3-dhcp");
				} else { //sinon on l'active
					$update_query = "UPDATE params SET value='$_GET[valeur]' where name='dhcp_on_boot'";
					mysql_query($update_query);
					echo "Module $module activ&#233;.<br>\n";
				}
			}
			//	exec("/usr/bin/sudo /usr/share/se3/scripts/makedhcpdconf");
			if($_GET[valeur]=="0") { 
				$STOP_START="stop";
				$update_query = "UPDATE params SET value='$_GET[valeur]' where name='dhcp_on_boot'";
				mysql_query($update_query);
				exec("/usr/bin/sudo /usr/share/se3/scripts/makedhcpdconf");
				exec("/usr/bin/sudo /usr/share/se3/scripts/makedhcpdconf $STOP_START");
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;
		// Conf du clonage
		case "clonage":
			if($_GET[valeur]=="1") {
				$clonage_actif = exec("dpkg -s se3-clonage | grep \"Status: install ok\" > /dev/null && echo 1");
				// Si paquet pas installe
				if($clonage_actif!="1") {
					system("/usr/bin/sudo /usr/share/se3/scripts/install_se3-module.sh se3-clonage");
				} else {
					$update_query = "UPDATE params SET value='$_GET[valeur]' where name='clonage'";
					mysql_query($update_query);
					exec("/usr/bin/sudo /usr/share/se3/scripts/se3_tftp_boot_pxe.sh start");
					echo "Module $module activ&#233;.<br>\n";
				}
			}
			if($_GET[valeur]=="0") { 
				exec("/usr/bin/sudo /usr/share/se3/scripts/se3_tftp_boot_pxe.sh stop");
				$update_query = "UPDATE params SET value='$_GET[valeur]' where name='clonage'";
				mysql_query($update_query);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;
		// Conf d'unattended
		case "unattended":
			if($_GET[valeur]=="1") {
				$unattended_actif = exec("dpkg -s se3-unattended | grep \"Status: install ok\" > /dev/null && echo 1");
				// Si paquet pas installe
				if($unattended_actif!="1") {
					system("/usr/bin/sudo /usr/share/se3/scripts/install_se3-module.sh se3-unattended");
				} else {
					$update_query = "UPDATE params SET value='$_GET[valeur]' where name='unattended'";
					mysql_query($update_query);
                                        // activer unattended, c'est activer le clonage
					$update_query = "UPDATE params SET value='$_GET[valeur]' where name='clonage'";
					mysql_query($update_query);
					exec("/usr/bin/sudo /usr/share/se3/scripts/se3_tftp_boot_pxe.sh start");
					echo "Module $module et clonage activ&#233;s.<br>\n";
				}
			}
			if($_GET[valeur]=="0") { 
				$update_query = "UPDATE params SET value='$_GET[valeur]' where name='unattended'";
				mysql_query($update_query);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;
		// conf fond d'ecran
		case "fondecran":
			$valeur_fondecran=($_GET['valeur']==1) ? 1 : 0;
			$resultat=mysql_query("SELECT * FROM params WHERE name='menu_fond_ecran'");
			if(mysql_num_rows($resultat)==0){
				$sql = "INSERT INTO params VALUES('','menu_fond_ecran','$valeur_fondecran','','Affichage ou non du menu fond d ecran','6')";
			} else {
				$sql = "UPDATE params SET value='$valeur_fondecran' where name='menu_fond_ecran'";
			}
			
			if ($valeur_fondecran == 1) {
				system("/usr/bin/sudo /usr/share/se3/scripts/install_se3-module.sh se3-fondecran",$return);
				if($return==0) {
				mysql_query($sql);
				echo "Module $module activ&#233;.<br>\n";
				}
				else{
				echo "Un probl&#232;me est survenu lors de l'installation de $module.<br>\n";
				}
				
			} else{
				mysql_query($sql);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;
		// Conf de WPKG
		case "wpkg":
			if($_GET[valeur]=="1") { //si on veut l'activer
				$wpkg_actif = exec("dpkg -s se3-wpkg | grep \"Status: install ok\" > /dev/null && echo 1");
				if($wpkg_actif!="1") { //paquet pas installe on l'installe
					system("/usr/bin/sudo /usr/share/se3/scripts/install_se3-module.sh se3-wpkg");
				} else { //sinon on l'active
					$update_query = "UPDATE params SET value='$_GET[valeur]' where name='wpkg'";
					mysql_query($update_query);
					echo "Module $module activ&#233;.<br>\n";
				}
			}
			if($_GET[valeur]=="0") { 
				$update_query = "UPDATE params SET value='$_GET[valeur]' where name='wpkg'";
				mysql_query($update_query);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;
		default:
			echo "Erreur : Module '$module' inconnu !<br>\n";
	} // \switch ($_GET[varb])
	echo "<a href=\"index.html\" target=\"_top\">Actualiser l'interface de gestion du serveur.</a>";
	exit;
}

/***************************************************************************************************/

// require ("config.inc.php");

echo "<h1>".gettext("Modules optionnels")."</H1>";

// Test si un paquet est en installation par la presence d'un lock.
exec("ls /var/lock/*.lck",$files,$return);
for ($i=0; $i< count($files); $i++) {
 	if ($files[$i] == "/var/lock/se3-dhcp.lck") {
		$dhcp_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-dhcp en cours.")."</center>";
	} elseif ($files[$i] == "/var/lock/se3-clonage.lck") {
		$clonage_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-clonage en cours.")."</center>";
	} elseif ($files[$i] == "/var/lock/se3-unattended.lck") {
		$unattended_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-unattended en cours.")."</center>";
	} elseif ($files[$i] == "/var/lock/se3-clamav.lck") {
		$clamav_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-clamav en cours.")."</center>";
	} elseif ($files[$i] == "/var/lock/se3-wpkg.lck") {
		$wpkg_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-wpkg en cours.")."</center>";
	}
}

// Fait un update pour rafraichir
// exec('/usr/bin/sudo /usr/share/se3/scripts/update-secu.sh');

// Affichage du form de mise &#224; jour des param&#232;tres
echo "<br><br>";
echo "<center>";
echo "<TABLE border=\"1\" width=\"80%\">";


/********************** Modules ****************************************************/

// Modules disponibles
echo "<TR><TD colspan=\"4\" align=\"center\" class=\"menuheader\" height=\"30\">\n";
echo gettext("Modules optionnels disponibles");
echo "</TD></TR>";

echo "<TR><TD align=\"center\" class=\"menuheader\" height=\"30\">\n";
echo gettext("Module");
echo "</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Install&#233;")."</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Disponible")."</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Etat")."</TD></TR>";


// Module sauvegarde
echo "<TR><TD>".gettext("Syst&#232;me de sauvegarde (sur disque)")."</TD>";
  
$backuppc_version_install = exec("apt-cache policy se3 | grep \"Install\" | cut -d\":\" -f2");
//$ocs_version_dispo = exec("apt-cache policy se3-ocs | grep \"Candidat\" | cut -d\":\" -f2");
// Cas pour le moment particulier backuppc n'est pas un paquet debian
echo "<TD align=\"center\">$backuppc_version_install</TD>";
// On teste si on a bien la derniere version

$backuppc_version_install="1";
$backuppc_version_dispo="1";
if ("$backuppc_version_install" == "$backuppc_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	// echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $backuppc_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=\"../test.php?action=settime\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>"; 
	echo "</TD>";
}

echo "<TD align=\"center\">";
if ($backuppc=="0") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Permet d\'activer l\'interface de sauvegarde')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=backuppc&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" ></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Permet de d&#233;sactiver l\'interface de sauvegarde')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=backuppc&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" ></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Sauvegarde sur bande
// test si l'entree existe dans la table params et si non la cree
entree_table_param_exist("savbandactiv","0","5","sauvegarde sur bande");
echo "</td></tr>\n";
echo "<tr><td>".gettext("Sauvegarde sur bande")."</td>\n";
echo "<td align=\"center\">-</td>\n";
echo "<TD align=\"center\">";
echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
echo "</TD>\n";
echo "<td align=\"center\">";
if ($savbandactiv=="1") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Cliquer ici afin de d&#233;sactiver la sauvegarde sur bande. <br>Ne pas oublier de param&#233;trer cette sauvegarde depuis le menu sauvegarde.')")."\">";
	echo "<a href=conf_modules.php?action=change&amp;varb=savbandactiv&amp;valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" alt=\"Enabled\"></a>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Cliquer ici afin d\'activer la sauvegarde sur bande. <br>Vous devrez param&#233;trer cette sauvegarde depuis le menu sauvegarde.')")."\">";
	echo "<a href=conf_modules.php?action=change&amp;varb=savbandactiv&amp;valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" alt=\"Disabled\"></a>";
}
echo "</td></tr>";


// Module Inventaire

$ocs_version_install = exec("apt-cache policy se3-ocs | grep \"Install\" | cut -d\":\" -f2");
$ocs_version_dispo = exec("apt-cache policy se3-ocs | grep \"Candidat\" | cut -d\":\" -f2");

echo "<TR><TD>".gettext("Syst&#232;me d'inventaire")."</TD>";


echo "<TD align=\"center\">$ocs_version_install</TD>";

// On teste si on a bien la derniere version
if ("$ocs_version_install" == "$ocs_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $ocs_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=ocs&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}
echo "<TD align=\"center\">";
if ($inventaire=="0") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Permet d\'activer l\'inventaire')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=inventaire&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" ></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Permet de d&#233;sactiver l\'inventaire')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=inventaire&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" ></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Module Antivirus
$clam = exec("dpkg -s se3-clamav | grep \"Status: install ok\"> /dev/null && echo 1");

$clam_version_install = exec("apt-cache policy se3-clamav | grep \"Install\" | cut -d\":\" -f2");
$clam_version_dispo = exec("apt-cache policy se3-clamav | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TR><TD>".gettext("Syst&#232;me anti-virus")."</TD>";
echo "<TD align=\"center\">$clam_version_install</TD>";

// On teste si on a bien la derniere version
if ("$clam_version_install" == "$clam_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $clam_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=clamav&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>"; 
	echo "</TD>";
}
echo "<TD align=\"center\">";
if(($antivirus!="1") || ($clam!="1")) {
	if($clam!="1") { 
		$clamav_message=gettext("<b>Attention : </b>Le paquet se3-clamav ne semble pas &#234;tre install&#233;. Cliquer sur la croix rouge pour l\'installer");
		$clam_install_alert="onClick=\"alert('Installation du packet se3-clamav. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\""; 
	} else { 
		$clamav_message=gettext("<b>Etat : D&#233;sactiv&#233;</b><br>Cliquer sur le croix rouge pour activer l\'antivirus"); 
	}
	echo "<u onmouseover=\"return escape('".$clamav_message."')\">";
	echo "<a href=conf_modules.php?action=change&varb=antivirus&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" $clam_install_alert></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Permet de d&#233;sactiver l\'anti-virus')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=antivirus&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" ></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Module DHCP
$dhcp_actif = exec("dpkg -s se3-dhcp | grep \"Status: install ok\" > /dev/null && echo 1");
echo "<TR><TD>".gettext("Serveur DHCP")."</TD>";

// On teste si on a bien la derniere version

$dhcp_version_install = exec("apt-cache policy se3-dhcp | grep \"Install\" | cut -d\":\" -f2");
$dhcp_version_dispo = exec("apt-cache policy se3-dhcp | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$dhcp_version_install</TD>";  
if ("$dhcp_version_install" == "$dhcp_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $dhcp_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=dhcp&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}
  
echo "<TD align=\"center\">";
if (($dhcp!="1") || ($dhcp_actif!="1")) {
	if($dhcp_actif!="1") { 
		$dhcp_message=gettext("<b>Attention :</b> le paquet se3-dhcp n\'est pas install&#233; sur ce serveur. Cliquer sur la croix rouge pour l\'installer"); 
		$dhcp_install_alert="onClick=\"alert('Installation du packet se3-dhcp. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\""; 
	} else { 
		$dhcp_message=gettext("<b>Etat : D&#233;sactiv&#233;</b><br> Cliquer sur la croix rouge pour l\'activer"); 
	}
	echo "<u onmouseover=\"return escape('".$dhcp_message."')\">";
	echo "<a href=conf_modules.php?action=change&varb=dhcp&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$dhcp_install_alert\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Cliquer sue l\'icone verte pour d&#233;sactiver le module serveur dhcp')")."\">";
	if($clonage=="1") { $dhcp_alert="onClick=\"alert('Le clonage des stations est actif, en désactivant le dhcp celui-ci ne pourra plus fonctionner')\""; }
	echo "<a href=conf_modules.php?action=change&varb=dhcp&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" \"$dhcp_alert\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Menu fond d'ecran
$resultat=mysql_query("SELECT * FROM params WHERE name='menu_fond_ecran'");
if(mysql_num_rows($resultat)==0){
	$menu_fond_ecran=0;
}
else{
	$ligne=mysql_fetch_object($resultat);
	if($ligne->value=="1"){
		$menu_fond_ecran=1;
	}
	else {
		$menu_fond_ecran=0;
	}
}
echo "<tr><td>".gettext("Syst&#232;me fond d'&#233;cran")."</TD>";
// On teste si on a bien la derniere version
// Cas particulier fond d'ecran n'est pas un paquet
$fond_version_install = exec("apt-cache policy se3 | grep \"Install\" | cut -d\":\" -f2");
// $fond_version_dispo = exec("apt-cache policy se3-fond | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$fond_version_install</TD>";
$fond_version_install="1";
$fond_version_dispo="1";
if ("$fond_version_install" == "$fond_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $fond_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=\"../test.php?action=settime\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}
echo "<TD align=\"center\">";
if ($menu_fond_ecran=="0") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Permet d\'activer l\'affichage du menu Fond d\'&#233;cran (sous-menu de Clients Windows en niveau exp&#233;rimental)')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=fondecran&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" ></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Permet de d&#233;sactiver l\'affichage du menu Fond d\'&#233;cran')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=fondecran&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Module clonage
$clonage_actif = exec("dpkg -s se3-clonage | grep \"Status: install ok\"> /dev/null && echo 1");
echo "<TR><TD>".gettext("Clonage de stations")."</TD>";

// On teste si on a bien la derniere version
$clonage_version_install = exec("apt-cache policy se3-clonage | grep \"Install\" | cut -d\":\" -f2");
$clonage_version_dispo = exec("apt-cache policy se3-clonage | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$clonage_version_install</TD>";
if ("$clonage_version_install" == "$clonage_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $clonage_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=clonage&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}
  
echo "<TD align=\"center\">";
if (($clonage!="1") || ($clonage_actif !="1")) {
	if($dhcp!="1") { $clonage_alert="onClick=\"alert('Le clonage ne peut fonctionner qu\'avec un serveur dhcp actif. Vous devrez donc activer celui de Se3 ou en installer un.')\""; }
	if($clonage_actif!="1") { 
		$clonage_message=gettext("<b>Attention : </b>Le paquet n\'est pas install&#233; sur ce serveur. Cliquer sur la croix rouge pour l\'installer. Attention, ce module n&#233;cessite le param&#233;trage du dhcp pour fonctionner");
		$clonage_alert="onClick=\"alert('Installation du packet se3-clonage. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\""; 
	} else { 
		$clonage_message=gettext("<b>Etat : D&#233;sactiv&#233;</b><br>Cliquer sur la croix rouge pour activer ce module. <br>Pour en savoir plus sur ce module voir la documentation en ligne."); 
	}
	echo "<u onmouseover=\"return escape('".$clonage_message."')\">";
	echo "<a href=conf_modules.php?action=change&varb=clonage&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$clonage_alert\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Module de clonage actif')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=clonage&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" ></a>";
	echo "</u>";
}
echo "</td></tr>\n";
// }

// Module unattended
$unattended_actif = exec("dpkg -s se3-unattended | grep \"Status: install ok\"> /dev/null && echo 1");
echo "<TR><TD>".gettext("Installation de stations")."</TD>";

// On teste si on a bien la derniere version
$unattended_version_install = exec("apt-cache policy se3-unattended | grep \"Install\" | cut -d\":\" -f2");
$unattended_version_dispo = exec("apt-cache policy se3-unattended | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$unattended_version_install</TD>";
if ("$unattended_version_install" == "$unattended_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $unattended_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=unattended&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}
  
echo "<TD align=\"center\">";
if (($unattended!="1") || ($unattended_actif !="1")) {
	if($clonage!="1") { $unattended_alert="onClick=\"alert('L'installation ne peut fonctionner qu\'avec un serveur tftp actif. Vous devrez donc activer celui de Se3 en activant le module Clonage.')\""; }
	if($unattended_actif!="1") { 
		$unattended_message=gettext("<b>Attention : </b>Le paquet n\'est pas install&#233; sur ce serveur. Cliquer sur la croix rouge pour l\'installer.");
		$unattended_alert="onClick=\"alert('Installation du packet se3-unattended. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\""; 
	} else { 
		$unattended_message=gettext("<b>Etat : D&#233;sactiv&#233;</b><br>Cliquer sur la croix rouge pour activer ce module. <br>Pour en savoir plus sur ce module voir la documentation en ligne."); 
	}
	echo "<u onmouseover=\"return escape('".$unattended_message."')\">";
	echo "<a href=conf_modules.php?action=change&varb=unattended&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$unattended_alert\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Module d\'installation de stations actif')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=unattended&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" ></a>";
	echo "</u>";
}
echo "</td></tr>\n";

// Module wpkg
$wpkg_actif = exec("dpkg -s se3-wpkg | grep \"Status: install ok\" > /dev/null && echo 1");
echo "<TR><TD>".gettext("WPKG (D&#233;ploiement d'applications)")."</TD>";

// On teste si on a bien la derniere version
$wpkg_version_install = exec("apt-cache policy se3-wpkg | grep \"Install\" | cut -d\":\" -f2");
$wpkg_version_dispo = exec("apt-cache policy se3-wpkg | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$wpkg_version_install</TD>";
if ("$wpkg_version_install" == "$wpkg_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $wpkg_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=wpkg&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}

echo "<TD align=\"center\">";
if (($wpkg!="1") || ($wpkg_actif!="1")) {
	if($wpkg_actif!="1") { 
		$wpkg_message=gettext("<b>Attention :</b> le paquet se3-wpkg n\'est pas install&#233; sur ce serveur. Cliquer sur la croix rouge pour l\'installer"); 
		$wpkg_install_alert="onClick=\"alert('Installation du packet se3-wpkg. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\""; 
	} else { 
		$wpkg_message=gettext("<b>Etat : D&#233;sactiv&#233;</b><br> Cliquer sur la croix rouge pour l\'activer"); 
	}

	echo "<u onmouseover=\"return escape('".$wpkg_message."')\">";
	echo "<a href=conf_modules.php?action=change&varb=wpkg&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$wpkg_install_alert\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Cliquer sue l\'icone verte pour d&#233;sactiver le module wpkg')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=wpkg&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" \"$wpkg_alert\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";



/************************* Fin modules ****************************************************/

echo "</td></tr>\n";
echo "</table>";

include("pdp.inc.php");
?>
