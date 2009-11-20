<?php

   /**
   
   * Test une requete sur le web wawadeb 
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
   * file: test_web.php
   */



require_once('entete_ajax.inc.php');
   $http=exec("cd /tmp; wget -q --tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr && echo \$? | rm -f /tmp/index.html.1*",$out,$retour);
   
   if ($retour=="0") {
   	$ok="1";
   } else {
   	$ok="0";
   }
die($ok);
?>
