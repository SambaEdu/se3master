<?php


   /**
   
   * Permet de creer un partage
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs Equipe TICE Crdp de Caen
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: partages/
   * file: create_share.php

  */	

  
  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-partages',"/var/www/se3/locale");
textdomain ('se3-partages');

foreach ($_POST as $cle=>$val) {
    $$cle = $val;
        }

 // Fonctions internes a create_share.php

/**
* Verifie si l'entree est bien un groupe ou un uid
* @Parametres $entry l'entree a tester
* @return true ou false
*/


function valid_uid_or_group($entry) {
  	if ($entry !="") {
		// Splitage de $share_user_list
		$tmp = preg_split ("/\[ \]/",$entry,20);
		for ( $loop=0; $loop < count($tmp); $loop++) {
			// Recherche si c'est un utilisateur ou un groupe
			$return=strpos( $tmp[$loop], "@");
			if ( strpos( $tmp[$loop], "@") === 0) {
				// Cette entree est un groupe
				$name_group = substr ($tmp[$loop], 1);
				// Recherche si le groupe existe
				$group=search_groups ("(cn=".$name_group.")");
				if ( count ($group) == 0 ) return false;
			} else {
				// Cette entree est un uid
				// Recherche si l'uid existe
				$users = search_people ("(uid=".$tmp[$loop].")");
				if ( count ($users) == 0 ) return false;
			}
		}
	}
	return true;
  }



/**
* Supprime les accents d'une chaine
* @Parametres $chaine a nettoyer
* @return la chaine sans accent
*/



 function cleanEntry ($chaine) {
  $chaine = stripslashes(strtr($chaine,
                  "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ ",
                  "aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn_"));
  return $chaine;
}


/**
* Normalise un path (supprime les accents et les espaces, met en minuscule ...
* @Parametres $path a nettoyer
* @return le chemin sans accent ...
*/


