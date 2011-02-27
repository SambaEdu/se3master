<?php


   /**
   
   * Ajoute des utilisateurs dans l'annuaire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note Modifie par Adrien CRESPIN -- Lycee Suzanne Valadon
   */

   /**

   * @Repertoire: annu
   * file: add_user.php
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


header_crypto_html("Creation utilisateur","../");
echo "<h1>".gettext("Annuaire")."</h1>\n";

@session_start();
$_SESSION["pageaide"]="Annuaire";
aff_trailer ("7");

$nom=$_POST['nom'];
$prenom=$_POST['prenom'];
$naissance=$_POST['naissance'];
$userpw=$_POST['userpwd'];
$sexe=$_POST['sexe'];
$categorie=$_POST['categorie'];
$add_user=$_POST['add_user'];
$string_auth=$_POST['string_auth'];
$string_auth1=$_POST['string_auth1'];
$dummy=$_POST['dummy'];
$dummy1=$_POST['dummy1'];



if (is_admin("Annu_is_admin",$login)=="Y") {
       if ( $add_user && ($string_auth || $string_auth1) ) {
			exec ("/usr/bin/python ".$path_to_wwwse3."/includes/decode.py '$string_auth'",$Res);
        	$naissance = $Res[0];
			exec ("/usr/bin/python ".$path_to_wwwse3."/includes/decode.py '$string_auth1'",$Res1);
        	$userpwd = $Res1[0];
		}
    // Ajout d'un utilisateur
    if (    ( !$nom || !$prenom )    // absence de nom ou de prenom
         || ( !$naissance && ( !$userpwd || ( $userpwd && !verifPwd($userpwd) ) ) ) // pas de date de naissance et mot de passe absent ou invalide
         || ( $naissance && !verifDateNaissance($naissance) )  // date de naissance invalide
         || ( ($naissance && verifDateNaissance($naissance)) && ($userpwd && !verifPwd($userpwd)) )  // date de naissance mais password invalide
       ) {
      ?>
	  <form name = "auth" action="add_user.php" method="post" onSubmit = "encrypt(document.auth)">
        <table border="0">
          <tbody>
            <tr>
              <td><?php echo gettext("Nom :"); ?></td>
              <td colspan="2" valign="top"><input type="sn" name="nom" value="<?php echo $nom ?>" size="20"></td>

            </tr>
            <tr>
              <td><?php echo gettext("Pr&#233;nom :"); ?></td>
              <td colspan="2" valign="top"><input type="cn" name="prenom" value="<?php echo $prenom ?>" size="20"></td>

            </tr>
            <tr>
              <td><?php echo gettext("Date de naissance :"); ?></td>
              <td>
		<input type="texte" name="dummy" value="<?php echo $naissance ?>" size="8">
		<input type="hidden" name="string_auth" value="">
		</td>
              <td>
                <font color="#FF9900">
                  &nbsp;<?php echo gettext("(YYYYMMDD) ce champ est optionnel, mais s'il n'est pas renseign&#233;, le champ mot de passe est obligatoire."); ?>
                </font>
              </td>
            </tr>
            <tr>
              <td><?php echo gettext("Mot de passe :"); ?></td>
              <td>
					<input type= "password" value="" name="dummy1" size='8'  maxlength='8'>
					<input type="hidden" name="string_auth1" value="">
		      </td>
              <td>
                <font color="#FF9900">
                  &nbsp;<?php echo gettext("Si le champ mot de passe est laiss&#233; vide, c'est la date de naissance qui sera utilis&#233;e."); ?>
                </font>
              </td>
            </tr>
            <tr>
              <td><?php echo gettext("Sexe :"); ?></td>
              <td colspan="2">
                <img src="images/gender_girl.gif" alt="F&#233;minin" width="14" height="14" hspace="4" border="0">
                <?php
                  echo "<input type=\"radio\" name=\"sexe\" value=\"F\"";
                  if (($sexe=="F")||(!$add_user)) echo " checked";
                  echo ">&nbsp;\n";
                ?>
                <img src="images/gender_boy.gif" alt="Masculin" width=14 height=14 hspace=4 border=0>
                <?php
                  echo "<input type=\"radio\" name=\"sexe\" value=\"M\"";
                  if ($sexe=="M") echo " checked";
                  echo ">&nbsp;\n";
                ?>
              </td>
            </tr>
            <tr>
              <td><?php echo gettext("Cat&#233;gorie"); ?></td>
              <td colspan="2" valign="top">
                <select name="categorie">
                  <?php
                    echo "<option value=\"Eleves\"";
                    if ($categorie  == "Eleves" ) echo "SELECTED";
                    echo ">".gettext("El&#232;ves")."</option>\n";
                    echo "<option value=\"Profs\"";
                    if ($categorie  == "Profs" ) echo "SELECTED";
                    echo ">".gettext("Profs")."</option>\n";
                    echo "<option value=\"Administratifs\"";
                    if ($categorie  == "Administratifs" ) echo "SELECTED";
                    echo ">".gettext("Administratifs")."</option>\n";
                  ?>
                </select>
              </td>
            </tr>
            <tr>
              <td></td>
              <td></td>
	      <td >
                <input type="hidden" name="add_user" value="true">
                <input type="submit" value="<?php echo gettext("Lancer la requ&#234;te"); ?>">
              </td>
            </tr>
          </tbody>
        </table>
      </form>
      <?php
	    crypto_nav("../");
        if ($add_user) {
          if ( (!$nom)||(!$prenom)) {
            echo "<div class=error_msg>".gettext("Vous devez obligatoirement renseigner les champs : nom, pr&#233;nom !")."</div><br>\n";
          } elseif ( !$naissance && !$userpwd ) {
            	echo "<div class='error_msg'>";
             	echo gettext("Vous devez obligatoirement renseigner un des deux champs «mot de passe» ou «date de naissance».");
             	echo "</div><BR>\n";
          } else {
            	if ( ($userpwd) && !verifPwd($userpwd) ){
              		echo "<div class='error_msg'>";
                    	echo gettext("Vous devez proposer un mot de passe d'une longueur comprise entre 4 et 8 caract&#232;res
                    alphanum&#233;riques avec obligatoirement un des caract&#232;res sp&#233;ciaux suivants")."&nbsp;".$char_spec."&nbsp;".gettext("ou &#224; d&#233;faut laisser le champ mot de passe vide et dans ce cas c'est la date de naissance
                    qui sera utilis&#233;e.")."
                  </div><BR>\n";
            }
            if ( ($naissance) && !verifDateNaissance($naissance) ){
              	echo "<div class='error_msg'>";
                echo gettext("Le champ date de naissance doit &#234;tre obligatoirement au format Ann&#233;eMoisJour (YYYYMMDD).");
                echo "</div><BR>\n";
            }
          }
        }

    } else {
      	// Verification si ce nouvel utilisateur n'existe pas deja
      	$prenom = stripslashes($prenom); $nom = stripslashes($nom);
      	$cn =utf8_encode($prenom." ".$nom);
      	$people_exist=search_people("(cn=$cn)");

      	if (count($people_exist)) {
        	echo "<div class='error_msg'>";
                echo gettext("Echec de cr&#233;ation : L'utilisateur")." <font color=\"black\"> $prenom $nom</font>".gettext(" est d&#233;ja pr&#233;sent dans l'annuaire.");
              	echo "</div><BR>\n";
      	} else {
        	// Positionnement de la date de naissance ou du mot de passe par defaut
        	if (!$naissance ) $naissance="00000000";
        		if (!$userpwd ) $userpwd=$naissance;
        		// Creation du nouvel utilisateur
        		exec ("/usr/share/se3/sbin/userAdd.pl \"$prenom\" \"$nom\" \"$userpwd\" \"$naissance\" \"$sexe\" \"$categorie\"",$AllOutPut,$ReturnValue);
        		// Compte rendu de creation
        		if ($ReturnValue == "0") {
	  			if($sexe=="M"){
            				echo gettext("L'utilisateur ")." $prenom $nom ".gettext(" a &#233;t&#233; cr&#233;&#233; avec succ&#232;s.")."<BR>";
	  			} else {
            				echo gettext("L'utilisateur ")." $prenom $nom ".gettext(" a &#233;t&#233; cr&#233;&#233;e avec succ&#232;s.")."<BR>";
	  			}
	  		$users = search_people ("(cn=$cn)");
	  		if ( count ($users) ) {
	  			echo gettext("Son identifiant est ")."<STRONG>".$users[0]["uid"]."</STRONG><BR>\n";
				echo "<LI><A HREF=\"add_user_group.php?uid=".$users[0]["uid"]."\">".gettext("Ajouter &#224; des groupes...")."</A>\n";
	  		}
        	} else {
         	 	echo "<div class='error_msg'>".gettext("Erreur lors de la cr&#233;ation du nouvel utilisateur")." $prenom $nom
                  	<font color='black'>(".gettext("type d'erreur :")." $ReturnValue)
                  	</font>,".gettext(" veuillez contacter")."
                  	<A HREF='mailto:$MelAdminLCS?subject=PB creation nouvel utilisateur Se3'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
        	}
      }
    }
} else {
	echo "<div class=error_msg>".gettext("Cette application, n&#233;cessite les droits d'administrateur du serveur SambaEdu !")."</div>";
}

include ("pdp.inc.php");
?>
