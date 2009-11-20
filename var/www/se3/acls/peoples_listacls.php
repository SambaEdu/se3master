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
   * file: peoples_listacls.php

  */	


  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  require_once ("lang.inc.php");
  bindtextdomain('se3-acls',"/var/www/se3/locale");
  textdomain ('se3-acls');


  $nom = $_POST['nom'];
  $classe = $_POST['classe'];
  $fullname = $_POST['fullname'];
  $priority_name = $_POST['priority_name'];
  $priority_surname = $_POST['priority_surname'];
  $priority_classe = $_POST['priority_classe'];

  // Aide
  $_SESSION["pageaide"]="ACL#En_utilisant_l.27interface_SambaEdu";


  // Convertion en utf_8
  $nom = utf8_encode($nom);
  $fullname = utf8_encode($fullname);
  // Construction du filtre de la branche people
  if ($nom && !$fullname) {
 	 // Recherche sur sn
    	if ($priority_name=="contient") {
      		$filter_people="(sn=*$nom*)";
    	} elseif($priority_name=="commence") {
      		$filter_people="(sn=$nom*)";
    	} else {
      		$filter_people="(sn=*$nom)";
    	}
  } elseif ($fullname && !$nom) {
  	// Recherche sur cn
    	if ($priority_surname=="contient") {
      		$filter_people="(cn=*$fullname*)";
    	} elseif($priority_surname=="commence") {
      		$filter_people="(cn=$fullname*)";
    	} else {
      		$filter_people="(cn=*$fullname)";
    	}
  } elseif ($fullname && $nom) {
  	// Recherche sur sn ET cn
    	if ($priority_name=="contient") {
      		if ($priority_surname=="contient") {
        		$filter_people="(&(sn=*$nom*)(cn=*$fullname*))";
      		} elseif($priority_surname=="commence") {
        		$filter_people="(&(sn=*$nom*)(cn=$fullname*))";
      		} else {
        		$filter_people="(&(sn=*$nom*)(cn=*$fullname))";
      		}

    	} elseif($priority_name=="commence") {
      		if ($priority_surname=="contient") {
        		$filter_people="(&(sn=$nom*)(cn=*$fullname*))";
      		} elseif($priority_surname=="commence") {
        		$filter_people="(&(sn=$nom*)(cn=$fullname*))";
      		} else {
        		$filter_people="(&(sn=$nom*)(cn=*$fullname))";
      		}
    	} else {
      		if ($priority_surname=="contient") {
        		$filter_people="(&(sn=*$nom)(cn=*$fullname*))";
      		} elseif($priority_surname=="commence") {
        		$filter_people="(&(sn=*$nom)(cn=$fullname*))";
      		} else {
        		$filter_people="(&(sn=*$nom)(cn=*$fullname))";
      		}
    	}
  }

echo "<br><br><br><center><B><a href=\"#\" onClick=\"window.close ();\">".gettext("Fermer la fen&#234;tre")."</a></B></center><br><br><br>";
if ($filter_people ) {
	// recherche dans la branche People
    	$users = search_people ($filter_people);
    	if (count($users)) {
		if (count($users)==1) {
			echo "<p><STRONG>".count($users)."</STRONG> ".gettext(" utilisateur r&#233;pond &#224; ces crit&#232;res de recherche")."</p>\n";
		} else {
			echo "<p><STRONG>".count($users)."</STRONG> ".gettext("utilisateurs r&#233;pondent &#224; ces crit&#232;res de recherche")."</p>\n";
		}

		echo "<UL>\n";
		echo"<form><select name=\"liste\" onChange=\"Reporter(this)\">";
		echo "<option value=\"\">".gettext("Votre choix ...")."</option>";
		for ($loop=0; $loop<count($users);$loop++) {
	    		echo "<option value=\"".$users[$loop]["uid"]."\">".$users[$loop]["fullname"]."</option>";  
		}
		
		echo "<br><br><br><br><br><center><B><a href=\"#\" onClick=\"window.close ();\">".gettext("Fermer la fen&#234;tre")."</a></B></center>";
	  	echo "</form></UL>\n";
    	} else {
        	echo " <STRONG>".gettext("Pas de r&#233;sultats")."</STRONG> ".gettext("correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<BR>\n";
    	}
} else {
	// Aucun criteres de recherche
	echo " <STRONG>".gettext("Pas de r&#233;sultats !")."</STRONG><BR>".gettext("
	   Veuillez compl&#233;ter au moins l'un des deux champs (nom, pr&#233;nom) du formulaire de recherche !")."<BR>\n";
}


include ("pdp.inc.php");

?>
		
