<?php


   /**
   * Fonctions LDAP

   * @Version $Id$

   * @Projet LCS / SambaEdu

   * @Auteurs Equipe Tice academie de Caen
   * @Auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @Auteurs oluve olivier.le_monnier@crdp.ac-caen.fr

   * @Note: Ce fichier de fonction doit etre appele par un include

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: ldap.inc.php
   * @Repertoire: includes/
   */



  require_once ("lang.inc.php");
  bindtextdomain('se3-core',"/var/www/se3/locale");
  textdomain ('se3-core');

// Pour activer/désactiver la modification du givenName (Prenom) lors de la modification dans annu/mod_user_entry.php
$corriger_givenname_si_diff="n";

function cmp_fullname ($a, $b) {

	/**

	* Fonctions de comparaison utilisees dans la fonction usort, pour trier le fullname

	* @Parametres $a - La premiere entree 	$b - La deuxieme entree a comparer

	* @Return < 0 - Si $a est plus petit a $b  > 0 - Si $a est plus grand que $b
	*/


    return strcmp($a["fullname"], $b["fullname"]);
}

function cmp_name ($a, $b) {


	/**

	* Fonctions de comparaison utilisees dans la fonction usort, pour trier le name

	* @Parametres $a - La premiere entree 	$b - La deuxieme entree a comparer

	* @Return < 0 - Si $a est plus petit a $b  > 0 - Si $a est plus grand que $b

	*/

    return strcmp($a["name"], $b["name"]);
}

function cmp_cn ($a, $b) {

	/**

	* Fonctions de comparaison utilisees dans la fonction usort, pour trier le cn (common name)

	* @Parametres  $a - La premiere entree  $b - La deuxieme entree a comparer

	* @Return  < 0 - Si $a est plus petit a $b   > 0 - Si $a est plus grand que $b

	*/

        return strcmp($a["cn"], $b["cn"]);
}

function cmp_group ($a, $b) {

	/**

	* Fonctions de comparaison utilisees dans la fonction usort, pour trier les groupes

	* @Parametres  $a - La premiere entree 	$b - La deuxieme entree a comparer
	* @Return 	< 0 - Si $a est plus petit a $b  > 0 - Si $a est plus grand que $b

	*/
        return strcmp($a["group"], $b["group"]);
}

function cmp_cat ($a, $b) {


	/**

	* Fonctions de comparaison utilisees dans la fonction usort, pour trier les categories

	* @Parametres  $a - La premiere entree  $b - La deuxieme entree a comparer
	* @Return 	< 0 - Si $a est plus petit a $b  > 0 - Si $a est plus grand que $b

	*/
            return strcmp($a["cat"], $b["cat"]);
}

function cmp_printer ($a, $b) {

        /**
        * Fonctions de comparaison utilisees dans la fonction usort, pour trier le printer-name, insensible a la case
        * @Parametres  $a - La premiere entree  $b - La deuxieme entree a comparer
        * @Return  < 0 - Si $a est plus petit a $b   > 0 - Si $a est plus grand que $b
        */

        return strcasecmp($a["printer-name"], $b["printer-name"]);
}


function cmp_location ($a, $b) {

        /**
        * Fonctions de comparaison utilisees dans la fonction usort, pour trier le printer-location, insensible a la case
        * @Parametres  $a - La premiere entree  $b - La deuxieme entree a comparer
        * @Return  < 0 - Si $a est plus petit a $b   > 0 - Si $a est plus grand que $b
        */

        return strcasecmp($a["printer-location"], $b["printer-location"]);
}

function extract_login ($dn) {

	/**

	* Retourne un login a partir d'un dn

	* @Parametres $dn - Il est donne sous la forme uid=$login,ou=People,$base_dn
	* @Return 	Le login de l'utilisateur ($login)

	*/

	$login = preg_split ("/[\,\]/",$dn,4);
  	$login = preg_split ("/[\=\]/",$login[0],2);
  	return $login[1];
}


