<?php


   /**
   
   * Page qui teste l occupation des disques.
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
   * file: test_disks.php
   */



require_once('entete_ajax.inc.php');


// Partition root

$df_t=disk_total_space("/");
$df_f=disk_free_space("/");
$d1_freespace=$df_f / 1048576;
$d1_totalspace=$df_t / 1048576;
$d1_usedspace=$d1_totalspace - $d1_freespace;
$pourcent=$d1_usedspace / $d1_totalspace;
$pourc = $pourcent*100;
$pourc = round($pourc, 2);
$d1_usedspace = $d1_usedspace / 1024;
$d1_usedspace = round($d1_usedspace,2);
$d1_totalspace = $d1_totalspace / 1024;
$d1_totalspace = round($d1_totalspace,2);
$d1_freespace = $d1_freespace / 1024;
$d1_freespace = round($d1_freespace,2);
$disk1 = $pourc;

// Partition /var/se3

$df_t=disk_total_space("/var/se3");
$df_f=disk_free_space("/var/se3");
$d2_freespace=$df_f / 1048576;
$d2_totalspace=$df_t / 1048576;
$d2_usedspace=$d2_totalspace - $d2_freespace;
$pourcent=$d2_usedspace / $d2_totalspace;
$pourc = $pourcent*100;
$pourc = round($pourc, 2);
$d2_usedspace = $d2_usedspace / 1024;
$d2_usedspace = round($d2_usedspace,2);
$d2_totalspace = $d2_totalspace / 1024;
$d2_totalspace = round($d2_totalspace,2);
$d2_freespace = $d2_freespace / 1024;
$d2_freespace = round($d2_freespace,2);
$disk2 = $pourc;

// Partition /home
$df_t=disk_total_space("/home");
$df_f=disk_free_space("/home");
$d3_freespace=$df_f / 1048576;
$d3_totalspace=$df_t / 1048576;
$d3_usedspace=$d3_totalspace - $d3_freespace;
$pourcent=$d3_usedspace / $d3_totalspace;
$pourc = $pourcent*100;
$pourc = round($pourc, 2);
$d3_usedspace = $d3_usedspace / 1024;
$d3_usedspace = round($d3_usedspace,2);
$d3_totalspace = $d3_totalspace / 1024;
$d3_totalspace = round($d3_totalspace,2);
$d3_freespace = $d3_freespace / 1024;
$d3_freespace = round($d3_freespace,2);
$disk3 = $pourc;

// Partition /var
$df_t=disk_total_space("/var");
$df_f=disk_free_space("/var");
$d4_freespace=$df_f / 1048576;
$d4_totalspace=$df_t / 1048576;
$d4_usedspace=$d4_totalspace - $d4_freespace;
$pourcent=$d4_usedspace / $d4_totalspace;
$pourc = $pourcent*100;
$pourc = round($pourc, 2);
$d4_usedspace = $d4_usedspace / 1024;
$d4_usedspace = round($d4_usedspace,2);
$d4_totalspace = $d4_totalspace / 1024;
$d4_totalspace = round($d4_totalspace,2);
$d4_freespace = $d4_freespace / 1024;
$d4_freespace = round($d4_freespace,2);
$disk4 = $pourc;

$flux .= "var arr_space_disks1=new Array('$disk1','$d1_totalspace','$d1_usedspace','$d1_freespace');";
$flux .= "var arr_space_disks2=new Array('$disk2','$d2_totalspace','$d2_usedspace','$d2_freespace');";
$flux .= "var arr_space_disks3=new Array('$disk3','$d3_totalspace','$d3_usedspace','$d3_freespace');";
$flux .= "var arr_space_disks4=new Array('$disk4','$d4_totalspace','$d4_usedspace','$d4_freespace');";
die($flux);
?>
