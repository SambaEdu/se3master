<?php

/**
   * Page qui permet de gerer les modules (installation - desactivation - mises a jour)
   * @Version $Id: conf_modules.php 9320 2016-04-28 22:23:34Z keyser $

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


ob_implicit_flush(true);
ob_end_flush();

$module = "se3-".$_GET['varb'];
// Mise a jour
if ($_GET['action'] == "update") {
	echo "<h1>Instation ou mise &#224; jour du module $module</h1>";
	//same command as majtest.php - keyser 10-2016
	//system('sleep 1; /usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh se3 &');
	system("sleep 1; /usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i $module &");
    echo "<br><a href=\"conf_modules.php\">Retour &#224; l'interface de gestion des modules.</a>";
	exit;
}

// Change dans la base
if ($_GET['action'] == "change") {

	echo "<H1>Modification de l'&#233;tat du module $module</H1>";
	// Change dnas la table params
	$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "UPDATE params set value='".$_GET['valeur']."' where name='$_GET[varb]'");
	switch ($_GET['varb']) {
		case "savbandactiv":
			if ($_GET['valeur'] == "1") {
				echo "Module $module activ&#233;.<br>\n";
			} else{
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;

        // Installation de l'inventaire
		case "inventaire":
			if($_GET['valeur']=="1") {
				$ocs_actif = exec("dpkg -s se3-ocs | grep \"Status: install ok\" > /dev/null && echo 1");
				// Si paquet pas installe
				if($ocs_actif!="1") {
					system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-ocs");
                                    }


				echo "Module $module activ&#233;.<br>\n";
			} else{
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;

		// Installation de l'antivirus (se3-clamav)
		case "antivirus":
			$clamav_actif = exec("dpkg -s se3-clamav | grep \"Status: install ok\" > /dev/null && echo 1");
			if(($_GET['valeur']=="1") && ($clamav_actif!="1")) { //paquet pas installe on l'installe
					system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-clamav");
					echo "Module $module activ&#233;.<br>\n";
			} else {
				$update_query = "UPDATE clamav_dirs SET frequency='none'";
				mysqli_query($GLOBALS["___mysqli_ston"], $update_query);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;

		// Installation  du dhcp
		case "dhcp":
			if($_GET['valeur']=="1") { //si on veut l'activer
				$STOP_START="start";
				$dhcp_actif = exec("dpkg -s se3-dhcp | grep \"Status: install ok\" > /dev/null && echo 1");
				if($dhcp_actif!="1") { //paquet pas installe on l'installe
					system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-dhcp");
				} else { //sinon on l'active
					$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='dhcp_on_boot'";
					mysqli_query($GLOBALS["___mysqli_ston"], $update_query);
					echo "Module $module activ&#233;.<br>\n";
				}
			}
			//	exec("/usr/bin/sudo /usr/share/se3/scripts/makedhcpdconf");
			if($_GET['valeur']=="0") {
				$STOP_START="stop";
				$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='dhcp_on_boot'";
				mysqli_query($GLOBALS["___mysqli_ston"], $update_query);
				exec("/usr/bin/sudo -H /usr/share/se3/scripts/makedhcpdconf");
				exec("/usr/bin/sudo -H /usr/share/se3/scripts/makedhcpdconf $STOP_START");
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;

		// Installation  du clonage
		case "clonage":
			if($_GET['valeur']=="1") {
				$clonage_actif = exec("dpkg -s se3-clonage | grep \"Status: install ok\" > /dev/null && echo 1");
				// Si paquet pas installe
				if($clonage_actif!="1") {
					system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-clonage");
				} else {
					$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='clonage'";
					mysqli_query($GLOBALS["___mysqli_ston"], $update_query);
					exec("/usr/bin/sudo -H /usr/share/se3/scripts/se3_tftp_boot_pxe.sh start");
					echo "Module $module activ&#233;.<br>\n";
				}
			}
			if($_GET['valeur']=="0") {
				exec("/usr/bin/sudo -H /usr/share/se3/scripts/se3_tftp_boot_pxe.sh stop");
				$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='clonage'";
				mysqli_query($GLOBALS["___mysqli_ston"], $update_query);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;

		// Installation  d'unattended
// 		case "unattended":
// 			if($_GET['valeur']=="1") {
// 				$unattended_actif = exec("dpkg -s se3-unattended | grep \"Status: install ok\" > /dev/null && echo 1");
// 				// Si paquet pas installe
// 				if($unattended_actif!="1") {
// 					system("/usr/bin/sudo /usr/share/se3/scripts/install_se3-module.sh -i se3-unattended");
// 				} else {
// 					$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='unattended'";
// 					mysql_query($update_query);
//                                         // activer unattended, c'est activer le clonage
// 					$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='clonage'";
// 					mysql_query($update_query);
// 					exec("/usr/bin/sudo /usr/share/se3/scripts/se3_tftp_boot_pxe.sh start");
// 					echo "Module $module et clonage activ&#233;s.<br>\n";
// 				}
// 			}
// 			if($_GET['valeur']=="0") {
// 				$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='unattended'";
// 				mysql_query($update_query);
// 				echo "Module $module d&#233;sactiv&#233;.<br>\n";
// 			}
// 			break;

		// Installation  fond d'ecran
		case "fondecran":
			$valeur_fondecran=($_GET['valeur']==1) ? 1 : 0;
			$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * FROM params WHERE name='menu_fond_ecran'");
			if(mysqli_num_rows($resultat)==0){
				$sql = "INSERT INTO params VALUES('','menu_fond_ecran','$valeur_fondecran','','Affichage ou non du menu fond d ecran','6')";
			} else {
				$sql = "UPDATE params SET value='$valeur_fondecran' where name='menu_fond_ecran'";
			}

			if ($valeur_fondecran == 1) {
				system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-fondecran",$return);
				if($return==0) {
				mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				echo "Module $module activ&#233;.<br>\n";
				}
				else{
				echo "Un probl&#232;me est survenu lors de l'installation de $module.<br>\n";
				}

			} else{
				mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;

		// Installation d'internet (se3-internet)
		case "internet":
			$valeur_internet=($_GET['valeur']==1) ? 1 : 0;
			$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * FROM params WHERE name='internet'");
			if(mysqli_num_rows($resultat)==0){
				$sql = "INSERT INTO params VALUES('','internet','1','','Activation ou d�sactivation module se3-internet','6')";
			} else {
				$sql = "UPDATE params SET value='$valeur_internet' where name='internet'";
			}

			if ($valeur_internet == 1) {
				system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-internet",$return);
				if($return==0) {
				mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				echo "Module $module activ&#233;.<br>\n";
				}
				else{
				echo "Un probl&#232;me est survenu lors de l'installation de $module.<br>\n";
				}

			} else{
				mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;

		// Installation de BackupPc (se3-backup)
		case "backup":
			if($_GET['valeur']=="1") {
				$backup_actif = exec("dpkg -s se3-backup | grep \"Status: install ok\" > /dev/null && echo 1");
				// Si paquet pas installe
				if($backup_actif!="1") {
					system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-backup");
				} else {
					$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='backuppc'";
					mysqli_query($GLOBALS["___mysqli_ston"], $update_query);
                                        echo "Module $module activ&#233;.<br>\n";
				}
			}
			if($_GET['valeur']=="0") {
				$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='backuppc'";
				mysqli_query($GLOBALS["___mysqli_ston"], $update_query);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
				include ("fonction_backup.inc.php");
                                stopBackupPc();
			}
			break;

		// conf synchro
		case "synchro":
			$valeur_synchro=($_GET['valeur']==1) ? 1 : 0;
			$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * FROM params WHERE name='unison'");
			if(mysqli_num_rows($resultat)==0){
				$sql = "INSERT INTO params VALUES('','unison','1','','Activation ou d�sactivation module se3-synchro','6')";
			} else {
				$sql = "UPDATE params SET value='$valeur_synchro' where name='unison'";
			}

			if ($valeur_synchro == 1) {
				system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-synchro",$return);
				if($return==0) {
				mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				echo "Module $module activ&#233;.<br>\n";
				}
				else{
				echo "Un probl&#232;me est survenu lors de l'installation de $module.<br>\n";
				}

			} else{
				system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -r se3-synchro",$return);
				mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;

		// conf se3-logonpy
		case "logonpy":
			$valeur_logonpy=($_GET['valeur']==1) ? 1 : 0;


			if ($valeur_logonpy == 1) {
				system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-logonpy",$return);
				if($return==0) {
				echo "Module $module mis &#224; jour.<br>\n";
				}
				else{
				echo "Un probl&#232;me est survenu lors de l'installation de $module.<br>\n";
				}

			}
			break;

		// Installation de  se3-domain
		case "domain":
			$valeur_domain=($_GET['valeur']==1) ? 1 : 0;


			if ($valeur_domain == 1) {
				system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-domain",$return);
				if($return==0) {
				echo "Module $module mis &#224; jour.<br>\n";
				}
				else{
				echo "Un probl&#232;me est survenu lors de l'installation de $module.<br>\n";
				}

			}
			break;


		// Installation de WPKG (se3-wpkg)
		case "wpkg":
			if($_GET['valeur']=="1") { //si on veut l'activer
				$wpkg_actif = exec("dpkg -s se3-wpkg | grep \"Status: install ok\" > /dev/null && echo 1");
				if($wpkg_actif!="1") { //paquet pas installe on l'installe
					system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-wpkg");
				} else { //sinon on l'active
					$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='wpkg'";
					mysqli_query($GLOBALS["___mysqli_ston"], $update_query);
					echo "Module $module activ&#233;.<br>\n";
				}
			}
			if($_GET['valeur']=="0") {
				$update_query = "UPDATE params SET value='".$_GET['valeur']."' where name='wpkg'";
				mysqli_query($GLOBALS["___mysqli_ston"], $update_query);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			}
			break;

        // Installation du client Linux
		case "linux":
			$valeur_linux=($_GET['valeur']==1) ? 1 : 0;
			echo $valeur_linux;
			$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * FROM params WHERE name='support_linux'");
			if(mysqli_num_rows($resultat)==0){
				$sql = "INSERT INTO params VALUES('','support_linux','$valeur_linux','','Installation du backport se3-clients-linux pour linux','6')";
			} else {
				$sql = "UPDATE params SET value='$valeur_linux' where name='support_linux'";
			}

			if ($valeur_linux == 1) {
				system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-clients-linux",$return);
				if($return==0) {
				mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				echo "Support linux activ&#233;.<br>\n";
				}
				else{
				echo "Un probl&#232;me est survenu lors de l'installation du backport se3-clients-linux.<br>\n";
				}

			}
			break;

		// Installation  de PhpLdapAdmin (se3-pla)
		case "pla":
			$valeur_pla=($_GET['valeur']==1) ? 1 : 0;
			$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * FROM params WHERE name='pla'");
			if(mysqli_num_rows($resultat)==0){
				$sql = "INSERT INTO params VALUES('','pla','$valeur_pla','','Installation de phpldapadmin','6')";
			} else {
				$sql = "UPDATE params SET value='$valeur_pla' where name='pla'";
			}

			if ($valeur_pla == 1) {
				// on active
				system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-pla",$return);
				if($return==0) {
				mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				echo "phpldapadmin activ&#233;.<br>\n";
				}
				else{
				echo "Un probl&#232;me est survenu lors de l'installation de phpldapadmin.<br>\n";
				}

			} else{
				// on désactive
				system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -r se3-pla",$return);
				mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				echo "Module $module d&#233;sactiv&#233;.<br>\n";
			
			}
			break;

        // Installation de Radius (se3-radius)
        case "radius":
            $valeur_radius=($_GET['valeur']==1) ? 1 : 0;
            $resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * FROM params WHERE name='radius'");
            if(mysqli_num_rows($resultat)==0){
                $sql = "INSERT INTO params VALUES('','radius','1','','Activation ou d&#233sactivation module se3-radius','6')";
            } else {
                $sql = "UPDATE params SET value='$valeur_radius' where name='radius'";
            }

            if ($valeur_radius == 1) {
                system("/usr/bin/sudo -H /usr/share/se3/scripts/install_se3-module.sh -i se3-radius",$return);
                if($return==0) {
                mysqli_query($GLOBALS["___mysqli_ston"], $sql);
                echo "Module $module activ&#233;.<br>\n";
                }
                else{
                echo "Un probl&#232;me est survenu lors de l'installation de $module.<br>\n";
                }

            } else{
                mysqli_query($GLOBALS["___mysqli_ston"], $sql);
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

echo "<h1>".gettext("Gestion des modules SE3")."</H1>";

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
	} elseif ($files[$i] == "/var/lock/se3-logonpy.lck") {
		$logonpy_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-logonpy en cours.")."</center>";
	} elseif ($files[$i] == "/var/lock/se3-domain.lck") {
		$domain_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-domain en cours.")."</center>";
	} elseif ($files[$i] == "/var/lock/se3-internet.lck") {
		$internet_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-internet en cours.")."</center>";
	} elseif ($files[$i] == "/var/lock/se3-backup.lck") {
		$backup_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-backup en cours.")."</center>";
	} elseif ($files[$i] == "/var/lock/se3-clients-linux.lck") {
		$clients_linux_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-clients-linux en cours.")."</center>";
	} elseif ($files[$i] == "/var/lock/se3-synchro.lck") {
		$synchro_lock="yes";
		echo "<br><center>".gettext("Attention : installation du paquet se3-synchro en cours.")."</center>";
    } elseif ($files[$i] == "/var/lock/se3-radius.lck") {
        $radius_lock="yes";
        echo "<br><center>".gettext("Attention : installation du paquet se3-synchro en cours.")."</center>";
	}

}

// Fait un update pour rafraichir
// exec('/usr/bin/sudo /usr/share/se3/scripts/update-secu.sh');

// Affichage du form de mise &#224; jour des param&#232;tres



/********************** Modules ****************************************************/
echo "<br><br>";
echo "<center>";
echo "<TABLE border=\"1\" width=\"80%\">";