function getprenom($fullname,$name) {

	/**
	* Extrait le prenom depuis le nom complet et le nom

	* @Parametres $fullname - Il est donne sous la forme Prenom Nom
	* @Parametres $name - Le nom de famille de l'utilisateur

	* @Return 	Le prenom de l'utilisateur

	*/

	$expl=explode(" ","$fullname");
    	$namexpl=explode(" ",$name);
    	$j=0;
    	$prenom="";
    	for ($i=0; $i<count($expl); $i++) {
        	if (strtolower($expl[$i])!=strtolower($namexpl[$j]))  {
             		if ("$prenom" == "") $prenom=$expl[$i];
         		else $prenom.=" ".$expl[$i];
        	} else $j++;
    	}

	return $prenom;
}



function duree ($t0,$t1) {

	/**
	* Calcule la duree entre t0 et t1

	* @Parametres $t0 - Il est donne sous en seconde
	* @Parametres $t1 - Il est donne sous en seconde

	* @Return 	La duree en seconde

	*/

	$result0 = preg_split ("/[\ \!\?]/", $t0, 2);
  	$t0ms = $result0[0];
  	$t0s  = $result0[1];
  	$result1 = preg_split ("/[\ \!\?]/", $t1, 2);
  	$t1ms = $result1[0];
  	$t1s  = $result1[1];
  	$tini= ( $t0s +  $t0ms );
  	$tfin= ( $t1s +  $t1ms );
  	$temps = ( $tfin - $tini );
  	return ($temps);
}


function people_get_variables ($uid, $mode) {


	/**
	* Retourne un tableau avec les variables d'un utilisateur (a partir de l'annuaire LDAP)

        * @Parametres $uid - L'uid de l'utilisateur
	* @Parametres $mode : - true => recherche  - de l'ensemble des parametres utilisateur - des groupes d'appartenance - false => recherche  - de quelques parametres utilisateur


	* @Return  Un tableau contenant les informations sur l'utilisateur (uid)

	*/

	global $ldap_server, $ldap_port, $dn;
  	global $error;
  	$error="";

  	// LDAP attribute
  	$ldap_people_attr = array(
    		"uid",                      // login
    		"cn",                   // Prenom Nom
    		"sn",                       // Nom
    		"givenname",          // Pseudo -> Prenom
    		"mail",                 // Mail
    		"telephonenumber",// Num telephone
    		"homedirectory",    // Home directory personnal web space
    		"description",
    		"loginshell",
    		"gecos",             // Prenom Nom (cn sans accents ),Date de naissance,Sexe (F/M),Status administrateur Se3/Lcs (Y/N obsolete
  			"employeeNumber",
			"initials"             // pseudo
	);


  	$ldap_group_attr = array (
    		"cn",
    		//"member",      // Membres du Group Profs
    		"owner",
    		"description",  // Description de l'equipe
    		"member"
  	);

  	$ds = @ldap_connect ( $ldap_server, $ldap_port );
  	if ( $ds ) {
    		$r = @ldap_bind ( $ds ); // Bind anonyme
    		if ($r) {
      			$result = @ldap_read ( $ds, "uid=".$uid.",".$dn["people"], "(objectclass=posixAccount)", $ldap_people_attr );
      			if ($result) {
        			$info = @ldap_get_entries ( $ds, $result );
        			if ( $info["count"]) {

          				// Traitement du champ gecos pour extraction de date de naissance, sexe, isAdmin
          				$gecos = $info[0]["gecos"][0];
          				$tmp = preg_split ("/[\,\]/",$info[0]["gecos"][0],4);
          				$ret_people = array (
              					"uid"           => $info[0]["uid"][0],
              					"nom"           => stripslashes(utf8_decode($info[0]["sn"][0])),
              					"fullname"      => stripslashes(utf8_decode($info[0]["cn"][0])),
              					"prenom"        => utf8_decode($info[0]["givenname"][0]),
              					"pseudo"        => utf8_decode($info[0]["initials"][0]),
              					"gecos"        => utf8_decode($info[0]["gecos"][0]),
              					"email"         => $info[0]["mail"][0],
              					"tel"           => $info[0]["telephonenumber"][0],
              					"homedirectory" => $info[0]["homedirectory"][0],
              					"description"   => utf8_decode($info[0]["description"][0]),
              					"shell"         => $info[0]["loginshell"][0],
              					"sexe"          => $tmp[2],
              					"admin"         => $tmp[3],
						"employeeNumber" => $info[0]["employeenumber"][0]
            				);
        			}

				@ldap_free_result ( $result );
      			}

			if ($mode) {
        			// Recherche des groupes d'appartenance dans la branche Groups
        			$filter = "(|(&(objectclass=groupOfNames)(member= uid=$uid,".$dn["people"]."))(&(objectclass=groupOfNames)(owner= uid=$uid,".$dn["people"]."))(&(objectclass=posixGroup)(memberuid=$uid)))";
        			$result = @ldap_list ( $ds, $dn["groups"], $filter, $ldap_group_attr );
        			if ($result) {
          				$info = @ldap_get_entries ( $ds, $result );
          				if ( $info["count"]) {
            					for ($loop=0; $loop<$info["count"];$loop++) {
              						if ($info[$loop]["member"][0] == "") $typegr="posixGroup"; else $typegr="groupOfNames";
              						$ret_group[$loop] = array (
                						"cn"           => $info[$loop]["cn"][0],
                						"owner"        => $info[$loop]["owner"][0],
                						"description"  => utf8_decode($info[$loop]["description"][0]),
                						"type" => $typegr
              						);
            					}

						usort($ret_group, "cmp_cn");
          				}

					@ldap_free_result ( $result );
        			}
      			} // Fin recherche des groupes
    		} else {
      			$error = gettext("Echec du bind anonyme");
    		}

		@ldap_close ( $ds );
  	} else {
    		$error = gettext("Erreur de connection au serveur LDAP");
  	}

  	return array($ret_people, $ret_group);
}



