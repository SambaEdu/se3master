<?php


   /**
   
   * Interface de gestion des acl
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Equipe Tice academie de Caen
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: acls
   * file: groups_listacls.php

  */	


  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  require_once ("lang.inc.php");
  bindtextdomain('se3-acls',"/var/www/se3/locale");
  textdomain ('se3-acls');

  // Aide
  $_SESSION["pageaide"]="ACL#En_utilisant_l.27interface_SambaEdu";

  $group = $_POST['group'];
  $priority_group = $_POST['priority_group'];
  
if (!$group) {
	$filter = "(cn=*)";
} else {
	if ($priority_group == "contient") {
    	  	$filter = "(cn=*$group*)";
    	} elseif ($priority_group == "commence") {
      		$filter = "(|(cn=Classe_$group*)(cn=Cours_$group*)(cn=Equipe_$group*)(cn=Matiere_$group*)(cn=$group*))";
    	} else {
       		$filter = "(|(cn=Classe_*$group)(cn=Cours_*$group)(cn=Equipe_*$group)(cn=Matiere_*$group)(cn=*$group))";
    	}
}

$groups=search_groups($filter);
  
echo "<br><br><br><center><B><a href=\"#\" onClick=\"window.close ();\">".gettext("Fermer la fen&#234;tre")."</a></B></center><br><br><br>";

if (count($groups)) {
	if (count($groups)==1) {
 		echo "<p><STRONG>".count($groups)."</STRONG>".gettext(" groupe r&#233;pond &#224; ces crit&#232;res de recherche")."</p>\n";
    	} else {
      		echo "<p><STRONG>".count($groups)."</STRONG>".gettext(" groupes r&#233;pondent &#224; ces crit&#232;res de recherche")."</p>\n";
    	}
      	echo "<UL>\n";
        echo"<form><select name=\"liste\" onChange=\"Reporter(this)\">";
        echo "<option value=\"\">".gettext("Votre choix ...")."</option>";
        for ($loop=0; $loop<count($groups);$loop++) {
		echo "<option value=\"".$groups[$loop]["cn"]."\">".$groups[$loop]["cn"]."</option>";
	}
        echo "</form></UL>\n";
     
} else {
	echo "<STRONG>".gettext("Pas de r&#233;sultats")."</STRONG>".gettext(" correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<BR>";
}
include ("pdp.inc.php");
?>
