<?php


/**

* Interface de gestion du fond d'ecran
* @Version $Id$ 


* @Projet LCS / SambaEdu 

* @auteurs  Stephane Boireau

* @Licence Distribue selon les termes de la licence GPL

* @note 

*/

/**

* @Repertoire: fond_ecran
* file: searchacls.php

*/	


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

//include "crob_ldap_functions.php";

require_once("lang.inc.php");
bindtextdomain('se3-fond',"/var/www/se3/locale");
textdomain ('se3-fond');

//aide
$_SESSION["pageaide"]="Le_module_Syst%C3%A8me_fond_d\'%C3%A9cran";

//aff_trailer ("2");

$is_posted=isset($_POST['is_posted']) ? $_POST['is_posted'] : (isset($_GET['is_posted']) ? $_GET['is_posted'] : NULL);

function affiche_sur_N_colonnes($tableau,$nb_col=3) {
	$retour="";

	$nb_lignes_par_colonne=round(count($tableau)/$nb_col);

	$retour.="<table width='100%'>\n";
	$retour.="<tr valign='top' align='center'>\n";

	$cpt = 0;

	$retour.="<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
	$retour.="<td align='left'>\n";

	for($loop=0;$loop<count($tableau);$loop++) {

		//affichage $nb_col colonnes
		if(($cpt>0)&&(round($cpt/$nb_lignes_par_colonne)==$cpt/$nb_lignes_par_colonne)){
			$retour.="</td>\n";
			$retour.="<td align='left'>\n";
		}

		$retour.=$tableau[$loop];
		$retour.="<br />\n";
		$cpt++;
	}

	$retour.="</td>\n";
	$retour.="</tr>\n";
	$retour.="</table>\n";

	return $retour;
}

