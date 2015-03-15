<?php


   /**
   
   * Cherche un utilisateur 
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe Tice academie de Caen
   * @auteurs jLCF >:> jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: parcs/
   * file: search_user.php

  */	



  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  // Internasionalisation
  require_once ("lang.inc.php");
  bindtextdomain('se3-parcs',"/var/www/se3/locale");
  textdomain ('se3-parcs');


  //aff_trailer ("2");
    $titre=gettext("Rechercher un utilisateur");
    $texte ="<form action=\"../acls/peoples_listacls.php\" method = post>\n";
 
    $texte .= "<table>\n";
    $texte .= "<tbody>\n";
    $texte .= "<tr>\n";
    $texte .= "<td>Nom complet :</td>\n";
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
    $texte .= "<td>".gettext("Nom")." :</td>\n";
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
    $texte .= "<div align=center><input type=\"submit\" Value=\"".gettext("Lancer la requ&#234te")."\"></div>";
    $texte .= "</form>\n";
    mktable($titre,$texte);


echo "<br><br><br><br><br><center><B><a href=\"#\" onClick=\"window.close ();\">".gettext("Fermer la fen&#234tre")."</a></B></center>";

include ("pdp.inc.php");
?>
