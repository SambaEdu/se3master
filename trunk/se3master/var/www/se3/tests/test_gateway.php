<?php

   /**
   
   * Test la connexion a internet
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
   * file: test_gateway.php
   */



require_once('entete_ajax.inc.php');
/********** Test de la conf du serveur **********************/
$phpv2=preg_replace("/[^0-9\.]+/","",phpversion());
$phpv=$phpv2-0;


/*
// Verifie la connexion a internet si ligne_internet = 0% alors on a internet
$PING_INTERNET="72.14.207.99";
if ($phpv>=4.2) {
	$PING="ping -c 1 -w 1 $PING_INTERNET | awk '/packet/ {print $6}'";
} else {
	$PING="ping -c 1 $PING_INTERNET | awk '/packet/ {print $7}'";
}
*/

// Ping passerelle
//$PING_ROUTEUR=`cat /etc/network/interfaces | grep gateway | grep -v broadcast | cut -d" " -f 2`;
//$PING_ROUTEUR=trim($PING_ROUTEUR);
$PING_DNS_EXT = "193.49.64.5";
if ($phpv>=4.2) {
	$PING="ping -c 1 -w 1 $PING_DNS_EXT | awk '/packet/ {print $6}'";
} else {
	$PING="ping -c 1 $PING_DNS_EXT | awk '/packet/ {print $7}'";
}
$ligne=exec("$PING",$test,$testretour);
//$ok="0";
/*
if (($_POST['ligne_internet'] == "0%") && ($ligne == "0%")) {
	$ok="1";
} elseif (($_POST['ligne_internet'] != "0%") && ($ligne != "0%")) {
	$ok="1";
} elseif (($_POST['ligne_internet'] != "0%") && ($ligne == "0%")) {
	$ok="1";
} elseif (($_POST['ligne_internet'] == "0%") && ($ligne != "0%")) {
	$ok="0";
}
*/

if ($ligne == "0%")
	echo "1";
else 
	echo "0";
?>
