<?php


   /**
   
   * Reinitialise les mots de passe
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
   * file: pass_user_init.php
   */





include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');


if ((is_admin("annu_can_read",$login)=="Y") || (is_admin("Annu_is_admin",$login)=="Y") || (is_admin("savajon_is_admin",$login)=="Y"))  {

	//Aide
	$_SESSION["pageaide"]="Annuaire";

	echo "<h1>".gettext("Annuaire")."</h1>\n";

	$uid_init=$_GET['uid'];

	// Recherche d'utilisateurs dans la branche people
	$filter="(uid=$uid_init)";
	$ldap_search_people_attr = array("gecos");

	$ds = @ldap_connect ( $ldap_server, $ldap_port );
	if ( $ds ) {
    		$r = @ldap_bind ( $ds ); // Bind anonyme
    		if ($r) {
      			// Recherche dans la branche people
      			$result = @ldap_search ( $ds, $dn["people"], $filter, $ldap_search_people_attr );
      			if ($result) {
        			$info = @ldap_get_entries ( $ds, $result );
        			if ( $info["count"]) {
          				for ($loop=0; $loop<$info["count"];$loop++) {
         					$gecos = $info[0]["gecos"][0];
         					$tmp = preg_split ("/[\,\]/",$info[0]["gecos"][0],4);
         					$date_naiss=$tmp[1];
         					echo gettext("Vous avez choisi de r&#233;initiliser le mot de passe &#224; la date de naissance")."<br><br>";
        					// echo $date_naiss;
        		 			userChangedPwd($uid_init, $date_naiss);
           				}
        			}
        			
				@ldap_free_result ( $result );
      			} else {
        			$error = gettext("Erreur de lecture dans l'annuaire LDAP");
      			}

    		} else {
      			$error = gettext("Echec du bind anonyme");
    		}
    		@ldap_close ( $ds );
  	} else {
    		$error = gettext("Erreur de connection au serveur LDAP");
  	}
}

include("pdp.inc.php");
?>

