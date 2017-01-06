<?php


   /**
   
   * Recherche les utilisateurs a partir de l'annuaire
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
   * file: search.php
   */




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

if ((is_admin("annu_can_read",$login)=="Y") || (is_admin("Annu_is_admin",$login)=="Y"))  {

	//aide
	$_SESSION["pageaide"]="Annuaire";

	echo "<h1>".gettext("Annuaire")."</h1>\n";
	aff_trailer ("2");

	$titre=gettext("Rechercher un utilisateur");
   	$texte ="<form action=\"peoples_list.php\" method='post' id='form1'>\n";
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
	$texte .= "<td><input type=\"text\" name=\"fullname\" id=\"fullname\"></td>\n";
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
	$texte .= "<td><input type=\"text\" name=\"nom\" id=\"nom\"></td>\n";
	$texte .= "</tr>\n";
	$texte .= "<tr>\n";
	$texte .= "<td>".gettext("Classe :")."</td>\n";
	$texte .= "<td>\n";
	$texte .= "<select name=\"priority_classe\">\n";
	$texte .= "<option value=\"contient\">".gettext("contient")."</option>\n";
	$texte .= "<option value=\"commence\">".gettext("commence par")."</option>\n";
	$texte .= "<option value=\"finit\">".gettext("finit par")."</option>\n";
	$texte .= "</select>\n";
	$texte .= "</td>\n";
	$texte .= "<td><input type=\"text\" name=\"classe\" id=\"classe\"></td>\n";
	$texte .= "</tr>\n";
	$texte .= "</tbody>\n";
 	$texte .= "</table>\n";
	$texte .= "<div align=center>
	<input type=\"submit\" id='input_submit_1' value=\"".gettext("Lancer la requ&#234;te")."\" />
	<input type=\"button\" id='input_submit_2' value=\"".gettext("Lancer la requ&#234;te")."\" style='display:none' onclick='test_form1()' />
</div>";
	$texte .= "</form>\n";
	mktable($titre,$texte);

    // Recherche d'un groupe (classe, Equipe, Cours ...)
 	$titre = gettext("Rechercher un groupe (classe, &#233;quipe, cours ...)")."</h2>\n";
    	$texte = "<form action=\"groups_list.php\" method='post' id='form2'>\n";
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
	$texte .= "<td><input type=\"text\" name=\"group\" id=\"group\"></td>\n";
	$texte .= "</tr>\n";
	$texte .= "</tbody>\n";
 	$texte .= "</table>\n";
	$texte .= "<div align=center>
	<input type=\"submit\" id='input_submit_3' value=\"".gettext("Lancer la requ&#234;te")."\" />
	<input type=\"button\" id='input_submit_4' value=\"".gettext("Lancer la requ&#234;te")."\" style='display:none' onclick='test_form2()' />
</div>";
    	$texte .= "</form>\n";
	echo "<BR>";

	$texte.="<script type='text/javascript'>
	document.getElementById('input_submit_1').style.display='none';
	document.getElementById('input_submit_2').style.display='';
	document.getElementById('input_submit_3').style.display='none';
	document.getElementById('input_submit_4').style.display='';

	if(document.getElementById('fullname')) {
		document.getElementById('fullname').focus();
	}

	function test_form1() {
		if((document.getElementById('fullname').value=='')&&(document.getElementById('nom').value=='')&&(document.getElementById('classe').value=='')) {
			alert('Tous les champs du formulaire sont vides.');
		}
		else {
			document.getElementById('form1').submit();
		}
	}

	function test_form2() {
		if(document.getElementById('group').value=='') {
			alert('Le champ groupe ne peut pas etre vide.');
		}
		else {
			document.getElementById('form2').submit();
		}
	}

</script>";

	mktable($titre,$texte);
}
  include ("pdp.inc.php");
?>
