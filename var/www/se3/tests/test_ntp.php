<?php

   /**
   
   * Test la connexion au serveur ntp
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
   * file: test_ntp.php
   */


require_once('entete_ajax.inc.php');
 $ok_ntp=system("/usr/sbin/ntpdate -q $ntpserv >/dev/null", $retval);
 
 if ($retval=="0") {
   	$ok="1";
   } else {
   	$ok="0";
  }
die($ok);
?>
