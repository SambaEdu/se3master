<?php


   /**
   
   * Affiche les groupes a partir de l'annuaires
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
   * file: group.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

$_SESSION["pageaide"]="Annuaire";
echo "<h1>".gettext("Annuaire")."</h1>";

$filter=$_GET['filter'];

// Menu Annuaire ->
aff_trailer ("8_$filter");


#$TimeStamp_0=microtime();
$group=search_groups ("(cn=".$filter.")");

$uids = search_uids ("(cn=".$filter.")");


$people = search_people_groups ($uids,"(sn=*)","cat");

  #$TimeStamp_1=microtime();
  #############
  # DEBUG     #
  #############
  #echo "<u>debug</u> :Temps de recherche = ".duree($TimeStamp_0,$TimeStamp_1)."&nbsp;s<BR><BR>";
  #############
  # Fin DEBUG #
  #############
// Affiche les membres du groupe
if (count($people)) {
	$intitule =  strtr($filter,"_"," ");
    	echo "<U>".gettext("Groupe")."</U> : $intitule <font size=\"-2\">".$group[0]["description"]."</font><BR>\n";
    	echo gettext("Il y a ") . count($people)." membre";
    	if ( count($people) >1 ) echo "s";
    		echo gettext(" dans ce groupe")."<BR>\n";
    		for ($loop=0; $loop < count($people); $loop++) {
      			if (($people[$loop]["cat"] == "Equipe") or ($people[$loop]["prof"]==1)) {
        			echo "<img src=\"images/gender_teacher.gif\" alt=\"Professeur\" width=18 height=18 hspace=1 border=0>\n";
      			} else {
        			if ($people[$loop]["sexe"]=="F") {
          				echo "<img src=\"images/gender_girl.gif\" alt=\"El&egrave;ve\" width=14 height=14 hspace=3 border=0>\n";
        			} else {
          				echo "<img src=\"images/gender_boy.gif\" alt=\"El&egrave;ve\" width=14 height=14 hspace=3 border=0>\n";
        			}
      			}

			// Si on a pas les droits on n'a pas de lien
			if ((ldap_get_right("Annu_is_admin",$login) == "Y") || (ldap_get_right("annu_can_read",$login) == "Y")) {
      				echo "<A href=\"people.php?uid=".$people[$loop]["uid"]."\">".$people[$loop]["fullname"]."</A>";
			} else {
				// si on a les droits sovajon_is_admin on v&#233;rifie si on a la classe
				$uid_eleve=$people[$loop]["uid"];
				if ((tstclass($login,$uid_eleve)==1) and (ldap_get_right("sovajon_is_admin",$login)=="Y") and ($people[$loop]["prof"]!=1)) {
					 echo "<A href=\"people.php?uid=".$people[$loop]["uid"]."\">".$people[$loop]["fullname"]."</A>";
				} else {
					echo $people[$loop]["fullname"];
				}
			}


      			if ( $people[$loop]["owner"] ) {
        			echo "<strong><font size=\"-2\" color=\"#ff8f00\">&nbsp;&nbsp;(".gettext("professeur principal").")</font></strong>";
        			$owner = $people[$loop]["uid"];
      			}
      			echo "<BR>\n";
    		}
  	} else {
    		echo " <STRONG>".gettext("Pas de membres")."</STRONG> ".gettext(" dans le groupe")." $filter.<BR>";
  	}


	//
	// Affichage menu admin (se3_is_admin et Annu_is_admin)
	// Pour les groupes sauf pour les groupes Eleves Profs Administratifs
	//

  	if ( (is_admin("Annu_is_admin",$login) == "Y") && ($filter!="Eleves" && $filter!="Profs" && $filter!="Administration" && $group[0]["gidnumber"] != $defaultgid) ) {
    		echo "<br><ul style=\"color: red;\">\n";

     		// Affichage du menu "Ajouter des membres" si le groupe est de type Equipe_ ou Classe
   		if (  preg_match ("/Equipe_/", $filter) || preg_match("/Classe_/", $filter) ) {
      			echo "<li><a href=\"add_list_users_group.php?cn=$filter\">".gettext("Ajouter des membres")."</a></li>\n";
    		}

    		// keyser ajout MC Marques
    		// Affichage du menu "Ajouter des membres" si le groupe n'est de type Equipe_ ou Classe
		// pour ajouter dans un sous-groupe
   		if (  !preg_match ("/Equipe_/", $filter) && !preg_match("/Classe_/", $filter) ) {
      			echo "<li><a href=\"aj_ssgroup.php?cn=$filter\">".gettext("Ajouter des membres")."</a></li>\n";
    		}
    		// fin ajout

		//Lien pour supprimer des membres
		if (count($people) ) {
      			echo "<li><a href=\"del_user_group.php?cn=$filter\">".gettext("Enlever des membres")."</a></li>\n";
    		}

		// Lien pour supprimer le groupe
		echo "<li><a href=\"del_group.php?cn=$filter\" onclick= \"return getconfirm();\">".gettext("Supprimer ce groupe")."</a></li>\n";

		// Lien pour modifier la description du groupe
    		echo "<li><a href=\"mod_group_descrip.php?cn=$filter\">".gettext("Modifier la description de ce groupe")."</a></li>\n";


//    		if ( preg_match("/Equipe_/",$filter) ) {
//      			if ( $owner ) {
//        			echo "<li><a href=\"mod_owner_group.php?cn=$filter&owner=$owner\">".gettext("R&#233;affecter le professeur principal")."</a></li>\n";
//      			} else {
//        			echo "<li><a href=\"mod_owner_group.php?cn=$filter\">".gettext("Affecter un professeur principal")."</a></li>\n";
//      			}
//    		}


		// Affiche un listing du groupe
		echo "<li><a href=\"grouplist.php?filter=$filter\" target='_new'>".gettext("Afficher un listing du groupe")."</a></li>\n";

		// Envoyer un popup a ce groupe
		echo "  <li><a href=\"pop_group.php?filter=$filter\">".gettext("Envoyer un Pop Up &#224; ce groupe")."</a></li>\n";

    		// Affichage menu gestion des droits
		// si la personne est admin uniquement
    		if (ldap_get_right("se3_is_admin",$login) == "Y") {
    			// Affichage du menu "Deleguer un droit a un groupe"
        		echo "<li><a href=\"add_group_right.php?cn=$filter\">".gettext("G&#233;rer les droits de ce groupe")."</a></li>\n";
			// ajout par keyser : affichage supplementaire pour les groupes tpe / idd ...
			if (!preg_match("!Equipe|Cours|Classe|Matiere|Administratifs|admins!", "$filter")) {
				echo "<li><a href=\"refresh_grpclass.php?nom_grp=$filter\">".gettext("Cr&#233;er ou rafraichir une ressource groupe classe(s)")."</a></li>\n";
			}

		} // Fin Affichage menu droits
		echo "</ul>\n";
  } else if (ldap_get_right("se3_is_admin",$login) == "Y") {
    	// Affichage du menu "Deleguer un droit a un groupe"
        echo "<br><ul style=\"color: red;\">\n";
        echo "<li><a href=\"pop_group.php?filter=$filter\">".gettext("Envoyer un Pop Up &#224; ce groupe")."</a></li>\n";
        echo "<li><a href=\"add_group_right.php?cn=$filter\">".gettext("G&#233;rer les droits de ce groupe")."</a></li>\n";
    	echo "<li><a href=\"grouplist.php?filter=$filter\" target='_new'>".gettext("Afficher un listing du groupe")."</a></li>\n";
		echo "<li><a href=\"create_template_group.php?filter=$filter\">".gettext("Cr&#233;er un dossier de template pour le groupe")."</a></li>\n";
	echo "</ul>\n";
  }

 //echo "<br />";


	if (ldap_get_right("se3_is_admin",$login) == "Y") {
		echo "<ul style=\"color: red;\">\n";
		echo "<li><a href=\"create_template_group.php?filter=$filter\">".gettext("Cr&#233;er un dossier de template pour le groupe")."</a></li>\n";
		echo "</ul>\n";
	}

  // ajout du lien trombinoscope
  // Si Annu_is_admin et le repertoire existe on peut  voir les trombinoscopes
  //
  if ((ldap_get_right("Annu_is_admin",$login) == "Y") && is_dir("/var/se3/Docs/trombine")) {
  	      	echo "<ul style=\"color: red;\">\n";
      		echo "<li><a href=\"trombin.php?filter=$filter\" target='_new'>".gettext("Afficher un trombinoscope du groupe")."</a></li>\n";
        	echo "</ul>\n";
  }

// Si le groupe est classe
if (preg_match("/Classe_/i", "$filter")) {
	$classe = preg_replace("/Classe_/i","",$filter);
	$classe = "Equipe_".$classe;
}
// Si le groupe est cours
if (preg_match("/Cours_/i", "$filter")) {
	$classe = $filter;
	$classe = preg_replace("/Classe_/i","",$filter);
}

// echo "are_you_in_group($login,$classe)";
// echo are_you_in_group($login,$classe);
// pour pas avoir un double affichage
 if (ldap_get_right("Annu_is_admin",$login) != "Y") {
        // Si sovajon_is_admin et prof de la classe
        if ((ldap_get_right("sovajon_is_admin",$login)=="Y") and (are_you_in_group($login,$classe))) {

                // Affiche trombinoscope de la classe
                echo "<ul style=\"color: red;\">\n";
                echo "<li><a href=\"trombin.php?filter=$filter\" target='_new'>".gettext("Afficher un trombinoscope du groupe")."</a></li>\n";

                // Affiche un listing du groupe si on a une Classe
                // que pour les eleves

                if (preg_match("/Classe_/i", "$filter")) {
                // if ((preg_match("/Classe_/i", "$filter")) || (preg_match("/Cours_/i", "$filter"))) {
                                echo "<li><a href=\"grouplist.php?filter=$filter\" target='_new'>".gettext("Afficher un listing du groupe")."</a></li>\n";
                }
                echo "</ul>\n";
        } elseif (ldap_get_right("annu_can_read",$login)=="Y") {
                // Affiche trombinoscope de la classe
                echo "<li><a href=\"trombin.php?filter=$filter\" target='_new'>".gettext("Afficher un trombinoscope du groupe")."</a></li>\n";
                echo "</ul>\n";
        }
  }


  // Modifie par Wawa
  // Affichage de l'equipe pedagogique associee a la classe

  if (preg_match("/Classe/",$filter,$matche)) {
    	$filter2 = preg_replace("/Classe_/","Equipe_",$filter);
    	$uids2 = search_uids ("(cn=".$filter2.")");
    	$people2 = search_people_groups ($uids2,"(sn=*)","cat");
   	if (count($people2)) {
    		// affichage des resultats
    		echo "<BR><U>".gettext("Professeurs de la classe")."</U> : <a href=\"group.php?filter=$filter2\">$filter2</A><BR>\n";
    		for ($loop=0; $loop < count($people2); $loop++) {
      			if ($people2[$loop]["cat"] == "Equipe") {
        			echo "<img src=\"images/gender_teacher.gif\" alt=\"Professeur\" width=18 height=18 hspace=1 border=0>\n";
      			} else {
        			if ($people2[$loop]["sexe"]=="F") {
          				echo "<img src=\"images/gender_girl.gif\" alt=\"El&egrave;ve\" width=14 height=14 hspace=3 border=0>\n";
        			} else {
          				echo "<img src=\"images/gender_boy.gif\" alt=\"El&egrave;ve\" width=14 height=14 hspace=3 border=0>\n";
        		}
      		}

		// On a un lien sur les profs uniquement si on est annu_can_read ou Annu_is_admin
		if ((ldap_get_right("Annu_is_admin",$login) == "Y")|| (ldap_get_right("annu_can_read",$login) == "Y")) {
      			echo "<A href=\"people.php?uid=".$people2[$loop]["uid"]."\">".$people2[$loop]["fullname"]."</A>";
		} else {
			echo $people2[$loop]["fullname"];
		}
      		if ( $people2[$loop]["owner"] ) {
        		echo "<strong><font size=\"-2\" color=\"#ff8f00\">&nbsp;&nbsp;(professeur principal)</font></strong>";
        		$owner = $people2[$loop]["uid"];
      		}
      		echo "<BR>\n";
    	}
  }
}


// Modifie par Wawa
// Affichage du rebond sur la classe associee a une equipe pedagogique

  if (preg_match("/Equipe/",$filter,$matche))  {
    	$filter2 = preg_replace("/Equipe_/","Classe_",$filter);
    	$uids2 = search_uids ("(cn=".$filter2.")");
    	$people2 = search_people_groups ($uids2,"(sn=*)","cat");
    	if (count($people2)) {
    		// affichage des resultats
    		echo "<BR>".gettext("Il y a ") . count($people2) . gettext(" &#233;l&#232;ves dans la ")."<a href=\"group.php?filter=$filter2\">$filter2</A>".gettext(" associ&#233;e &#224; cette &#233;quipe.")."\n";
    		echo "<BR>\n";
  	}
  }

  include ("pdp.inc.php");
?>