// Modules disponibles
echo "<TR><TD colspan=\"4\" align=\"center\" class=\"menuheader\" height=\"30\">\n";
echo gettext("Etat des modules indispensables");
echo "</TD></TR>";

echo "<TR><TD align=\"center\" class=\"menuheader\" height=\"30\">\n";
echo gettext("Module");
echo "</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Install&#233;")."</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Disponible")."</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Etat")."</TD></TR>";


// Module se3-domain
$domain_actif = exec("dpkg -s se3-domain | grep \"Status: install ok\"> /dev/null && echo 1");
echo "<TR><TD>".gettext("Scripts de jonction au domaine (se3-domain)")."</TD>";

// On teste si on a bien la derniere version
$domain_version_install = exec("apt-cache policy se3-domain | grep \"Install\" | cut -d\":\" -f2");
$domain_version_dispo = exec("apt-cache policy se3-domain | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$domain_version_install</TD>";
if ("$domain_version_install" == "$domain_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $domain_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=domain&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}

echo "<TD align=\"center\">";
if ($domain_actif!="1") {
	$domain_message=gettext("<b>Attention : </b>Le paquet n\'est pas install&#233; sur ce serveur. Cliquez pour l\'installer.");
	$domain_alert="onClick=\"alert('Installation du packet se3-domain. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\"";

	echo "<u onmouseover=\"return escape('".$domain_message."')\">";
	echo "<a href=conf_modules.php?action=change&varb=domain&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$domain_alert\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Module install�')")."\">";
	echo "<IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" >";
	echo "</u>";
}

// Module se3-logonpy
$logonpy_actif = exec("dpkg -s se3-logonpy | grep \"Status: install ok\"> /dev/null && echo 1");
echo "<TR><TD>".gettext("Gestion de l'environnement (se3-logonpy)")."</TD>";

// On teste si on a bien la derniere version
$logonpy_version_install = exec("apt-cache policy se3-logonpy | grep \"Install\" | cut -d\":\" -f2");
$logonpy_version_dispo = exec("apt-cache policy se3-logonpy | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$logonpy_version_install</TD>";
if ("$logonpy_version_install" == "$logonpy_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $logonpy_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=logonpy&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}

echo "<TD align=\"center\">";
if ($logonpy_actif!="1") {
	$logonpy_message=gettext("<b>Attention : </b>Le paquet n\'est pas install&#233; sur ce serveur. Cliquez pour l\'installer.");
	$logonpy_alert="onClick=\"alert('Installation du packet se3-logonpy. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\"";

	echo "<u onmouseover=\"return escape('".$logonpy_message."')\">";
	echo "<a href=conf_modules.php?action=change&varb=logonpy&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$logonpy_alert\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Module install&#233</b>')")."\">";
	echo "<IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" >";
	echo "</u>";
}



echo "</td></tr>\n";
echo "</table>";

echo "<br><br>";
echo "<TABLE border=\"1\" width=\"80%\">";

// Modules disponibles
echo "<TR><TD colspan=\"4\" align=\"center\" class=\"menuheader\" height=\"30\">\n";
echo gettext("Etat des modules optionnels");
echo "</TD></TR>";

echo "<TR><TD align=\"center\" class=\"menuheader\" height=\"30\">\n";
echo gettext("Module");
echo "</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Install&#233;")."</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Disponible")."</TD><TD align=\"center\" class=\"menuheader\" height=\"30\">".gettext("Etat")."</TD></TR>";



