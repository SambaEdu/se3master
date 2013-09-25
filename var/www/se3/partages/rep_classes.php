<?php
/**
* Creation des repertoires classes et mise en place des ACL
* @Version $Id$ 
* @Projet LCS / SambaEdu 
* @auteurs Philippe Chadefaux, denis bonnenfant
* @Licence Distribue selon les termes de la licence GPL
* @note 
*/

/**
* @Repertoire: partages/
* file: rep_classes.php
*/

include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-partages',"/var/www/se3/locale");
textdomain ('se3-partages');

//aide
$_SESSION["pageaide"]="Ressources_et_partages";


$texte_alert="Vous allez supprimer tout le repertoire classe. Voulez vous vraiment continuer ?";
?>
<script type="text/javascript">

/**
* Affiche une boite de dialogue pour demander confirmation
* @language Javascript
* @Parametres
* @return 
*/

function areyousure() {
	var messageb = "<?php echo "$texte_alert"; ?>";
	if (confirm(messageb)) {
		return true;
	}
	else {
		return false;
	}
}
</script>

<?php
if ((is_admin("se3_is_admin",$login)=="Y") or
(is_admin("annu_is_admin",$login)=="Y")) {

	function my_echo_debug($chaine) {
		$debug=0;
		if($debug==1) {
			echo "<span style='color:red'>".$chaine."</span><br />\n";
		}
	}

	echo "<h1>".gettext("Cr&#233;ation des r&#233;pertoires classes")."</h1>\n";
	
	// On ajoute les classes
	if($_POST['create_folders_classes']) {
		$new_folders_classes=$_POST['new_folders_classes'];
		for ($loop=0; $loop < count($new_folders_classes); $loop++) {
			list($Classe,$Niveau)=preg_split("/Classe_/",$new_folders_classes[$loop]);

			// On fait le test avant le updateClasses.pl parce qu'updateClasses.pl fait sauter les droits Profs
			$commande="cd /var/se3/Classes; /usr/bin/getfacl . | grep default:group:Profs >/dev/null && echo 1";
			my_echo_debug("Test: $commande");
			$acl_group_profs_classes = exec($commande);
			my_echo_debug("\$acl_group_profs_classe=$acl_group_profs_classes");

			$commande="/usr/bin/sudo /usr/share/se3/scripts/updateClasses.pl -c $Niveau";
			my_echo_debug("<p>Creation du dossier de classe $Niveau : ".$commande);
			system($commande);
			$rep_niveau = "/var/se3/Classes/Classe_".$Niveau;

			if ($acl_group_profs_classes=="1") {
				// Il existe deja des droits pour le groupe Profs dans /var/se3/Classes
				// On va remettre les droits Profs en tenant compte des ajouts de dossiers effectues

				echo "Attribution des droits au groupe Profs.<br />";
				$commande="/usr/bin/sudo /usr/share/se3/scripts/se3_droits_profs_sur_classes.sh";
				my_echo_debug($commande);
				system ($commande);
			}

			if (is_dir("$rep_niveau")) {
				echo "R&#233;pertoire classe ".$Niveau. " cr&#233;&#233;.<br />\n";
			} else {
				echo "Echec : Cr&#233;ation du r&#233;pertoire classe ".$Niveau."<br />\n";
				echo "V&#233;rifier que le groupe Equipe correspondant &#224; la Classe existe.<br />\n";
			}
		}
	}

	// On supprime les classes
	if($_POST['delete_folders_classes']) {
		$old_RessourcesClasses=$_POST['old_RessourcesClasses'];	
			for ($loop=0; $loop < count($old_RessourcesClasses); $loop++) {
			list($Classe,$Niveau)=preg_split("/Classe_/",$old_RessourcesClasses[$loop]);
			system ("/usr/bin/sudo /usr/share/se3/scripts/deleteClasses.sh $Niveau");
			$rep_niveau = "/var/se3/Classes/Classe_".$Niveau;
			
			if ( ! is_dir("$rep_niveau")) {
				echo "Suppression du r&#233;pertoire classe ".$Niveau."<br />\n";
			} else {
				echo "Echec : Suppression du r&#233;pertoire classe ".$Niveau."<br />\n";
			}
		}
	}

	// On rafaichit on ne sait jamais, cela replace les acl
	if($_POST['refresh_folders_classes']) {
		$dirClasses = dir ("/var/se3/Classes");
		$indice=0;
		while ( $Entry = $dirClasses ->read() ) {
			if ( preg_match("/^Classe_/", $Entry) ) {
				$RessourcesClasses[$indice] = $Entry;
				list($Classe,$Niveau)=preg_split("/Classe_/",$RessourcesClasses[$indice]);
				//echo "Rafraichissement du r&#233;pertoire classe ".$Niveau."<br />\n";
				$commande="/usr/bin/sudo /usr/share/se3/scripts/updateClasses.pl -c $Niveau";
				my_echo_debug($commande);
				system ($commande);
				$indice++;
			}
		}

		// Dans le cas ou on donne le droit a tous les profs sur les repertoires classes
		if ($_POST['acl_group_profs']) {
			$commande="/usr/bin/sudo /usr/share/se3/scripts/se3_droits_profs_sur_classes.sh";
			my_echo_debug($commande);
			system ($commande);

		} else {
			// Effacement du droit Profs sur les fichiers et dossiers existants
			$commande="/usr/bin/sudo /usr/share/se3/scripts/se3_droits_profs_sur_classes.sh droits=n";
			my_echo_debug($commande);
			system ($commande);
		}
		echo "<br /><br /><center>\n";
		echo "<a href=rep_classes.php>Continuez</a>\n";
		echo "</center>\n";
		include ("pdp.inc.php");
		exit;
	}

	// On rafaichit les classes selectionnees
	if($_POST['refresh_classes']||$_POST['clean_classes']) {
		$refresh_RessourcesClasses=$_POST['old_RessourcesClasses'];	
		if ( count($refresh_RessourcesClasses) > 0 ) {
			for ($loop=0; $loop < count($refresh_RessourcesClasses); $loop++) {
				list($Classe,$Niveau)=preg_split("/Classe_/",$refresh_RessourcesClasses[$loop]);
				if($_POST['refresh_classes']) {

					// On fait le test avant le updateClasses.pl parce qu'updateClasses.pl fait sauter les droits Profs
					$commande="cd /var/se3/Classes/$refresh_RessourcesClasses[$loop]; /usr/bin/getfacl . | grep default:group:Profs >/dev/null && echo 1";
					my_echo_debug("Test: $commande");
					$acl_group_profs_classe = exec($commande);
					my_echo_debug("\$acl_group_profs_classe=$acl_group_profs_classe");

					// Rafraichissement de la classe:
					//echo "<b>rafraichissement de la classe : $Niveau</b><br />\n";
					$commande="/usr/bin/sudo /usr/share/se3/scripts/updateClasses.pl -c $Niveau ";
					my_echo_debug($commande);
					system ($commande);
			
					// Dans le cas ou on donne le droit a tous les profs sur les repertoires classes
					if ($_POST['acl_group_profs']) {
						$commande="/usr/bin/sudo /usr/share/se3/scripts/se3_droits_profs_sur_classes.sh classe=".$refresh_RessourcesClasses[$loop];
						my_echo_debug($commande);
						system ($commande);
					}
					else {
						if ($acl_group_profs_classe=="1") {

							echo "<b>Rafraichissement de la classe : ".$refresh_RessourcesClasses[$loop]."</b><br />\n";

							$commande="/usr/bin/sudo /usr/share/se3/scripts/se3_droits_profs_sur_classes.sh classe=".$refresh_RessourcesClasses[$loop]." droits=n";
							my_echo_debug($commande);
							system ($commande);
							echo "Suppression des droits pour le groupe Profs (<em>seuls les profs de l'equipe ont encore les droits</em>).<br />\n";
						}
						else {
							// Vérification au cas ou le script updateClasses.pl serait modifie entre temps
							$commande="cd /var/se3/Classes/$refresh_RessourcesClasses[$loop]; /usr/bin/getfacl . | grep default:group:Profs >/dev/null && echo 1";
							my_echo_debug("Test: $commande");
							$acl_group_profs_classe = exec($commande);
							my_echo_debug("\$acl_group_profs_classe=$acl_group_profs_classe");

							if ($acl_group_profs_classe!="1") {
								echo "Le groupe Profs n'a pas de droits sur le dossier (<em>seuls les profs de l'equipe ont les droits</em>).<br />\n";
							}
						}
					}
				} elseif($_POST['clean_classes']) {
					echo "<b>Nettoyage de la classe : $Niveau</b><br />\n";
					$commande="/usr/bin/sudo /usr/share/se3/scripts/cleanClasses.pl $Niveau ";
					my_echo_debug($commande);
					system ($commande);
				}
			}
		}
		echo "<br /><br /><center>\n";
		echo "<a href=rep_classes.php>Continuez</a>\n";
		echo "</center>\n";
		include ("pdp.inc.php");
		exit;
	}

	echo "<br />\n";
	// configuration mono serveur  : determination des parametres du serveur
	$serveur=search_machines ("(l=maitre)", "computers");
	$cn_srv= $serveur[0]["cn"];
	$stat_srv = $serveur[0]["l"];
	$ipHostNumber =  $serveur[0]["ipHostNumber"];

	// Recherche de la liste des classes dans l'annuaire
	$list_classes=search_groups("cn=Classe_*");
	// Recherche des sous dossiers classes d&#233;ja existant sur le serveur selectionn&#233;

	// Constitution d'un tableau avec les ressources deja existantes
	$dirClasses = dir ("/var/se3/Classes");
	$indice=0;
	while ( $Entry = $dirClasses ->read() ) {
		if (( preg_match("/^Classe_/", $Entry) ) && (!preg_match("/^Classe_grp/", $Entry) )) {
			$RessourcesClasses[$indice] = $Entry;
			$indice++;
		}
	}
	if(isset($RessourcesClasses)) {
		array_multisort($RessourcesClasses, SORT_STRING);    
	}

	// Creation d'un tableau des nouvelles ressources a cr&#233;er  par
	// elimination des ressources deja existantes
	$k=0;
	for ($i=0; $i < count($list_classes); $i++ ) {
		for ($j=0; $j < count($RessourcesClasses); $j++ ) {
			if (  $list_classes[$i]["cn"] ==  $RessourcesClasses[$j])  {
				$exist = true;
				break;
			} else { $exist = false; }
		}
		
		if (!$exist) {
			$list_new_classes[$k]["cn"]= $list_classes[$i]["cn"];
			$k++;
		}
	}

	// Affichage de la table
	echo "<H3>Gestion des ressources classes</H3>\n";
	echo "<br />\n";
	echo "<table BORDER=1 CELLPADDING=3 CELLSPACING=1 RULES=COLS>\n";
	echo "<tr class=\"menuheader\" height=\"30\">\n";
	echo "<td align=\"center\">Classes &#224; cr&#233;er ";
	echo "<u onmouseover=\"return escape".gettext("('Classes disponibles dans l\'annuaire, mais dont le r&#233;pertoire n\'a pas encore &#233;t&#233; cr&#233;&#233; sur le serveur.<br /><b>Remarque :</b> Un r&#233;pertoire peut &#234;tre cr&#233;&#233;, que si il existe une &#233;quipe correspondante.')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u></td>\n";
	echo "<td align=\"center\">Classes cr&#233;&#233;es ";
		echo "</td></tr>\n";
		echo "<tr><td align=\"center\">\n";
		// Affichage menu de s&#233;lection des sous-dossiers classes a cr&#233;er
		if   ( count($list_new_classes)>15) $size=15; else $size=count($list_new_classes);
		if ( count($list_new_classes)>0) {
			echo "<form action=\"rep_classes.php\" method=\"post\">\n";
			echo "<select size=\"".$size."\" name=\"new_folders_classes[]\" multiple=\"multiple\">\n";
			for ($loop=0; $loop < count($list_new_classes); $loop++) {
				echo "<option value=".$list_new_classes[$loop]["cn"].">".$list_new_classes[$loop]["cn"]."\n";
			}
			echo "</select><br />\n";
			echo "<input type=\"hidden\" name=\"create_folders_classes\" value=\"true\">\n";
			// echo "<input type=\"hidden\" name=\"cn_srv\" value=\"$cn_srv\">\n";
			// echo "<input type=\"hidden\" name=\"ipHostNumber\" value=\"$ipHostNumber\">\n";

			echo "<input type=\"submit\" value=\"".gettext("Cr&#233;er")."\">\n";
			echo "</form>\n";
		
		// V&#233;rification selection d'au moins une classe
			if ( $create_folders_classes && count($new_folders_classes)==0 ) {
				echo "<div class='error_msg'>".gettext("Vous devez s&#233lectionner au moins une classe !")."</div>\n";
			}
		} else {
			echo "<div class='error_msg'>".gettext("Pas de nouvelles classes !")."</div>\n";
		}
		
	echo "</td><td align=\"center\">\n";
		if   ( count($RessourcesClasses)>15) $size=15; else $size=count($RessourcesClasses);
		if ( count($RessourcesClasses)>0) {
			echo "<form action=\"rep_classes.php\" method=\"post\">\n";
			echo "<select size=\"".$size."\" name=\"old_RessourcesClasses[]\" multiple=\"multiple\">\n";
			for ($loop=0; $loop < count($RessourcesClasses); $loop++) {
				echo "<option value=".$RessourcesClasses[$loop].">".$RessourcesClasses[$loop]."\n";
			}
			echo "</select><br />\n";
//        	echo "<input type=\"hidden\" name=\"refresh_classes\" value=\"true\">\n";
			echo "<input type=\"submit\" name=\"refresh_classes\" value=\"".gettext("Rafraichir")."\">\n";
			echo "<u onmouseover=\"return escape".gettext("('Choisir les classes que l\'on souhaite rafra&#238;chir,<br />par exemple suite &#224; l\'ajout de nouveaux &#233;l&#232;ves.<br /> En Cas de migration d\'une ann&#233;e &#224; une autre, les dossiers de l\ann&#233;e pr&#233;c&#233;dente des &#233;l&#232;ves seront copi&#233;s dans un sous-dossier Archive dans le dossier de la nouvelle ann&#233;e.<br /> Si l\'&#233;l&#232;ve n\'est plus dans l\'&#233;tablissement, son dossier est cach&#233;.<br />')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>\n";
			echo "<input type=\"submit\" name=\"clean_classes\" value=\"".gettext("Nettoyer")."\">\n";
			echo "<u onmouseover=\"return escape".gettext("('Choisir les classes que l\'on souhaite nettoyer, par exemple suite &#224; l\'ajout de nouveaux &#233;l&#232;ves.<br /> En Cas de migration d\'une ann&#233;e &#224; une autre, les dossiers de l\ann&#233;e pr&#233;c&#233;dente des &#233;l&#232;ves seront copi&#233;s dans un sous-dossier Archive dans le dossier de la nouvelle ann&#233;e.<br /> Si l\'&#233;l&#232;ve n\'est plus dans l\'&#233;tablissement, son dossier est cach&#233;.<br />')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>\n";
//        	echo "<input type=\"hidden\" name=\"delete_folders_classes\" value=\"true\">\n";
			echo "<input type=\"submit\" name=\"delete_folders_classes\" onClick=\"return areyousure()\" value=\"".gettext("Supprimer")."\">\n";
			echo "<u onmouseover=\"return escape".gettext("('<br /><b>Attention, la suppression entrainera la perte des donn&#233;es de la classe.</b>')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>\n";
			echo "</form>\n";
		// V&#233;rification selection d'au moins une classe
			if ( ($refresh_classes || $delete_folders_classes || $clean_classes) && count($old_RessourcesClasses)==0 ) {
				echo "<div class='error_msg'>".gettext("Vous devez s&#233lectionner au moins une classe !")."</div>\n";
			}
	}
/*	echo "</td><td align=\"center\">\n";
		if   ( count($RessourcesClasses)>15) $size=15; else $size=count($RessourcesClasses);
		if ( count($RessourcesClasses)>0) {
			echo "<form action=\"rep_classes.php\" method=\"post\">\n";
			echo "<select size=\"".$size."\" name=\"refresh_RessourcesClasses[]\" multiple=\"multiple\">\n";
			for ($loop=0; $loop < count($RessourcesClasses); $loop++) {
				echo "<option value=".$RessourcesClasses[$loop].">".$RessourcesClasses[$loop]."\n";
			}
			echo "</select><br />\n";
			echo "<input type=\"hidden\" name=\"refresh_classes\" value=\"true\">\n";
			echo "<input type=\"submit\" value=\"".gettext("Rafraichir")."\">\n";
			echo "</form>\n";
	}
*/
	echo "</td></tr>\n";
	echo "</table>\n";
	
	echo "<br />\n";
	echo "<H3>Rafraichir les r&#233;pertoires classes existants</H3>\n";
	echo "<form action=\"rep_classes.php\" method=\"post\">\n";
	$acl_group_profs_classes = exec("cd /var/se3/Classes; /usr/bin/getfacl . | grep group:Profs >/dev/null && echo 1");
	if ($acl_group_profs_classes=="1") {
		$CHECKED="checked";
	}	
	echo "Droits du groupe Profs : ";
	echo "<input type=\"checkbox\" name=\"acl_group_profs\" $CHECKED>\n";
	echo "<u onmouseover=\"return escape".gettext("('Permet de donner tous les droits (ACL) sur les classes existantes &#224; tous les membres du groupe Profs. Tous les profs ont tous les droits sur toutes les classes.<br />Si vous souhaitez donner un droit au groupe Profs sur une classe particuli&#232;re, vous devez passer par <i>Droits sur fichiers</i><br /><b>Attention : Le fonctionnement normal est d\'avoir la case non coch&#233;e. Les profs ont alors les droits uniquement sur leurs classes.</b>')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>\n";
		echo "<br /><br />\n";
	echo "<input type=\"hidden\" name=\"refresh_folders_classes\" value=\"true\">\n";
		echo "<input type=\"submit\" onClick=\"alert('Cette op&#233;ration peut &#234;tre tr&#232;s longue !')\"  value=\"".gettext("Rafraichir toutes les classes")."\">\n";
	echo "<u onmouseover=\"return escape".gettext("('Permet de reforcer les droits (ACL) sur les classes existantes.<br /><b>Attention : Cette op&#233;ration peut &#234;tre longue.</b>')")."\"><IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/system-help.png\"></u>\n";
		echo "</form>\n";


} // Fin if is_admin
include ("pdp.inc.php");
?>
