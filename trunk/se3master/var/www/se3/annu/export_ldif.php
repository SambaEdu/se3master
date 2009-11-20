<?php


   /**
   
   * Export un ldif de l'annuaire
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
   * file: export_ldif.php
   */



require "entete.inc.php";
include "ihm.inc.php";
include "ldap.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');
      
$_SESSION["pageaide"]="Annuaire";

if (is_admin("se3_is_admin",$login)=="Y") {
	$_SESSION["pageaide"]="Annuaire#Export_LDAP";
	echo "<h1>".gettext("Exportation de l'annuaire LDAP")."</h1>";

    	// Affichage du formulaire d'exportation LDAP
   	if (!isset($filtre)) {
        	echo "<H3>".gettext("Exportation d'annnuaire")."</H3>";
		// Filtrage des noms
		echo "<FORM action=\"export.php\" method=\"post\">\n";
		echo "<P>".gettext("Si vous laissez vide le champ filtre, la totalit&#233; de l'annuaire sera export&#233;")."\n";
		echo "<P>".gettext("Filtre LDAP :")." <INPUT TYPE=\"text\" NAME=\"filtre\"\n SIZE=\"60\">";
		echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
		echo "</FORM>\n";

		echo "<u onmouseover=\"return escape".gettext("('Permet d\'exporter en focntion de certain filtre.<br>Exemple exporter les mails des utilisateurs mettre simplement : <b>mail</b>.<br>Exporter juste un utilisateur par son login : <b>uid=son_login</b>.<br>Exporter les entr&#233;es de tous les utilisateurs dont les noms commencent par R : <b>sn=R*</b>')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\"></u>";
  	}
}
include ("pdp.inc.php");
?>