//if(!isset($_POST['is_posted'])) {
if(!isset($is_posted)) {
	$titre=gettext("Rechercher un utilisateur");

	$texte ="<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">\n";
	$texte .= "<table>\n";
	$texte .= "<tbody>\n";
	$texte .= "<tr>\n";
	$texte .= "<td>".gettext("Nom complet :")."</td>\n";
	$texte .= "<td>\n";
	$texte .= "<select name=\"priority_surname\">\n";
	$texte .= "<option value=\"contient\">".gettext("contient")."</option>\n";
	$texte .= "<option value=\"commence\">".gettext("commence par")."</option>\n";
	$texte .= "<option value=\"finit\">".gettext("finit par")."</option>\n";
	$texte .= "</select>\n";
	$texte .= "</td>\n";
	$texte .= "<td><input type=\"text\" name=\"fullname\"></td>\n";
	$texte .= "</tr>\n";
	$texte .= "<tr>\n";
	$texte .= "<td>".gettext("Nom :")."</td>\n";
	$texte .= "<td>\n";
	$texte .= "<select name=\"priority_name\">\n";
	$texte .= "<option value=\"contient\">".gettext("contient")."</option>\n";
	$texte .= "<option value=\"commence\">".gettext("commence par")."</option>\n";
	$texte .= "<option value=\"finit\">".gettext("finit par")."</option>\n";
	$texte .= "</select>\n";
	$texte .= "</td>\n";
	$texte .= "<td><input type=\"text\" name=\"nom\"></td>\n";
	$texte .= "</tr>\n";
	$texte .= "<tr>\n";
	$texte .= "</tbody>\n";
	$texte .= "</table>\n";
	$texte .= "<input type=\"hidden\" name=\"is_posted\" value=\"1\" />\n";
	$texte .= "<div align=center><input type=\"submit\" Value=\"".gettext("Lancer la requ&#234;te")."\"></div>";
	$texte .= "</form>\n";
	mktable($titre,$texte);

	// Recherche d'un groupe (classe, Equipe, Cours ...)
	$titre = gettext("Rechercher un groupe (classe, &#233;quipe, cours ...)")."</h2>\n";

	$texte = "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">\n";
	$texte .= "<table>\n";
	$texte .= "<tbody>\n";
	$texte .= "<tr>\n";
	$texte .= "<td>".gettext("Groupe :")."</td>\n";
	$texte .= "<td>\n";
	$texte .= "<select name=\"priority_group\">\n";
	$texte .= "<option value=\"contient\">".gettext("contient")."</option>\n";
	$texte .= "<option value=\"commence\">".gettext("commence par")."</option>\n";
	$texte .= "<option value=\"finit\">".gettext("finit par")."</option>\n";
	$texte .= "</select>\n";
	$texte .= "</td>\n";
	$texte .= "<td><input type=\"text\" name=\"group\"></td>\n";
	$texte .= "</tr>\n";
	$texte .= "</tbody>\n";
	$texte .= "</table>\n";
	$texte .= "<input type=\"hidden\" name=\"is_posted\" value=\"2\" />\n";
	$texte .= "<div align=center><input type=\"submit\" Value=\"".gettext("Lancer la requ&#234;te")."\"></div>\n";
	$texte .= "</form>\n";
	echo "<br />";
	mktable($titre,$texte);

	echo "<br /><br /><br /><br /><br /><center><B><a href=\"#\" onClick=\"window.close ();\">".gettext("Fermer la fen&#234;tre")."</a></B></center>";
	
	include ("pdp.inc.php");
	
	die();
}
//elseif($_POST['is_posted']=='1') {
elseif($is_posted=='1') {

	// Recuperation des variables
	$nom=isset($_POST['nom']) ? $_POST['nom'] : '';
	$classe=isset($_POST['classe']) ? $_POST['classe'] : '';
	$fullname=isset($_POST['fullname']) ? $_POST['fullname'] : '';
	$priority_name=isset($_POST['priority_name']) ? $_POST['priority_name'] : '';
	$priority_surname=isset($_POST['priority_surname']) ? $_POST['priority_surname'] : '';
	$priority_classe=isset($_POST['priority_classe']) ? $_POST['priority_classe'] : '';
	
	
	// Convertion en utf_8
	$nom = utf8_encode($nom);
	$fullname = utf8_encode($fullname);
	// Construction du filtre de la branche people
	if ($nom!='' && $fullname=='') {
		// Recherche sur sn
		if ($priority_name=="contient") {
			$filter_people="(sn=*$nom*)";
		} elseif($priority_name=="commence") {
			$filter_people="(sn=$nom*)";
		} else {
			$filter_people="(sn=*$nom)";
		}
	} elseif ($fullname!='' && $nom=='') {
		// Recherche sur cn
		if ($priority_surname=="contient") {
			$filter_people="(cn=*$fullname*)";
		} elseif($priority_surname=="commence") {
			$filter_people="(cn=$fullname*)";
		} else {
			$filter_people="(cn=*$fullname)";
		}
	} elseif ($fullname!='' && $nom!='') {
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
	
	echo "<br /><br /><br /><center><B><a href=\"#\" onClick=\"window.close ();\">".gettext("Fermer la fen&#234;tre")."</a></B></center><br /><br /><br />";
	if ($filter_people ) {
		// recherche dans la branche People
		$users = search_people ($filter_people);
		if (count($users)) {
			if (count($users)==1) {
				echo "<p><STRONG>".count($users)."</STRONG> ".gettext(" utilisateur r&#233;pond &#224; ces crit&#232;res de recherche")."</p>\n";
			} else {
				echo "<p><STRONG>".count($users)."</STRONG> ".gettext("utilisateurs r&#233;pondent &#224; ces crit&#232;res de recherche")."</p>\n";
			}

			/*
			echo "<UL>\n";
			echo "<form><select name=\"liste\" onChange=\"Reporter(this)\">";
			echo "<option value=\"\">".gettext("Votre choix ...")."</option>";
			for ($loop=0; $loop<count($users);$loop++) {
				echo "<option value=\"".$users[$loop]["uid"]."\">".$users[$loop]["fullname"]."</option>";  
			}
			
			echo "<br /><br /><br /><br /><br /><center><B><a href=\"#\" onClick=\"window.close ();\">".gettext("Fermer la fen&#234;tre")."</a></B></center>";
			echo "</form></UL>\n";
			*/

			$tab=array();
			for ($loop=0; $loop<count($users);$loop++) {
				//echo "<a href=\"javascript: update_login('".$users[$loop]["uid"]."')\">".$users[$loop]["fullname"]."</a><br />";
				$tab[]="<a href=\"javascript: update_login('".$users[$loop]["uid"]."')\">".$users[$loop]["fullname"]."</a>";
			}
			echo affiche_sur_N_colonnes($tab,3);

			echo "<script type='text/javascript'>
	function update_login(login) {
		window.opener.document.forms['form1'].elements['cible'].value=login;
		window.close ();
	}

	window.scrollbars.visible='true';
</script>";
		} else {
			echo " <STRONG>".gettext("Pas de r&#233;sultats")."</STRONG> ".gettext("correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<br />\n";
		}
	} else {
		// Aucun critere de recherche
		echo " <STRONG>".gettext("Pas de r&#233;sultats !")."</STRONG><br />".gettext("
		Veuillez compl&#233;ter au moins l'un des deux champs (nom, pr&#233;nom) du formulaire de recherche !")."<br />\n";
	}
}
//elseif($_POST['is_posted']=='2') {
elseif($is_posted=='2') {
	$group=isset($_POST['group']) ? $_POST['group'] : "";
	$priority_group=isset($_POST['priority_group']) ? $_POST['priority_group'] : "contient";

	if ($group=='') {
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

		/*
		echo "<UL>\n";
		echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">\n";
		echo "<select name=\"group\">\n";
		echo "<option value=\"\">".gettext("Votre choix ...")."</option>";
		for ($loop=0; $loop<count($groups);$loop++) {
			echo "<option value=\"".$groups[$loop]["cn"]."\">".$groups[$loop]["cn"]."</option>";
		}
		echo "</form></UL>\n";
		*/

		$tab=array();
		for ($loop=0; $loop<count($groups);$loop++) {
			//echo "<a href=\"".$_SERVER['PHP_SELF']."?group=".$groups[$loop]["cn"]."&amp;is_posted=3\">".$groups[$loop]["cn"]."</a><br />";
			$tab[]="<a href=\"".$_SERVER['PHP_SELF']."?group=".$groups[$loop]["cn"]."&amp;is_posted=3\">".$groups[$loop]["cn"]."</a>";
		}
		echo affiche_sur_N_colonnes($tab,3);

		echo "<script type='text/javascript'>
	window.scrollbars.visible='true';
</script>";

	} else {
		echo "<STRONG>".gettext("Pas de r&#233;sultats")."</STRONG>".gettext(" correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<BR>";
	}
	
}
elseif($is_posted=='3') {
	$group=isset($_GET['group']) ? $_GET['group'] : "";

	if($group!='') {
		$filter="cn=$group";

		$users=search_uids($filter);

		if (count($users)) {
			if (count($users)==1) {
				echo "<p><STRONG>".count($users)."</STRONG> ".gettext(" utilisateur r&#233;pond &#224; ces crit&#232;res de recherche")."</p>\n";
			} else {
				echo "<p><STRONG>".count($users)."</STRONG> ".gettext("utilisateurs r&#233;pondent &#224; ces crit&#232;res de recherche")."</p>\n";
			}

			$tab=array();
			for ($loop=0; $loop<count($users);$loop++) {
				$current_user=search_people("uid=".$users[$loop]["uid"]);
				if($current_user) {
					//echo "<a href=\"javascript: update_login('".$users[$loop]["uid"]."')\">".$current_user[0]["fullname"]."</a><br />";
					$tab[]="<a href=\"javascript: update_login('".$users[$loop]["uid"]."')\">".$current_user[0]["fullname"]."</a>";
				}
			}
			echo affiche_sur_N_colonnes($tab,3);

			echo "<script type='text/javascript'>
	function update_login(login) {
		window.opener.document.forms['form1'].elements['cible'].value=login;
		window.close ();
	}

	window.scrollbars.visible='true';
</script>";
		} else {
			echo " <STRONG>".gettext("Pas de r&#233;sultats")."</STRONG> ".gettext("correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<br />\n";
		}
	}
	else {
		echo "<STRONG>".gettext("Pas de r&#233;sultats")."</STRONG>".gettext(" correspondant aux crit&#232;res s&#233;lectionn&#233;s.")."<BR>";
	}
}
else {
	echo " <STRONG>".gettext("Anomalie !")."</STRONG><br />".gettext("Veuillez valider un des deux formulaires de recherche !")."<br />\n";
}

echo "<p><a href='".$_SERVER['PHP_SELF']."'>".gettext("Effectuer une autre recherche")."</a></p>\n";

include ("pdp.inc.php");
?>
