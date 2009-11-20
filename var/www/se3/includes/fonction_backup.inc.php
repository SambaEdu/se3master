<?php

   /**
   * Librairie de fonctions utilisees par backuppc
  
   * @Version $Id$
   
   * @Projet LCS / SambaEdu 
   
   * @Auteurs Philippe Chadefaux
   
   * @Note: Ce fichier de fonction doit etre appele par un include

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: fonction_backup.inc.php
   * @Repertoire: includes/ 
   */  
  
  

//=================================================

/**
* relit la conf du fichier

* @Parametres 
* @Return
*/

function umountUSB () { // relit la conf du fichier
	system('sudo /usr/share/se3/sbin/umountusbdisk.sh');
}	

//=================================================

/**
* relit la conf du fichier

* @Parametres
* @Return
*/

function restoreUSB () { // relit la conf du fichier
	system('sudo /usr/share/se3/sbin/restorebackup.sh');
}	

//=================================================

/**
* relit la conf du fichier

* @Parametres
* @Return
*/

/* Force la relecture de la conf */
function reloadBackuPpc () { // relit la conf du fichier
	exec('/usr/bin/sudo /usr/share/se3/scripts/startbackup reload');
}	

//=================================================

/**
* Stop BackupPc

* @Parametres
* @Return
*/

/* Stop backuppc */
function stopBackupPc () { // Stop BackupPc
	exec('/usr/bin/sudo /usr/share/se3/scripts/startbackup stop');
}

//=================================================

/**
*  Start BackupPc

* @Parametres
* @Return
*/

function startBackupPc () { // Start BackupPc
	exec('/usr/bin/sudo /usr/share/se3/scripts/startbackup start');
}

//=================================================

/**
* Indique si BackupPc est lance ou pas

* @Parametres
* @Return
*/

function EtatBackupPc () { // Indique si BackupPc est lance ou pas
	exec("/bin/ps awux",$ps);
	if (array_values(preg_grep("/BackupPC/",$ps))) { return 1; } else { return 0; }
}	

//=================================================

/**
* verifie l'existence des cles pour connexions ssh

* @Parametres
* @Return
*/

function KeyExist () { // verifie l'existence des cles pour connexions ssh
	return file_exists('/var/remote_adm/.ssh/id_rsa.pub');
}		

//=================================================

/**
* Creation de la cle pour ssh

* @Parametres
* @Return
*/

function CreeKey () { // Creation de la cle pour ssh
	if (!file_exists('/var/remote_adm/.ssh')) {
		mkdir("/var/remote_adm/.ssh",0744);
	}
	if (!file_exists('/var/remote_adm/.ssh/id_rsa.pub')) {
		exec("/usr/bin/ssh-keygen -t rsa -N '' -f /var/remote_adm/.ssh/id_rsa");
	}
}	

//=================================================

/**
* Retourne la valeur de la variable $Name contenu dans config.pl

* @Parametres
* @Return
*/

function variables($Name,$HostServer) { // Retourne la valeur de la variable $Name contenu dans config.pl
        if($HostServer=="") {
	       $filename="/etc/backuppc/config.pl";
	} else {
	       $filename="/etc/backuppc/".$HostServer.".pl";
	}

	if (file_exists("$filename")) { //Si le fichier existe on recherche les valeurs
	         $lignes = file("$filename");
	         foreach ($lignes as $num => $ligne) {
	               if (preg_match ("/$Conf{$Name}.*=(.*);/",$ligne, $reg)) {
			 if (preg_match ("/\[(.*)\]/",$reg[1],$reg2)) {
			 	$variable = trim ($reg2[1]);
				return $variable;
			    }	   
		             if (preg_match("/'(.*)'/",$reg[1],$reg2)) {
			     	   $variable = trim($reg2[1]);
			           return $variable;
			     }
			 $variable = trim($reg[1]);    
			 return $variable;     
	                 }
			 if (preg_match ("/$Name.*=>(.*),/",$ligne,$reg)) {
			 	$variable = trim($reg[1]);
			 	return $variable;
			}	
	         }
	}
}

