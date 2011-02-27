<?php

   /**
   
   * Connexion en TS ou VNC sur les clients
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe Tice academie de Caen
   * @auteurs Sandrine Dangreville

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: parcs/
   * file: tsvnc.php
   */		


$action=$_GET['action'];
$machine=$_GET['machine'];
$file=$_GET['file'];
switch ($action)
{
case "ts":
$get= fopen ($file, "r");
header("Content-type: application/force-download");
header("Content-Length: ".filesize($file));
header("Content-Disposition: attachment; filename=$machine.rdp");
readfile($file);
break;
case "vnc":
$get= fopen ($file, "r");
header("Content-type: application/force-download");
header("Content-Length: ".filesize($file));
header("Content-Disposition: attachment; filename=$machine.vnc");
readfile($file);
break;
}
?>