function search_people ($filter) {

	/**
	* Recherche d'utilisateurs dans la branche people

        * @Parametres $filter - Un filtre de recherche permettant l'extraction de l'annuaire des utilisateurs
	* @Return  Un tableau contenant les utilisateurs repondant au filtre de recherche ($filter)

	*/

	global $ldap_server, $ldap_port, $dn;
  	global $error;
  	$error="";

  	//LDAP attributes
  	$ldap_search_people_attr = array(
    		"uid",   // login
    		"cn",    // Nom complet
    		"sn"     // Nom
  	);

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
            					$ret[$loop] = array (
        						"uid"       => $info[$loop]["uid"][0],
        						"fullname"  => utf8_decode($info[$loop]["cn"][0]),
        						"name"  => utf8_decode($info[$loop]["sn"][0]),
            					);
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

	// Tri du tableau par ordre alphabetique
  	if (count($ret)) usort($ret, "cmp_name");
  	return $ret;
}



function search_uids ($filter) {


	/**
	* Recherche des uids dans des classes et equipes repondant au critere $filter  dans la branche Groups (posix)

        * @Parametres $filter - Un filtre de recherche permettant l'extraction de l'annuaire des utilisateurs
	* @Return  Un tableau contenant les utilisateurs repondant au filtre de recherche ($filter)

	*/

	global $ldap_server, $ldap_port, $dn, $ldap_classe_attr, $ldap_equipe_attr;
	global $error;
	$error="";

  	// LDAP attributs
  	$ldap_classe_attr = array (
    		"cn",
    		"memberuid" // Membres du groupe Classe
  	);

  	$ldap_equipe_attr = array (
    		"cn",
    		"member",   // Membres du groupe Profs
    		"owner"     // proprietaire du groupe
  	);

  	// echo "filtre : $filter";
  	$ds = @ldap_connect ( $ldap_server, $ldap_port );
  	if ( $ds ) {
    		$r = @ldap_bind ( $ds ); // Bind anonyme
    		if ($r) {
      			// if ((preg_match("/Matiere/",$filter,$matche) && preg_match("/Equipe/",$filter,$matche))||preg_match("/Classe/",$filter,$matche)) {
      				// Debug
      				//echo "filtre 1 memberuid : $filter<BR>";

      				// Recherche dans la branche Groups Classe_ et Cours_
      				$result=@ldap_list ($ds, $dn["groups"], $filter, $ldap_classe_attr);
      				if ($result) {
        				$info = @ldap_get_entries( $ds, $result );
        				if ($info["count"]) {
          					// Stockage des logins des membres des classes
          					//  dans le tableau $ret
          					$init=0;
          					for ($loop=0; $loop < $info["count"]; $loop++) {
            						$group=preg_split ("/[\_\]/",$info[$loop]["cn"][0],2);
            						for ( $i = 0; $i < $info[$loop]["memberuid"]["count"]; $i++ ) {
              							// Ajout de wawa : test si le gus est prof
              							$filtre1 = "(memberUid=".$info[$loop]["memberuid"][$i].")";
              							$result1=@ldap_read($ds,"cn=Profs,".$dn["groups"],$filtre1);
              							$ret[$init]["prof"]=@ldap_count_entries($ds,$result1);
              							@ldap_free_result ( $result1 );
              							// fin patch a wawa
              							$ret[$init]["uid"] = $info[$loop]["memberuid"][$i];
              							$ret[$init]["group"] = $group[1];
              							$ret[$init]["cat"] = $group[0];
              							$init++;
            						}
          					}

        				}

					ldap_free_result ( $result );
      				}
      			// }

			// Passage en posix plus lieu d'etre
			// if (preg_match("/Classe/",$filter,$matche)||preg_match("/Matiere/",$filter,$matche)||preg_match("/Equipe/",$filter,$matche)) {
        		// Modifie par Wawa: filter2 supprime
        		// $filter2 = preg_replace("/Classe_/","Equipe_",$filter);
        		// Debug
        		// echo "filtre 2 member : $filter2<BR>";
     			/*   $result=@ldap_list ($ds, $dn["groups"], $filter, $ldap_equipe_attr);
        		if ($result) {
          			$info = @ldap_get_entries( $ds, $result );
          			if ($info["count"]) {
            				$init=count($ret);
            				$owner = extract_login ($info[0]["owner"][0]);
            				for ($loop=0; $loop < $info["count"]; $loop++) {
              					$group=preg_split ("/[\_\]/",$info[$loop]["cn"][0],2);
              					for ( $i = 0; $i < $info[$loop]["member"]["count"]; $i++ ) {
              						// Cas ou un champ member est non vide
                					if ( extract_login ($info[$loop]["member"][$i])!="") {
                						$ret[$init]["uid"] = extract_login ($info[$loop]["member"][$i]);
                    						if ($owner == extract_login ($info[$loop]["member"][$i])) $ret[$init]["owner"] = true;
                    						$ret[$init]["group"] = $group[1];
                    						$ret[$init]["cat"] = $group[0];
                    						$init++;
                					}
              					}
            				}
          			}
          			@ldap_free_result ( $result );
        		}

      		} */

	} else {
      		$error = gettext("Echec du bind anonyme");
    	}

	@ldap_close ( $ds );
} else {
	$error = gettext("Erreur de connection au serveur LDAP");
}
  //$ret = doublon ($ret);
  return $ret;
}