//=================================================

/**
* retourne 1 si une machine existe dans le fichier hosts

* @Parametres
* @Return
*/

function HostExist($HostServer) { //retourne 1 si une machine existe dans le fichier hosts
// verifier si HostServer ne peut pas etre plus precis
	$filehost = '/etc/backuppc/hosts';
	$lignes = file("$filehost");
	foreach ($lignes as $num =>$line) {
		if (preg_match("/^$HostServer\s+/i",$line)) {
			return true;
		} 
	}
	return false;
}

//=================================================

/**
* Ajoute une machine dans le fichier hosts, retourne 0 si probleme

* @Parametres
* @Return
*/

function AjoutHosts($HostServer,$dhcp,$TypeServer) { // Ajoute une machine dans le fichier hosts, retourne 0 si probleme
	$filehost = '/etc/backuppc/hosts';
	$fp = fopen($filehost,"a+");
	if (!HostExist($HostServer)) {
		$ligne = "$HostServer \t $dhcp \t backuppc \t # $TypeServer\n";
		fwrite($fp, $ligne);
//		if (fwrite($fp,$ligne)== FALSE) {
//			return 0;
//		}
	}
	fclose($fp);
}

//=================================================

/**
* Detruit une entree dans le fichier hosts

* @Parametres
* @Return
*/

function DeleteHost($HostServer) { // Detruit une entree dans le fichier hosts
      $filehost = "/etc/backuppc/hosts";
      $filehost_tmp = $filehost.".tmp";
      if (file_exists($filehost)) { //On vire la machine dans le fichiers hosts
             $fp = fopen("$filehost","r");
             $fp_tmp = fopen("$filehost_tmp","w");
             while (!feof($fp)) {
                  $ligne = fgets($fp,1098);
                  if (!preg_match("/^$HostServer\s+/i",$ligne)) {
                        fwrite($fp_tmp,$ligne);
                  }
             }
             fclose($fp);
             fclose($fp_tmp);
             copy($filehost_tmp,$filehost);
             unlink($filehost_tmp);
      }
}

//=================================================

/**
* Retourne la conf du dhcp 0 ou 1

* @Parametres
* @Return
*/

function GetDhcp($HostServer) { // Retourne la conf du dhcp 0 ou 1
	$filehost = "/etc/backuppc/hosts";
	$lignes = file("$filehost");
	$expr = "/^$HostServer\s+([01])/";
	foreach ($lignes as $num => $line) {
		if (preg_match($expr,$line,$regs)) {
			return trim($regs[1]);
			
		}
	}
}	

//=================================================

/**
* retourne quand il est indique le type de serveur

* @Parametres
* @Return
*/

function GetTypeServer($HostServer) { // retourne quand il est indique le type de serveur
	$filehost = "/etc/backuppc/hosts";
	$lignes = file("$filehost");
	$expr = "/^$HostServer\s+.*[#]\s+(.*)/";
	foreach ($lignes as $num => $line) {
		if (preg_match($expr,$line,$regs)) {
			return trim($regs[1]);
		}
	}
}	

//=================================================

/**
* Detruit le reertoire de la sauvegarde de la machine HostServer

* @Parametres
* @Return
*/

function DeleteRep($HostServer) { // Detruit le repertoire de la sauvegarde de la machine HostServer
	$dir_backup="/var/lib/backuppc/pc/";
	if($HostServer=="") {
		return;
	}
	$rep = $dir_backup.$HostServer;
	if(is_dir($rep)) {
		passthru("rm -Rf $rep",$retourn);
		return $retourn;	
	}
}	

//=================================================

/**
* Test si backuppc est installe en local sur un Se3 ou un Slis ou une machine dediee

* @Parametres
* @Return
*/

