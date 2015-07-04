<?php


   /**
   
   * Retourne le nom DNS du serveur Se3
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
   * file: test_dns_se3.php
   */


	
	require_once('entete_ajax.inc.php');
	// Verifie DNS SE3

	preg_match("/^(http:\/\/)?([^\:]+)/i","$urlse3",$adress);
	//$com="/usr/bin/host -t A $adress[2] 2>&1";
	$phpv2=preg_replace("/[^0-9\.]+/","",phpversion());
	$phpv=$phpv2-0;

	
	// Verifie la resolution de urlse3 si reponse  0% alors la resolution interne fonctionne
	if ($phpv>=4.2) {
		$com="ping -c 1 -w 1 $adress[2] | awk '/packet/ {print $6}'";
	} else {
		$com="ping -c 1 $adress[2] | awk '/packet/ {print $7}'";
	}
	//die($com);
	
	$fp2=exec("$com",$out,$log);
	$sortie=trim(implode(' ',$out));
	//die($sortie);
	if ( $sortie == "0%" )
		die("1");
	else
		die("0");
	
	
?>
