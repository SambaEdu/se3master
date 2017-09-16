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
   * file: groups_list.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');


$group=isset($_POST['group']) ? $_POST['group'] : (isset($_GET['group']) ? $_GET['group'] : NULL);
$priority_group=isset($_POST['priority_group']) ? $_POST['priority_group'] : (isset($_GET['priority_group']) ? $_GET['priority_group'] : "contient");

// 20170915
$mes_groupes=isset($_POST['mes_groupes']) ? $_POST['mes_groupes'] : (isset($_GET['mes_groupes']) ? $_GET['mes_groupes'] : "n");
$is_prof=false;
$tab_mes_groupes=array();
if(are_you_in_group($login, "Profs")) {
	$is_prof=true;
	if($mes_groupes=="y") {
		$mes_infos=people_get_variables($login, true);

		/*
		echo "<pre>";
		print_r($mes_infos);
		echo "</pre>";
		*/

		if((isset($mes_infos[1]))&&(count($mes_infos[1])>0)) {
			for($loop=0;$loop<count($mes_infos[1]);$loop++) {
				if(isset($mes_infos[1][$loop]["cn"])) {
					if(preg_match("/^Equipe_/",$mes_infos[1][$loop]["cn"])) {
						$tab_mes_groupes[]=preg_replace("/^Equipe_/", "Classe_", $mes_infos[1][$loop]["cn"]);
					}
					$tab_mes_groupes[]=$mes_infos[1][$loop]["cn"];
				}
			}
		}

		/*
		echo "\$tab_mes_groupes<pre>";
		print_r($tab_mes_groupes);
		echo "</pre>";
		*/
	}
}

echo "<h1>".gettext("Annuaire")."</h1>\n";
$_SESSION["pageaide"]="Annuaire";

if ((ldap_get_right("Annu_is_admin",$login)=="Y") || (ldap_get_right("Annu_can_read",$login)=="Y") || (ldap_get_right("se3_is_admin",$login)=="Y")) {

	aff_trailer ("3");

	if (!$group) {
		$filter = "(cn=*)";
	} else {
		if ($priority_group == "contient") {
	      		$filter = "(cn=*$group*)";
	    	} elseif ($priority_group == "commence") {
	      		$filter = "(|(cn=Classe_$group*)(cn=Cours_$group*)(cn=Equipe_$group*)(cn=Matiere_$group*)(cn=$group*))";
	    	} else {
	      		// $priority_group == "finit"
	      		$filter = "(|(cn=Classe_*$group)(cn=Cours_*$group)(cn=Equipe_*$group)(cn=Matiere_*$group)(cn=*$group))";
    		}
	}

	// Remplacement *** ou ** par *
	$filter=preg_replace("/\*\*\*/","*",$filter);
	$filter=preg_replace("/\*\*/","*",$filter);
	
	#$TimeStamp_0=microtime();
	$groups=search_groups($filter);
	#$TimeStamp_1=microtime();
	  #############
	  # DEBUG     #
	  #############
	  #echo "<u>debug</u> :Temps de recherche = ".duree($TimeStamp_0,$TimeStamp_1)."&nbsp;s<BR>";
	  #############
	  # Fin DEBUG #
	  #############
	// affichage de la liste des groupes trouves
	if (count($groups)) {
		if((!$is_prof)||($mes_groupes!="y")) {
			if (count($groups)==1) {
				echo "<p><STRONG>".count($groups)."</STRONG>".gettext(" groupe r&#233;pond &#224; ces crit&#232;res de recherche")."</p>\n";
			} else {
				echo "<p><STRONG>".count($groups)."</STRONG>".gettext(" groupes r&#233;pondent &#224; ces crit&#232;res de recherche")."</p>\n";
			}
		}
		else {
			echo "<p>".gettext("Mes groupes de la cat&#233;gorie choisie")."</p>\n";
		}
		$nb_groupes_trouves=0;
	    echo "<UL>\n";
	    for ($loop=0; $loop < count($groups); $loop++) {
	    		// 20170915
	    		$afficher=true;
	    		if(($is_prof)&&($mes_groupes=="y")&&(!in_array($groups[$loop]["cn"], $tab_mes_groupes))) {
		    		$afficher=false;
	    		}
	    		if($afficher) {
				echo "<LI><A href=\"group.php?filter=".$groups[$loop]["cn"]."\">";
				if ($groups[$loop]["type"]=="posixGroup") {
		  		 echo "<STRONG>".$groups[$loop]["cn"]."</STRONG>";
		  		}
				else {
			  		echo $groups[$loop]["cn"];
			  	}
				echo "</A>&nbsp;&nbsp;&nbsp;<font size=\"-2\">".$groups[$loop]["description"]."</font></LI>\n";
				$nb_groupes_trouves++;
			}
            }
    	    echo "</UL>\n";
		echo "<p>$nb_groupes_trouves groupe(s) trouv&#233;(s).<p>";
	} else {
    		echo "<STRONG>".gettext("Pas de r&#233;sultats")."</STRONG> ".gettext("correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<BR>";
	}
  
} 
	

include ("pdp.inc.php");
?>
