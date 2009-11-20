<?php

   /**
   
   * Test si la table corresp exist
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
   * file: test_keys.php
   */


require_once('entete_ajax.inc.php');
$query="select * from corresp";
$resultat=mysql_query($query);
$ligne=mysql_num_rows($resultat);

if($ligne == "0") { // si aucune cle dans la base SQL
	$ok="0";
} else {
	$ok="1";
}
die($ok);
?>
