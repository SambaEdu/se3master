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
   	$texte ="<form action=\"peoples_list.php\" method = post>\n";
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
	$texte .= "<td>".gettext("Classe :")."</td>\n";
	$texte .= "<td>\n";
	$texte .= "<select name=\"priority_classe\">\n";
	$texte .= "<option value=\"contient\">".gettext("contient")."</option>\n";
	$texte .= "<option value=\"commence\">".gettext("commence par")."</option>\n";
	$texte .= "<option value=\"finit\">".gettext("finit par")."</option>\n";
	$texte .= "</select>\n";
	$texte .= "</td>\n";
	$texte .= "<td><input type=\"text\" name=\"classe\"></td>\n";
	$texte .= "</tr>\n";
	$texte .= "</tbody>\n";
 	$texte .= "</table>\n";
	$texte .= "<div align=center><input type=\"submit\" Value=\"".gettext("Lancer la requ&#234;te")."\"></div>";
	$texte .= "</form>\n";
	mktable($titre,$texte);

    // Recherche d'un groupe (classe, Equipe, Cours ...)
 	$titre = gettext("Rechercher un groupe (classe, &#233;quipe, cours ...)")."</h2>\n";
    	$texte = "<form action=\"groups_list.php\" method = post>\n";
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
	$texte .= "<div align=center><input type=\"submit\" Value=\"".gettext("Lancer la requ&#234;te")."\"></div>\n";
    	$texte .= "</form>\n";
	echo "<BR>";
	mktable($titre,$texte);
}
  include ("pdp.inc.php");
?>