// Module backup
$backup_actif = exec("dpkg -s se3-backup | grep \"Status: install ok\"> /dev/null && echo 1");
echo "<TR><TD>".gettext("Sauvegarde sur disque ou NAS (se3-backup)")."</TD>";

// On teste si on a bien la derniere version
$backup_version_install = exec("apt-cache policy se3-backup | grep \"Install\" | cut -d\":\" -f2");
$backup_version_dispo = exec("apt-cache policy se3-backup | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$backup_version_install</TD>";
if ("$backup_version_install" == "$backup_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $backup_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=backup&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}

echo "<TD align=\"center\">";
if (($backuppc!="1") || ($backup_actif !="1")) {
	if($backup_actif!="1") {
		$backup_message=gettext("<b>Attention : </b>Le paquet n\'est pas install&#233; sur ce serveur. Cliquer sur la croix rouge pour l\'installer.");
		$backup_alert="onClick=\"alert('Installation du packet se3-backup. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\"";
	} else {
		$backup_message=gettext("<b>Etat : D&#233;sactiv&#233;</b><br>Cliquer sur la croix rouge pour activer ce module. <br>Pour en savoir plus sur ce module voir la documentation en ligne.");
	}
	echo "<u onmouseover=\"return escape('".$backup_message."')\">";
	echo "<a href=conf_modules.php?action=change&varb=backup&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$backup_alert\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Module sauvegarde backuppc actif')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=backup&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" ></a>";
	echo "</u>";
}

echo "</td></tr>\n";



// Module Inventaire

$ocs_version_install = exec("apt-cache policy se3-ocs | grep \"Install\" | cut -d\":\" -f2");
$ocs_version_dispo = exec("apt-cache policy se3-ocs | grep \"Candidat\" | cut -d\":\" -f2");

echo "<TR><TD>".gettext("Syst&#232;me d'inventaire (se3-ocs)")."</TD>";


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
echo "<TR><TD>".gettext("Syst&#232;me anti-virus (se3-clamav)")."</TD>";
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
echo "<TR><TD>".gettext("Serveur DHCP (se3-dhcp)")."</TD>";

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
	if($clonage=="1") { $dhcp_alert="onClick=\"alert('Le clonage des stations est actif, en d�sactivant le dhcp celui-ci ne pourra plus fonctionner')\""; }
	echo "<a href=conf_modules.php?action=change&varb=dhcp&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" \"$dhcp_alert\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Menu fond d'ecran
$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * FROM params WHERE name='menu_fond_ecran'");
if(mysqli_num_rows($resultat)==0){
	$menu_fond_ecran=0;
}
else{
	$ligne=mysqli_fetch_object($resultat);
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


//Menu support clients linux
$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * FROM params WHERE name='support_linux'");
if(mysqli_num_rows($resultat)==0){
	$support_linux=0;
}
else{
	$ligne=mysqli_fetch_object($resultat);
	if($ligne->value=="1"){
		$support_linux=1;
	}
	else {
		$support_linux=0;
	}
}
echo "<tr><td>".gettext("Support des clients GNU/linux")."</TD>";
// On teste si on a bien la derniere version
// Cas particulier fond d'ecran n'est pas un paquet
$linux_version_install = exec("apt-cache policy se3-clients-linux | grep \"Install\" | cut -d\" \" -f4");
// $fond_version_dispo = exec("apt-cache policy se3-fond | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$linux_version_install</TD>";
//$linux_version_install="1";
$linux_version_dispo = exec("apt-cache policy se3-clients-linux | grep \"Candidat\" | cut -d\" \" -f4");
if ("$linux_version_install" == "$linux_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version du paquet se3-clients-linux disponible')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Cliquer ici pour lancer l\'installation ou la mise &#224; jour du paquet se3-clients-linux')")."\"><a href=\"conf_modules.php?action=change&varb=linux&valeur=1\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";

}
echo "<TD align=\"center\">";
if ($support_linux=="0") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Permet d\'activer le support des stations linux en installant le module se3 ad&#233;quat)')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=linux&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" ></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Le support des clients linux est actif')")."\">";
	echo "<IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\"></a>";
	echo "</u>";
}
echo "</td></tr>\n";