function search_groups ($filter) {

	/**
	* Recherche une liste de groupes repondants aux criteres fixes par la variable $filter. Les filtres sont les memes que pour ldapsearch.
	* Par exemple (&(uidMember=wawa)(uidMember=toto)) recherche le groupe contenant les utilisateurs wawa et toto.

        * @Parametres $filter - Un filtre de recherche permettant l'extraction de l'annuaire des utilisateurs


	* @Return 	Retourne un tableau $groups avec le cn et la description de chaque groupe
	*/


	global $ldap_server, $ldap_port, $dn;
  	global $error;
  	// LDAP attributs
  	$ldap_group_attr = array (
    		"objectclass",
    		"cn",
    		"memberUid",
    		"gidnumber",
    		"description"  // Description du groupe
  	);

	$ds = @ldap_connect ( $ldap_server, $ldap_port );
  	if ( $ds ) {
    		$r = @ldap_bind ( $ds ); // Bind anonyme
    		if ($r) {
      			$result = @ldap_list ( $ds, $dn["groups"], $filter, $ldap_group_attr );

      			if ($result) {
        			$info = @ldap_get_entries ( $ds, $result );
        			if ( $info["count"]) {
          				for ($loop=0; $loop < $info["count"]; $loop++) {
            					$groups[$loop]["cn"] = $info[$loop]["cn"][0];
             					$groups[$loop]["gidnumber"] = $info[$loop]["gidnumber"][0];
            					$groups[$loop]["description"] = utf8_decode($info[$loop]["description"][0]);
             					// Recherche de posixGroup ou groupOfNames
            					for ($i=0; $i < $info[$loop]["objectclass"]["count"]; $i++) {
              						if  ($info[$loop]["objectclass"][$i] != "top") $type =  $info[$loop]["objectclass"][$i];
            					}
            					$groups[$loop]["type"] =  $type;
          				}
        			}

				@ldap_free_result ( $result );
      			}
    		}

		@ldap_close($ds);
  	}

	if (count($groups)) usort($groups, "cmp_cn");

  	return $groups;
}




