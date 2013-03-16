<?php


   /**
   
   * Affiche les utilisateurs a partir de l'annuaire
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
   * file: annu.php
   */



include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

if (is_admin("Annu_is_admin",$login)=="Y")
        $_SESSION["pageaide"]="Annuaire";
else if (ldap_get_right("sovajon_is_admin",$login)=="Y")
        $_SESSION["pageaide"]="L%27interface_prof#Annuaire";
else $_SESSION["pageaide"]="L%27interface_%C3%A9l%C3%A8ve#Acc.C3.A9der_.C3.A0_l.27annuaire";

echo "<h1>".gettext("Annuaire")."</h1>\n";

aff_trailer ("1");
// Affichage des coordonnees de l'Etablissement
/*
$ldap_etab_attr = array(
    "ou",                 // Intitule de l'Etablissement
    "street",
    "l",
    "postOfficeBox",
    "PostalCode",
    "telephoneNumber"
  );

$ds = @ldap_connect ( $ldap_server, $ldap_port );
if ( $ds ) {
	$r = @ldap_bind ( $ds ); // Bind anonyme
    	if ($r) {
      		$result = @ldap_read ( $ds, $ldap_base_dn, "(objectclass=organizationalUnit)", $ldap_etab_attr );
      		if ($result) {
        		$info = @ldap_get_entries ( $ds, $result );
        		if ( $info["count"]) {
          			echo "<blockquote style=\"font-size: large; font-weight: bold; text-align: center\">\n";
         			echo utf8_decode($info[0]["ou"][0])."<BR>\n";
          			echo $info[0]["street"][0]."<BR>\n";
          			if ( $info[0]["postofficebox"][0]) {
            				echo $info[0]["postofficebox"][0]."&nbsp;-&nbsp;";
          			}
          			echo $info[0]["postalcode"][0]." ".utf8_decode($info[0]["l"][0])."<BR>\n";
          			echo "Tel. ".$info[0]["telephonenumber"][0]."\n";
          			echo"</blockquote>\n";
        		}
        	@ldap_free_result ( $result );
      	}
} else {
	$error = gettext("Echec du bind anonyme");
}
	@ldap_close ( $ds );
} else {
  	$error = gettext("Erreur de connection au serveur LDAP");
}
*/

aff_mnu_search(is_admin("Annu_is_admin",$login));
if (ldap_get_right("Annu_is_admin",$login)=="Y") {
	//echo "<ul><li><b>".gettext("Administration :")."</b></li>";
	echo "<ul><li><b>".gettext("Administration :")."</b>\n";
  	echo "<ul>\n";
	echo "<li><a href=\"delete_right.php\">".gettext("Enlever un droit d'administration.")."</a></li>\n";
    	echo "<li><a href=\"peoples_desac.php\">".gettext("D&#233;sactiver des comptes.")."</a></li>\n";
     	echo "<li><a href=\"peoples_desac.php?action=activ\">".gettext("Activer des comptes.")."</a></li>\n";
     	echo "<li><a href=\"../infos/infomdp.php\">".gettext("Tester les mots de passe.")."</a></li>\n";
     	echo "<li><a href=\"reinit_mdp.php\">".gettext("R&#233;initialiser/Modifier les mots de passe.")."</a></li>\n";
     	if (getintlevel()>=1)
       		echo "<li><a href=\"remplace.php\">".gettext("Attribution des droits &#224; un rempla&#231;ant.")."</a></li>\n";
    	echo "</ul>\n";
	echo "</li>\n";
	echo "</ul>\n";

include("listing.inc.php");

}

include ("pdp.inc.php");
?>