function TypeMachine() { // Test si backuppc est installe en local sur un Se3 ou un Slis ou une machine dediee
	if(is_dir("/usr/share/se3")) {
		return Se3;
	}	
	if(file_exists("/etc/version_slis")) {
		return Slis;
	}
	if(is_dir("/usr/share/lcs")) {
		return Lcs;
	}	
}	

//=================================================

/**
* active ou desactive la sauvegarde

* @Parametres
* @Return
*/

function Desactive($HostServer,$Etat) { // active ou desactive la sauvegarde
	$filehost="/etc/backuppc/".$HostServer.".pl";
	$fp=fopen("$filehost","rb");
	$cont = fread($fp, filesize($filehost));
	fclose ($fp);
	
	$mod = "/[\$]Conf\{FullPeriod\}\s*=\s*(.+);/";
	if ($Etat == "1") {
		$mod_chang="";
	} else {	
		$mod_chang = "\$Conf{FullPeriod} = $Etat;";
	}
	if(preg_match($mod,$cont)) {
		$cont = preg_replace($mod,$mod_chang,$cont);
		$fp = fopen("$filehost","w");
		fwrite($fp,$cont);
		fclose ($fp);		
	} else {
		$fp = fopen("$filehost","w");
		fwrite($fp,$cont);
		fwrite($fp,$mod_chang);
		fclose ($fp);	
	}	
}

//=================================================

/**
* Recherche si la sauvegarde pour $HostServer est active

* @Parametres
* @Return
*/

function EtatDesactive($HostServer) { // Recherche si la sauvegarde pour $HostServer est active
	$filehost="/etc/backuppc/".$HostServer.".pl";
	$fp=fopen("$filehost","rb");
	$cont = fread($fp, filesize($filehost));
	fclose ($fp);
	
	$mod = "/[\$]Conf\{FullPeriod\}\s*=\s*(-1);/";

	if(preg_match($mod,$cont)) {
		return true;
	} else {
		return false;
	}
}

//=================================================

/**
* //vire les / lorsqu'ils sont en double ou triple

* @Parametres
* @Return
*/

function stripslashes2($valeur) { //vire les / lorsqu'ils sont en double ou triple
	$valeur = str_replace("\\\\'","'",$valeur);
	$valeur = str_replace("\\'","'",$valeur);
	$valeur = str_replace("'\\\\","'\\",$valeur);
	$valeur = stripslashes($valeur);
	return $valeur;
}

//=================================================

/**
*  deplace le repertoire de sauvegarde $drive vers $space

* @Parametres
* @Return
*/

function MoveRep($drive,$space) { // deplace le repertoire de sauvegarde $drive vers $space
	$cmd="/usr/bin/sudo /usr/share/se3/scripts/move_rep_backuppc.sh ".$drive." ".$space;
	exec($cmd);
}	

//=================================================

/**
* Verifie la coherence entre le fichier host et la presence du fichier machine.pl

* @Parametres
* @Return
*/

function HostCoherence() { // Verifie la coherence entre le fichier host et la presence du fichier machine.pl
	$filehost = '/etc/backuppc/hosts';
	$lignes = file("$filehost");
	foreach ($lignes as $num =>$line) {
		if (!preg_match("/^#|^host\s+|^$/",$line)) {
				preg_match("/(.*)\s+[01].*/",$line,$reg);
				$host=trim($reg[1]);
				$fichier = "/etc/backuppc/".$host.".pl";
				if(!file_exists("$fichier")) {
					DeleteHost($host);
				}	
		}
		
	}
}

//=================================================

/**
* Test si www-se3 a bien les droits pour ecrire dans le repertoire

* @Parametres
* @Return
*/

function TestEcrire($repertoire) { // Test si ww-se3 a bien les droits pour ecrire dans le repertoire
	$ok = true;
	$nom_fichier = "$repertoire/test.tmp";
	$f = @fopen($nom_fichier,"w");
	if (!$f) $ok = false;
	elseif (!@fclose($f)) $ok = false;
	elseif (!@unlink($nom_fichier)) $ok = false;
	return $ok;
}	

?>
