<?php


   /**
   * Fonctions pour l'import sconet

   * @Version $Id$

   * @Projet LCS / SambaEdu

   * @Auteurs Stephane Boireau

   * @Note

   * @Licence Distribue sous la licence GPL
   */

   /**

   * file: crob_ldap_functions.inc.php
   * @Repertoire: includes/
   */




//================================================

/**

* Fonction de generation de mot de passe recuperee sur TotallyPHP
* Aucune mention de licence pour ce script...

* @Parametres
* @Return 1 ou 0

* The letter l (lowercase L) and the number 1
* have been removed, as they can be mistaken
* for each other.
*/

function createRandomPassword($nb_chars) {
	$chars = "abcdefghijkmnopqrstuvwxyz023456789";
	srand((double)microtime()*1000000);
	$i = 0;
	$pass = '' ;

	//while ($i <= 7) {
	//while ($i <= 5) {
	while ($i <= $nb_chars) {
		$num = rand() % 33;
		$tmp = substr($chars, $num, 1);
		$pass = $pass . $tmp;
		$i++;
	}

	return $pass;
}
//================================================

/**

* Fonction qui retourne la date et l'heure

* @Parametres
* @Return jour/moi/annee heure:mn:seconde

*/

function date_et_heure() {
	$instant = getdate();
	$annee = $instant['year'];
	$mois = sprintf("%02d",$instant['mon']);
	$jour = sprintf("%02d",$instant['mday']);
	$heure = sprintf("%02d",$instant['hours']);
	$minute = sprintf("%02d",$instant['minutes']);
	$seconde = sprintf("%02d",$instant['seconds']);

	$retour="$jour/$mois/$annee $heure:$minute:$seconde";

	return $retour;
}


//================================================

/**

* Lit le fichier ssmtp et en retourne le contenu

* @Parametres
* @Return

*/

function lireSSMTP() {
	$chemin_ssmtp_conf="/etc/ssmtp/ssmtp.conf";

	$tabssmtp=array();

	if(file_exists($chemin_ssmtp_conf)) {
		$fich=fopen($chemin_ssmtp_conf,"r");
		if(!$fich){
			return false;
		}
		else{
			while(!feof($fich)){
				$ligne=fgets($fich,4096);
				if(strstr($ligne,"root=")){
					unset($tabtmp);
					$tabtmp=explode('=',$ligne);
					$tabssmtp["root"]=trim($tabtmp[1]);
				}
				elseif(strstr($ligne,"mailhub=")){
					unset($tabtmp);
					$tabtmp=explode('=',$ligne);
					$tabssmtp["mailhub"]=trim($tabtmp[1]);
				}
				elseif(strstr($ligne,"rewriteDomain=")){
					unset($tabtmp);
					$tabtmp=explode('=',$ligne);
					$tabssmtp["rewriteDomain"]=trim($tabtmp[1]);
				}
			}
			fclose($fich);

			return $tabssmtp;
		}
	}
	else {
		return false;
	}
}


//================================================

/**

* Affiche le texte ou le contenu d'un fichier
* @Parametres texte
* @Return

*/

function my_echo($texte){
	global $echo_file, $dest_mode;

	$destination=$dest_mode;

	if((!file_exists($echo_file))||($echo_file=="")){
		$destination="";
	}

	switch($destination){
		case "file":
			$fich=fopen($echo_file,"a+");
			fwrite($fich,"$texte");
			fclose($fich);
			break;
		default:
			echo "$texte";
			break;
	}
}


//================================================

/**

* remplace les accents
* @Parametres chaine a traiter
* @Return la chaine sans accents

*/

function remplace_accents($chaine){
	//$retour=strtr(preg_replace("/¼/","OE",preg_replace("/½/","oe",$chaine)),"ÀÄÂÉÈÊËÎÏÔÖÙÛÜÇçàäâéèêëîïôöùûü","AAAEEEEIIOOUUUCcaaaeeeeiioouuu");
	$retour=strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$chaine"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz");
	return $retour;
}


//================================================

/**

* Retourne des infos sur l'admin ldap
* @Parametres
* @Return

*/

