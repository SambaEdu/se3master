<?php


   /**
   
   * Modifie le mot de passe
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
   * file: mod_pwd.php
   */





require "config.inc.php";
require "functions.inc.php";


$login=isauth();
  if ($login == "") header("Location:$urlauth");

require "ldap.inc.php";
require "ihm.inc.php";
require "jlcipher.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

// HTMLPurifier
require_once ("traitement_data.inc.php");

header_crypto_html(gettext("Modification mot de passe"),"../");

$DEBUG="0";

// Aide
@session_start();
$_SESSION["pageaide"]="L%27interface_%C3%A9l%C3%A8ve#Mon_mot_de_passe";

echo "<h1>".gettext("Changement du mot de passe")."</h1>\n";
aff_trailer ("5");




//====================================
// Ajout crob pour restreindre l'acces au changement de mot de passe
if(isset($crob_ele_modif_pwd)){
	if($crob_ele_modif_pwd=='n'){
		if(are_you_in_group ($login, 'Eleves')){
			echo gettext("<h3>Changement de mot de passe</h3>");
			echo "Modification interdite.";
			include ("pdp.inc.php");
			exit();
		}
	}
}
//====================================



if ($_POST['mod_pwd']) {
        // decryptage des mdp
	$string_auth=$_POST['string_auth'];
	$string_auth1=$_POST['string_auth1'];
        exec ("/usr/bin/python ".$path_to_wwwse3."/includes/decode.py '$string_auth'",$Res);
        $new_password = $Res[0];
        exec ("/usr/bin/python ".$path_to_wwwse3."/includes/decode.py '$string_auth1'",$Res1);
        $verif_password = $Res1[0];
        #DEBUG
        if ($DEBUG=="1") {
                echo "crypto new mdp  :  $string_auth<br>crypto verif mdp  : $string_auth1<br>";
                echo "old_mdp : ".$_POST['old_password']." new mdp  : $new_password verif mdp  : $verif_password<br>";
        }
}

// teste si il faut reservir le formulaire de saisie
if ( (!$_POST['mod_pwd']) ||
        (($_POST['mod_pwd'])&&(!verifPwd($new_password))) ||
        (($_POST['mod_pwd'])&&($new_password != $verif_password)) ||
        (($_POST['mod_pwd'])&&(!user_valid_passwd ( $login, $_POST['old_password'] )))
     ) {

    echo gettext("<h3>Changement de mot de passe</h3>");
    ?>
	 <form name = "auth" action="mod_pwd.php" method="post" onSubmit = "encrypt(document.auth)">
        <table border="0">
          <tr>
            <td><?php echo gettext("Mot de passe actuel");?> : </td>
            <td><input type="password" name="old_password" size="20"></td>
          </tr>
          <tr>
            <td><?php echo gettext("Nouveau mot de passe");?> : </td>
            <td>
                    <input type= "password" value="" name="dummy" size='20'  maxlength='20'>
                    <input type="hidden" name="string_auth" value="">
			</td>
          </tr>
          <tr>
            <td><?php echo gettext("Ressaisir nouveau mot de passe");?> : </td>
            <td>
                    <input type= "password" value="" name="dummy1" size='20'  maxlength='20'>
                    <input type="hidden" name="string_auth1" value="">
			</td>
          <tr>
          <tr>
            <td colspan=2 align=center>
              <input type="hidden" name="mod_pwd" value="true">
              <input type="submit" value=<?php echo gettext("Valider"); ?>>
            </td>
          </tr>
        </table>
      </form>
    <?php
	crypto_nav("../");
    if( $_POST['mod_pwd'] )  {
      // Verification de l'ancien mot de passe
      if (! user_valid_passwd ( $login, $_POST['old_password'] ) ) {
        echo "<div class='error_msg'>".gettext("Votre mot de passe actuel est erron&#233; !")."</div><BR>\n";
       }
      // Verification du nouveau mot de passe
       elseif ( !verifPwd($new_password)  ) {
         echo "<div class='error_msg'>".gettext("Vous devez proposer un mot de passe d'une longueur comprise entre 4 et 8 caract&#232;res alphanum&#233;riques avec obligatoirement un des caract&#232;res sp&#233;ciaux suivants")." $char_spec</div><BR>\n";
      }
      // Verification de la coherence des deux mots de passe
       elseif ( $new_password != $verif_password ) {
        echo "<div class='error_msg'>".gettext("La v&#233;rification de votre nouveau mot de passe a &#233;chou&#233;e !")."</div><BR>\n";
      }
    }
} else {
    // Changement du mot de passe
    userChangedPwd($login, $new_password);
}

require ("pdp.inc.php");
?>
