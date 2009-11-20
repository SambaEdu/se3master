<?php


   /**
   
   * Page qui teste les requetes DNS
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
   * file: test_dns.php
   */


require_once('entete_ajax.inc.php');
// Verifie DNS

   $IP_WAWA=@gethostbyname('wawadeb.crdp.ac-caen.fr');
   if ($IP_WAWA=="193.49.66.139") {
   	$ok="1";
   } else {
   	$ok="0";
  }

die($ok);
?>
