<?php


   /**
   
   * Test les services qui tournent :onduleur, samba ... 
   * @Version $Id$ 
   * @Projet LCS / SambaEdu 
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note
   * Modifications proposees par Sebastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax.
 
   
   */

   /**

   * @Repertoire: /tests/
   * file: test_services.php
   */



require_once('entete_ajax.inc.php');


//######################## CONTROLE LES SERVICES ##################################//
// Controle le temps de la machine
$la=date("G:i:s d/m/Y");
// Controle si le fichier ssmtp a ete configure
$ssmtp = exec("dpkg -l | grep ssmtp > /dev/null && echo 1");
if ($ssmtp == "1") {
	if(file_exists("/etc/ssmtp/ssmtp.conf")) {
		$okmail ="1";
	} else {
		$okmail="0";
	}
}
// Test le serveur smb
  $domaine = exec('cat /etc/samba/smb.conf | grep workgroup | cut -d" " -f 3');
  $smb = exec("smbclient -L localhost -N | grep -i $domaine >/dev/null && echo 1");
  
   if ($smb == "1") {
  	$oksmb="1";
  } else {
	$oksmb="0";
}
 
  
// Test le sid samba et la presence d'un eventuel doublon de sid
  $testsid = exec('sudo /usr/share/se3/scripts/testSID.sh');
  
   if ($testsid == "") {
  	$oksid="1";
  } else {
	 $oksid="0";
  }

// Test la base MySQL
  $mysql = exec('sudo /usr/share/se3/sbin/testMySQL.sh',$out,$err);
 
  if ($err == "0") {
  	$okmysql="1";
  } else {
  	$okmysql="0";
  }

// Controle si le dhcp tourne si celui-ci a ete installe
$dhcp_install = exec("dpkg -l | grep dhcp3 > /dev/null && echo 1");

if (($dhcp_install == "1") && ($dhcp =="1")) {
	
  	$dhcp_state=exec("sudo /usr/share/se3/scripts/makedhcpdconf state");
	if($dhcp_state==1) {
		$okdhcp="1";
	} else {
		$okdhcp="0";
	}
}
else {
	$okdhcp = "-1";
}

// Test la presence d'un onduleur
  $ups = exec("upsc myups@localhost");
  $ups_charge = exec("upsc myups@localhost battery.charge");
 
  if ($ups_charge != "") {
  	$ups_mfr = exec("upsc myups@localhost ups.mfr");
	$ups_model = exec("upsc myups@localhost ups.model");
	//echo " <I> ( $ups_mfr $ups_model )</I>";
  }
  
  if ($ups_charge != "") {  
		$okondul="1";
       
  } else {
  		$okondul="0";

  }

die("var arr_services=new Array('$okmail','$oksmb','$oksid','$okmysql','$okdhcp','$okondul');");


?>
