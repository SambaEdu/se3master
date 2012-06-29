 <?php

/**
   
   * Gestion des dossiers /var/se3/Classes/Classe_XXX/_echange
   * @Version $Id$
   
   * @Projet SE3
   
   * @auteurs Humblement bricole par Stephane Boireau (AS Bernay/Pont-Audemer (27)) d'apres plusieurs pages;o):

   * @note Et j'ai emprunte la fonction classes_prof() a la page echanges/distribuer.php de Jean Gourdin - Pierre-Yves Petit

   * @Licence Distribue selon les termes de la licence GPL
*/



/**
	* @Repertoire  dossier_echange/
	* file dossier_echange.php
*/


  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  require_once ("lang.inc.php");
  bindtextdomain('se3-dossier_echange',"/var/www/se3/locale");
  textdomain ('se3-dossier_echange');

  foreach ($_POST as $cle=>$val) {
    $$cle = $val;
  }


  //Pour tenir compte des essais...
  $nom_de_la_page="dossier_echange.php";

  echo "<h1>".gettext("Gestion des dossiers d'&#233change")."</h1>";

  //if (is_admin("se3_is_admin",$login)=="Y") {

	//aide
	$_SESSION["pageaide"]="Ressources_et_partages#Dossier_.C3.A9change";



	//La gestion multi-serveur n'est pas en place...
	//Je l'ai laissee en pensant faire des essais par la suite,
	//mais je n'ai encore jamais fait l'install multi-serveur
	//et par consequent, je n'en ai pas sous le coude pour tester;o).


	// Prepositionnement variables
	$mono_srv = false;
	$multi_srv = false;
	// Recherche de la nature mono ou multi serveur de la plateforme SE3
	$master=search_machines ("(l=maitre)", "computers");
	$slaves= search_machines ("(l=esclave)", "computers");
	if ( count($master) == 0 ) {
		echo gettext("<P>ERREUR : Il n'y a pas de serveur maitre d&#233clar&#233 dans l'annuaire ! <BR>Veuillez contacter le super utilisateur du serveur SE3.</P>");
	} elseif (  count($master) == 1  && count($slaves) == 0 ) {
		// Plateforme mono-serveur
		$mono_srv = true;
	} elseif (  count($master) == 1  && count($slaves) > 0  ) {
		$multi_srv = true;
	}
	// Fin Recherche de la nature mono ou multi serveur de la plateforme SE3

	if ( $mono_srv ) {
		// configuration mono serveur  : determination des parametres du serveur
		$serveur=search_machines ("(l=maitre)", "computers");
		$cn_srv= $serveur[0]["cn"];
		$stat_srv = $serveur[0]["l"];
		$ipHostNumber =  $serveur[0]["ipHostNumber"];
	} elseif ($multi_srv) {
		// configuration multi-serveurs : presentation d'un form de selection du serveur
		if ( !$selected_srv && !$del_folders_classes) {
			echo gettext("<P><H3>S&#233lection du serveur ou vous souhaitez lister les ressources classes disponibles : </H3>");
			$servers=search_computers ("(|(l=esclave)(l=maitre))");
			echo "<form action=\"$nom_de_la_page\" method=\"post\">\n";
			for ($loop=0; $loop < count($servers); $loop++) {
				echo $servers[$loop]["description"]." ".$servers[$loop]["cn"]."&nbsp;<input type=\"radio\" name=\"cn_srv\" value =\"".$servers[$loop]["cn"]."\"";
				if ($loop==0) echo "checked";
				echo "><BR>\n";
			}
			$form="<input type=\"reset\" value=\"R&#233;initialiser la s&#233;lection\">\n";
			$form ="<input type=\"hidden\" name=\"selected_srv\" value=\"true\">\n";
			$form.="<input type=\"submit\" value=\"Valider\">\n";
			$form.="</form>\n";
			echo $form;
		} elseif ( $selected_srv && $multi_srv) {
			// configuration multi serveurs  : determination des parametres du serveur
			$serveur=search_machines ("(cn=$cn_srv)", "computers");
			$stat_srv = $serveur[0]["l"];
			$ipHostNumber =  $serveur[0]["ipHostNumber"];
		}
	}



	// Recherche des ressources classes existantes
	if (is_admin("se3_is_admin",$login)=="Y") {
		if ($stat_srv == "maitre") {
			// Serveur maitre  :  Recherche des ressources classes existantes
			// ouverture du repertoire Classes
			$loop=0;
			$repClasses = dir ("/var/se3/Classes/");
			// recuperation de chaque entree
			while ($ressource =  $repClasses->read()) {
				if ( preg_match("/^Classe_/", $ressource) ) {
					$list_ressources[$loop]= $ressource;
					$loop++;
				}
			}
			$repClasses->close();
		} elseif  ($stat_srv == "esclave") {
			// Serveur esclave :  Recherche des ressources classes existantes
			exec ("ssh -l remote_adm $ipHostNumber 'ls /var/se3/Classes'", $list_ressources, $ReturnValue);
		}
	}
	else{
		include("fonc_outils.inc.php");
		$list_ressources=classes_prof($login);
	}
	// Fin  Recherche des ressources classes existantes



	// Presentation de la liste  des ressources disponibles
	if (  ($stat_srv == "maitre" || $stat_srv == "esclave")  ) {

		//Le choix des classes a traiter est-il fait?
		if(!isset($choice_done)){
			//echo "<H3>".gettext("Liste des ressources  Classes disponibles sur le serveur "). "$cn_srv</H3>\n";
			echo "<H3>".gettext("Cr&#233ation/Activation/D&#233sactivation des dossiers _echange sur le serveur "). "$cn_srv</H3>\n";
			if (count($list_ressources) == 0 ) {
				echo "<P>".gettext("Il n'y a pas de ressources Classes sur ce serveur !")."</P>\n";
			}  else {
				if   ( count($list_ressources)>10) $size=10; else $size=count($list_ressources);
				//echo "<h4>Cr&#233;ation/Activation/D&#233;sactivation des dossiers _echange</h4>";
				//echo "<form>\n";
				echo "<form action=\"$nom_de_la_page\" method=\"post\">\n";
				// Affichage liste des ressources disponibles
				/*
				echo "<select size=\"".$size."\" name=\"list_classes[]\" multiple=\"multiple\">\n";
				for ($loop=0; $loop<count($list_ressources);$loop++) {
					echo "<option value=".$list_ressources[$loop].">".$list_ressources[$loop]."\n";
				}
				echo "</select><br>\n";
				*/

				/*
				//AJOUT MODIF
				*/
				echo "<p>".gettext("Les boutons sont plac&#233s dans l'&#233tat actuel.")."<br>\n";
				echo gettext("Seules les classes pour lesquelles vous modifierez le choix seront affect&#233es.")."<br>\n";
				echo gettext("L'acc&#232s au dossier I:\public est aussi activ&#233/verrouill&#233 par la m&#234me op&#233ration.")."</p>\n";
				echo "<table border=\"1\">";
				echo "<tr class=\"menuheader\" height=\"30\" style=\"font-weight:bold;\" align=\"center\">";
				echo "<td>".gettext("Classe")."</td>";
				echo "<td>".gettext("Etat actuel")."</td>";
				echo "<td>".gettext("Actif")."</td>";
				echo "<td>".gettext("Verrouill&#233")."</td>";
				echo "<td>".gettext("R&#233activer<br>automatiquement<br>l'acc&#232s<br> apr&#232s...")."</td></tr>\n";
				for ($loop=0; $loop<count($list_ressources);$loop++) {
					//Recuperation de l'etat actuel:
					exec ("/usr/bin/sudo /usr/share/se3/scripts/echange_classes.sh \"$list_ressources[$loop]\" \"etat\"",$resultat);
					
					// Si actif
					$color_actif="";
					$pre_selectionne="";
					if("$resultat[0]"=="actif"){
						$color_actif=" bgcolor=\"#00FF00\"";
						$pre_selectionne=" checked=\"true\"";
					}

					echo "<tr align=\"center\" $color_actif>\n";
					echo "<td>$list_ressources[$loop]<input type=\"hidden\" name=\"list_classes[$loop]\" value=\"$list_ressources[$loop]\"></td>\n";
					echo "<td>$resultat[0]<input type=\"hidden\" name=\"etat_actuel[$loop]\" value=\"$resultat[0]\"></td>\n";

					echo "<td><input type=\"radio\" name=\"activate[$loop]\" value=\"actif\"$pre_selectionne></td>\n";

					$pre_selectionne="";
					if("$resultat[0]"=="verrouille"){
						$pre_selectionne=" checked=\"true\"";
					}
					echo "<td><input type=\"radio\" name=\"activate[$loop]\" value=\"verrouille\"$pre_selectionne></td>\n";

					//Delai:
					echo "<td>\n";
					echo "<input type=\"checkbox\" name=\"delai[$loop]\" value=\"oui\">\n";
					echo "<select name=\"heures[$loop]\">\n";
					for($i=0;$i<=12;$i++){
						echo "<option value=\"$i\">$i</option>\n";
					}
					echo "</select> H \n";
					echo "<select name=\"minutes[$loop]\">\n";
					for($i=0;$i<=55;$i=$i+5){
						echo "<option value=\"$i\">$i</option>\n";
					}
					echo "</select> MIN \n";
					echo "</td>\n";
					//echo "<input type=\"text\" name=\"minutes\" value=\"5\">minutes</td>";
					echo "</tr>\n";
					unset($resultat);
				}
				echo "</table>\n";
				/*
				//FIN MODIF
				*/

				echo "<input type=\"hidden\" name=\"stat_srv\" value=\"$stat_srv\">\n";
				echo "<input type=\"hidden\" name=\"choice_done\" value=\"true\">\n";
				//echo "Activer: <input type=\"radio\" name=\"activate\" value=\"yes\" checked> / \n";
				//echo "<input type=\"radio\" name=\"activate\" value=\"no\">: D&#233;sactiver<BR>\n";
				echo "<input type=\"submit\" value=\"Envoyer\">";
				echo "</form>\n";
			}
		}
		else {
			//PARTIE ACTION:
			//Le choix des classes a traiter a ete  effectue dans le formulaire ci-dessus.
			//echo "<p>activate=$activate</p>\n";
			echo "<h3>".gettext("Traitement des dossiers _echange")."</h3>\n";

			/*
			if(count($list_classes)=="0"){
				echo "<p>".gettext("Cr&#233nom de bourricot, vous n'avez pas s&#233lectionn&#233 de classe !")."</p>";
			}
			else{
				for ($loop=0; $loop<count($list_classes); $loop++) {
					if ($list_classes[$loop]){
						if("$activate"=="yes"){
							$textactivation="Activation";
						}
						else{
							$textactivation="D&#233;sactivation";
						}
						#echo "<p>Traitement du dossier $list_classes[$loop]/_echange<br>\n";
						echo "<p>$textactivation du dossier $list_classes[$loop]/_echange<br>\n";
						//echo "exec (\"/bin/sh /usr/share/se3/scripts/echange_classes.sh \\\"$list_classes[$loop]\\\" \\\"$activate\\\"\")</p>\n";
						exec ("/usr/bin/sudo /usr/share/se3/scripts/echange_classes.sh \"$list_classes[$loop]\" \"$activate\"");
						echo "</p>\n";
					}
				}
			}
			*/

			for ($loop=0; $loop<count($list_classes); $loop++) {
				//echo "<p>".count($list_classes)."</p>\n";
				//if("$list_classes[$loop]"!=""){
				if ("$etat_actuel[$loop]"!="$activate[$loop]"){
					if("$activate[$loop]"!=""){
						if("$activate[$loop]"=="actif"){
							$textactivation="Activation";
						}
						else{
							$textactivation="D&#233;sactivation";
						}
						echo "<p>$textactivation ".gettext(" du dossier")." $list_classes[$loop]/_echange<br>\n";
						exec ("/usr/bin/sudo /usr/share/se3/scripts/echange_classes.sh \"$list_classes[$loop]\" \"$activate[$loop]\"");
						//sleep(1);

						//Dans le cas ou il existait une temporisation,
						//comme on refait ici le choix d'activer ou de desactiver l'acces,
						//on vide l'eventuelle temporisation anterieure.
						if(file_exists("$chemin_tmp/levee_restriction_echange_$list_classes[$loop].sh")){
							unlink("$chemin_tmp/levee_restriction_echange_$list_classes[$loop].sh");
						}

						sleep(1);

						//On ne met en place que des temporisations de deverrouillage
						//(donc: quand un verrouillage est mis en place, on teste si un minutage est demande)
						if(("$delai[$loop]"=="oui")&&("$activate[$loop]"=="verrouille")&&(("$minutes[$loop]"!="0")||("$heures[$loop]"!="0"))){
							$chemin_tmp="/tmp";
							//$chemin_tmp="/var/remote_adm";
							/*
							//Le fichier est vire plus haut.
							if(file_exists("$chemin_tmp/levee_restriction_echange_$list_classes[$loop].sh")){
								unlink("$chemin_tmp/levee_restriction_echange_$list_classes[$loop].sh");
							}
							*/
							$fichier=fopen("$chemin_tmp/levee_restriction_echange_$list_classes[$loop].sh","w+");
							if($fichier){
								fwrite($fichier,"#!/bin/bash\n");
								fwrite($fichier,"/usr/bin/sudo /usr/share/se3/scripts/echange_classes.sh \"$list_classes[$loop]\" \"actif\"\n");
								chmod("$chemin_tmp/levee_restriction_echange_$list_classes[$loop].sh",0700);
								fclose($fichier);

								$duree_delai=$heures[$loop]*60+$minutes[$loop];

								exec ("at -f $chemin_tmp/levee_restriction_echange_$list_classes[$loop].sh +$duree_delai minute");
								echo gettext("Les dossiers I:\public et H:\\")."$list_classes[$loop]\_echange ".gettext(" seront r&#233activ&#233s dans ")." $duree_delai ".gettext("minutes.\n");

								unset($duree_delai);
							}
						}
						echo "</p>\n";
					}
					else{
						//Pas de modification pour $list_classes[$loop]
						//parce que le dossier n'est pas encore initialies
						//et qu'aucune case n'etait selectionnee.
						echo "\n";
					}
				}
				else{
					//Pas de modification pour cette classe
					//(le bouton radio d'activation/verrouillage n'a pas ete deplace).
					echo "\n";
				}
				//}
			}
			echo "<p><a href=\"dossier_echange.php\">".gettext("Retour au menu 'Dossier _echange'")."</a></p>\n";
		}
	}
  //} // Fin if is_admin
  include ("pdp.inc.php");
?>
