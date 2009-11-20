<?php


   /**

   * Modifie une entree utilisateur
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
   * file: mod_entry.php
   */



require "config.inc.php";
require "functions.inc.php";
require "ldap.inc.php";
require "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

$login=isauth();
if ($login == "") header("Location:$urlauth");

// Recuperation des entrees de l'utilisateur a modifier
$people_attr=people_get_variables ($login, false);
$people_attr[0]["prenom"]=getprenom($people_attr[0]["fullname"],$people_attr[0]["nom"]);


if (is_admin("Annu_is_admin",$login)=="Y") {
	// Redirection vers mod_user_entry.php
    	header("Location:mod_user_entry.php?uid=$login");
} else {
	// header_html();
	include ("entete.inc.php");

	// Aide
	$_SESSION["pageaide"]="L\'interface_&#233;l&#232;ve#Modifier_mon_compte";


	// Variables
	$nom = $_POST['nom'];
	$prenom = $_POST['prenom'];
	$telephone = $_POST['telephone'];
	$mod_entry = $_POST['mod_entry'];

	echo "<h1>".gettext("Annuaire")."</h1>\n";

	aff_trailer ("4");


	//====================================
	// Ajout crob pour restreindre l'acces au changement des infos de la fiche
	// Pour interdire:
	// echo "insert into params set name='crob_ele_modif_fich', value='n';" | mysql -uroot se3db
	// ou
	// echo "update params set value='n' where name='crob_ele_modif_fich';" | mysql -uroot se3db

	// Pour autoriser: (situation par défaut)
	// echo "insert into params set name='crob_ele_modif_fich', value='y';" | mysql -uroot se3db
	// ou
	// echo "update params set value='y' where name='crob_ele_modif_fich';" | mysql -uroot se3db
	if(isset($crob_ele_modif_fich)){
		if($crob_ele_modif_fich=='n'){
			if(are_you_in_group ($login, 'Eleves')){
?>
        	<table border="0" width="90%" align="center">
	  	<tbody>
	    	<tr>
	      	   <td width="27%">Login :&nbsp;</td>
              	   <td width="73%" colspan="2"><tt><strong><?php echo $people_attr[0]["uid"]?></strong></tt></td>
	    	</tr>
	    	<tr>
	      	   <td width="27%"><?php echo gettext("Pr&#233;nom"); ?> :&nbsp;</td>
              	   <td width="73%" colspan="2"><?php echo $people_attr[0]["prenom"]?></td>
	    	</tr>
	    	<tr>
	      	<td><?php echo gettext("Nom"); ?>&nbsp;:&nbsp;</td>
	      	   <td colspan="2"><?php echo $people_attr[0]["nom"]?></td>
	    	</tr>
	    	<tr>
	      	   <td><?php echo gettext("T&#233;l&#233;phone"); ?> :&nbsp;</td>
	           <td colspan="2"><?php echo $people_attr[0]["tel"] ?></td>
	    	</tr>
	  	</tbody>
        	</table>
<?php
				include ("pdp.inc.php");
				exit();
			}
		}
	}
	//====================================



       // Changement parametres  pour l'utilisateur de «base»
       // Interface pour permettre les modifs
 	if ( (!$mod_entry) || ( !verifTel($telephone) || !verifEntree($nom) || !verifEntree($prenom)  ) ) {
      ?>
      		<form action="mod_entry.php" method="post">
        	<table border="0" width="90%" align="center">
	  	<tbody>
	    	<tr>
	      	   <td width="27%">Login :&nbsp;</td>
              	   <td width="73%" colspan="2"><tt><strong><?php echo $people_attr[0]["uid"]?></strong></tt></td>
	    	</tr>
	    	<tr>
	      	   <td width="27%"><?php echo gettext("Pr&#233;nom"); ?> :&nbsp;</td>
              	   <td width="73%" colspan="2"><input type="text" name="prenom" value="<?php echo $people_attr[0]["prenom"]?>" size="20"></td>
	    	</tr>
	    	<tr>
	      	<td><?php echo gettext("Nom"); ?>&nbsp;:&nbsp;</td>
	      	   <td colspan="2"><input type="text" name="nom" value="<?php echo $people_attr[0]["nom"]?>" size="20"></td>
	    	</tr>
	    	<tr>
	      	   <td><?php echo gettext("T&#233;l&#233;phone"); ?> :&nbsp;</td>
	           <td colspan="2"><input type="text" name="telephone" value="<?php echo $people_attr[0]["tel"] ?>" size="10"></td>
	    	</tr>
	    	<tr>
	      	   <td></td>
                   <td >
                	<input type="hidden" name="mod_entry" value="true">
                	<input type="submit" value="<?php echo gettext("Lancer la requ&#234;te"); ?>">
              	   </td>
	      	   <td></td>
	    	</tr>
	  	</tbody>
        	</table>
      		</form>

		<?php
      		if ($mod_entry && (( !verifEntree($nom) || !verifEntree($prenom) ) || ( !verifTel($telephone) ) )) {
        		// verification des saisies
        		// nom prenom
        		if ( !verifEntree($nom) || !verifEntree($prenom) ) {
          			echo "<div class=\"error_msg\">".gettext("Les champs nom et prenom, doivent comporter au minimum 4 caract&#232;res alphanum&#233;riques.")."</div><BR>\n";
        		}
        		// tel
        		if ( !verifTel($telephone) ) {
          			echo "<div class=\"error_msg\">".gettext("Le num&#233;ro de t&#233;l&#233;phone que vous avez saisi, n'est pas conforme.")."</div><BR>\n";
        		}
      		}
       		// fin verification des saisies
	} else {
      		// Positionnement des entrees a modifier
      		$entry["sn"] = utf8_encode($nom);
      		$entry["cn"] = utf8_encode($prenom) . " " .  utf8_encode($nom);
      		if ( $telephone && verifTel($telephone) ) { $entry["telephonenumber"]=$telephone ; }

      		// Modification des entrees
      		$ds = @ldap_connect ( $ldap_server, $ldap_port );
      		if ( $ds ) {
        		$r = @ldap_bind ( $ds, $adminDn, $adminPw ); // Bind en admin
        		if ($r) {
          			if (@ldap_modify ($ds, "uid=".$people_attr[0]["uid"].",".$dn["people"],$entry)) {
            				echo "<strong>".gettext("Vos entr&#233;es ont &#233;t&#233; modifi&#233;e avec succ&#232;s.")."</strong><BR>\n";
          			} else {
            				echo "<strong>".gettext("Echec de la modification, veuillez contacter")." </strong><A HREF='mailto:$MelAdminLCS?subject=PB modification des entrees admin'>".gettext("l'administrateur du syst&#232;me")."</A><BR>\n";
          			}
        		}
        		@ldap_close ( $ds );
      		} else {
        		echo gettext("Erreur de connection &#224; l'annuaire, veuillez contacter")." </strong><A HREF='mailto:$MelAdminLCS?subject=PB connection a l'annuaire'>".gettext("l'administrateur du syst&#232;me")."</A>".gettext("administrateur")."<BR>\n";
      		}

		// Fin modifications
    	}
}


include ("pdp.inc.php");
?>
