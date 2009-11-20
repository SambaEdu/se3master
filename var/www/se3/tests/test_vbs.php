<?php


   /**
   
   * Test la presence des vbs 
   * @Version $Id$ 
   * @Projet LCS / SambaEdu 
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note
   * Modifications proposées par Sébastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax.
 
   
   */

   /**

   * @Repertoire: /tests/
   * file: test_vbs.php
   */


require_once('entete_ajax.inc.php');
 // Controle l'installation des vbs
$DIR_VBS="/var/se3/Progs/install/installdll/rejoin_se3_XP.vbs";
if(@is_dir("/var/se3/Progs/install/installdll")) {
	$ok="1";
} else {
	$ok="0";
}
die($ok);
?>
