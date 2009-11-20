<?php

   /**
   
   * Page qui permet d'appeler l'aide sur le mediawiki   
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Philippe Chadefaux
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note Il faut lui passer le nom de la page que l'on veut voir s'ouvrir dans un popup lorsque l'on clique sur le point d'interrogation de l'aide
   
   */

   /**

   * @Repertoire: /
   * file: aide.php

  */	



session_start();

$page=$_SESSION["pageaide"];

$url="http://wwdeb.crdp.ac-caen.fr/mediase3/index.php/$page";

echo "<SCRIPT LANGUAGE=JavaScript>";
echo "setTimeout('top.location.href=\"$url\"',\"10\")";
echo "</SCRIPT>";

?>