function search_people_groups ($uids,$filter,$order) {


	/**
	* Recherche des utilisateurs dans la branche people a partir d'un tableau d'uids nons tries


	* @Parametres $order - "cat"   => Tri par categorie (Eleves, Equipe...)
        * @Parametres       - "group" => Tri par intitule de group (ex: 1GEA, TGEA...)
	* @Parametres $uids - Tableau d'uids d'utilisateurs
	* @Parametres $filter - Filtre de recherche

	* @Return 	Retourne un tableau des utilisateurs repondant au filtre de recherche
	*/

	global $ldap_server, $ldap_port, $dn;
  	global $error;
  	$error="";

  	// LDAP attributs
  	$ldap_user_attr = array(
    		"cn",                 // Nom complet
    		"sn",                 // Nom
    		"gecos",            // Nom prenom (cn sans accents), Date de naissance,Sexe (F/M),Status administrateur LCS (Y/N)
		"employeeNumber"
  	);

  	if (!$filter) $filter="(sn=*)";
  	$ds = @ldap_connect ( $ldap_server, $ldap_port );
  	if ( $ds ) {
    		$r = @ldap_bind ( $ds ); // Bind anonyme
    		if ($r) {
      			$loop1=0;
      			for ($loop=0; $loop < count($uids); $loop++) {
        			$result = @ldap_read ( $ds, "uid=".$uids[$loop]["uid"].",".$dn["people"], $filter, $ldap_user_attr );
        			if ($result) {
          				$info = @ldap_get_entries ( $ds, $result );
          				if ( $info["count"]) {
            					// traitement du gecos pour identification du sexe
            					$gecos = $info[0]["gecos"][0];
            					$tmp = preg_split ("/[\,\]/",$gecos,4);
            					#echo "debug ".$info["count"]." init ".$init." loop ".$loop."<BR>";
            					$ret[$loop1] = array (
                					"uid"           => $uids[$loop]["uid"],
                					"fullname"      => utf8_decode($info[0]["cn"][0]),
                					"name"          => utf8_decode($info[0]["sn"][0]),
                					"sexe"          => $tmp[2],
                					"owner"         => $uids[$loop]["owner"],
                					"group"         => $uids[$loop]["group"],
                					"cat"           => $uids[$loop]["cat"],
                					"gecos"         => $gecos,
                					"prof"          => $uids[$loop]["prof"],
							"employeeNumber" => $info[0]["employeenumber"][0]
            					);

						$loop1++;
          				}

					@ldap_free_result ( $result );
        			}
      			}
    		} else {
      			$error = gettext("Echec du bind anonyme");
    		}

		@ldap_close ( $ds );
  	} else $error = gettext("Erreur de connection au serveur LDAP");

  	if (count($ret)) {

    		# Correction tri du tableau
    		# Tri par critere categorie ou intitule de groupe
    		if ( $order == "cat" ) usort ($ret, "cmp_cat");
      		elseif ( $order == "group" ) usort ($ret, "cmp_group");
    		# Recherche du nombre de categories ou d'intitules de groupe
    		$i = 0;
    		for ( $loop=0; $loop < count($ret); $loop++) {
			if ( $ret[$loop][$order] != $ret[$loop-1][$order]) {
	    			$tab_order[$i] = $ret[$loop][$order];
	    			$i++;
			}
    		}

		if (count($tab_order) > 0 ) {
			$ret_final = array();
			# On decoupe le tableau $ret en autant de sous tableaux $tmp que de criteres $order
			for ($i=0; $i < count($tab_order); $i++) {
	    			$j=0;
	    			for ( $loop=0; $loop < count($ret); $loop++) {
					if ( $ret[$loop][$order] == $tab_order[$i] ) {
		    				$ret_tmp[$i][$j] = $ret[$loop];
		    				$j++;
					}
	    			}
			}

			# Tri alpabetique des sous tableaux
			for ( $loop=0; $loop < count($ret_tmp); $loop++) usort ($ret_tmp[$loop], "cmp_name");

			# Reassemblage des tableaux temporaires
			for ($loop=0; $loop < count($tab_order); $loop++)  $ret_final = array_merge ($ret_final, $ret_tmp[$loop]);
			return $ret_final;
    		} else {
			usort ($ret, "cmp_name");
			return $ret;
    		}
  	}
}


