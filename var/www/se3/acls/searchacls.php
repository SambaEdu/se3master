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
   * file: searchacls.php

  */	


  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  require_once ("lang.inc.php");
  bindtextdomain('se3-acls',"/var/www/se3/locale");
  textdomain ('se3-acls');

   // Aide
   $_SESSION["pageaide"]="ACL#En_utilisant_l.27interface_SambaEdu";

  //aff_trailer ("2");

	$titre=gettext("Rechercher un utilisateur");
   	$texte ="<form action=\"peoples_listacls.php\" method = post>\n";
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
	//$texte .= "<td>Classe :</td>\n";
	//$texte .= "<td>\n";
	//$texte .= "<select name=\"priority_classe\">\n";
	//$texte .= "<option value=\"contient\">contient</option>\n";
	//$texte .= "<option value=\"commence\">commence par</option>\n";
	//$texte .= "<option value=\"finit\">finit par</option>\n";
	//$texte .= "</select>\n";
	//$texte .= "</td>\n";
	//$texte .= "<td><input type=\"text\" name=\"classe\"></td>\n";
	//$texte .= "</tr>\n";
	$texte .= "</tbody>\n";
 	$texte .= "</table>\n";
	$texte .= "<div align=center><input type=\"submit\" Value=\"".gettext("Lancer la requ&#234;te")."\"></div>";
    $texte .= "</form>\n";
	mktable($titre,$texte);

    // Recherche d'un groupe (classe, Equipe, Cours ...)
 	$titre = gettext("Rechercher un groupe (classe, &#233;quipe, cours ...)")."</h2>\n";
    	$texte = "<form action=\"groups_listacls.php\" method = post>\n";
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

echo "<br><br><br><br><br><center><B><a href=\"#\" onClick=\"window.close ();\">".gettext("Fermer la fen&#234;tre")."</a></B></center>";

include ("pdp.inc.php");
?>