// Module clonage
$clonage_actif = exec("dpkg -s se3-clonage | grep \"Status: install ok\"> /dev/null && echo 1");
echo "<TR><TD>".gettext("Clonage / sauvegarde - restauration de stations (se3-clonage)")."</TD>";

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
// $unattended_actif = exec("dpkg -s se3-unattended | grep \"Status: install ok\"> /dev/null && echo 1");
// echo "<TR><TD>".gettext("Installation de stations (se3-unattended)")."</TD>";
// 
// // On teste si on a bien la derniere version
// $unattended_version_install = exec("apt-cache policy se3-unattended | grep \"Install\" | cut -d\":\" -f2");
// $unattended_version_dispo = exec("apt-cache policy se3-unattended | grep \"Candidat\" | cut -d\":\" -f2");
// echo "<TD align=\"center\">$unattended_version_install</TD>";
// if ("$unattended_version_install" == "$unattended_version_dispo") {
// 	echo "<TD align=\"center\">";
// 	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
// 	echo "</TD>";
// } else {
// 	echo "<TD align=\"center\">";
// 	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $unattended_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=unattended&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
// 	echo "</TD>";
// }
// 
// echo "<TD align=\"center\">";
// if (($unattended!="1") || ($unattended_actif !="1")) {
// 	if($clonage!="1") { $unattended_alert="onClick=\"alert('L'installation ne peut fonctionner qu\'avec un serveur tftp actif. Vous devrez donc activer celui de Se3 en activant le module Clonage.')\""; }
// 	if($unattended_actif!="1") {
// 		$unattended_message=gettext("<b>Attention : </b>Le paquet n\'est pas install&#233; sur ce serveur. Cliquer sur la croix rouge pour l\'installer.");
// 		$unattended_alert="onClick=\"alert('Installation du packet se3-unattended. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\"";
// 	} else {
// 		$unattended_message=gettext("<b>Etat : D&#233;sactiv&#233;</b><br>Cliquer sur la croix rouge pour activer ce module. <br>Pour en savoir plus sur ce module voir la documentation en ligne.");
// 	}
// 	echo "<u onmouseover=\"return escape('".$unattended_message."')\">";
// 	echo "<a href=conf_modules.php?action=change&varb=unattended&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$unattended_alert\"></a>";
// 	echo "</u>";
// } else {
// 	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Module d\'installation de stations actif')")."\">";
// 	echo "<a href=conf_modules.php?action=change&varb=unattended&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" ></a>";
// 	echo "</u>";
// }
// echo "</td></tr>\n";