// Recherche de machines
function search_computers ($filter) {

	/**
	* Recherche de machines dans ou computers

        * @Parametres $filter - Un filtre de recherche permettant l'extraction de l'annuaire des machines
	* @Return Retourne un tableau avec les machines
	*/

	return search_machines($filter,"computers");
}



function search_machines ($filter,$branch) {

	/**
	* Recherche de machines dans l'ou $branch

        * @Parametres $filter - Un filtre de recherche permettant l'extraction de l'annuaire des machines
	* @Parametres $branch - L'ou correspondant a l'ou contenant les machines

	* @Return 	Retourne un tableau avec les machines
	*/

  	global $ldap_server, $ldap_port, $dn;
  	global $error;

  	// LDAP attributs
  	if ("$branch"=="computers")
    		$ldap_computer_attr = array (
    			"cn",
    			"ipHostNumber",   // ip Host
    			"l",                        // Status de la machine
    			"description"        // Description de la machine
    		);
  	else
    		$ldap_computer_attr = array (
    			"cn"
    		);

  	$ds = @ldap_connect ( $ldap_server, $ldap_port );
  	if ( $ds ) {
    		$r = @ldap_bind ( $ds ); // Bind anonyme
    		if ($r) {
      			$result = @ldap_list ( $ds, $dn[$branch], $filter, $ldap_computer_attr );
      			if ($result) {
        			$info = @ldap_get_entries ( $ds, $result );
        			if ( $info["count"]) {
          				for ($loop=0; $loop < $info["count"]; $loop++) {
            					$computers[$loop]["cn"] = $info[$loop]["cn"][0];
            					if ("$branch"=="computers") {
                					$computers[$loop]["ipHostNumber"] = $info[$loop]["iphostnumber"][0];
                					$computers[$loop]["l"] = $info[$loop]["l"][0];
                					$computers[$loop]["description"] = utf8_decode($info[$loop]["description"][0]);
            					}
          				}
        			}

				@ldap_free_result ( $result );
      			}
    		}

    		@ldap_close($ds);
  	}

	return $computers;
}


function search_parcs ($machine) {


	/**
	* Recherche les parcs ou se trouve la  machine $machine

	* @Parametres $machine - Le nom de la machine dont on cherche les parcs
	* @Return Retourne un tableau avec les parcs contenant la machine
	*/

  	global $ldap_server, $ldap_port, $dn;
  	global $error;

    	$ldap_computer_attr = array (
    		"cn"
    	);

    	$filter = "(&(objectclass=groupOfNames)(member=cn=$machine,".$dn["computers"]."))";
  	$ds = @ldap_connect ( $ldap_server, $ldap_port );
  	if ( $ds ) {
    		$r = @ldap_bind ( $ds ); // Bind anonyme
    		if ($r) {
      			$result = @ldap_list ( $ds, $dn["parcs"], $filter, $ldap_computer_attr );
      			if ($result) {
        			$info = @ldap_get_entries ( $ds, $result );
        			if ( $info["count"]) {
          				for ($loop=0; $loop < $info["count"]; $loop++) {
            					$computers[$loop]["cn"] = $info[$loop]["cn"][0];
          				}
        			}

				@ldap_free_result ( $result );
      			}
    		}

    		@ldap_close($ds);
  	}

	return $computers;
}


