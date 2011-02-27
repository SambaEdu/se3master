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

$uid=$_GET['uid'];
if ($uid=="") { $uid=$_POST['uid']; }

$user_entry=$_POST['user_entry'];
$telephone=$_POST['telephone'];
$nom=$_POST['nom'];
$prenom=$_POST['prenom'];
$description=$_POST['description'];
$userpwd=$_POST['userpwd'];
$shell=$_POST['shell'];
$password=$_POST['password'];
$string_auth=$_POST['string_auth'];


if (($isadmin=="Y") or ((tstclass($login,$uid)==1) and (ldap_get_right("sovajon_is_admin",$login)=="Y"))) {
	// Recuperation des entrees de l'utilisateur a modifier
    	$user=people_get_variables ($uid, false);
	// decodage du mot de passe
	if ($user_entry) {
        	// decryptage des mdp
        	exec ("/usr/bin/python ".$path_to_wwwse3."/includes/decode.py '$string_auth'",$Res);
         	$userpwd = $Res[0];
  	}
    	// Modification des entrees
    	if ( !$user_entry || !verifTel($telephone) || !verifEntree($nom) || !verifEntree($prenom) || !verifDescription($description) || ($userpwd && !verifPwd($userpwd)) ) {
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
            <?php if ($isadmin=="Y") {
	    ?>
	      <td><?php echo gettext("Adresse m&#232;l"); ?>&nbsp;:&nbsp;</td>
	      <td colspan="2"><input type="text" name="mail" value="<?php echo $user[0]["email"]?>" size="20"></td>
	    </tr>
	    <tr>
	      <td><em>Shell&nbsp;</em> :&nbsp;</td>
	      <td>
                <select name="shell">
                  <option <?php if ($user[0]["shell"] == "/bin/bash") echo "selected" ?>>/bin/bash</option>
                  <option <?php if ($user[0]["shell"] == "/bin/true") echo "selected" ?>>/bin/true</option>
                  <option <?php if ($user[0]["shell"] == "/usr/lib/sftp-server") echo "selected" ?>>/usr/lib/sftp-server</option>
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
                    <input type= "password" value="" name="dummy" size='8'  maxlength='10'>
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
      if ($user_entry) {
        // verification des saisies
        // nom prenom
        if ( !verifEntree($nom) || !verifEntree($prenom) ) {
          echo "<div class=\"error_msg\">".gettext("Les champs nom et prenom, doivent comporter au minimum 3 caract&#232;res alphab&#233;tiques.")."</div><BR>\n";
        }
        // profil
        if ( $description && !verifDescription($description) ) {
          echo "<div class=\"error_msg\">".gettext("Veuillez reformuler le champ description.")."</div><BR>\n";
        }
        // tel
        if ( $telephone && !verifTel($telephone) ) {
          echo "<div class=\"error_msg\">".gettext("Le num&#233;ro de t&#233;l&#233;phone que vous avez saisi, n'est pas conforme.")."</div><BR>\n";
        }
        // mot de passe
        if ( $userpwd && !verifPwd($userpwd) ) {
          echo "<div class='error_msg'>";
          echo gettext("Vous devez proposer un mot de passe d'une longueur comprise entre 4 et 8 caract&#232;res
                 alphanum&#233;riques avec obligatoirement un des caract&#232;res sp&#233;ciaux suivants");
	  echo " ($char_spec) </div><BR>\n";
        }
        // fin verification des saisies
      }
    } else {
      // Positionnement des entrees a modifier
      //$entry["sn"] =  stripslashes ( utf8_encode($nom) );
      //$entry["cn"] = stripslashes ( utf8_encode($prenom)." ".utf8_encode($nom) );
      $entry["sn"] =  stripslashes ( ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz"))) );
      $entry["cn"] = stripslashes ( ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")))." ".ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz"))) );

      //======================================
      // Correction du gecos:
      //echo "\$user[0][\"gecos\"]=".$user[0]["gecos"]."<br />";
      if($user[0]["gecos"]!="") {
         $tab_gecos=explode(",",$user[0]["gecos"]);
         //$entry["gecos"]=ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")))." ".ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz"))).",".$tab_gecos[1].",".$tab_gecos[2].",".$tab_gecos[3];
         $entry["gecos"]=ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")))." ".ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$nom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz"))).",".$tab_gecos[1].",".$tab_gecos[2].",".$tab_gecos[3];
      }

      if($corriger_givenname_si_diff=="y") {
        // Ajout: crob 20080611
        // Variable initialisée dans includes/ldap.inc.php: $corriger_givenname_si_diff
        // placée pour permettre de désactiver temporairement cette partie

        // Le givenName est destiné à prendre pour valeur le Prenom de l'utilisateur
        //$entry["givenName"] = ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom"))))," 'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")));
        $entry["givenName"] = ucfirst(strtolower(strtr(preg_replace("/Æ/","AE",preg_replace("/æ/","ae",preg_replace("/¼/","OE",preg_replace("/½/","oe","$prenom")))),"'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚİ¾´áàâäãåçéèêëîïìíñôöğòóõ¨ûüùúıÿ¸","_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz")));
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
      }
      // Modification des entrees
      $ds = @ldap_connect ( $ldap_server, $ldap_port );
      if ( $ds ) {
        $r = @ldap_bind ( $ds, $adminDn, $adminPw ); // Bind en admin
        if ($r) {
          if (@ldap_modify ($ds, "uid=".$uid.",".$dn["people"],$entry)) {
            echo "<strong>".gettext("Les entr&#233;es ont &#233;t&#233; modifi&#233;es avec succ&#232;s.")."</strong><BR>\n";
          } else {
            echo "<strong>".gettext("Echec de la modification, veuillez contacter")." </strong><A HREF='mailto:$MelAdminLCS?subject=PB modification entrees utilisateur'>".gettext("l'administrateur du syst&#232;me")."</A><BR>\n";
          }
        }
        @ldap_close ( $ds );
      } else {
        echo gettext("Erreur de connection &#224; l'annuaire, veuillez contacter")." </strong><A HREF='mailto:$MelAdminLCS?subject=PB connection a l'annuaire'>".gettext("l'administrateur du syst&#232;me</A>administrateur")."<BR>\n";
      }

      // Fin modification des entrees
      // Changement du mot de passe
      if ( $userpwd && verifPwd($userpwd) ) {
        userChangedPwd($uid, $userpwd);
      }
    }
  } else {
    echo "<div class=error_msg>".gettext("Cette fonctionnalit&#233; n&#233;cessite des droits d'administration SambaEdu !")."</div>";
}



include ("pdp.inc.php");
?>
