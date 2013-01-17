<?php


/**

* Modifie l'entree d'un utilisateur
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
* file: mod_user_entry.php
*/





require "config.inc.php";
require "functions.inc.php";


// require "entete.inc.php";
require "ldap.inc.php";
require "ihm.inc.php";
require "jlcipher.inc.php";

require "crob_ldap_functions.php";

$login=isauth();
// if ($login != "") {

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

header_crypto_html(gettext("Modification parametres utilisateur"),"../");

// Aide
@session_start();
$_SESSION["pageaide"]="Annuaire#Modifier_mon_compte";

echo "<h1>".gettext("Annuaire")."</h1>\n";


aff_trailer ("4");

$isadmin=is_admin("Annu_is_admin",$login);

$uid=isset($_GET['uid']) ? $_GET['uid'] : (isset($_POST['uid']) ? $_POST['uid'] : NULL);

if(!isset($uid)) {
	echo "<p style='color:red'>Erreur&nbsp;: Aucun utilisateur n'a &#233;t&#233; choisi.</p>\n",
	include ("pdp.inc.php");
	die();
}

//debug_var();

$user_entry=isset($_POST['user_entry']) ? $_POST['user_entry'] : '';
$telephone=isset($_POST['telephone']) ? $_POST['telephone'] : '';
$nom=isset($_POST['nom']) ? $_POST['nom'] : '';
$prenom=isset($_POST['prenom']) ? $_POST['prenom'] : '';
$description=isset($_POST['description']) ? $_POST['description'] : '';
$userpwd=isset($_POST['userpwd']) ? $_POST['userpwd'] : '';
$mail=isset($_POST['mail']) ? $_POST['mail'] : '';
$shell=isset($_POST['shell']) ? $_POST['shell'] : '';
$password=isset($_POST['password']) ? $_POST['password'] : '';
$string_auth=isset($_POST['string_auth']) ? $_POST['string_auth'] : '';

$naissance=isset($_POST['naissance']) ? $_POST['naissance'] : '';
$employeeNumber=isset($_POST['employeeNumber']) ? $_POST['employeeNumber'] : '';

