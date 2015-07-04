<?php


   /**
   
   * Test la presence script integration 
   * @Version $Id$ 
   * @Projet LCS / SambaEdu 
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note
   * Modifications proposees par Sebastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax - modif keyser passage a rejointSE3.bat.
 
   
   */

   /**

   * @Repertoire: /tests/
   * file: test_vbs.php
   */


require_once('entete_ajax.inc.php');
 // Controle l'installation des vbs
$filename="/home/netlogon/domscripts/rejointSE3.exe";

if (file_exists($filename)) { 
	$ok="1";
} else {
	$ok="0";
}
die($ok);
?>
