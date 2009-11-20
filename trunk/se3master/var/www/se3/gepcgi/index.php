<?php


   /**
   
   * Affiche la page avant import sconet
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe TICE CRDP de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: gepcgi/
   * file: index.php

  */	


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

if (is_admin("Annu_is_admin",$login)=="Y") {
        
	$_SESSION["pageaide"]="Gestion_des_utilisateurs";
?>
 	<h1>Importation des donn&eacute;es de l'annuaire</h1>

	<h2>Principe</h2>

	<p>Le fonctionnement du serveur  <span class="abbrev">Samba&Eacute;du</span> est fond&eacute; sur l'utilisation d'un annuaire des utilisateurs et des diff&eacute;rents groupes de travail associ&eacute;s.</p>


	<p>Il est possible d'importer les informations n&eacute;cessaires &agrave; la constitution automatique de l'annuaire <span class="abbrev">Ldap</span> des acteurs de l'&eacute;tablissement scolaire, donc du syst&egrave;me directement depuis les fichiers sconet ou depuis un ensemble de fichiers texte correctement format&eacute;s


	<h2>Importation</h2>
	<ul>
  	<li><a href="../annu/import_sconet.php">directement via des fichiers CSV/XML de sconet</a></li>
	<li><a href="texte.php">Via des fichiers texte</a></li>
	</ul>

	
	<?php
}
include("pdp.inc.php");
?>