if (($isadmin=="Y") or ((tstclass($login,$uid)==1) and (ldap_get_right("sovajon_is_admin",$login)=="Y"))) {
	// Recuperation des entrees de l'utilisateur a modifier
	$user=people_get_variables ($uid, false);

	// decodage du mot de passe
	if ((isset($user_entry))&&($user_entry)&&($string_auth!='')) {
		// decryptage des mdp
		exec ("/usr/bin/python ".$path_to_wwwse3."/includes/decode.py '$string_auth'",$Res);
		$userpwd = $Res[0];
	}
	// Modification des entrees
	//if (!isset($user_entry) || !verifTel($telephone) || !verifEntree($nom) || !verifEntree($prenom) || !verifDescription($description) || ($userpwd && !verifPwd($userpwd)) ) {
	/*
	if (!verifTel($telephone)) {
		echo "ERREUR tel<br />";
	}
	if (!verifEntree($nom)) {
		echo "ERREUR nom<br />";
	}
	if (!verifEntree($prenom)) {
		echo "ERREUR prenom<br />";
	}
	if (!verifDescription($description)) {
		echo "ERREUR description<br />";
	}
	if (!verifDateNaissance($naissance)) {
		echo "ERREUR naissance<br />";
	}
	if (!verifPwd($userpwd)) {
		echo "ERREUR pwd<br />";
	}
	*/

	$info_employeeNumber="";
	if($employeeNumber!='') {
		$tmp_tab=verif_employeeNumber($employeeNumber);
		if(($tmp_tab)&&(count($tmp_tab)>0)) {
			if($tmp_tab[0]!=$uid) {
				$info_employeeNumber="Le num&#233;ro <b>$employeeNumber</b> est d&#233;j&#225; attribu&#233; &#225; <a href='".$_SERVER['PHP_SELF']."?uid=".$tmp_tab[0]."'>".$tmp_tab[0]."</a> dans la branche <b>".$tmp_tab[-1]."</b><br />";
			}
		}
	}

	$employeeNumber0=$employeeNumber;
	$employeeNumber=preg_replace("/[^0-9A-Za-z]/","",$employeeNumber);
	
	if($employeeNumber!=$employeeNumber0) {
		$info_employeeNumber.="Un ou des caract&#232;res non valides ont &#233;t&#233; saisis dans le num&#233;ro '<b>$employeeNumber0</b>'";
	}

	if (!isset($user_entry) || !verifTel($telephone) || !verifEntree($nom) || !verifEntree($prenom) || !verifDescription($description) || ($userpwd && !verifPwd($userpwd)) || (($naissance!='')&&(!verifDateNaissance($naissance))) || ($info_employeeNumber!="")) {
		// Quand la migration givenName<-Prenom et seeAlso<-pseudo sera effectuee, on pourra modifier ci-dessous:
		$user[0]["prenom"]=getprenom($user[0]["fullname"],$user[0]["nom"]);
		?>
		<form name = "auth" action="mod_user_entry.php" method="post" onSubmit = "encrypt(document.auth)">
		<table align="center" border="0" width="90%">
		<tbody>
			<tr>
			<td width="27%">Login :&nbsp;</td>
				<td width="73%" colspan="2"><tt><strong><?php echo $user[0]["uid"]?></strong></tt></td>
			</tr>
			<tr>
			<td width="27%"><?php echo gettext("Pr&#233;nom"); ?> :&nbsp;</td>
				<td width="73%" colspan="2"><input type="text" name="prenom" value="<?php echo $user[0]["prenom"]?>" size="20"></td>
			</tr>
			<tr>
			<td><?php echo gettext("Nom"); ?>&nbsp;:&nbsp;</td>
			<td colspan="2"><input type="text" name="nom" value="<?php echo $user[0]["nom"]?>" size="20"></td>
			</tr>

			<?php
				if ($isadmin=="Y") {

					$tmp_tab=explode(",", $user[0]["gecos"]);
					if(isset($tmp_tab[1])) {$naissance=$tmp_tab[1];} else {$naissance="";}

					if(isset($user[0]["employeeNumber"])) {$employeeNumber=$user[0]["employeeNumber"];}
			?>

			<tr>
			<td><?php echo gettext("Date de naissance"); ?>&nbsp;:&nbsp;</td>
			<td colspan="2"><input type="text" name="naissance" value="<?php echo $naissance?>" size="20"></td>
			</tr>

			<tr>
				<td valign='top'><?php echo gettext("Numero"); ?>&nbsp;:&nbsp;</td>
				<td valign='top'><input type="text" name="employeeNumber" value="<?php echo $employeeNumber?>" size="20"></td>
				<td>
					<font color="orange">
					<u><?php echo gettext("Attention"); ?></u> :<?php echo gettext(" Le num&#233;ro correspond &#225; l'attribut 'employeeNumber' dans l'annuaire LDAP.<br />C'est ce num&#233;ro qui est utilis&#233; lors d'un import des comptes pour d&#233;terminer si le compte existe d&#233;j&#225; ou non.<br />Ne le changez pas sans bonne raison."); ?>
					</font>
				</td>
			</tr>

			<tr>
			<td><?php echo gettext("Adresse m&#232;l"); ?>&nbsp;:&nbsp;</td>
			<td colspan="2"><input type="text" name="mail" value="<?php echo $user[0]["email"]?>" size="20"></td>
			</tr>

			<tr>
			<td><em>Shell&nbsp;</em> :&nbsp;</td>
			<td>
					<select name="shell">
					<option <?php if ($user[0]["shell"] == "/bin/bash") echo "selected" ?>>/bin/bash</option>
					<option <?php if ($user[0]["shell"] == "/bin/true") echo "selected" ?>>/bin/true</option>
					</select>
			</td>
				<td>
					<font color="orange">
					<u><?php echo gettext("Attention"); ?></u> :<?php echo gettext(" Si vous choisissez /bin/bash,&nbsp;cet utilisateur disposera d'un shell sur le serveur."); ?>
					</font>
				</td>
			</tr>
			<tr>
			<td valign="center"><?php echo gettext("Profil"); ?> :&nbsp;</td>
			<td valign="bottom" colspan="2"><textarea name="description" rows="2" cols="40"><?php echo $user[0]["description"]; ?></textarea></td>
			</tr>
			<tr>
			<td><?php echo gettext("T&#233;l&#233;phone"); ?> :&nbsp;</td>
			<td colspan="2"><input type="text" name="telephone" value="<?php echo $user[0]["tel"] ?>" size="10"></td>
			</tr>
				<?php } ?>
			<tr>
			<td><?php echo gettext("Mot de passe"); ?>:&nbsp;</td>
			<td>
						<input type= "password" value="" name="dummy" size='20'  maxlength='20'>
						<input type="hidden" name="string_auth" value="">
			</td>
			<td>
					<font color="orange">
					<u><?php echo gettext("Attention"); ?></u> : <?php echo gettext("Si vous laissez ce champ vide,&nbsp;c'est l'ancien mot de passe qui sera conserv&#233;."); ?>
					</font>
			</td>
			</tr>
			<tr>
			<td></td>
			<td align="left">
					<input type="hidden" name="uid" value="<?php echo $uid ?>">
					<input type="hidden" name="user_entry" value="true">
					<input type="submit" value="<?php echo gettext("Lancer la requ&#234;te"); ?>">
				</td>
			</tr>
		</tbody>
		</table>
	</form>
	<?php
		crypto_nav("../");
		if ((isset($user_entry))&&($user_entry)) {
			// verification des saisies
			// nom prenom
			if ( !verifEntree($nom) || !verifEntree($prenom) ) {
				echo "<div class=\"error_msg\">".gettext("Les champs nom et prenom, doivent comporter au minimum 3 caract&#232;res alphab&#233;tiques.")."</div><br />\n";
			}
			// profil
			if ( $description && !verifDescription($description) ) {
				echo "<div class=\"error_msg\">".gettext("Veuillez reformuler le champ description.")."</div><br />\n";
			}
			// tel
			if ( $telephone && !verifTel($telephone) ) {
				echo "<div class=\"error_msg\">".gettext("Le num&#233;ro de t&#233;l&#233;phone que vous avez saisi, n'est pas conforme.")."</div><br />\n";
			}

			// Date de naissance
			if ($naissance!='' && !verifDateNaissance($naissance)) {
				echo "<div class=\"error_msg\">".gettext("La date de naissance que vous avez saisie, n'est pas conforme.")."</div><br />\n";
			}

			// mot de passe
			if ( $userpwd && !verifPwd($userpwd) ) {
				echo "<div class='error_msg'>";
				echo gettext("Vous devez proposer un mot de passe d'une longueur comprise entre 4 et 8 caract&#232;res
					alphanum&#233;riques avec obligatoirement un des caract&#232;res sp&#233;ciaux suivants");
				echo " ($char_spec) </div><br />\n";
			}

			if($info_employeeNumber!="") {
				echo "<div class=\"error_msg\">".gettext("$info_employeeNumber.")."</div><br />\n";
			}

			// fin verification des saisies
		}
	} else {
		// Positionnement des entrees a modifier
		//$entry["sn"] =  stripslashes ( utf8_encode($nom) );
		//$entry["cn"] = stripslashes ( utf8_encode($prenom)." ".utf8_encode($nom) );
		$entry["sn"] =  stripslashes ( ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚÝ¾´áàâäãåçéèêëîïìíñôöðòóõ¨ûüùúýÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz"))) );
		$entry["cn"] = stripslashes ( ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚÝ¾´áàâäãåçéèêëîïìíñôöðòóõ¨ûüùúýÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")))." ".ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚÝ¾´áàâäãåçéèêëîïìíñôöðòóõ¨ûüùúýÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz"))) );
	
		//======================================
		// Correction du gecos:
		//echo "\$user[0][\"gecos\"]=".$user[0]["gecos"]."<br />";
		if($user[0]["gecos"]!="") {
			$tab_gecos=explode(",",$user[0]["gecos"]);
			//$entry["gecos"]=ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚÝ¾´áàâäãåçéèêëîïìíñôöðòóõ¨ûüùúýÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")))." ".ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚÝ¾´áàâäãåçéèêëîïìíñôöðòóõ¨ûüùúýÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz"))).",".$tab_gecos[1].",".$tab_gecos[2].",".$tab_gecos[3];
			$entry["gecos"]=ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚÝ¾´áàâäãåçéèêëîïìíñôöðòóõ¨ûüùúýÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")))." ".ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚÝ¾´áàâäãåçéèêëîïìíñôöðòóõ¨ûüùúýÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz"))).",".$tab_gecos[1].",".$tab_gecos[2].",".$tab_gecos[3];
		}
	
		if($corriger_givenname_si_diff=="y") {
			// Ajout: crob 20080611
			// Variable initialisée dans includes/ldap.inc.php: $corriger_givenname_si_diff
			// placée pour permettre de désactiver temporairement cette partie
	
			// Le givenName est destiné à prendre pour valeur le Prenom de l'utilisateur
			//$entry["givenName"] = ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚÝ¾´áàâäãåçéèêëîïìíñôöðòóõ¨ûüùúýÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")));
			$entry["givenName"] = ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚÝ¾´áàâäãåçéèêëîïìíñôöðòóõ¨ûüùúýÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")));
		}
	
		// Il faudrait aussi corriger le gecos et pour cela récupérer le sexe et la date de naissance
		// On ne les trouve que dans le gecos ici.
		// Et le gecos n'est pas récupéré avec $user=people_get_variables ($uid, false);
		// Et on récupère un $user[0][pseudo] <- givenName
		/*
		echo "<p>Valeur des attributs avant modification: <br />";
		foreach($user[0] as $key => $value) {
			echo "\$user[0][$key]=$value<br />";
		}
		*/
		// La fonction people_get_variables() est utilisée dans pas mal de pages modifier le retour si givenName prend pour valeur Prenom va être lourd.
		//======================================
	
		if ( $isadmin=="Y" ) {
			$entry["loginshell"] = $shell;
			// Modification du homeDirectory
			if ( $shell == "/usr/lib/sftp-server" )
			$entry["homedirectory"] = "/home/".$user[0]["uid"]."/./";
			else $entry["homedirectory"] = "/home/".$user[0]["uid"];
			if ( $mail != "" ) $entry["mail"] = $mail;
			if ( $telephone && verifTel($telephone) ) $entry["telephonenumber"]=$telephone ;
			if ( $description && verifDescription($description) ) $entry["description"]=utf8_encode(stripslashes($description));

			if ($naissance!='' && verifDateNaissance($naissance)) {
				if(isset($entry["gecos"])) {
					$tmp_tab=explode(",", $entry["gecos"]);
					if(isset($tmp_tab[3])) {
						$entry["gecos"]=$tmp_tab[0].",".$naissance.",".$tmp_tab[2].",".$tmp_tab[3];
					}
					else {
						echo "<div class=\"error_msg\">".gettext("L'attribut gecos n'est pas correctement renseign&#233;.")."</div><br />\n";
					}
				}
			}

			if($employeeNumber!="") {$entry["employeeNumber"] = $employeeNumber;}
		}
		// Modification des entrees
		$ds = @ldap_connect ( $ldap_server, $ldap_port );
		if ( $ds ) {
			$r = @ldap_bind ( $ds, $adminDn, $adminPw ); // Bind en admin
			if ($r) {
				if (@ldap_modify ($ds, "uid=".$uid.",".$dn["people"],$entry)) {
					echo "<strong>".gettext("Les entr&#233;es ont &#233;t&#233; modifi&#233;es avec succ&#232;s.")."</strong><br />\n";
				} else {
					echo "<strong>".gettext("Echec de la modification, veuillez contacter")." </strong><A HREF='mailto:$MelAdminLCS?subject=PB modification entrees utilisateur'>".gettext("l'administrateur du syst&#232;me")."</A><br />\n";
				}
			}
			@ldap_close ( $ds );

			if((isset($user[0]["employeeNumber"]))&&($user[0]["employeeNumber"]!="")&&($employeeNumber=='')) {
				// Il faut vider l'employeeNumber
				$tab_attr=array();
				$tab_attr["employeeNumber"]=$user[0]["employeeNumber"];
				if(modify_attribut ("uid=".$uid, "people", $tab_attr, "del")) {
					echo "<strong>".gettext("L'employeeNumber a &#233;t&#233; correctement vid&#233;.")."</strong><br />\n";
				}
				else {
					echo "<strong>".gettext("Echec de la modification de l'employeeNumber, veuillez contacter")." </strong><A HREF='mailto:$MelAdminLCS?subject=PB modification entrees utilisateur'>".gettext("l'administrateur du syst&#232;me")."</A><br />\n";
				}
			}

		} else {
			echo gettext("Erreur de connection &#224; l'annuaire, veuillez contacter")." </strong><A HREF='mailto:$MelAdminLCS?subject=PB connection a l'annuaire'>".gettext("l'administrateur du syst&#232;me</A>administrateur")."<br />\n";
		}
	
		// Fin modification des entrees
		// Changement du mot de passe
		if ( $userpwd && verifPwd($userpwd) ) {
			userChangedPwd($uid, $userpwd);
		}

		echo "<p><a href='".$_SERVER['PHP_SELF']."?uid=".$uid."'>Retour vers la fiche de $uid</a></p>\n";
	}
} else {
	echo "<div class=error_msg>".gettext("Cette fonctionnalit&#233; n&#233;cessite des droits d'administration SambaEdu !")."</div>";
}



include ("pdp.inc.php");
?>
