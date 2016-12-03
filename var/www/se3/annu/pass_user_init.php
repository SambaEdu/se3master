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
	
	if (!isset($_SESSION['comptes_crees'])) {
		$_SESSION['comptes_crees'] = array(array())  ;  // un sous-tableau par compte ; le deuxième tableau est, dans l'ordre nom, prenom, classe (?? en fait, non) (ou 'prof'), uid, password
		array_splice($_SESSION['comptes_crees'], 0, 1);
	}

	echo "<h1>".gettext("Annuaire")."</h1>\n";

	$uid_init=$_GET['uid'];

	// Recherche d'utilisateurs dans la branche people
	$filter="(uid=$uid_init)";
	$ldap_search_people_attr = array("gecos","givenName","sn");

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

						$prenom = $info[0]["givenname"][0];
						$nom = $info[0]["sn"][0];
						$tmp = preg_split ("/,/",$info[0]["gecos"][0],4);
						$date_naiss=$tmp[1];

						echo "<a href='people.php?uid=$uid_init' title=\"Retour à la fiche de l'utilisateur $nom $prenom.\">$nom $prenom</a>&nbsp;: ";

						switch ($pwdPolicy) {
							case 0:		// date de naissance
								$userpwd=$date_naiss;
								echo gettext("Mot de passe r&#233;initialis&#233; &#224; la date de naissance : ");
								break;
							case 1:		// semi-aleatoire
								exec("/usr/share/se3/sbin/gen_pwd.sh -s", $out);
								$userpwd=$out[0];
								echo gettext("Mot de passe r&#233;initialis&#233; &#224; : ");
								break;
							case 2:		// aleatoire
								exec("/usr/share/se3/sbin/gen_pwd.sh -a", $out);
								$userpwd=$out[0];
								break;
								echo gettext("Mot de passe r&#233;initialis&#233; &#224; : ");
						}

						echo $userpwd."<br><br>";
						userChangedPwd($uid_init, $userpwd);

						// ajouter vérification de doublon en cas de modifs successives pour un même uid.
						$doublon = false;
						foreach($_SESSION['comptes_crees'] as &$key) {
							if ($key['uid'] == $uid_init){  // doublon : mise à jour pwd
								$doublon = true;
								$key['pwd'] = $userpwd;
								break;
							}
						}
						if (!$doublon) {
							$nouveau = array('nom'=>"$nom", 'pre'=>"$prenom", 'uid'=>"$uid_init", 'pwd'=>"$userpwd");
							$_SESSION['comptes_crees'][]=$nouveau;
						}
						$doublon = false;

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

	include("listing.inc.php");

}

include("pdp.inc.php");
?>

