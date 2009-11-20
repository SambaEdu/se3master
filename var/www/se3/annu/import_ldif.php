<?php


   /**
   
   * Importation a partir d'un ldif de l'annuaires
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
   * file: import_ldif.php
   */




require "entete.inc.php";
include "ihm.inc.php";
include "ldap.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');


if (is_admin("se3_is_admin",$login)=="Y") {
	$_SESSION["pageaide"]="Annuaire#Import_LDAP";
	echo "<h1>".gettext("Importation de l'annuaire LDAP")."</h1>";

	$uploaddir = "/tmp/";
	$nomFichier = basename($_FILES['ldiffile']['name']);
	if ( $nomFichier != '') {
		$ldiffile = $uploaddir . $nomFichier;
		echo "<h3>Transfert du fichier ldif</h3>\n";
		if (move_uploaded_file($_FILES['ldiffile']['tmp_name'], $ldiffile)) {
			echo "Le fichier '$nomFichier' a &#233;t&#233; transf&#233;r&#233; avec succ&#232;s.<br>\n";
			echo "<H3>".gettext("Publication du fichier")."</H3>";
			echo "<PRE>\n";	

   			system ("ldapadd -x -c -h $ldap_server -D $adminRdn,$ldap_base_dn -w $adminPw -f $ldiffile");
   			echo "</PRE>\n";
   			unlink ("$ldiffile");
		} else {
			echo "Erreur de transfert du fichier '$nomFichier'.<br>\n";
		}

	} else {
   		// Affichage du formulaire d'exportation LDAP
      		echo "<H3>".gettext("Importation dans l'annnuaire")."</H3>";
		// Filtrage des noms
		echo "<FORM action=\"import_ldif.php\" method=\"post\" ENCTYPE=\"multipart/form-data\">\n";
		echo "<P>".gettext("Ajoute les donn&#233;es de votre fichier ldif &#224; l'annuaire")." <P>".gettext("Les doublons ne seront pas import&#233;");
		echo "<P>".gettext("Fichier ldif &#224; importer :")." <input name='ldiffile' type='file'>";
		echo "<DIV align='center'><INPUT type='submit' VALUE='".gettext("Importer le fichier")."'></DIV>\n";
		echo "</FORM>\n";
  	}
}
include ("pdp.inc.php");
?>