function get_infos_admin_ldap(){
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


//================================================

/**

* test si l'ou trash existe sinon la cree
* @Parametres
* @Return

*/


function test_creation_trash(){
	global $ldap_server, $ldap_port, $dn, $ldap_base_dn;
	global $error;
	$error="";

	// Parametres
	// Aucun

	// Tableau retourne
	$tab=array();

	fich_debug("======================\n");
	fich_debug("test_creation_trash:\n");

	$ds=@ldap_connect($ldap_server,$ldap_port);
	if($ds){
		$r=@ldap_bind($ds);// Bind anonyme
		if($r){
			$attribut=array("ou","objectClass");

			// A REVOIR... LE TEST MERDOUILLE... IL A L'AIR DE RETOURNER vrai meme si ou=Trash n'existe pas

			$result=ldap_search($ds,$ldap_base_dn,"ou=Trash",$attribut);
			fich_debug("ldap_search($ds,\"$ldap_base_dn\",\"ou=Trash\",$attribut)\n");
			//echo "<p>ldap_search($ds,$dn[$branche],\"$filtre\",$attribut);</p>";
			if($result){
				fich_debug("La branche Trash existe.\n");
				@ldap_free_result($result);
			}
			else{
				fich_debug("La branche Trash n'existe pas.\n");

				// On va la creer.
				unset($attributs);
				$attributs=array();
				$attributs["ou"]="Trash";
				$attributs["objectClass"]="organizationalUnit";

				//$r=@ldap_bind($ds);// Bind anonyme
				$adminLdap=get_infos_admin_ldap();
				$r=@ldap_bind($ds,$adminLdap["adminDn"],$adminLdap["adminPw"]); // Bind admin LDAP
				if($r){
					$dn_entree="ou=Trash,".$ldap_base_dn;
					fich_debug("Cr&#233;ation de la branche: ");
					$result=ldap_add($ds,"$dn_entree",$attributs);
					if(!$result){
						$error="Echec d'ajout de l'entree ou=Trash";
						fich_debug("ECHEC\n");
						fich_debug("\$error=$error\n");
					}
					else{
						fich_debug("SUCCES\n");
					}
					@ldap_free_result($result);
				}
				else{
					$error=gettext("Echec du bind admin LDAP");
					fich_debug("\$error=$error\n");
				}
			}
		}
		else{
			$error=gettext("Echec du bind anonyme");
			fich_debug("\$error=$error\n");
		}
		@ldap_close($ds);
	}
	else{
		$error=gettext("Erreur de connection au serveur LDAP");
		fich_debug("\$error=$error\n");
	}

	if($error!=""){
		echo "error=$error<br />\n";
	}
}


//================================================

/**

* Ajoute une entree dans l'annuaire
* @Parametres
* @Return

*/


function add_entry ($entree, $branche, $attributs){
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";

	// Parametres:
	/*
		$entree: uid=toto
		$branche: people, groups,... ou rights
		$attributs: tableau associatif des attributs
	*/

	$ds=@ldap_connect($ldap_server,$ldap_port);
	if($ds){
		//$r=@ldap_bind($ds);// Bind anonyme
		$adminLdap=get_infos_admin_ldap();
		$r=@ldap_bind($ds,$adminLdap["adminDn"],$adminLdap["adminPw"]); // Bind admin LDAP
		if($r){
			$dn_entree="$entree,".$dn["$branche"];
			$result=ldap_add($ds,"$dn_entree",$attributs);
			if(!$result){
				$error="Echec d'ajout de l'entree $entree";
			}
			@ldap_free_result($result);
		}
		else{
			$error=gettext("Echec du bind admin LDAP");
		}
		@ldap_close($ds);
	}
	else{
		$error=gettext("Erreur de connection au serveur LDAP");
	}

	if($error==""){
		return true;
	}
	else{
		//echo "<p>$error</p>";
		return false;
	}
}


//================================================

/**

* Supprime une entree de l'annuaire
* @Parametres
* @Return

*/

function del_entry ($entree, $branche){
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";

	// Parametres:
	/*
		$entree: uid=toto
		$branche: people, groups,... ou rights
	*/

	$ds=@ldap_connect($ldap_server,$ldap_port);
	if($ds){
		//$r=@ldap_bind($ds);// Bind anonyme
		$adminLdap=get_infos_admin_ldap();
		$r=@ldap_bind($ds,$adminLdap["adminDn"],$adminLdap["adminPw"]); // Bind admin LDAP
		if($r){
			$result=ldap_delete($ds,"$entree,".$dn["$branche"]);
			if(!$result){
				$error="Echec de la suppression de l'entree $entree";
			}
			@ldap_free_result($result);
		}
		else{
			$error=gettext("Echec du bind admin LDAP");
		}
		@ldap_close($ds);
	}
	else{
		$error=gettext("Erreur de connection au serveur LDAP");
	}

	if($error==""){
		return true;
	}
	else{
		//echo "<p>$error</p>";
		return false;
	}
}



//================================================

/**

* Modifie une entree dans l'annuaire
* @Parametres
* @Return

*/

function modify_entry ($entree, $branche, $attributs){
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";

	// Je ne suis pas sur d'avoir bien saisi le fonctionnement de la fonction ldap_modify() de PHP
	// Du coup, je lui ai prefere les fonctions ldap_mod_add(), ldap_mod_del() et ldap_mod_replace() utilisees dans ma fonction modify_attribut()

	// Parametres:
	/*
		$entree: uid=toto
		$branche: people, groups,... ou rights
		$attributs: tableau associatif des attributs
	*/

	$ds=@ldap_connect($ldap_server,$ldap_port);
	if($ds){
		//$r=@ldap_bind($ds);// Bind anonyme
		$adminLdap=get_infos_admin_ldap();
		$r=@ldap_bind($ds,$adminLdap["adminDn"],$adminLdap["adminPw"]);// Bind admin LDAP
		if($r){
			$result=ldap_modify($ds,"$entree,".$dn["$branche"],$attributs);
			if(!$result){
				$error="Echec d'ajout de l'entree $entree";
			}
			@ldap_free_result($result);
		}
		else{
			$error=gettext("Echec du bind anonyme");
		}
		@ldap_close($ds);
	}
	else{
		$error=gettext("Erreur de connection au serveur LDAP");
	}

	if($error==""){
		return true;
	}
	else{
		return false;
	}
}


//================================================

/**

* Modifie un attribut dans l'annuaire
* @Parametres
* @Return

*/


function modify_attribut ($entree, $branche, $attributs, $mode){
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";

	// Parametres:
	/*
		$entree: uid=toto
		$branche: people, groups,... ou rights
		$attribut: tableau associatif des attributs a modifier
		$mode: add replace ou del

		// Pour del aussi, il faut fournir la bonne valeur de l'attribut pour que cela fonctionne
		// On peut ajouter, modifier, supprimer plusieurs attributs a la fois.
	*/

	$ds=@ldap_connect($ldap_server,$ldap_port);
	if($ds){
		//$r=@ldap_bind($ds);// Bind anonyme
		$adminLdap=get_infos_admin_ldap();
		$r=@ldap_bind($ds,$adminLdap["adminDn"],$adminLdap["adminPw"]);// Bind admin LDAP
		if($r){
			switch($mode){
				case "add":
					$result=ldap_mod_add($ds,"$entree,".$dn["$branche"],$attributs);
					break;
				case "del":
					$result=ldap_mod_del($ds,"$entree,".$dn["$branche"],$attributs);
					break;
				case "replace":
					$result=ldap_mod_replace($ds,"$entree,".$dn["$branche"],$attributs);
					break;
			}
			if(!$result){
				$error="Echec d'ajout de la modification $mode sur $entree";
			}
			@ldap_free_result($result);
		}
		else{
			$error=gettext("Echec du bind anonyme");
		}
		@ldap_close($ds);
	}
	else{
		$error=gettext("Erreur de connection au serveur LDAP");
	}

	if($error==""){
		return true;
	}
	else{
		return false;
	}
}


/*
function crob_init() {
	// Recuperation de variables dans la base MySQL se3db
	//global $domainsid,$uidPolicy;
        global $defaultgid,$domain,$defaultshell,$domainsid;

	$domainsid="";
	$sql="select value from params where name='domainsid';";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)==1){
		$lig_tmp=mysql_fetch_object($res);
		$domainsid=$lig_tmp->value;
	} else {
            // Cas d'un LCS ou sambaSID n'est pas dans la table params
            unset($retval);
            exec ("ldapsearch -x -LLL  objectClass=sambaDomain | grep sambaSID | cut -d ' ' -f 2",$retval);
            $domainsid = $retval[0];
            // Si il n'y a pas de sambaSID dans l'annuaire, on fixe une valeur factice
            // Il faudra appliquer un correct SID lors de l'installation d'un se3
            if (!isset($domainsid)) $domainsid ="S-0-0-00-0000000000-000000000-0000000000";
        }

	$uidPolicy="";
	$sql="select value from params where name='uidPolicy';";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)==1){
		$lig_tmp=mysql_fetch_object($res);
		$uidPolicy=$lig_tmp->value;
	}

	$defaultgid="";
	$sql="select value from params where name='defaultgid';";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)==1){
		$lig_tmp=mysql_fetch_object($res);
		$defaultgid=$lig_tmp->value;
	} else {
            // Cas d'un LCS ou defaultgid n'est pas dans la table params
            exec ("getent group lcs-users | cut -d ':' -f 3", $retval);
            $defaultgid= $retval[0];
        }

	$domain="";
	$sql="select value from params where name='domain';";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)==1){
		$lig_tmp=mysql_fetch_object($res);
		$domain=$lig_tmp->value;
	}

	$defaultshell="";
	$sql="select value from params where name='defaultshell';";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)==1){
		$lig_tmp=mysql_fetch_object($res);
		$defaultshell=$lig_tmp->value;
	}
}
*/


//================================================

/**

* Active le mode debug
* @Parametres
* @Return

*/

function fich_debug($texte){
	// Passer la variable ci-dessous a 1 pour activer l'ecriture d'infos de debuggage dans /tmp/debug_se3lcs.txt
	// Il conviendra aussi d'ajouter des appels fich_debug($texte) la ou vous en avez besoin;o).
	$debug=0;

	if($debug==1){
		$fich=fopen("/tmp/debug_se3lcs.txt","a+");
		fwrite($fich,$texte);
		fclose($fich);
	}
}

//================================================

/**

* Cree l'uid a partir du nom prenom et de la politique de login
* @Parametres
* @Return

*/

function creer_uid($nom,$prenom){
	global $uidPolicy;
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";

	fich_debug("======================\n");
	fich_debug("creer_uid:\n");
	fich_debug("\$nom=$nom\n");
	fich_debug("\$prenom=$prenom\n");

	fich_debug("\$uidPolicy=$uidPolicy\n");
	fich_debug("\$ldap_server=$ldap_server\n");
	fich_debug("\$ldap_port=$ldap_port\n");
	fich_debug("\$error=$error\n");
	fich_debug("\$dn=$dn\n");

/*
	# Il faudrait ameliorer la fonction pour gerer les "Le goff Martin" qui devraient donner "Le_goff-Martin"
	# Actuellement, on passe tous les espaces a _
*/

	// Recuperation de l'uidPolicy (et du sid)
	//crob_init(); Ne sert a rien !!!
	//echo "<p>\$uidPolicy=$uidPolicy</p>";

	// Filtrer certains caracteres:
	//nom=$(echo "$nom" | tr " àâäéèêëîïôöùûü" "-aaaeeeeiioouuu" | sed -e "s/'//g")
	//$nom=strtolower(strtr("$nom"," 'àâäéèêëîïôöùûüçÇÂÄÊËÎÏÔÖÙÛÜ","__aaaeeeeiioouuucCAAEEIIOOUUU"));
	//$prenom=strtolower(strtr("$prenom"," 'àâäéèêëîïôöùûüçÇÂÄÊËÎÏÔÖÙÛÜ","__aaaeeeeiioouuucCAAEEIIOOUUU"));
	$nom=strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz"));
	$prenom=strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz"));

	fich_debug("Apr&#232;s filtrage...\n");
	fich_debug("\$nom=$nom\n");
	fich_debug("\$prenom=$prenom\n");

	//ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´
	//AAAAAAACEEEEIIIINOOOOOSUUUUYYZ
	//áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸
	//aaaaaaceeeeiiiinoooooosuuuuyyz

	/*
	# Valeurs de l'uidPolicy
	#	0: prenom.nom
	#	1: prenom.nom tronque a 19
	#	2: pnom tronque a 19
	#	3: pnom tronque a 8
	#	4: nomp tronque a 8
	#	5: nomprenom tronque a 18
	*/

	switch($uidPolicy){
		case 0:
			$uid=$prenom.".".$nom;
			break;
		case 1:
			$uid=$prenom.".".$nom;
			$uid=substr($uid,0,19);
			break;
		case 2:
			$ini_prenom=substr($prenom,0,1);
			$uid=$ini_prenom.$nom;
			$uid=substr($uid,0,19);
			break;
		case 3:
			$ini_prenom=substr($prenom,0,1);
			$uid=$ini_prenom.$nom;
			$uid=substr($uid,0,8);
			break;
		case 4:
			$debut_nom=substr($nom,0,7);
			$ini_prenom=substr($prenom,0,1);
			$uid=$debut_nom.$ini_prenom;
			break;
		case 5:
			$uid=$nom.$prenom;
			$uid=substr($uid,0,18);
			break;
		default:
			$ERREUR="oui";
	}

	fich_debug("\$uid=$uid\n");
	fich_debug("\$ERREUR=$ERREUR\n");

	// Pour faire disparaitre les caracteres speciaux restants:
	$uid=preg_replace("/[^a-z_.-]/","",$uid);

	fich_debug("Apr&#232;s filtrage...\n");
	fich_debug("\$uid=$uid\n");

	$test_caract1=substr($uid,0,1);
	if(strlen(preg_replace("/[a-z]/","",$test_caract1))!=0){
		$error="Le premier caract&#232;re de l'uid n'est pas une lettre.";
	}
	else{
		// Debut de l'uid... pour les doublons...
		$prefuid=substr($uid,0,strlen($uid)-1);
		$prefuid2=substr($uid,0,strlen($uid)-2);
		// Ou renseigner un uid_initial ou uid_souche
		$uid_souche=$uid;

		$ok_uid="non";

		$attr=array("uid");

		$ds=@ldap_connect($ldap_server,$ldap_port);
		if($ds){
			$r=@ldap_bind($ds);// Bind anonyme
			//$adminLdap=get_infos_admin_ldap();
			//$r=@ldap_bind($ds,$adminLdap["adminDn"],$adminLdap["adminPw"]);// Bind admin LDAP
			if($r){
				$cpt=2;
				//while($ok_uid=="non"){
				//while(($ok_uid=="non")&&($cpt<10)){
				while(($ok_uid=="non")&&($cpt<100)){
					$result=ldap_search($ds,$dn["people"],"uid=$uid*",$attr);
					if ($result) {
						$info=@ldap_get_entries($ds,$result);
						if($info){
							$ok_uid="oui";
							for($i=0;$i<$info["count"];$i++){
								//echo "<p>";
								// En principe, il n'y a qu'un uid par entree...
								for($loop=0;$loop<$info[$i]["uid"]["count"]; $loop++) {
									//echo "\$info[$i][\"uid\"][$loop]=".$info[$i]["uid"][$loop]."<br />\n";
									if($info[$i]["uid"][$loop]==$uid){
										$ok_uid="non";
										//$uid=substr($uid,0,strlen($uid)-1).$cpt;
										//$uid=substr($uid,0,strlen($uid)-strlen($cpt)).$cpt;
										//$uid=$prefuid.$cpt;
										$uid=substr($uid_souche,0,strlen($uid_souche)-strlen($cpt)).$cpt;
										fich_debug("Doublons... \$uid=$uid\n");
										$cpt++;
									}
								}
								//echo "</p>\n";
							}
						}
					}
					else{
						$error="Echec de la lecture des entr&#233;es...";
						fich_debug("\$error=$error\n");
					}
					@ldap_free_result($result);
				}

				// Vérification que l'uid n'était pas en Trash
				$result=ldap_search($ds,$dn["trash"],"uid=$uid*",$attr);
				if ($result) {
					$info=@ldap_get_entries($ds,$result);
					if($info){
						$ok_uid="oui";
						for($i=0;$i<$info["count"];$i++){
							//echo "<p>";
							// En principe, il n'y a qu'un uid par entree...
							for($loop=0;$loop<$info[$i]["uid"]["count"]; $loop++) {
								//echo "\$info[$i][\"uid\"][$loop]=".$info[$i]["uid"][$loop]."<br />\n";
								if($info[$i]["uid"][$loop]==$uid){
									$ok_uid="non";
									$error="L'uid <b style='color:red;'>$uid</b> existe dans la branche Trash.";
								}
							}
							//echo "</p>\n";
						}
					}
				}

			}
			else{
				$error=gettext("Echec du bind anonyme");
				fich_debug("\$error=$error\n");
			}
			@ldap_close($ds);
		}
		else{
			$error=gettext("Erreur de connection au serveur LDAP");
			fich_debug("\$error=$error\n");
		}
	}

	if($error!=""){
		echo "error=$error<br />\n";
		fich_debug("\$error=$error\n");
		return false;
	}
	//elseif($cpt>=10){
		//$error="Il y a au moins 10 uid en doublon...<br />On en est &#224; $uid<br />Etes-vous s&#251;r qu'il n'y a pas des personnes qui ont quitt&#233; l'&#233;tablissement?";
	elseif($cpt>=100){
		$error="Il y a au moins 100 uid en doublon...<br />On en est &#224; $uid<br />Etes-vous s&#251;r qu'il n'y a pas des personnes qui ont quitt&#233; l'&#233;tablissement?";
		echo "error=$error<br />\n";
		fich_debug("\$error=$error\n");
		return false;
	}
	else{
		// Retourner $uid
		return $uid;
	}
}



//================================================

/**

* Tester si l'employeeNumber est dans l'annuaire ou non...
* @Parametres
* @Return

*/

/*
function verif_employeeNumber($employeeNumber){
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";
	// Tester si l'employeeNumber est dans l'annuaire ou non...

	//$attribut=array("uid","employeenumber");
	//$attribut=array("employeenumber");
	$attribut=array("uid");
	$tab=get_tab_attribut("people","employeenumber=$employeeNumber",$attribut);

	if(count($tab)>0){return $tab;}else{return false;}
}
*/
function verif_employeeNumber($employeeNumber) {
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";
	// Tester si l'employeeNumber est dans l'annuaire ou non...

	//$attribut=array("uid","employeenumber");
	//$attribut=array("employeenumber");
	$attribut=array("uid");
	$tab=get_tab_attribut("people","employeenumber=$employeeNumber",$attribut);

	$attribut=array("uid");
	$tab2=get_tab_attribut("people","employeenumber=".sprintf("%05d",$employeeNumber),$attribut);

	$attribut=array("uid");
	$tab3=get_tab_attribut("trash","employeenumber=".$employeeNumber,$attribut);

	$attribut=array("uid");
	$tab4=get_tab_attribut("trash","employeenumber=".sprintf("%05d",$employeeNumber),$attribut);

	/*
	echo "count($tab)=".count($tab)."<br />\n";
	for($i=0;$i<count($tab);$i++){
		echo "tab[$i]=$tab[$i]<br />\n";
	}
	*/

	if(count($tab)>0){$tab[-1]="people";return $tab;}
	elseif(count($tab2)>0){$tab2[-1]="people";return $tab2;}
	elseif(count($tab3)>0){$tab3[-1]="trash";return $tab3;}
	elseif(count($tab4)>0){$tab4[-1]="trash";return $tab4;}
	else{return false;}
}


//================================================

/**

* Tester si un uid existe ou non dans l'annuaire pour $nom et $prenom sans employeeNumber ... ce qui correspondrait a un compte cree a la main.
* @Parametres
* @Return

*/


function verif_nom_prenom_sans_employeeNumber($nom,$prenom){
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";
	// Tester si un uid existe ou non dans l'annuaire pour $nom et $prenom sans employeeNumber...
	// ... ce qui correspondrait a un compte cree a la main.

	$attribut=array("uid");
	$tab1=array();
	//$tab1=get_tab_attribut("people","cn='$prenom $nom'",$attribut);
 	$tab1=get_tab_attribut("people","cn=$prenom $nom",$attribut);

	//echo "<p>error=$error</p>";

	$trouve=0;
	if(count($tab1)>0){
		//echo "<p>count(\$tab1)>0</p>";
		for($i=0;$i<count($tab1);$i++){
			$attribut=array("employeenumber");
			$tab2=get_tab_attribut("people","uid=$tab1[$i]",$attribut);
			if(count($tab2)==0){
				//echo "<p>count(\$tab2)==0</p>";
				$trouve++;
				$uid=$tab1[$i];
				//echo "<p>uid=$uid</p>";
			}
		}

		// On ne cherche a traiter que le cas d'une seule correspondance.
		// S'il y en a plus, on ne pourra pas identifier...
		if($trouve==1){
			return $uid;
		}
		else{
			return false;
		}
	}
	else{
		return false;
	}
}


//================================================

/**

* Obtient un tableau avecc les attributs
* @Parametres $attribut doit etre un tableau d'une seule valeur  Ex.: $attribut[0]="uidNumber";

* @Return un tableau avec les attributs

*/

function get_tab_attribut($branche, $filtre, $attribut){
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";

	// Parametres
	// $attribut doit etre un tableau d'une seule valeur.
	// Ex.: $attribut[0]="uidNumber";

	// Tableau retourne
	$tab=array();

	fich_debug("======================\n");
	fich_debug("get_tab_attribut:\n");

	$ds=@ldap_connect($ldap_server,$ldap_port);
	if($ds){
		$r=@ldap_bind($ds);// Bind anonyme
		if($r){
			$result=ldap_search($ds,$dn[$branche],"$filtre",$attribut);
			fich_debug("ldap_search($ds,".$dn[$branche].",\"$filtre\",$attribut)\n");
			//echo "<p>ldap_search($ds,$dn[$branche],\"$filtre\",$attribut);</p>";
			if ($result){
				//echo "\$result=$result<br />";
				$info=@ldap_get_entries($ds,$result);
				if($info){
					fich_debug("\$info[\"count\"]=".$info["count"]."\n");
					//echo "<br />".$info["count"]."<br />";
					for($i=0;$i<$info["count"];$i++){
						fich_debug("\$info[$i][$attribut[0]][\"count\"]=".$info[$i][$attribut[0]]["count"]."\n");
						for($loop=0;$loop<$info[$i][$attribut[0]]["count"]; $loop++) {
							$tab[]=$info[$i][$attribut[0]][$loop];
							fich_debug("\$tab[]=".$info[$i][$attribut[0]][$loop]."\n");
						}
					}
					rsort($tab);
				}
				else{
					fich_debug("\$info vide... @ldap_get_entries($ds,$result) n'a rien donn&#233;.\n");
				}
			}
			else{
				$error="Echec de la lecture des entr&#233;es: ldap_search($ds,".$dn[$branche].",\"$filtre\",$attribut)";
				fich_debug("\$error=$error\n");
			}
			@ldap_free_result($result);

		}
		else{
			$error=gettext("Echec du bind anonyme");
			fich_debug("\$error=$error\n");
		}
		@ldap_close($ds);
	}
	else{
		$error=gettext("Erreur de connection au serveur LDAP");
		fich_debug("\$error=$error\n");
	}

	if($error!=""){
		echo "error=$error<br />\n";
	}

	return $tab;
}


//================================================

/**

* Recherche le premier uidNumber disponible  On demarre les uid a 1001, mais admin est en 5000:
* @Parametres

* @Return

*/


function get_first_free_uidNumber(){
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";

	// On demarre les uid a 1001, mais admin est en 5000:
	// unattend est en 1000 chez moi... mais cela peut changer avec des etablissements dont l'annuaire SE3 date d'avant l'ajout d'unattend
	$first_uidNumber=1000;
	$last_uidNumber=4999;
	//$last_uidNumber=1200;

	unset($attribut);
	$attribut=array();
	$attribut[0]="uidnumber";
	//$tab=array();
	//$tab=get_tab_attribut("people", "uid=*", $attribut);
	$tab1=array();
	$tab1=get_tab_attribut("people", "uid=*", $attribut);
	$tab2=array();
	$tab2=get_tab_attribut("trash", "uid=*", $attribut);
	$tab=array_merge($tab1,$tab2);
	rsort($tab);

	/*
	// Debug:
	echo "count(\$tab)=".count($tab)."<br />";
	for($i=0;$i<count($tab);$i++){
		echo "\$tab[$i]=$tab[$i]<br />";
	}
	*/

	/*
	// Methode OK, mais on risque la penurie des uidNumber entre 1000 et 5000
	// a ne pas recuperer des uidNumber d'utilisateurs qui ont quitte l'etablissement
	//$last_uidNumber=1473;
	$uidNumber=$last_uidNumber;
	while((!in_array($uidNumber,$tab))&&($uidNumber>$first_uidNumber)){
		$uidNumber--;
		//echo "\$uidNumber=$uidNumber<br />";
	}
	$uidNumber++;
	if(($uidNumber>$last_uidNumber)||(in_array($uidNumber,$tab))){
		$error="Il n'y a plus de plus grand uidNumber libre en dessous de $last_uidNumber";
		echo "error=$error<br />";
		return false;
	}
	else{
		echo "<p><b>\$uidNumber=$uidNumber</b></p>";
		return $uidNumber;
	}
	*/


	//TEST: $last_uidNumber=1200;
	// Ou: on recherche le plus petit uidNumber dispo entre $first_uidNumber et $last_uidNumber
	$uidNumber=$first_uidNumber;
	while((in_array($uidNumber,$tab))&&($uidNumber<$last_uidNumber)){
		$uidNumber++;
	}
	//echo "<p><b>\$uidNumber=$uidNumber</b></p>";

	if(($uidNumber==$last_uidNumber)&&(in_array($uidNumber,$tab))){
		$error="Il n'y a plus d'uidNumber libre";
		//echo "error=$error<br />";
		return false;
	}
	else{
		return $uidNumber;
	}

	/*
	// Ou: On mixe les deux methodes:
	// C'EST UNE FAUSSE SOLUTION:
	// Quand tout va etre rempli la premiere fois, on va commencer a recuperer des uidNumber par le haut des qu'un uidNumber va se liberer et on va re-affecter des uidNumber utilises recemment.
	$uidNumber=$last_uidNumber;
	while((!in_array($uidNumber,$tab))&&($uidNumber>$first_uidNumber)){
		$uidNumber--;
		//echo "\$uidNumber=$uidNumber<br />";
	}
	$uidNumber++;
	if(($uidNumber>$last_uidNumber)||(in_array($uidNumber,$tab))){
		// On commence a reaffecter des uidNumber libres par le bas
		$uidNumber=$first_uidNumber;
		while((in_array($uidNumber,$tab))&&($uidNumber<$last_uidNumber)){
			$uidNumber++;
		}

		if(($uidNumber==$last_uidNumber)&&(in_array($uidNumber,$tab))){
			$error="Il n'y a plus d'uidNumber libre";
			//echo "error=$error<br />";
			return false;
		}
		else{
			return $uidNumber;
		}
	}
	else{
		//echo "<p><b>\$uidNumber=$uidNumber</b></p>";
		return $uidNumber;
	}
	*/
}


//================================================

/**

* Recherche le premier gidNumber disponible
* @Parametres

* @Return

*/


function get_first_free_gidNumber($start=NULL){
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";

	/*
	# Quelques groupes:
	# 5000:admins
	# 5001:Eleves
	# 5002:Profs
	# 5003:Administratifs
	# 1560:overfill
	# 1000:lcs-users
	# 998:machines
	*/

	$first_gidNumber=2000;
	$last_gidNumber=4999;
	//$last_gidNumber=2010;

	if((isset($start))&&(strlen(preg_replace("/[0-9]/","",$start))==0)&&($start>=$first_gidNumber)) {
		$first_gidNumber=$start;
		$last_gidNumber=64000;
	}

	unset($attribut);
	$attribut=array();
	$attribut[0]="gidnumber";

	$tab1=array();
	$tab1=get_tab_attribut("people", "uid=*", $attribut);

	$tab=array();
	for($i=0;$i<count($tab1);$i++){
		//echo "\$tab1[$i]=$tab1[$i]<br />";
		$tab[]=$tab1[$i];
	}

	//echo "<hr />";

	$tab2=array();
	$tab2=get_tab_attribut("groups", "cn=*", $attribut);

	for($i=0;$i<count($tab2);$i++){
		//echo "\$tab2[$i]=$tab2[$i]<br />";
		if(!in_array($tab2[$i],$tab)){
			$tab[]=$tab2[$i];
		}
	}
	rsort($tab);

	/*
	// Debug:
	echo "count(\$tab)=".count($tab)."<br />";
	for($i=0;$i<count($tab);$i++){
		echo "\$tab[$i]=$tab[$i]<br />";
	}
	*/

	// On recherche le plus petit gidNumber dispo entre $first_gidNumber et $last_gidNumber
	$gidNumber=$first_gidNumber;
	while((in_array($gidNumber,$tab))&&($gidNumber<$last_gidNumber)){
		$gidNumber++;
	}
	//echo "<p><b>\$gidNumber=$gidNumber</b></p>";

	if(($gidNumber==$last_gidNumber)&&(in_array($gidNumber,$tab))){
		$error="Il n'y a plus de gidNumber libre";
		//echo "error=$error<br />";
		return false;
	}
	else{
		return $gidNumber;
	}
	// Pour controler:
	// ldapsearch -xLLL gidNumber | grep gidNumber | sed -e "s/^gidNumber: //" | sort -n -r | uniq | head
	// ldapsearch -xLLL gidNumber | grep gidNumber | sed -e "s/^gidNumber: //" | sort -n -r | uniq | tail
}

/*
function add_user($uid,$nom,$prenom,$sexe,$naissance,$password,$employeeNumber){
	// Recuperer le gidNumber par defaut -> lcs-users (1000) ou slis (600)
	global $defaultgid,$domain,$defaultshell,$domainsid,$uidPolicy;

	fich_debug("================\n");
	fich_debug("add_user:\n");
	fich_debug("\$defaultgid=$defaultgid\n");
	fich_debug("\$domain=$domain\n");
	fich_debug("\$defaultshell=$defaultshell\n");
	fich_debug("\$domainsid=$domainsid\n");
	fich_debug("\$uidPolicy=$uidPolicy\n");

	global $pathscripts;
	fich_debug("\$pathscripts=$pathscripts\n");


	// crob_init(); Ne sert a rien !!!!
	$nom=preg_replace("/[^a-z_-]/","",strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")));
	$prenom=preg_replace("/[^a-z_-]/","",strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")));

	$nom=ucfirst(strtolower($nom));
	$prenom=ucfirst(strtolower($prenom));

	fich_debug("\$nom=$nom\n");
	fich_debug("\$prenom=$prenom\n");


	// Recuperer un uidNumber:
	//$uidNumber=get_first_free_uidNumber();
	if(!get_first_free_uidNumber()){return false;exit();}
	$uidNumber=get_first_free_uidNumber();
	$rid=2*$uidNumber+1000;
	$pgrid=2*$defaultgid+1001;

	fich_debug("\$uidNumber=$uidNumber\n");


	// Faut-il interdire les espaces dans le password? les apostrophes?
	// Comment le script ntlmpass.pl prend-il le parametre sans les apostrophes?

	$ntlmpass=explode(" ",exec("$pathscripts/ntlmpass.pl '$password'"));

	$sambaLMPassword=$ntlmpass[0];
	$sambaNTPassword=$ntlmpass[1];
	$userPassword=exec("$pathscripts/unixPassword.pl '$password'");

	$attribut=array();
	$attribut["uid"]="$uid";
	$attribut["cn"]="$prenom $nom";

	$attribut["givenName"]=strtolower($prenom).strtoupper(substr($nom,0,1));

	$attribut["sn"]="$nom";

	$attribut["mail"]="$uid@$domain";
	$attribut["objectClass"]="top";

	// Comme la cle est toujours objectClass, cela pose un probleme: un seul attribut objectClass est ajoute (le dernier defini)
	//$attribut["objectClass"]="posixAccount";
	//$attribut["objectClass"]="shadowAccount";
	//$attribut["objectClass"]="person";
	//$attribut["objectClass"]="inetOrgPerson";
	//$attribut["objectClass"]="sambaSamAccount";

	$attribut["loginShell"]="$defaultshell";
	$attribut["uidNumber"]="$uidNumber";

	$attribut["gidNumber"]="$defaultgid";

	$attribut["homeDirectory"]="/home/$uid";
	$attribut["gecos"]="$prenom $nom,$naissance,$sexe,N";

	$attribut["sambaSID"]="$domainsid-$rid";
        $attribut["sambaPrimaryGroupSID"]="$domainsid-$pgrid";

	$attribut["sambaPwdLastSet"]="1";
	$attribut["sambaPwdMustChange"]="2147483647";
	$attribut["sambaAcctFlags"]="[U          ]";
	$attribut["sambaLMPassword"]="$sambaLMPassword";
	$attribut["sambaNTPassword"]="$sambaNTPassword";
	$attribut["userPassword"]="{crypt}$userPassword";

	// IL faut aussi l'employeeNumber
	if("$employeeNumber"!=""){
		$attribut["employeeNumber"]="$employeeNumber";
	}

	$result=add_entry("uid=$uid","people",$attribut);
	if($result){
		// Reste a ajouter les autres attributs objectClass
		unset($attribut);
		$attribut=array();
		$attribut["objectClass"]="posixAccount";
		if(modify_attribut("uid=$uid","people", $attribut, "add")){
			unset($attribut);
			$attribut=array();
			$attribut["objectClass"]="shadowAccount";
			if(modify_attribut("uid=$uid","people", $attribut, "add")){
				unset($attribut);
				$attribut=array();
				$attribut["objectClass"]="person";
				if(modify_attribut("uid=$uid","people", $attribut, "add")){
					unset($attribut);
					$attribut=array();
					$attribut["objectClass"]="inetOrgPerson";
					if(modify_attribut("uid=$uid","people", $attribut, "add")){
						unset($attribut);
						$attribut=array();
						$attribut["objectClass"]="sambaSamAccount";
						if(modify_attribut("uid=$uid","people", $attribut, "add"))  return true;
						else return false;
					} else return false;
				} else return false;
			} else return false;
		} else return false;
	} else return false;
}
*/


//================================================

/**

* Ajoute un utilisateur dans l'annuaire LDAP
* @Parametres

* @Return

*/

function add_user($uid,$nom,$prenom,$sexe,$naissance,$password,$employeeNumber){
	// Recuperer le gidNumber par defaut -> lcs-users (1000) ou slis (600)
	global $defaultgid,$domain,$defaultshell,$domainsid,$uidPolicy;
	global $attribut_pseudo;

	fich_debug("================\n");
	fich_debug("add_user:\n");
	fich_debug("\$defaultgid=$defaultgid\n");
	fich_debug("\$domain=$domain\n");
	fich_debug("\$defaultshell=$defaultshell\n");
	fich_debug("\$domainsid=$domainsid\n");
	fich_debug("\$uidPolicy=$uidPolicy\n");

	global $pathscripts;
	fich_debug("\$pathscripts=$pathscripts\n");


	// crob_init(); Ne sert a rien !!!!
	//$nom=preg_replace("/[^a-z_-]/","",strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")));
	//$prenom=preg_replace("/[^a-z_-]/","",strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")));
	$nom=preg_replace("/[^a-z_ -]/","",strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")));
	$prenom=preg_replace("/[^a-z_ -]/","",strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")));

	$nom=ucfirst(strtolower($nom));
	$prenom=ucfirst(strtolower($prenom));

	fich_debug("\$nom=$nom\n");
	fich_debug("\$prenom=$prenom\n");


	// Recuperer un uidNumber:
	//$uidNumber=get_first_free_uidNumber();
	if(!get_first_free_uidNumber()){return false;exit();}
	$uidNumber=get_first_free_uidNumber();
	$rid=2*$uidNumber+1000;
	$pgrid=2*$defaultgid+1001;

	fich_debug("\$uidNumber=$uidNumber\n");


	// Faut-il interdire les espaces dans le password? les apostrophes?
	// Comment le script ntlmpass.pl prend-il le parametre sans les apostrophes?

	$ntlmpass=explode(" ",exec("$pathscripts/ntlmpass.pl '$password'"));

	$sambaLMPassword=$ntlmpass[0];
	$sambaNTPassword=$ntlmpass[1];
	$userPassword=exec("$pathscripts/unixPassword.pl '$password'");

	$attribut=array();
	$attribut["uid"]="$uid";
	$attribut["cn"]="$prenom $nom";

	//$attribut["givenName"]=strtolower($prenom).strtoupper(substr($nom,0,1));
	$attribut["givenName"]=ucfirst(strtolower($prenom));
	//$attribut["$attribut_pseudo"]=strtolower($prenom).strtoupper(substr($nom,0,1));
	$attribut["$attribut_pseudo"]=preg_replace("/ /","_",strtolower($prenom).strtoupper(substr($nom,0,1)));

	$attribut["sn"]="$nom";

	$attribut["mail"]="$uid@$domain";
	//$attribut["objectClass"]="top";
	/*
	// Comme la cle est toujours objectClass, cela pose un probleme: un seul attribut objectClass est ajoute (le dernier defini)
	$attribut["objectClass"]="posixAccount";
	$attribut["objectClass"]="shadowAccount";
	$attribut["objectClass"]="person";
	$attribut["objectClass"]="inetOrgPerson";
	$attribut["objectClass"]="sambaSamAccount";
	*/
	$attribut["objectClass"][0]="top";
	$attribut["objectClass"][1]="posixAccount";
	$attribut["objectClass"][2]="shadowAccount";
	$attribut["objectClass"][3]="person";
	$attribut["objectClass"][4]="inetOrgPerson";
	$attribut["objectClass"][5]="sambaSamAccount";

	$attribut["loginShell"]="$defaultshell";
	$attribut["uidNumber"]="$uidNumber";

	$attribut["gidNumber"]="$defaultgid";

	$attribut["homeDirectory"]="/home/$uid";
	$attribut["gecos"]="$prenom $nom,$naissance,$sexe,N";

	$attribut["sambaSID"]="$domainsid-$rid";
        $attribut["sambaPrimaryGroupSID"]="$domainsid-$pgrid";

	$attribut["sambaPwdMustChange"]="2147483647";
	$attribut["sambaPwdLastSet"]="1";
	$attribut["sambaAcctFlags"]="[U          ]";
	$attribut["sambaLMPassword"]="$sambaLMPassword";
	$attribut["sambaNTPassword"]="$sambaNTPassword";
	$attribut["userPassword"]="{crypt}$userPassword";

	// IL faut aussi l'employeeNumber
	if("$employeeNumber"!=""){
		$attribut["employeeNumber"]="$employeeNumber";
	}

	$result=add_entry("uid=$uid","people",$attribut);

	if($result){
		/*
		// Reste a ajouter les autres attributs objectClass
		unset($attribut);
		$attribut=array();
		$attribut["objectClass"]="posixAccount";
		if(modify_attribut("uid=$uid","people", $attribut, "add")){
			unset($attribut);
			$attribut=array();
			$attribut["objectClass"]="shadowAccount";
			if(modify_attribut("uid=$uid","people", $attribut, "add")){
				unset($attribut);
				$attribut=array();
				$attribut["objectClass"]="person";
				if(modify_attribut("uid=$uid","people", $attribut, "add")){
					unset($attribut);
					$attribut=array();
					$attribut["objectClass"]="inetOrgPerson";
					if(modify_attribut("uid=$uid","people", $attribut, "add")){
						unset($attribut);
						$attribut=array();
						$attribut["objectClass"]="sambaSamAccount";
						if(modify_attribut("uid=$uid","people", $attribut, "add"))  return true;
						else return false;
					} else return false;
				} else return false;
			} else return false;
		} else return false;
		*/
		return true;
	} else return false;
}


//================================================

/**

* Verifie et corrige le Gecos
* @Parametres

* @Return

*/

function verif_et_corrige_gecos($uid,$nom,$prenom,$naissance,$sexe){
	// Verification/correction du GECOS

    global $simulation;

	// Correction du nom/prenom fournis
	$nom=remplace_accents(traite_espaces($nom));
	$prenom=remplace_accents(traite_espaces($prenom));

	$nom=preg_replace("/[^a-z_-]/","",strtolower("$nom"));
	$prenom=preg_replace("/[^a-z_-]/","",strtolower("$prenom"));

	$nom=ucfirst(strtolower($nom));
	$prenom=ucfirst(strtolower($prenom));

	unset($attribut);
	$attribut=array("gecos");
	$tab=get_tab_attribut("people", "uid=$uid", $attribut);
	if(count($tab)>0){
		if("$tab[0]"!="$prenom $nom,$naissance,$sexe,N"){
			unset($attributs);
			$attributs=array();
			$attributs["gecos"]="$prenom $nom,$naissance,$sexe,N";
			$attributs["cn"]="$prenom $nom";
			$attributs["givenName"]=strtolower($prenom).strtoupper(substr($nom,0,1));
			$attributs["sn"]="$nom";
			my_echo("Correction de l'attribut 'gecos': ");
			if($simulation!='y') {
                if(modify_attribut ("uid=$uid", "people", $attributs, "replace")){
				    my_echo("<font color='green'>SUCCES</font>");
                }
                else{
                    my_echo("<font color='red'>ECHEC</font>");
                    $nb_echecs++;
                }
            }
            else {
                my_echo("<font color='blue'>SIMULATION</font>");
            }
            my_echo("<br />\n");
		}
	}
}

/**

* Verifie et corrige le givenName
* @Parametres

* @Return

*/

function verif_et_corrige_givenname($uid,$prenom) {
	// Verification/correction du givenName

    global $simulation;

	// Correction du nom/prenom fournis
	$prenom=remplace_accents(traite_espaces($prenom));

	$prenom=preg_replace("/[^a-z_-]/","",strtolower("$prenom"));

	// FAUT-IL LA MAJUSCULE?
	$prenom=ucfirst(strtolower($prenom));

	unset($attribut);
	//$attribut=array("givenName");
	$attribut=array("givenname");
	$tab=get_tab_attribut("people", "uid=$uid", $attribut);
	//my_echo("\$tab=get_tab_attribut(\"people\", \"uid=$uid\", \$attribut)<br />");
	//my_echo("count(\$tab)=".count($tab)."<br />");
	if(count($tab)>0){
		//my_echo("\$tab[0]=".$tab[0]." et \$prenom=$prenom<br />");
		if("$tab[0]"!="$prenom") {
			unset($attributs);
			$attributs=array();
			//$attributs["givenName"]=strtolower($prenom);
			$attributs["givenName"]=$prenom;
			my_echo("Correction de l'attribut 'givenName': ");
			if($simulation!='y') {
                if(modify_attribut ("uid=$uid", "people", $attributs, "replace")) {
                    my_echo("<font color='green'>SUCCES</font>");
                }
                else{
                    my_echo("<font color='red'>ECHEC</font>");
                    $nb_echecs++;
                }
            }
            else {
                my_echo("<font color='blue'>SIMULATION</font>");
            }
			my_echo("<br />\n");
		}
	}
}

/**

* Verifie et corrige le pseudo
* @Parametres

* @Return

*/

function verif_et_corrige_pseudo($uid,$nom,$prenom) {
	// Verification/correction de l'attribut choisi pour le pseudo
	global $attribut_pseudo;
	global $annuelle;
    global $simulation;

	// En minuscules pour la recherche:
	$attribut_pseudo_min=strtolower($attribut_pseudo);

	// Correction du nom/prenom fournis
	$nom=remplace_accents(traite_espaces($nom));
	$prenom=remplace_accents(traite_espaces($prenom));

	$nom=preg_replace("/[^a-z_-]/","",strtolower("$nom"));
	$prenom=preg_replace("/[^a-z_-]/","",strtolower("$prenom"));

	unset($attribut);
	$attribut=array("$attribut_pseudo_min");
	$tab=get_tab_attribut("people", "uid=$uid", $attribut);
	//my_echo("\$tab=get_tab_attribut(\"people\", \"uid=$uid\", \$attribut)<br />");
	//my_echo("count(\$tab)=".count($tab)."<br />");

	$tmp_pseudo=strtolower($prenom).strtoupper(substr($nom,0,1));
	if(count($tab)>0){
		// Si le pseudo existe déjà, on ne réinitialise le pseudo que lors d'un import annuel
		if($annuelle=="y") {
			//my_echo("\$tab[0]=".$tab[0]." et \$prenom=$prenom<br />");
			//$tmp_pseudo=strtolower($prenom).strtoupper(substr($nom,0,1));
			if("$tab[0]"!="$tmp_pseudo") {
				unset($attributs);
				$attributs=array();
				$attributs["$attribut_pseudo"]=$tmp_pseudo;
				my_echo("Correction de l'attribut '$attribut_pseudo': ");
                if($simulation!='y') {
                    if(modify_attribut ("uid=$uid", "people", $attributs, "replace")) {
                        my_echo("<font color='green'>SUCCES</font>");
                    }
                    else{
                        my_echo("<font color='red'>ECHEC</font>");
                        $nb_echecs++;
                    }
                }
                else {
                    my_echo("<font color='blue'>SIMULATION</font>");
                }
                my_echo("<br />\n");
			}
		}
	}
	else {
		// L'attribut pseudo n'existait pas:
		unset($attributs);
		$attributs=array();
		//$attributs["$tmp_pseudo"]=strtolower($prenom).strtoupper(substr($nom,0,1));
		$attributs["$attribut_pseudo"]=$tmp_pseudo;
		my_echo("Renseignement de l'attribut '$attribut_pseudo': ");
        if($simulation!='y') {
            if(modify_attribut("uid=$uid", "people", $attributs, "add")) {
                my_echo("<font color='green'>SUCCES</font>");
            }
            else{
                my_echo("<font color='red'>ECHEC</font>");
                $nb_echecs++;
            }
        }
        else {
            my_echo("<font color='blue'>SIMULATION</font>");
        }
		my_echo("<br />\n");
	}
}

function get_uid_from_f_uid_file($employeeNumber) {
	global $dossier_tmp_import_comptes;

	if(!file_exists("$dossier_tmp_import_comptes/f_uid.txt")) {
		return false;
	}
	else {
		$ftmp=fopen("$dossier_tmp_import_comptes/f_uid.txt","r");
		while(!feof($ftmp)) {
			$ligne=trim(fgets($ftmp,4096));

			if($tab=explode(";",$ligne)) {
				if("$tab[0]"=="$employeeNumber") {
					// On controle le login
					if(strlen(preg_replace("/[A-Za-z0-9._\-]/","",$tab[1]))==0) {
						return $tab[1];
					}
					else {
						return false;
					}
					break;
				}
			}
		}
	}
}


/**
* Recherche les compte dans la branche Trash
* @Parametres $filter filtre ldap de recherche
* @return
*/

// Fonction extraite de /annu/ldap_cleaner.php

function search_people_trash ($filter) {
	//global $ldap_server, $ldap_port, $dn, $adminDn, $adminPw;
	global $ldap_server, $ldap_port, $dn;
	global $error;
	$error="";
	global $sambadomain;

	$adminLdap=get_infos_admin_ldap();
	$adminDn=$adminLdap["adminDn"];
	$adminPw=$adminLdap["adminPw"];

	//LDAP attributes

	$ldap_search_people_attr = array(
		"sambaacctFlags",
		"sambapwdMustChange",
		"sambantPassword",
		"sambalmPassword",
		"sambaSID",
		"sambaPrimaryGroupSID",
		"userPassword",
		"gecos",
		"employeenumber",
		"homedirectory",
		"gidNumber",
		"uidNumber",
		"loginShell",
		"objectClass",
		"mail",
		"sn",
		"givenName",
		"cn",
		"uid"
	);

	$ds = @ldap_connect ( $ldap_server, $ldap_port );
	if ( $ds ) {
		$r = @ldap_bind ( $ds,$adminDn, $adminPw );
		if ($r) {
		// Recherche dans la branche trash
		$result = @ldap_search ( $ds, $dn["trash"], $filter, $ldap_search_people_attr );
		if ($result) {
			$info = @ldap_get_entries ( $ds, $result );
			if ( $info["count"]) {
			for ($loop=0; $loop<$info["count"];$loop++) {
				if ( isset($info[$loop]["employeenumber"][0]) ) {
						$ret[$loop] = array (
						"sambaacctflags"      => $info[$loop]["sambaacctflags"][0],
						"sambapwdmustchange"  => $info[$loop]["sambapwdmustchange"][0],
						"sambantpassword"     => $info[$loop]["sambantpassword"][0],
						"sambalmpassword"     => $info[$loop]["sambalmpassword"][0],
						"sambasid"            => $info[$loop]["sambasid"][0],
						"sambaprimarygroupsid"   => $info[$loop]["sambaprimarygroupsid"][0],
						"userpassword"        => $info[$loop]["userpassword"][0],
						"gecos"               => $info[$loop]["gecos"][0],
						"employeenumber"      => $info[$loop]["employeenumber"][0],
						"homedirectory"       => $info[$loop]["homedirectory"][0],
						"gidnumber"           => $info[$loop]["gidnumber"][0],
						"uidnumber"           => $info[$loop]["uidnumber"][0],
						"loginshell"          => $info[$loop]["loginshell"][0],
						"mail"                => $info[$loop]["mail"][0],
						"sn"                  => $info[$loop]["sn"][0],
						"givenname"           => $info[$loop]["givenname"][0],
						"cn"                  => $info[$loop]["cn"][0],
						"uid"                 => $info[$loop]["uid"][0],
						);
				} else {
						$ret[$loop] = array (
						"sambaacctflags"      => $info[$loop]["sambaacctflags"][0],
						"sambapwdmustchange"  => $info[$loop]["sambapwdmustchange"][0],
						"sambantpassword"     => $info[$loop]["sambantpassword"][0],
						"sambalmpassword"     => $info[$loop]["sambalmpassword"][0],
						"sambasid"            => $info[$loop]["sambasid"][0],
						"sambaprimarygroupsid"   => $info[$loop]["sambaprimarygroupsid"][0],
						"userpassword"        => $info[$loop]["userpassword"][0],
						"gecos"               => $info[$loop]["gecos"][0],
						"homedirectory"       => $info[$loop]["homedirectory"][0],
						"gidnumber"           => $info[$loop]["gidnumber"][0],
						"uidnumber"           => $info[$loop]["uidnumber"][0],
						"loginshell"          => $info[$loop]["loginshell"][0],
						"mail"                => $info[$loop]["mail"][0],
						"sn"                  => $info[$loop]["sn"][0],
						"givenname"           => $info[$loop]["givenname"][0],
						"cn"                  => $info[$loop]["cn"][0],
						"uid"                 => $info[$loop]["uid"][0],
						);
				}
			}
			}
			@ldap_free_result ( $result );
		} else $error = "Erreur de lecture dans l'annuaire LDAP";
		} else $error = "Echec du bind en admin";
		@ldap_close ( $ds );
	} else $error = "Erreur de connection au serveur LDAP";
	// Tri du tableau par ordre alphabetique
	if (count($ret)) usort($ret, "cmp_name");
	return $ret;
} // Fin function search_people_trash


// Les temps sont durs, il faut faire les poubelles pour en recuperer des choses...
function recup_from_trash($uid) {
	global $ldap_server, $ldap_port, $dn, $ldap_base_dn;

	$recup=false;

	$adminLdap=get_infos_admin_ldap();
	$adminDn=$adminLdap["adminDn"];
	$adminPw=$adminLdap["adminPw"];

	$user = search_people_trash ("uid=$uid");
	// Positionnement des constantes "objectclass"
	$user[0]["sambaacctflags"]="[U         ]";
	$user[0]["objectclass"][0]="top";
	$user[0]["objectclass"][1]="posixAccount";
	$user[0]["objectclass"][2]="shadowAccount";
	$user[0]["objectclass"][3]="person";
	$user[0]["objectclass"][4]="inetOrgPerson";
	$user[0]["objectclass"][5]="sambaAccount";
	$user[0]["objectclass"][5]="sambaSamAccount";

	$f=fopen("/tmp/recup_from_trash.txt","a+");
	foreach($user[0] as $key => $value) {
		fwrite($f,"\$user[0]['$key']=$value\n");
	}
	fwrite($f,"=======================\n");
	fclose($f);

	$ds = @ldap_connect ( $ldap_server, $ldap_port );
	if ( $ds ) {
		$f=fopen("/tmp/recup_from_trash.txt","a+");
		fwrite($f,"\$ds OK\n");
		fwrite($f,"=======================\n");
		fclose($f);

		$r = @ldap_bind ( $ds, $adminDn, $adminPw ); // Bind en admin
		if ($r) {
			$f=fopen("/tmp/recup_from_trash.txt","a+");
			fwrite($f,"\$r OK\n");
			fwrite($f,"=======================\n");
			fclose($f);

			// Ajout dans la branche people
			if ( @ldap_add ($ds, "uid=".$user[0]["uid"].",".$dn["people"],$user[0] ) ) {
				$f=fopen("/tmp/recup_from_trash.txt","a+");
				fwrite($f,"\ldap_add OK\n");
				fwrite($f,"=======================\n");
				fclose($f);

				// Suppression de la branche Trash
				@ldap_delete ($ds, "uid=".$user[0]["uid"].",".$dn["trash"] );
				$recup=true;
			}
			else {
				$recup=false;
			}
		}
	}
	ldap_close($ds);

	return $recup;
}

?>
