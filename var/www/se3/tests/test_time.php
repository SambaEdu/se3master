<?php


   /**
   
   * Page qui teste l heure de la machine
   * @Version $Id$ 
   * @Projet LCS / SambaEdu 
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note 
   * Modifications proposees par Sebastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax.
   * Modification du systeme d'infos bulles.(Nouvelle version de wz-tooltip Fonctions Tip('msg') TagToTip() UnTip() ) Ancienne version incompatible avec ajax
   * Externalisation des messages contenus dans les infos-bulles. 
   * Nouvelle organisation de l'arborescence.
 
   
   */

   /**

   * @Repertoire: /tests/
   * file: test_time.php
   */


require_once('entete_ajax.inc.php');
	
  $voir = exec("/usr/sbin/ntpdate -q $ntpserv | grep ntpdate | cut -d\" \" -f10");
  
  if( ($voir < 60) && ( trim($voir) != '')) {
  	$ok ="1";
  } else {
  	$ok ="0";
  }

die($ok);
?>