// Module wpkg
$wpkg_actif = exec("dpkg -s se3-wpkg | grep \"Status: install ok\" > /dev/null && echo 1");
echo "<TR><TD>".gettext("D&#233;ploiement d'applications (se3-wpkg)")."</TD>";

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


// Module internet
$internet_actif = exec("dpkg -s se3-internet | grep \"Status: install ok\"> /dev/null && echo 1");
echo "<TR><TD>".gettext("contr&#244;le de l'acc&#232;s internet (se3-internet)")."</TD>";

// On teste si on a bien la derniere version
$internet_version_install = exec("apt-cache policy se3-internet | grep \"Install\" | cut -d\":\" -f2");
$internet_version_dispo = exec("apt-cache policy se3-internet | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$internet_version_install</TD>";
if ("$internet_version_install" == "$internet_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $internet_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=internet&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}

echo "<TD align=\"center\">";
if (($internet!="1") || ($internet_actif !="1")) {
	if($internet_actif!="1") {
		$internet_message=gettext("<b>Attention : </b>Le paquet n\'est pas install&#233; sur ce serveur. Cliquer sur la croix rouge pour l\'installer.");
		$internet_alert="onClick=\"alert('Installation du packet se3-internet. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\"";
	} else {
		$internet_message=gettext("<b>Etat : D&#233;sactiv&#233;</b><br>Cliquer sur la croix rouge pour activer ce module. <br>Pour en savoir plus sur ce module voir la documentation en ligne.");
	}
	echo "<u onmouseover=\"return escape('".$internet_message."')\">";
	echo "<a href=conf_modules.php?action=change&varb=internet&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$internet_alert\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Module contr&#244;le de l\'acc&#232;s internet des stations actif')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=internet&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" ></a>";
	echo "</u>";
}


// Module synchro
$synchro_actif = exec("dpkg -s se3-synchro | grep \"Status: install ok\"> /dev/null && echo 1");
echo "<TR><TD>".gettext("synchronisation distante de fichiers (se3-synchro)")."</TD>";

// On teste si on a bien la derniere version
$synchro_version_install = exec("apt-cache policy se3-synchro | grep \"Install\" | cut -d\":\" -f2");
$synchro_version_dispo = exec("apt-cache policy se3-synchro | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$synchro_version_install</TD>";
if ("$synchro_version_install" == "$synchro_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $synchro_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=synchro&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";
}

echo "<TD align=\"center\">";
if (($unison!="1") || ($synchro_actif !="1")) {
	if($synchro_actif!="1") {
		$synchro_message=gettext("<b>Attention : </b>Le paquet n\'est pas install&#233; sur ce serveur. Cliquer sur la croix rouge pour l\'installer.");
		$synchro_alert="onClick=\"alert('Installation du packet se3-synchro. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\"";
	} else {
		$synchro_message=gettext("<b>Etat : D&#233;sactiv&#233;</b><br>Cliquer sur la croix rouge pour activer ce module. <br>Pour en savoir plus sur ce module voir la documentation en ligne.");
	}
	echo "<u onmouseover=\"return escape('".$synchro_message."')\">";
	echo "<a href=conf_modules.php?action=change&varb=synchro&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$synchro_alert\"></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Module de synchronisation distance de ses donn&#233;es des stations actif')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=synchro&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" ></a>";
	echo "</u>";
}


//**********************************************************************************************************************************
// Module PLA

$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * FROM params WHERE name='pla'");
if(mysqli_num_rows($resultat)==0){
	$pla=0;
}
else{
	$ligne=mysqli_fetch_object($resultat);
	if($ligne->value=="1"){
		// installé
		$pla=1;
	}
	else {
		// pas installé
		$pla=0;
	}
}
echo "<tr><td>".gettext("phpldapadmin")."</TD>";
// On teste si on a bien la derniere version
$pla_version_install = exec("apt-cache policy se3-pla | grep \"Install\" | cut -d\" \" -f4");

echo "<TD align=\"center\">$pla_version_install</TD>";

$pla_version_dispo = exec("apt-cache policy se3-pla | grep \"Candidat\" | cut -d\" \" -f4");
if ("$pla_version_install" == "$pla_version_dispo") {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version du paquet se3-pla disponible')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
	echo "</TD>";
} else {
	echo "<TD align=\"center\">";
	echo "<u onmouseover=\"return escape".gettext("('Cliquer ici pour lancer l\'installation ou la mise &#224; jour du paquet se3-pla')")."\"><a href=\"conf_modules.php?action=change&varb=pla&valeur=1\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
	echo "</TD>";

}
echo "<TD align=\"center\">";
if ($pla=="0") {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : D&#233;sactiv&#233;</b><br><br>Permet d\'installer phpldapadmin)')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=pla&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" ></a>";
	echo "</u>";
} else {
	echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>phpldapadmin est actif')")."\">";
	echo "<a href=conf_modules.php?action=change&varb=pla&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\"></a>";
	echo "</u>";
}


// Module Radius

$resultat=mysqli_query($GLOBALS["___mysqli_ston"], "SELECT * FROM params WHERE name='radius'");
if(mysqli_num_rows($resultat)==0){
    $radius=0;
}
else{
    $ligne=mysqli_fetch_object($resultat);
    if($ligne->value=="1"){
        // installé
        $radius=1;
    }
    else {
        // pas installé
        $radius=0;
    }
}

$radius_actif = exec("dpkg -s se3-radius | grep \"Status: install ok\"> /dev/null && echo 1");
echo "<TR><TD>".gettext("Installation de Radius (se3-radius)")."</TD>";

// On teste si on a bien la derniere version
$radius_version_install = exec("apt-cache policy se3-radius | grep \"Install\" | cut -d\":\" -f2");
$radius_version_dispo = exec("apt-cache policy se3-radius | grep \"Candidat\" | cut -d\":\" -f2");
echo "<TD align=\"center\">$radius_version_install</TD>";
if ("$radius_version_install" == "$radius_version_dispo") {
    echo "<TD align=\"center\">";
    echo "<u onmouseover=\"return escape".gettext("('Pas de nouvelle version de ce module')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/recovery.png\"></u>";
    echo "</TD>";
} else {
    echo "<TD align=\"center\">";
    echo "<u onmouseover=\"return escape".gettext("('Mise &#224; jour version $radius_version_dispo disponible.<br>Cliquer ici pour lancer la mise &#224; jour de ce module.')")."\"><a href=conf_modules.php?action=update&varb=radius&valeur=1><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/warning.png\"></a></u>";
    echo "</TD>";
}

echo "<TD align=\"center\">";
if (($radius!="1") || ($radius_actif !="1")) {
    if($radius_actif!="1") {
        $radius_message=gettext("<b>Attention : </b>Le paquet n\'est pas install&#233; sur ce serveur. Cliquer sur la croix rouge pour l\'installer.");
        $radius_alert="onClick=\"alert('Installation du packet se3-radius. Cela peut prendre un peu de temps. Vous devez avoir une connexion internet active')\"";
    } else {
        $synchro_message=gettext("<b>Etat : D&#233;sactiv&#233;</b><br>Cliquer sur la croix rouge pour activer ce module. <br>Pour en savoir plus sur ce module voir la documentation en ligne.");
    }
    echo "<u onmouseover=\"return escape('".$radius_message."')\">";
    echo "<a href=conf_modules.php?action=change&varb=radius&valeur=1><IMG style=\"border: 0px solid;\" SRC=\"elements/images/disabled.png\" \"$radius_alert\"></a>";
    echo "</u>";
} else {
    echo "<u onmouseover=\"return escape".gettext("('<b>Etat : Activ&#233;</b><br><br>Module radius pour la prise en charge du Wifi')")."\">";
    echo "<a href=conf_modules.php?action=change&varb=radius&valeur=0><IMG style=\"border: 0px solid;\" SRC=\"elements/images/enabled.png\" ></a>";
    echo "</u>";
}




echo "</td></tr>\n";



/************************* Fin modules ****************************************************/

echo "</td></tr>\n";
echo "</table>";

include("pdp.inc.php");
?>