function cleanPath ( $path ) {
	// Enleve les accents et «minusculise»
	$path = strtolower(cleanEntry($path));
	// Enleve les .,;:!&~#'{}()[]`^@°+-
	$path = strtr($path,"\.,;:!&~#'{}()[]`^@§°+-*","                        ");
	// Enleve les espaces
	$path = str_replace(" ", "", $path);
	// Enleve / en debut et en fin du chemin
	while (preg_match("/^\//",$path) ) $path = preg_replace ("/^\//", "", $path);
        while (preg_match("/\/$/",$path) ) $path = preg_replace ("/\/$/", "", $path);
	return $path;
}





  if (is_admin("se3_is_admin",$login)=="Y") {

	//aide
	$_SESSION["pageaide"]="Ressources_et_partages";


	echo "<h1>".gettext("Cr&#233;ation de partage")."</h1>";

	// Definition des messages d'alerte
	$alerte_1="<div class='error_msg'>\n".gettext("Votre demande de cr&#233ation d'un nouveau partage n'a pas &#233t&#233 prise en compte car une t&#226;che d'administration est en cours sur le serveur")." <b>\n";
	$alerte_2="</b>,".gettext(" veuillez r&#233it&#233rer votre demande plus tard. Si le probl&#232;me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";
	$alerte_3="<div class='error_msg'>".gettext("Votre demande de cr&#233ation d'un nouveau partage a &#233chou&#233. Si le probl&#232;me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";

	// gettext a revoir: boireaus: J'ai ajoute un 's' a 'incoherente' et vire un 'e'
	$alerte_4="<div class='error_msg'>".gettext("Votre demande de cr&#233ation d'un nouveau partage a &#233chou&#233 car les informations collect&#233es sont incoh&#233rentes !!")."\n";

	// gettext a revoir: boireaus: J'ai ajoute modifie
	$share_name_alert = "<div class='error_msg'><li>".gettext("Le partage")." <strong>$share_name</strong> ".gettext("est d&#233ja pr&#233sent dans un des fichiers smb*.conf.")."\n";

	$share_user_list_alert = "<div class='error_msg'><li>".gettext("La liste des Utilisateur(s) ou groupe(s) autoris&#233s ou refus&#233s comporte une ou des entr&#233es invalides.")."\n";
	$share_admin_alert = "<div class='error_msg'><li>".gettext("La liste des utilisateur(s) ou groupe(s) ayant les droits d'administration sur ce partage  comporte une ou des enr&#233es invalides.")."\n";
	// Definition des messages d'info
	$info_1 = gettext("Cette t&#226;che est ordonnanc&#233e, vous recevrez un m&#232;l de confirmation de cr&#233ation dans quelques instants...");

	// A gettextiser: boireaus
	$Alert_ShareName_User_Ldap=gettext("<div class='error_msg'>Le nom de partage ne doit pas &#234tre le nom de login d'un utilisateur dans l'annuaire LDAP.</div>");

    	#------------------------------------------
    	// Prepositionnement variables
	$Verif_Empty = true;

    	if ( mono_srv() ) {
		// configuration mono serveur  : determination des parametres du serveur
		$serveur=search_machines ("(l=maitre)", "computers");
		$cn_srv= $serveur[0]["cn"];
		$stat_srv = $serveur[0]["l"];
		$ipHostNumber =  $serveur[0]["ipHostNumber"];
    	} else {
 		// configuration multi-serveurs : presentation d'un form de selection du serveur
		if ( !$selected_srv && !$End_ph1) {
			echo "<H3>".gettext("S&#233lection du serveur ou vous souhaitez cr&#233er ce partage :")." </H3>\n";
			$servers=search_computers ("(|(l=esclave)(l=maitre))");
			echo "<form action=\"create_share.php\" method=\"post\">\n";
 			for ($loop=0; $loop < count($servers); $loop++) {
				echo $servers[$loop]["description"]." ".$servers[$loop]["cn"]."&nbsp;<input type=\"radio\" name=\"cn_srv\" value =\"".$servers[$loop]["cn"]."\"";
				if ($loop==0) echo "checked";
				echo "><BR>\n";
			}
        		$form="<input type=\"reset\" value=\"".gettext("R&#233initialiser la s&#233lection")."\">\n";
        		$form ="<input type=\"hidden\" name=\"selected_srv\" value=\"true\">\n";
        		$form.="<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
        		$form.="</form>\n";
        		echo $form;
		} else {
        		// Determination des parametres du serveur cible dans le cas d'une conf multi-serveurs
        		$serveur=search_machines ("(cn=$cn_srv)", "computers");
        		$stat_srv = $serveur[0]["l"];
        		$ipHostNumber =  $serveur[0]["ipHostNumber"];
      		}
 	}
    	// Fin selection et recherche des caracteristiques du serveur

	// Recherche si il y a des champs obligatoires vides
	if ( $End_ph1 && ( !$share_name || !$share_path ) )  $Verif_Empty = false;

	// Phase 1 : Saisie des donnees
	if ( ( !$End_ph1 &&  $stat_srv ) || ($End_ph1 && $Verif_Empty == false ) ) {
		echo "<H3>".gettext("Cr&#233ation d'un partage sur ")."$cn_srv : </h3>\n";
		echo "<H6>".gettext("[Phase1] Collecte des informations relatives au partage :")."</h6>\n";
		// Liste des parcs
		$list_parcs=search_machines("objectclass=groupOfNames","parcs");
		// Preparation du formulaire
		$form ="<form action='create_share.php' method='post'>\n";
		$form .="<table border='0'><tr>\n";
		// Nom du partage
		$form .="<td colspan='2'>".gettext("Nom du partage")."</td>\n";
		$form .="<td><input type='text' name='share_name' size='15'></td>\n";
		$form .="<td>&nbsp;*</td>\n";
		$form .="</tr><tr>\n";
		// Chemin du partage
		$form .="<td colspan='2'>".gettext("Chemin")." /var/se3/</td>\n";
		$form .="<td><input type='text' name='share_path' size='15'></td>\n";
		$form .="<td>&nbsp;*</td>\n";
		// Commentaire
		$form .="</tr><tr>\n";
		$form .="<td colspan='2'>".gettext("Commentaire")."</td>\n";
		$form .="<td><input type='text' name='share_comment' size='15'></td>\n";
		$form .="<td></td>\n";
		// Groupe propietiare du rep correspondant au partage
		$form .="</tr><tr>\n";
		$form .="<td colspan='2'>".gettext("Groupe propri&#233;taire du r&#233;pertoire")."</td>\n";
		$form .="<td><select name='dir_grp_owner'>
		<option value=\"$defaultgid\">".gettext("Tous")."</option>
		<option value=\"eleves\">".gettext("El&#232;ves")."</option>
		<option value=\"profs\">".gettext("Profs")."</option>
		<option value=\"admins\">".gettext("Admins")."</option>
		</select>
		<select name='dir_grp_perm'>
		<option value=\"rwx\">".gettext("Ecriture")."</option>
		<option value=\"rx\">".gettext("Lecture")."</option>
		</select>
		</td>\n";
		$form .="<td></td>\n";
		$form .="</tr><tr>\n";
		// Droits pour les autres
		$form .="</tr><tr>\n";
		$form .="<td colspan='2'>".gettext("Droits pour les autres")."</td>\n";
		$form .="<td><select name='dir_rights_other'>
		<option value=\"rx-\">".gettext("Lecture")."</option>
		<option value =\"---\">".gettext("Aucun")."</option>
		</select></td>\n";
		$form .="<td></td>\n";
		$form .="</tr><tr>\n";
		// Utilisateurs autorises ou refuses
		$form .="<td>Utilisateur(s)</td>\n";
		$form .="<td><select name='share_user_type'><option value=\"autorises\">".gettext("autoris&eacute;s")."</option><option value=\"refuses\">".gettext("refus&eacute;s")."</option></select></td>\n";
		$form .="<td><input type='text' name='share_user_list' size='15'></td>\n";
		$form .="<td></td>\n";
		$form .="</tr><tr>\n";
		$form .="<td colspan='2'>".gettext("Utilisateur(s) ayant les droits")."</td>\n";
		$form .="<td rowspan='2'><input type='text' name='share_admin' size='15'></td>\n";
		$form .="<td></td>\n";
		$form .="</tr><tr>\n";
		$form .="<td colspan='2'>".gettext("d'administration sur ce partage")."</td>\n";
		$form .="<td></td>\n";
		$form .="</tr><tr>\n";
		$form .="<td colspan='2'>".gettext("Limitation d'acc&#233s &#224 un parc")."</td>\n";
            	$form .= "<td>\n<SELECT NAME=\"parc_restrict\" SIZE=\"1\">";
		$form.= "<option value=\"\">\n";
            	for ($loop=0; $loop < count($list_parcs); $loop++) {
                	$form.= "<option value=".$list_parcs[$loop]["cn"].">".$list_parcs[$loop]["cn"]."\n";
            	}
            	$form .= "</SELECT>&nbsp;&nbsp;\n</td>";
		$form .="<td></td>\n";
		$form .="</tr><tr>\n";
		$form .="<td colspan='4'>";
		$form .= "<input type='hidden' value='true' name='End_ph1'>\n";
		$form .= "<input type='hidden' name='cn_srv' value='$cn_srv'>\n";
		$form .= "<input type='hidden' name='stat_srv' value='$stat_srv'>\n";
		$form .= "<input type='hidden' name='ipHostNumber' value='$ipHostNumber'>\n";
		$form .="</td></tr>\n";
		$form .="<td><input type='submit' value='".gettext("Soumettre")."' name='submit'></td>\n";
		$form .="<td>&nbsp;</td>\n";
		$form .="<td><input type='reset' value='".gettext("Recommencer")."' name='reset'></td>\n";
		$form .="<td>&nbsp;</td>\n";
		$form .="</tr></table>\n";
		$form .="</form>\n";
		echo $form;

		if ( $End_ph1 && $Verif_Empty == false )
			echo gettext ("<div class='error_msg'>".gettext("Les champs marqu&#233s d'une &#233toile doivent &#234tre compl&#233t&#233s !")."</div>")."\n";

	} elseif ( $End_ph1 ) {

		// Convertion des infos valid users ou invalid users
		if ( $share_user_type == "autorises" ) $share_user_type = "valid users"; else $share_user_type = "invalid users";
		// Phase 2  : Verification coherence des informations collectees
		$Verif_Result = true; $Verif_share_user_list = true; $Verif_share_admin = true;
		## Cas du maitre ou de l'esclave
			## Verifier validite des entrees du formulaire et nettoyage
			$share_name =  strtolower(cleanEntry($share_name));
			$share_comment =  cleanEntry($share_comment);
			$share_path =  cleanPath($share_path);
			$share_user_list =  strtolower(cleanEntry($share_user_list));
			$share_admin =  strtolower(cleanEntry($share_admin));
			/*
			echo "DEBUG >> 	share_name $share_name<br>
			                           	share_comment $share_comment<br>
							share_path [$share_path]<br>
							share_user_list  $share_user_list<br>
							share_admin $share_admin<br>";
			*/
			## Verifier validite de la liste valid ou invalid users
			if (! valid_uid_or_group($share_user_list) ) {
				$Verif_Result = false;
				$Verif_share_user_list = false;
				#echo "DEBUG >> Invalid user list<br>";
			}
			## Verifier validite de la liste admin users
			if (! valid_uid_or_group($share_admin) ) {
				$Verif_Result = false;
				$Verif_share_admin = false;
				#echo "DEBUG >> Invalid admin user list<br>";
			}
			## Fine verification des entrees du formulaire et du nettoyage
		if ( $stat_srv == "maitre" ) {
			// Cas du maitre
			// Verifier la presence du partage dans smb.conf
			//exec ("/bin/grep \"<$share_name>\" /etc/samba/smb.conf", $AllOutPut, $ReturnValueShareName);
			// Le test n'etait pas bon: Il ne detectait que les partages ajoutes par l'utilisateur
			//                          pas ceux existant en standard dans SE3
			//                          Et il ne tient pas compte de la casse.
			//                          Et il ne teste pas les smb_*.conf
			exec ("/bin/grep -i \"\[$share_name\]\" /etc/samba/smb*.conf", $AllOutPut, $ReturnValueShareName);
			if ( $ReturnValueShareName == 0 ) $Verif_Result = false;
		} elseif ( $stat_srv == "esclave" ) {
			// Cas de l'esclave
			// Verifier la presence du partage dans smb.conf
        		//exec ("ssh -l remote_adm $ipHostNumber '/bin/grep \"$share_name\" /etc/samba/smb.conf'", $AllOutPut, $ReturnValueShareName);
				// Correction du test: idem
        		exec ("ssh -l remote_adm $ipHostNumber '/bin/grep -i \"\[$share_name\]\" /etc/samba/smb*.conf'", $AllOutPut, $ReturnValueShareName);
			if ( $ReturnValueShareName == 0 ) $Verif_Result = false;
		}

		// Est-ce que le nom de partage correspond a un utilisateur dans l'annuaire:
		$ReturnValueShareName_User_Ldap=1;
		$tab_test_user=people_get_variables($share_name, false);
		/*
		echo "count($tab_test_user)=".count($tab_test_user)."<br />";
		foreach($tab_test_user as $cle => $valeur){
			echo "\$tab_test_user[$cle]=$valeur<br />";
		}
		echo "count($tab_test_user[0])=".count($tab_test_user[0])."<br />";
		foreach($tab_test_user[0] as $cle => $valeur){
			echo "\$tab_test_user[0][$cle]=$valeur<br />";
		}
		*/
		if(count($tab_test_user[0])!=0){
			$ReturnValueShareName_User_Ldap=0;
			$Verif_Result = false;
		}

	        #### $Verif_Result = false; // Forcee a false pour DEBUG !
		// Phase 2' : Affichage des caracteristique du nouveau partage
		echo "<H3>".gettext("Cr&#233ation d'un partage sur")." $cn_srv : </h3>\n";
		echo "<H6>".gettext("[Phase 2] Controle de coh&#233rence :")."</h6>\n";
		if ( !$Verif_Result ) {
			echo $alerte_4;
			if ( $ReturnValueShareName == 0 ) echo $share_name_alert;
			if ( $Verif_share_user_list == false ) echo $share_user_list_alert;
			if ( $Verif_share_admin == false ) echo $share_admin_alert;

			if($ReturnValueShareName_User_Ldap==0){
				// Le nom de partage correspond a un utilisateur dans l'annuaire:
				echo $Alert_ShareName_User_Ldap;
			}

			echo "</div><br>\n";
		} else {
			// Si creation sur serveur maitre
			if ( $stat_srv == "maitre" )
			{
				system("sudo /usr/share/se3/scripts/create_share.sh \
				\"$share_name\" \"$share_comment\" \"$share_path\"\
				\"$REMOTE_ADDR\" \"$share_user_type\" \"$dir_rights_other\"\
				\"$dir_grp_owner\" \"$dir_grp_perm\" admin=\"$share_admin\"\
				parc=\"$parc_restrict\" user_list=\"$share_user_list\"");

			// Si creation sur un esclave
			} elseif ( $stat_srv == "esclave" ) {
				system("ssh -l remote_adm $ipHostNumber sudo /usr/share/se3/scripts/create_share.sh\
								      \"$share_name\" \"$share_comment\" \"$share_path\"\
								      \"$REMOTE_ADDR\" \"$share_user_type\" \"$dir_rights_other\"\
								      \"$dir_grp_owner\" \"$dir_grp_perm\" admin=\"$share_admin\"\
								      parc=\"$parc_restrict\" user_list=\"$share_user_list\"");
			} // Fin elseif ( $stat_srv == "esclave" )
		} // Fin if ( !$Verif_Result )
	// Fin elseif End_ph1
	}

} // Fin if is_admin
  include ("pdp.inc.php");
?>
