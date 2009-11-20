<?php
/* $Id$ */

/******************************************************************
SambaEdu3 - 16 janvier 2005
Chadefaux Philippe
Interface d'administration de SambaEdu

Ce script est distribué selon les termes de la licence GPL

Librairie d'outils
******************************************************************/

#
# Fonctions utilises
#

     
function inventaire_actif() {
	include("config.inc.php");
	return $inventaire;
}	

function fping($ip) { // Ping une machine Return 1 si Ok 0 pas de ping
	return exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'");
}


function avoir_ip($mpenc) { // Retourne l'adresse IP d'une machine en fonction de son nom ou 0 si pas d'IP
                 
	$mp_curr=search_machines("(&(cn=$mpenc)(objectClass=ipHost))","computers");
        if ($mp_curr[0]["ipHostNumber"]) {
                $iphost=$mp_curr[0]["ipHostNumber"];
		return $iphost;
	} else {
		return 0;
	}	
}	

function testMaintenance($mpenc) { // Retourne si une machine a une demande de maintenance et le type
	$dbnameinvent="ocsweb";
        include("dbconfig.inc.php");
	$authlink_invent=@mysql_connect($_SESSION["SERVEUR_SQL"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	@mysql_select_db($dbnameinvent) or die("Impossible de se connecter &#224; la base $dbnameinvent.");
        $query="select * from repairs where (STATUT='2' or STATUT='0') and NAME='$mpenc'";
        $result = mysql_query($query,$authlink_invent);
        $ligne=mysql_num_rows($result);
	if ($ligne > 0) {
                while ($row = mysql_fetch_array($result)) {
                        return $row["PRIORITE"];
		}
	}
}	

function connexion_smb($mpenc) { //Retourne le login de la connexion en cours ou 0
	$ip=avoir_ip($mpenc);
	$connect_smb = exec("smbstatus | grep '($ip)' | cut -d' ' -f5");
	if ($connect_smb!="") { return $connect_smb; } 
}

function der_inventaire($nom_machine) { // retourne la date du dernier inventaire a partir de hardware
        include "dbconfig.inc.php";
	$dbnameinvent="ocsweb";

	$authlink_invent=@mysql_connect($_SESSION["SERVEUR_SQL"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	@mysql_select_db($dbnameinvent) or die("Impossible de se connecter &#224; la base $dbnameinvent.");
	
	$query="select OSNAME,WORKGROUP,PROCESSORS,MEMORY,IPADDR,LASTDATE from hardware where NAME='$nom_machine'";
	$result = mysql_query($query,$authlink_invent);
	if ($result) {
        	$ligne=mysql_num_rows($result);
		if ($ligne > 0) {
                	while ($res = mysql_fetch_array($result)) {
				$retour = $res["OSNAME"]." WG : ".$res["WORKGROUP"]." P : ".$res["PROCESSORS"]." Mem : ".$res["MEMORY"]." DI : ";
	        		if ($res["LASTDATE"]) {
		        		$retour .= date('d M Y',strtotime($res["LOGDATE"]));
				}
			}
		} else {
			$retour=0;
		}
		
		return $retour;
	} else { // Pas d'inventaire à ce nom
		return 0;
	}	
}

function avoir_systemid($nom_machine) { // retourne l'ID de $nom_machine ou 0 à partir de la table hardware
        include "dbconfig.inc.php";
	$dbnameinvent="ocsweb";

	$authlink_invent=@mysql_connect($_SESSION["SERVEUR_SQL"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	@mysql_select_db($dbnameinvent) or die("Impossible de se connecter &#224; la base $dbnameinvent.");
	
	$query="select ID from hardware where NAME='$nom_machine'";
	$result = mysql_query($query,$authlink_invent);
	if ($result) {
        	$ligne=mysql_num_rows($result);
		if ($ligne > 0) {
                	while ($res = mysql_fetch_array($result)) {
				$retour=$res["ID"];
			}
		} else {
			$retour=0;
		}
		
		return $retour;
	} else { // Pas d'inventaire à ce nom
		return 0;
	}	
}

function type_os($nom_machine) { // retourne l'os de la machine
        include "dbconfig.inc.php";
	$dbnameinvent="ocsweb";

	$authlink_invent=@mysql_connect($_SESSION["SERVEUR_SQL"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	@mysql_select_db($dbnameinvent) or die("Impossible de se connecter &#224; la base $dbnameinvent.");
	
	$query="select OSNAME from hardware where NAME='$nom_machine'";
	$result = mysql_query($query,$authlink_invent);
	if ($result) {
        	$ligne=mysql_num_rows($result);
		if ($ligne > 0) {
                	while ($res = mysql_fetch_array($result)) {
				$retour = $res["OSNAME"];
				if (eregi('XP',$retour)) { // Pour le moment on a que 2 types d'icones 98 ou XP
					$retour="XP";
					return $retour;
				} elseif (eregi('2000',$retour)) {
					$retour="XP";
					return $retour;
				} elseif (eregi('2003',$retour)) {
					$retour="XP";
					return $retour;
				} elseif (eregi('ME',$retour)) {
					$retour="98";
					return $retour;
				} elseif (eregi('98',$retour)) {
				        $retour="98";
					return $retour;
				} elseif (eregi('95',$retour)) {
				         $retour="98";
					 return $retour;
				} elseif (eregi('Linux',$retour)) {
				         $retour="Linux";
					 return $retour;
				} else return 0;
				
			}
		} else {
			return 0;
		}
		
	} else { // Pas d'inventaire à ce nom
		return 0;
	}	
}

function move_computer_parc($parc,$computer) { // Supprime une machine d'un parc
	 // Suppression des machines dans le parc
	include ("config.inc.php"); 
	$cDn = "cn=".$computer.",".$computersRdn.",".$ldap_base_dn;
	$pDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn;
	exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$cDn\" \"$pDn\"");
        exec ("/usr/share/se3/sbin/printers_group.pl");

}
			  
function move_parc($parc) { // Supprime un parc si celui-ci est vide
	include ("config.inc.php"); 
	$cDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn;
        exec ("/usr/share/se3/sbin/entryDel.pl \"$cDn\"");
	exec ("/usr/share/se3/sbin/printers_group.pl");
}	

function test_cups() { //test si cups tourne
	$status_cups=exec("/usr/bin/lpstat -r");
        if ($status_cups=="scheduler is running") {
		return 1;
        //	$icone_cups="enabled.png";
        } else {	
		return 0;
	//	$icone_cups="disabled.png";
	}								   
}

// pas utilisé pour le moment
function start_cups() { //demarre ou stop cups
	if (test_cups()==0) {
		exec ("sudo /etc/init.d/cupsys start");
	} else {
		exec ("sudo /etc/init.d/cupsys stop");
	}
}

function move_printer_parc($parc,$printer) { // Sort une imprimante $printer du parc $parc
	if ($parc !="" && $printer != "") {
		exec ("/usr/share/se3/sbin/printerDelPark.pl $printer $parc",$AllOutPutValue,$ReturnValue);
	}
}				      

function move_printer($printer) { // Supprime une imprimante definitivement
     exec ("/usr/share/se3/sbin/printerDel.pl $printer",$AllOutPutValue,$ReturnValue);
}

function stop_start_printer($printer,$status) { //Stop ou start une imprimante
	if (isset($printer)){
      		exec ("/usr/bin/$status $printer");
	}	
}						

?>
