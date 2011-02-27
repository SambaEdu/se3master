<?php


   /**
   
   * Affiche une liste d'utilisateurs a partir de l'annuaire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: peoples_list.php
   */



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');


if ((is_admin("Annu_is_admin",$login)=="Y") || (is_admin("Annu_can_read",$login)=="Y") || (is_admin("sovajon_is_admin",$login)=="Y")) {

	$priority_surname=$_POST['priority_surname'];
	$fullname=$_POST['fullname'];
	$priority_name=$_POST['priority_name'];
	$nom=$_POST['nom']; 
	$priority_classe=$_POST['priority_classe'];
	$classe=$_POST['classe'];

	// Aide
	$_SESSION["pageaide"]="Annuaire";

	echo "<h1>".gettext("Annuaire")."</h1>\n";

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

	// Remplacement de *** ou ** par *
	$filter_people = preg_replace("/\*\*\*/","*",$filter_people);
	$filter_people = preg_replace("/\*\*/","*",$filter_people);
	if ($filter_people && !$classe) {
		// recherche dans la branche People
		#$TimeStamp_0=microtime();
	    	$users = search_people ($filter_people);
	    	#$TimeStamp_1=microtime();
	
	    	// Affichage menu haut de page
	    	aff_trailer("3");
	    	#############
	    	# DEBUG     #
	    	#############
	    	#echo "<u>debug</u> :Temps de recherche = ".duree($TimeStamp_0,$TimeStamp_1)."&nbsp;s<BR>";
	    	#############
	    	# Fin DEBUG #
	    	#############
	    	if (count($users)) {
	      		if (count($users)==1) {
	        		echo "<p><STRONG>".count($users)."</STRONG>".gettext(" utilisateur r&#233;pond &#224; ces crit&#232;res de recherche")."</p>\n";
	      		} else {
	        		echo "<p><STRONG>".count($users)."</STRONG>".gettext(" utilisateurs r&#233;pondent &#224; ces crit&#232;res de recherche")."</p>\n";
	      		}

	      		echo "<UL>\n";
	      		for ($loop=0; $loop<count($users);$loop++) {
	        		echo "<LI><A href=\"people.php?uid=".$users[$loop]["uid"]."\">".$users[$loop]["fullname"]."</A></LI>\n";
	      		}
	      		echo "</UL>\n";
	    	} else {
	        	echo " <STRONG>".gettext("Pas de r&#233;sultats")."</STRONG>".gettext(" correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<BR>\n";
	    	}

	} elseif ($classe) {
	       	// Recherche des classes et equipes dans la branche groups de l'annuaire
	       	if ($priority_classe=="contient") {
	       		$filter_classe="(cn=Classe_*$classe*)";
	       	} elseif($priority_classe=="commence") {
	        	$filter_classe="(cn=Classe_$classe*)";
	       	} else {
	         	$filter_classe="(cn=Classe_*$classe)";
	       	}
	       	// Remplacement de *** ou ** par *
	       	$filter_classe = preg_replace("/\*\*\*/","*",$filter_classe);
	       	$filter_classe = preg_replace("/\*\*/","*",$filter_classe);
	       	#$TimeStamp_0=microtime();
	       	$uids = search_uids ($filter_classe);
	       	$people = search_people_groups ($uids,$filter_people,"group");
	       	#$TimeStamp_1=microtime();
	       	// Affichage menu haut de page
	       	aff_trailer("3");
	       	#############
	       	# DEBUG     #
	       	#############
	       	# echo "<u>debug</u> :Temps de recherche = ".duree($TimeStamp_0,$TimeStamp_1)."&nbsp;s<BR>";
	       	#############
	       	# DEBUG     #
	       	#############
	       	if (count($people)) {
	        	if (count($people)==1) {
	           		echo "<p><STRONG>".count($people)."</STRONG>".gettext(" utilisateur r&#233;pond &#224; ces crit&#232;res de recherche.")."</p>\n";
	         	} else {
	           		echo "<p><STRONG>".count($people)."</STRONG>".gettext(" utilisateurs r&#233;pondent &#224; ces crit&#232;res de recherche.")."</p>\n";
	         	}
	         	// affichage des resultats
	         	for ($loop=0; $loop < count($people); $loop++) {
	           		if ( $people[$loop]["group"] != $people[$loop-1]["group"]) {
	             			echo "<U>Classe</U> : ".$people[$loop]["group"]."<BR>\n";
	           		}

	           		if ($people[$loop]["cat"] == "Equipe") {
	               			echo "<img src=\"images/gender_teacher.gif\" width=18 height=18 hspace=1 border=0>\n";
	           		} else {
	             			if ($people[$loop]["sexe"]=="F") {
	               				echo "<img src=\"images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>\n";
	             			} else {
	               				echo "<img src=\"images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>\n";
	             			}
	           		}
	           		echo "<A href=\"people.php?uid=".$people[$loop]["uid"]."\">".$people[$loop]["fullname"]."</A><BR>\n";
	         	}
	
       		} else {
	           	echo " <STRONG>".gettext("Pas de r&#233;sultats")."</STRONG>".gettext(" correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<BR>
	                  ".gettext("Retour au")." <A href=\"annu.php\">".gettext("formulaire de recherche")."</A>...<BR>\n";
	       	}

	} else {
	       	// Aucun criteres de recherche
		echo " <STRONG>".gettext("Pas de r&#233;sultats !")."</STRONG><BR>
	       	".gettext("Veuillez compl&#233;ter au moins l'un des trois champs (nom, pr&#233;nom, classe) du")." <A href=\"annu.php\">".gettext("formulaire de recherche")."</A> !<BR>\n";
	}

}

include ("pdp.inc.php");
?>