function gof_members ($gof,$branch,$extract) {

	/**
	* Liste les membres du groupOfNames $gof

	* @Parametres $gof - $gof est le GroupOfNames dans lequel on recherche un objet
	* @Parametres $branche - L'ou ou se fait la recherche
	* @Parametres $extract - Peut prendre la valeur 1 pour n'extraire qu'un membre.

	* @Return  Retourne un tableau avec les membres du GroupOfNames repondant a la recherche
	*/

  	global $ldap_server, $ldap_port, $dn;
  	global $error;
  	$error="";

  	// LDAP attributs
  	$members_attr = array (
      		"member"   // Membres du groupe Profs
  	);
  	$ds = @ldap_connect ( $ldap_server, $ldap_port );
  	if ( $ds ) {
    		$r = @ldap_bind ( $ds ); // Bind anonyme
    		if ($r) {
        		$result=@ldap_read ($ds, "cn=$gof,".$dn[$branch], "cn=*", $members_attr);
        		if ($result) {
          			$info = @ldap_get_entries( $ds, $result );
          			if ($info["count"]==1) {
            				$init=0;
             				for ($loop=0; $loop < $info[0]["member"]["count"]; $loop++) {
                				if ($extract==1) $ret[$loop]=extract_login($info[0]["member"][$loop]);
                				else $ret[$loop]=$info[0]["member"][$loop];
                			}
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
	return $ret;
}




function tstclass($prof,$eleve) {

	/**
	* test si $eleve est dans la classe de $prof

	* @note Les tests sont effectues sur l'appartenance a au moins un groupe dans l'ou Groups. Normalement le prof et l'eleve sont tous les deux dans le groupe Cours_
	* @note On teste d'abord si le prof est bien dans l'equipe dela classe de l'eleve
	
	* @Parametres $prof - Le nom du prof suppose etre prof de $eleve
	* @Parametres $eleve - L'eleve de $prof a tester.

	* @Return 	Retourne 1 si on a une reponse positive

	*/


  	$filtre= "(&(memberUid=$eleve)(cn=Classe_*))";
  	$classe=search_groups($filtre);
	if (count($classe)==1) {
	        $equipe=preg_replace("/Classe_/","Equipe_",$classe[0]["cn"]);       
	  	$filtre= "(&(memberUid=$prof)(cn=$equipe))";
		$res=search_groups($filtre);
		if (count($res)==1) {
			$tstclass=1;
		}
	} else {	 
	  	$filtre= "(&(memberUid=$prof)(memberUid=$eleve))";
	  	$grcomm=search_groups($filtre);
	  	$tstclass=0;
	  	if (count($grcomm)>0) {
	  	      	$i=0;
	  	      	while (($i< count($grcomm)) and ($tstclass==0)) {
	  	              if (preg_match("/Cours/",$grcomm[$i]["cn"],$matche)) $tstclass=1;
	  		      $i++;
			}
		}
  	}

	return $tstclass;
}


function verifGroup($login){

	/**
	* test si $login est dans le groupe Profs

	* @Parametres $login - Le login de l'utilisateur a tester

	* @Return 	Retourne True si on a une reponse positive

	*/

	$group=FALSE;
    	// verification de l'utilisateur, eleve ou prof
    	list($user, $groups)=people_get_variables($login, true);
    	for ($loop=0; $loop < count ($groups) ; $loop++) {
        	if($groups[$loop]["cn"]=="Profs") {
           		$group=TRUE;
        	}
    	}

    	return $group;
}


function userChangedPwd($uid, $userpwd) {


	/**
	* Change le mot de passe d'un utilisateur

	* @Parametres $uid - Le login de la personne
	* @Parametres $userpwd - Le mot de passe de la personne

	* @Return 	Retourne un affichage HTML

	*/

 	exec ("/usr/share/se3/sbin/userChangePwd.pl '$uid' '$userpwd'",$AllOutPut,$ReturnValue);
  	if ($ReturnValue == "0") {
    		echo "<strong>".gettext("Le mot de passe a &#233;t&#233; modifi&#233; avec succ&#232;s.")."</strong><br>\n";
  	} else {
    		echo "<div class='error_msg'>".gettext("Echec de la modification")." <font color='black'>(".gettext("type d'erreur")." : $ReturnValue)</font>, ".gettext("veuillez contacter")." <A HREF='mailto:$MelAdminLCS?subject=".gettext("PB changement mot de passe")."'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
  	}
}


function userDesactive($uid,$act) {

	/**
	* Active ou desactive le compte d'un utilisateur

	* @Parametres $uid - Le login de la personne
	* @Parametres $act - Ce qui doit etre fait

	* @Return 	Retourne un affichage HTML

	*/

	exec ("/usr/share/se3/sbin/userdesactive.pl '$uid' '$act'",$AllOutPut,$ReturnValue);
  	if ($ReturnValue == "0") {
  		if ($act) {
			echo "<strong>".gettext("Le compte a &#233;t&#233; activ&#233; avec succ&#232;s.")."</strong><br>\n";
		} else {
			echo "<strong>".gettext("Le compte a &#233;t&#233; d&#233;sactiv&#233; avec succ&#232;s.")."</strong><br>\n";
		}
  	} else {
    		echo "<div class='error_msg'>".gettext("Echec de la modification")." <font color='black'>(".gettext("type d'erreur")." : $ReturnValue)</font>, ".gettext("veuillez contacter")." <A HREF='mailto:$MelAdminLCS?subject=".gettext("PB changement mot de passe")."'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
  	}
}


function are_you_in_group ($login, $group) {

	/**
	* Test si $login se trouve dans le groupe $group (de la branche Groups)

	* @Parametres $login - L'uid de l'utilisateur que l'on veut tester
	* @Parametres $group - Le groupe dans lequel l'utilisateur doit se trouver

	* @Return 	true - Si la personne est dans le groupe  false - Si elle n'est pas dans le groupe
	*/

	$filtre = "(&(memberUid=$login)(cn=$group))";
	$grcomm=search_groups($filtre);
	if (count($grcomm)>0) { return true; } else { return false; }
}


function search_description_parc ($parc) {


	/**
	* Recherche la machine prof d'un parc (champ description) 

	* @Parametres $parc - Le nom du parc
	* @Return Retourne le nom de la machine prof du parc $parc
	*/

  	global $ldap_server, $ldap_port, $dn;
  	global $error;

    	$ldap_computer_attr = array (
    		"description"
    	);

    	$filter = "(&(cn=$parc)(description=*))";
	$ds = @ldap_connect ( $ldap_server, $ldap_port );
  	if ( $ds ) {
    		$r = @ldap_bind ( $ds ); // Bind anonyme
    		if ($r) {
      			$result = ldap_list ( $ds, $dn["parcs"], $filter, $ldap_computer_attr );
	if ($result) {
        		$info = @ldap_get_entries ( $ds, $result );
        			if ( $info["count"]) {
					return $info[0]["description"][0];

        			} else {
					return false;
				}	

				@ldap_free_result ( $result );
      			}
    		}

    		@ldap_close($ds);
  	}

}


function modif_description_parc ($parc,$entree) {


	/**
	* Modifie le champ description de parc 

	* @Parametres $parc - Le nom du parc
	* @Parametres $entree - La valeur a rentrer, si entree vide on vide le champ
	* @Return Retourne 1 ou 0 si pas d'erreur
	*/

  	global $ldap_server, $ldap_port, $dn;
  	global $error;


	$ds = @ldap_connect ( $ldap_server, $ldap_port );
  	if ( $ds ) {
		$adminLdap=get_infos_admin_ldap2();
	        $r=@ldap_bind($ds,$adminLdap["adminDn"],$adminLdap["adminPw"]); // Bind admin LDAP

    		// $r = @ldap_bind ( $ds ); // Bind anonyme
    		if ($r) {
			if($entree=="") { $entree="0"; }
			$parc_entree="cn=$parc,".$dn["parcs"];
			$mod_descript=array();
			$mod_descript["description"][0]=$entree;
			$result = ldap_modify ( $ds, $parc_entree, $mod_descript );
		}

    		@ldap_close($ds);
  	}

}


/**

* Retourne des infos sur l'admin ldap, pour une connexion authentifiee
* @Parametres
* @Return un tableau avec les donnees de l'admin ldap

*/

function get_infos_admin_ldap2(){
	//global $dn;
	global $ldap_base_dn;

	$adminLdap=array();

	// Etablir la connexion au serveur et la selection de la base?

	$sql="SELECT value FROM params WHERE name='adminRdn'";
	$res1=mysql_query($sql);
	if(mysql_num_rows($res1)==1){
		$lig_tmp=mysql_fetch_object($res1);
		$adminLdap["adminDn"]=$lig_tmp->value.",".$ldap_base_dn;
	}

	$sql="SELECT value FROM params WHERE name='adminPw'";
	$res2=mysql_query($sql);
	if(mysql_num_rows($res2)==1){
		$lig_tmp=mysql_fetch_object($res2);
		$adminLdap["adminPw"]=$lig_tmp->value;
	}

	return $adminLdap;
}




?>
