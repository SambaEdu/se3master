<?php


   /**
   
   * Test la presence script integration 
   * @Version $Id: test_clo.php 5915 2010-11-04 22:58:06Z keyser $ 
   * @Projet LCS / SambaEdu 
   * @auteurs Philippe Chadefaux  MrT
   * @Licence Distribue selon les termes de la licence GPL
   * @note
   * Modifications proposees par Sebastien Tack (MrT)
   * Optimisation du lancement des scripts bash par la technologie asynchrone Ajax - modif keyser passage a rejointSE3.bat.
 
   
   */

   /**

   * @Repertoire: /tests/
   * file: test_clo.php
   */


require_once('entete_ajax.inc.php');
 // Controle l'installation des dispositifs clonage
    if ($udpcast_ajour == "0") { 
            $ok="0";
    } elseif ($udpcast_ajour == "0") {
        $ok="0";
    } elseif ($slitaz_ajour == "0") {
        $ok="0";
    } elseif ($rescd_ajour == "0") { 
         $ok="0";
    } elseif ($clonezilla_ajour == "0") { 
         $ok="0";
    } elseif ($clinux_ajour == "0") { 
         $ok="0";

    } else {
            $ok="1";
    }
die($ok);
?>
