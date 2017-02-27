<?php

   /**
   
   * Test si la table corresp exist
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
   * file: test_keys.php
   */


require_once('entete_ajax.inc.php');
$query="select * from corresp";
$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
$ligne=mysqli_num_rows($resultat);

if($ligne == "0") { // si aucune cle dans la base SQL
	$ok="0";
} else {
	$ok="1";
}
die($ok);
?>
