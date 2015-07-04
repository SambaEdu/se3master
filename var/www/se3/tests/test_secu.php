<?php


   /**
   
   * Test les mises a jour debian 
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
   * file: test_secu.php
   */


	require_once('entete_ajax.inc.php');
   

	// Mises a jour de secu debian

	$secu = exec('sudo /usr/share/se3/scripts/update-secu.sh 2>&1',$retour,$retourV);
	//die(print_r($retour));
	if (trim($secu) == "1")
		$ok="1";
	else
		$ok="0";

	die($ok);
?>
