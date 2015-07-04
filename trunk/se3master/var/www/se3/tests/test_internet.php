<?php


   /**
   
   * Test la connexion au serveur web wawadeb
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
   * file: test_internet.php
   */



require_once('entete_ajax.inc.php');
/********** Test de la conf du serveur **********************/
$phpv2=preg_replace("/[^0-9\.]+/","",phpversion());
$phpv=$phpv2-0;

/*******************************************************/

$PING_INTERNET="195.221.20.10";
if ($phpv>=4.2) {
	$PING="ping -c 1 -w 1 $PING_INTERNET | awk '/packet/ {print $6}'";
} else {
	$PING="ping -c 1 $PING_INTERNET | awk '/packet/ {print $7}'";
}

$ligne_internet=exec("$PING",$test,$testretour);
if ($ligne_internet != "0%") { // on teste sur un autre serveur
   $PING_INTERNET="www.free.fr";
   if ($phpv>=4.2) {
	$PING="ping -c 1 -w 1 $PING_INTERNET | awk '/packet/ {print $6}'";
   } else {
	$PING="ping -c 1 $PING_INTERNET | awk '/packet/ {print $7}'";
   }
	
   $ligne_internet=exec("$PING",$test,$testretour);
}
// leb 30sept2007
if ($ligne_internet != "0%") { // test acces http
   $http=exec("cd /tmp; wget -q ---tries=1 --timeout=2 http://wawadeb.crdp.ac-caen.fr && echo \$? | rm -f /tmp/index.html.1*",$out,$retour);
   if ($retour=="0") {
       $ligne_internet = "0%";
   }
}
// fin-leb 30sept2007
// Verifie si proxy defini
$proxy=exec("cat /etc/profile | grep http_proxy= | cut -d= -f2");
if ($proxy != "") {
	preg_match("/http:\/\/(.*)\"/i",$proxy,$rest);
	putenv("http_proxy=$rest[1]");
}

//$ligne_internet="1%";


echo "$ligne_internet";
?>
