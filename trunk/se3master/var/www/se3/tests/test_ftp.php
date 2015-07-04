<?php

   /**
   
   * Test la connexion au serveur FTP wawadeb
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
   * file: test_ftp.php
   */



require_once('entete_ajax.inc.php');
// Contact serveur de mise a jour ftp
$FTP="wawadeb.crdp.ac-caen.fr";
$CONNECT_FTP=@ftp_connect("$FTP",0,30);

  if($CONNECT_FTP) {  $ok="1";
  } else { 
       $ok=0;
	@ftp_close($FTP);
  }
die($ok);
?>
