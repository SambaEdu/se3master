<?php	

/**

* Module fond d'ecran: Page principale

* @Version $Id$

* @Projet LCS / SambaEdu 

* @auteurs Stephane Boireau

* @Licence Distribue selon les termes de la licence GPL

* @Correctif de Laurent Joly pour compatibilite php5 du 09-03-2016


*/

/**

* @Repertoire: fond_ecran/
* file: fond.php

*/	


	include "entete.inc.php";
	include "ldap.inc.php";
	include "ihm.inc.php";
	// Pour MySQL et refreshzrn?:
	//require "../registre/include.inc.php";
	require "/var/www/se3/registre/include.inc.php";

	require_once("lang.inc.php");
	bindtextdomain('se3-fond',"/var/www/se3/locale");
	textdomain ('se3-fond');

	//aide
	$_SESSION["pageaide"]="Le_module_Syst%C3%A8me_fond_d\'%C3%A9cran#Param.C3.A8trage";
	
	// Chemin a recuperer par la suite depuis MySQL (ou depuis un fichier texte)
	//$chemin_param_fond="/usr/share/se3/etc/fonds_ecran";
	$chemin_param_fond="/etc/se3/fonds_ecran";
	//$dossier_upload_images="/var/remote_adm";
	$dossier_upload_images="/etc/se3/www-tools";
	$chemin_scripts="/usr/share/se3/scripts";

	// Tableau des couleurs HTML:
	$tabcouleur=Array("aliceblue","antiquewhite","aqua","aquamarine","azure","beige","bisque","black","blanchedalmond","blue","blueviolet","brown","burlywood","cadetblue","chartreuse","chocolate","coral","cornflowerblue","cornsilk","crimson","cyan","darkblue","darkcyan","darkgoldenrod","darkgray","darkgreen","darkkhaki","darkmagenta","darkolivegreen","darkorange","darkorchid","darkred","darksalmon","darkseagreen","darkslateblue","darkslategray","darkturquoise","darkviolet","deeppink","deepskyblue","dimgray","dodgerblue","firebrick","floralwhite","forestgreen","fuchsia","gainsboro","ghostwhite","gold","goldenrod","gray","green","greenyellow","honeydew","hotpink","indianred","indigo","ivory","khaki","lavender","lavenderblush","lawngreen","lemonchiffon","lightblue","lightcoral","lightcyan","lightgoldenrodyellow","lightgreen","lightgrey","lightpink","lightsalmon","lightseagreen","lightskyblue","lightslategray","lightsteelblue","lightyellow","lime","limegreen","linen","magenta","maroon","mediumaquamarine","mediumblue","mediumorchid","mediumpurple","mediumseagreen","mediumslateblue","mediumspringgreen","mediumturquoise","mediumvioletred","midnightblue","mintcream","mistyrose","moccasin","navajowhite","navy","oldlace","olive","olivedrab","orange","orangered","orchid","palegoldenrod","palegreen","paleturquoise","palevioletred","papayawhip","peachpuff","peru","pink","plum","powderblue","purple","red","rosybrown","royalblue","saddlebrown","salmon","sandybrown","seagreen","seashell","sienna","silver","skyblue","slateblue","slategray","snow","springgreen","steelblue","tan","teal","thistle","tomato","turquoise","violet","wheat","white","whitesmoke","yellow","yellowgreen");

	//Connexion a la base de donnees
	$etablissement_connexion_mysql=connexion();

	if (is_admin("se3_is_admin",$login)=="Y") {
		$titre=gettext("Aide en ligne");
		$texte=gettext("
			Vous &#234;tes administrateur du serveur SE3.<br>
			Avec le menu ci-dessous, vous pouvez mettre en place des fonds d'&#233;cran pour:<br>
			<ul>
			<li>l'utilisateur 'admin'</li>
			<li>le groupe 'Profs'</li>
			<li>le groupe 'Eleves'</li>
			<li>le groupe 'Administratifs'</li>
			<li>le groupe 'overfill'</li>
			<li>les groupes 'Classe_XXX'</li>
			</ul>
			<p>Les fonds peuvent n'&#234;tre que des liens vers un fond commun (<i>&#233;conomique en place</i>), ou des images avec annotation (<i>nom, pr&#233;nom, classe, photo</i>).</p>
			<p>Ces fonds peuvent &#234;tre des d&#233;grad&#233;s g&#233;n&#233;r&#233;s ou des images fournies par vos soins.</p>
			<p>Les fonds commun sont mis en place dans /var/se3/Docs/media/fonds_ecran/ et les fonds propres &#224; chaque utilisateur sont mis en place en /home/profiles/user/.fond/fond.jpg (<i>soit %USERPROFILES%\.fond\fond.jpg</i>).</p>
		");
		mkhelp($titre,$texte);

		$query="SHOW TABLES;";
		$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
		if(mysqli_num_rows($resultat)==0){
			echo "<p><b>".gettext("ERREUR").":</b> ".gettext("Il semble que la base de donn&#233;es choisie ne comporte aucune table").".</p>";
			include("pdp.inc.php");
			exit();
		}
		else{
			$table_wallpaper_existe="non";
			while($ligne=mysqli_fetch_array($resultat)){
				if($ligne[0]=="wallpaper"){
					$table_wallpaper_existe="oui";
				}
			}
			if($table_wallpaper_existe=="non"){
				$query="CREATE TABLE `wallpaper` (
`nom` CHAR( 80 ) NOT NULL ,
`valeur` CHAR( 30 ) NOT NULL ,
`identifiant` INT NOT NULL AUTO_INCREMENT ,
PRIMARY KEY ( `identifiant` )
);";
				$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
			}
		}

		echo "<h1>".gettext("Gestion de fonds d'&#233;cran")."</h1>\n";

		if((!file_exists("$chemin_param_fond/imagemagick_present.txt"))||(!file_exists("$chemin_param_fond/gsfonts_present.txt"))){
			//=================================================
			//                   INSTALLATION
			//=================================================
			echo "<h2>".gettext("Installation")."</h2>\n";
			echo "<p>".gettext("Le fonctionnement de la g&#233;n&#233;ration de fonds pour le Bureau n&#233;cessite l'installation des paquets suivants").":</p>\n";
			echo "<ul>\n";
			echo "	<li><p><b>ImageMagick</b>: ".gettext("Programme de g&#233;n&#233;ration/traitement d'images en ligne de commande").".</p></li>\n";
			echo "	<li><p><b>Gsfonts</b>: ".gettext("Des polices pour permettre l'annotation d'images").".</p></li>\n";
			echo "</ul>\n";
			echo "<blockquote>\n";
			echo "<p>".gettext("Lancement de l'installation......Ne fermez pas cette fen&#234;tre").":</p>\n";
			echo "</blockquote>\n";
			system("/usr/bin/sudo /usr/share/se3/scripts/install_se3-module.sh se3-fondecran",$return);
			if($return==0) {
			  echo "Installation Ok,.<br>\n";
			}
			else {
			  echo "Oups .... l'installation a renvoy&#233; une erreur :(((.<br>\n";
			}
			 echo "<a href=\"../fond_ecran/fonds.php\">Retour</a> ";
			
		}
		else{

			// Le choix de consultation/parametrage a-t-il ete POSTE?
			$choix1=isset($_POST['choix1']) ? $_POST['choix1'] : (isset($_GET['choix1']) ? $_GET['choix1'] : NULL);
			if(isset($choix1)){
				//$choix1=$_POST['choix1'];

				// Sinon, la variable peut:
				// - ne pas etre encore initialisee.
				// - etre intialisee directement sans validation de formulaire.
			}
			else{
				// Le dispositif est-il actif?
				$query="SELECT * FROM wallpaper WHERE nom='action'";
				$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
				if(mysqli_num_rows($resultat)==0){
					$dispositif_actif="non";
					//echo "<p>1</p>\n";
				}
				else{
					$ligne=mysqli_fetch_object($resultat);
					$valeur=$ligne->valeur;
					if($valeur=="actif"){
						$dispositif_actif="oui";
						//echo "<p>2</p>\n";
					}
					else{
						$dispositif_actif="non";
						//echo "<p>3</p>\n";
					}
				}

				//echo "<p>\$dispositif_actif=$dispositif_actif</p>\n";

				if($dispositif_actif=="oui"){
 					echo "<h2>".gettext("Choix")."</h2>\n";
					echo "<blockquote>\n";
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\" name=\"choix\">\n";
					echo "<p>".gettext("Voulez-vous").":</p>\n";
					echo "<ul style=\"list-style-type:none;\">\n";

					//echo "<li><p><input type=\"radio\" name=\"choix1\" value=\"verif_smb_conf\" checked=\"true\"> ".gettext("V&#233;rifier les fichiers /etc/samba/smb*.conf").".</p></li>\n";

					echo "<li><p><input type=\"radio\" name=\"choix1\" id=\"choix1_consulter\" value=\"consulter\" checked=\"true\" /><label for='choix1_consulter' style='cursor:pointer;'> ".gettext("Consulter les param&#233;trages actuels").".</label></p></li>\n";
					echo "<li><p><input type=\"radio\" name=\"choix1\" id=\"choix1_parametrer\" value=\"parametrer\" /><label for='choix1_parametrer' style='cursor:pointer;'> ".gettext("Effectuer des param&#233;trages").".</label></p></li>\n";
					echo "<li><p><input type=\"radio\" name=\"choix1\" id=\"choix1_supprimer\" value=\"supprimer\" /><label for='choix1_supprimer' style='cursor:pointer;'> ".gettext("Supprimer le cache").".</label></p></li>\n";
					//echo "<li><p><input type=\"radio\" name=\"choix1\" id=\"choix1_inserer_image\" value=\"inserer_image\" /><label for='choix1_inserer_image' style='cursor:pointer;'> ".gettext("Ins&#233;rer une image personnalis&#233;e dans le fond d'un utilisateur").".</label></p></li>\n";  	 
					echo "<li><p><a href='fond_perso.php'>".gettext("Ins&#233;rer une image personnalis&#233;e dans le fond d'un utilisateur").".</a></p></li>\n";  	 
					echo "</ul>\n";
					echo "<input type=\"submit\" name=\"bouton_choix\" value=\"".gettext("Valider")."\"></p>\n";
					echo "</form>\n";
					echo "</blockquote>\n";
				}
				else{
					// Seul le choix de parametrage peut convenir alors.
					$choix1="parametrer";
				}
			
			}

			if($choix1=="consulter"){
				echo "<h2>".gettext("Consultation des param&#232;tres")."</h2>\n";
				//echo "<blockquote>\n";

				$query="SELECT * FROM wallpaper WHERE nom LIKE 'fond_%' AND valeur='actif'";
				$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
				if(mysqli_num_rows($resultat)==0){
					echo "<p>".gettext("Le dispositif est actif, mais aucun param&#233;trage de fond n'est effectu&#233;").".</p>\n";
				echo "<br>"."<a href=\"../fond_ecran/fonds.php\">Retour</a> ";
				}
				else{
					echo "<h3>".gettext("Tableau des param&#233;trages effectu&#233;s")."</h3>\n";
					echo "<table border=\"1\">\n";

					echo "<tr style=\"font-weight:bold; text-align:center;\">\n";
					echo "<td>&nbsp;</td>\n";
					echo "<td colspan=\"5\">".gettext("Param&#232;tres de l'image")."</td>\n";
					echo "<td colspan=\"7\">".gettext("Param&#232;tres des annotations")."</td>\n";
					echo "</tr>\n";

					echo "<tr style=\"font-weight:bold; text-align:center;\">\n";
					echo "<td>".gettext("Utilisateur/groupe")."</td>\n";
					echo "<td>".gettext("Image")."</td>\n";
					echo "<td>".gettext("Largeur")."</td>\n";
					echo "<td>".gettext("Hauteur")."</td>\n";
					echo "<td>".gettext("Couleur")." 1</td>\n";
					echo "<td>".gettext("Couleur")." 2</td>\n";
					echo "<td>".gettext("Annotations")."</td>\n";
					echo "<td>".gettext("Couleur du texte")."</td>\n";
					echo "<td>".gettext("Taille de la police")."</td>\n";
					echo "<td>".gettext("Nom et pr&#233;nom")."</td>\n";
					echo "<td>".gettext("Classe")."</td>\n";
					echo "<td>".gettext("Photo")."</td>\n";
					echo "</tr>\n";

					function si_select_croix($valeur){
						if($valeur==1){
							$vartmp="X";
						}
						else{
							$vartmp="&nbsp;";
						}
						return $vartmp;
					}

					while($ligne=mysqli_fetch_object($resultat)){
						$groupe=substr($ligne->nom,5);
						if($groupe == "admin")
							$wallgrp="Adminse3";
						else
							$wallgrp=$groupe;
					
						// Reinitialisations:
						$type_image="&nbsp;";
						$largeur="&nbsp;";
						$hauteur="&nbsp;";
						$couleur1="&nbsp;";
						$couleur2="&nbsp;";
						$annotations="&nbsp;";
						$couleur_txt="&nbsp;";
						$taille_police="&nbsp;";
						$annotation_nom="&nbsp;";
						//$annotation_prenom="&nbsp;";
						$annotation_classe="&nbsp;";
						/*
						$annotation_login="&nbsp;";
						$annotation_machine="&nbsp;";
						$annotation_ip="&nbsp;";
						$annotation_arch="&nbsp;";
						$annotation_date="&nbsp;";
						*/
						$affiche_photo="&nbsp;";

						$query="SELECT valeur FROM wallpaper WHERE nom='type_image_$groupe'";
						$result1=mysqli_query($GLOBALS["___mysqli_ston"], $query);
						if(mysqli_num_rows($result1)==0){
							$type_image="???";
							// Il a du se passer quelque chose de travers...
						}
						else{
							$lig1=mysqli_fetch_object($result1);
							if($lig1->valeur=="image_fournie"){
								$type_image="Fournie";
							}
							else{
								$type_image="D&#233;grad&#233;";

								$query="SELECT valeur FROM wallpaper WHERE nom='largeur_$groupe'";
								$result2=mysqli_query($GLOBALS["___mysqli_ston"], $query);
								if(mysqli_num_rows($result2)==0){
									$largeur="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysqli_fetch_object($result2);
									$largeur=$lig2->valeur;
								}

								$query="SELECT valeur FROM wallpaper WHERE nom='hauteur_$groupe'";
								$result2=mysqli_query($GLOBALS["___mysqli_ston"], $query);
								if(mysqli_num_rows($result2)==0){
									$hauteur="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysqli_fetch_object($result2);
									$hauteur=$lig2->valeur;
								}

								$query="SELECT valeur FROM wallpaper WHERE nom='couleur1_$groupe'";
								$result2=mysqli_query($GLOBALS["___mysqli_ston"], $query);
								if(mysqli_num_rows($result2)==0){
									$couleur1="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysqli_fetch_object($result2);
									$couleur1=$lig2->valeur;
								}

								$query="SELECT valeur FROM wallpaper WHERE nom='couleur2_$groupe'";
								$result2=mysqli_query($GLOBALS["___mysqli_ston"], $query);
								if(mysqli_num_rows($result2)==0){
									$couleur2="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysqli_fetch_object($result2);
									$couleur2=$lig2->valeur;
								}

							}
						}

						$query="SELECT valeur FROM wallpaper WHERE nom='annotations_$groupe'";
						$result1=mysqli_query($GLOBALS["___mysqli_ston"], $query);
						if(mysqli_num_rows($result1)==0){
							$annotations="???";
							// Il a du se passer quelque chose de travers...
						}
						else{
							$lig1=mysqli_fetch_object($result1);
							if($lig1->valeur=="inactif"){
								$annotations="Non";
							}
							else{
								$annotations="Oui";

								/*
								$query="SELECT valeur FROM wallpaper WHERE nom='annotation_login_$groupe'";
								$result2=mysql_query($query);
								if(mysql_num_rows($result2)==0){
									$annotation_login="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysql_fetch_object($result2);
									$annotation_login=$lig2->valeur;
								}

								$query="SELECT valeur FROM wallpaper WHERE nom='annotation_machine_$groupe'";
								$result2=mysql_query($query);
								if(mysql_num_rows($result2)==0){
									$annotation_machine="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysql_fetch_object($result2);
									$annotation_machine=$lig2->valeur;
								}

								$query="SELECT valeur FROM wallpaper WHERE nom='annotation_ip_$groupe'";
								$result2=mysql_query($query);
								if(mysql_num_rows($result2)==0){
									$annotation_ip="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysql_fetch_object($result2);
									$annotation_ip=$lig2->valeur;
								}

								$query="SELECT valeur FROM wallpaper WHERE nom='annotation_arch_$groupe'";
								$result2=mysql_query($query);
								if(mysql_num_rows($result2)==0){
									$annotation_arch="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysql_fetch_object($result2);
									$annotation_arch=$lig2->valeur;
								}

								$query="SELECT valeur FROM wallpaper WHERE nom='annotation_date_$groupe'";
								$result2=mysql_query($query);
								if(mysql_num_rows($result2)==0){
									$annotation_date="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysql_fetch_object($result2);
									$annotation_date=$lig2->valeur;
								}

								*/

								$query="SELECT valeur FROM wallpaper WHERE nom='couleur_txt_$groupe'";
								$result2=mysqli_query($GLOBALS["___mysqli_ston"], $query);
								if(mysqli_num_rows($result2)==0){
									$couleur_txt="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysqli_fetch_object($result2);
									$couleur_txt=$lig2->valeur;
								}

								$query="SELECT valeur FROM wallpaper WHERE nom='taille_police_$groupe'";
								$result2=mysqli_query($GLOBALS["___mysqli_ston"], $query);
								if(mysqli_num_rows($result2)==0){
									$taille_police="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysqli_fetch_object($result2);
									$taille_police=$lig2->valeur;
								}

								$query="SELECT valeur FROM wallpaper WHERE nom='annotation_nom_$groupe'";
								$result2=mysqli_query($GLOBALS["___mysqli_ston"], $query);
								if(mysqli_num_rows($result2)==0){
									$annotation_nom="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysqli_fetch_object($result2);
									$annotation_nom=$lig2->valeur;
								}

								/*
								$query="SELECT valeur FROM wallpaper WHERE nom='annotation_prenom_$groupe'";
								$result2=mysql_query($query);
								if(mysql_num_rows($result2)==0){
									$annotation_prenom="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysql_fetch_object($result2);
									$annotation_prenom=$lig2->valeur;
								}
								*/

								$query="SELECT valeur FROM wallpaper WHERE nom='annotation_classe_$groupe'";
								$result2=mysqli_query($GLOBALS["___mysqli_ston"], $query);
								if(mysqli_num_rows($result2)==0){
									$annotation_classe="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysqli_fetch_object($result2);
									$annotation_classe=$lig2->valeur;
								}

								$query="SELECT valeur FROM wallpaper WHERE nom='affiche_photo_$groupe'";
								$result2=mysqli_query($GLOBALS["___mysqli_ston"], $query);
								if(mysqli_num_rows($result2)==0){
									$affiche_photo="???";
									// Il a du se passer quelque chose de travers...
								}
								else{
									$lig2=mysqli_fetch_object($result2);
									$affiche_photo=$lig2->valeur;
								}

							}
						}

						echo "<tr style=\"text-align:center;\">\n";
						echo "<td style=\"font-weight:bold;\">$groupe</td>\n";
						if (!file_exists("/var/www/se3/Admin/$wallgrp.jpg") and file_exists("/var/se3/Docs/media/fonds_ecran/$wallgrp.jpg"))
							symlink("/var/se3/Docs/media/fonds_ecran/$wallgrp.jpg", "/var/www/se3/Admin/$wallgrp.jpg");
						if (file_exists("/var/se3/Docs/media/fonds_ecran/$wallgrp.jpg"))
							echo "<td><img src=\"../Admin/$wallgrp.jpg?".rand(1,99999)."\" WIDTH=100 alt=\"Fond\"></td>\n";
						else
							echo "<td>Image /var/se3/Docs/media/fonds_ecran/$wallgrp.jpg introuvable!</td>";
						echo "<td>$largeur</td>\n";
						echo "<td>$hauteur</td>\n";
						echo "<td>$couleur1</td>\n";
						echo "<td>$couleur2</td>\n";
						echo "<td>$annotations</td>\n";
						echo "<td>$couleur_txt</td>\n";
						echo "<td>$taille_police</td>\n";
						echo "<td>".si_select_croix($annotation_nom)."</td>\n";
						//echo "<td>".si_select_croix($annotation_prenom)."</td>\n";
						echo "<td>".si_select_croix($annotation_classe)."</td>\n";
						echo "<td>".si_select_croix($affiche_photo)."</td>\n";
						/*
						echo "<td>".si_select_croix($annotation_login)."</td>\n";
						echo "<td>".si_select_croix($annotation_machine)."</td>\n";
						echo "<td>".si_select_croix($annotation_ip)."</td>\n";
						echo "<td>".si_select_croix($annotation_arch)."</td>\n";
						echo "<td>".si_select_croix($annotation_date)."</td>\n";
						echo "<td>".si_select_croix($affiche_photo)."</td>\n";
						*/
						echo "</tr>\n";
					}
					echo "</table>\n";
				echo "<br>"."<a href=\"../fond_ecran/fonds.php\">Retour</a> ";
				}

				//echo "</blockquote>\n";
			
			}
			
			elseif(($choix1=="supprimer")){
					exec("/usr/bin/sudo $chemin_scripts/genere_fond.sh variable_bidon supprimer");
			echo "<a href=\"../fond_ecran/fonds.php\">Retour</a> ";  
			}
			elseif(($choix1=="parametrer")){
				//=================================================
				//                   PARAMETRES
				//=================================================

				function recupere_valeur($nom,$valeur_defaut){
					$query="SELECT * FROM wallpaper WHERE nom='$nom'";
					$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
					if(mysqli_num_rows($resultat)==0){
						$valeur=$valeur_defaut;
					}
					else{
						$ligne=mysqli_fetch_object($resultat);
						$valeur=$ligne->valeur;
					}
					return $valeur;
				}

				function recupere_actif_ou_pas($nom){
					$query="SELECT * FROM wallpaper WHERE nom='$nom'";
					$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
					if(mysqli_num_rows($resultat)==0){
						$checked[1]=" checked=\"true\"";
						$checked[2]="";
					}
					else{
						$ligne=mysqli_fetch_object($resultat);
						$valeur=$ligne->valeur;
						if($valeur=="1"){
							$checked[1]=" checked=\"true\"";
							$checked[2]="";
						}
						else{
							$checked[1]="";
							$checked[2]=" checked=\"true\"";
						}
					}
					return $checked;
				}

				echo "<h2>".gettext("Param&#233;trage")."</h2>\n";

				if((!isset($_POST['groupe']))||($_POST['groupe']=="")){
					if(!isset($_POST['registre_zrn'])){
						echo "<h3>".gettext("Activation/d&#233;sactivation du dispositif")."</h3>\n";
						echo "<blockquote>\n";

						// Validation de l'activation/desactivation du dispositif:
						if(isset($_POST['activation_desactivation'])){
							// Validation des modifs:
							$action=$_POST['action'];

							//echo "<p>\$action=$action</p>\n";


							// Nettoyage:
							$query="DELETE FROM wallpaper WHERE nom='action'";
							$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
							// Mise a jour:
							$query="INSERT INTO wallpaper VALUES('action','$action','')";
							$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

							// Activation ou desactivation du lancement du script 'genere_fond.sh' lors du login.
							// La valeur est testee dans le lanceur.
							$fichier=$fichier=fopen("$chemin_param_fond/actif.txt","w+");
							if($action=="actif"){
								$ecriture=fwrite($fichier,"1");
								echo "<p>".gettext("La g&#233;n&#233;ration de fonds est activ&#233;e").".</p>\n";
							}
							else{
								$ecriture=fwrite($fichier,"0");
								echo "<p>".gettext("La g&#233;n&#233;ration de fonds est d&#233;sactiv&#233;e").".</p>\n";
								//echo "<p><i>NOTE:</i> Si des fonds existent dans les Homes des utilisateurs, ils n'ont pas �t� supprim�s par cette op�ration.<br>\nEn revanche, le script de g�n�ration de fonds ne sera plus ex�cut� � chaque login.</p>\n";
								echo gettext("<p><i>NOTE:</i> Si des fonds existent dans les Homes des utilisateurs, ils n'ont pas &#233;t&#233; supprim&#233;s par cette op&#233;ration.<br>\nEn revanche, il ne sera pas test&#233; chaque nuit, ni &#224; chaque login, si des modifications de fonds doivent &#234;tre effectu&#233;es.</p>\n");
							}
							$fermeture=fclose($fichier);
						}
						else{
							// Formulaire d'activation/desactivation du dispositif:
							echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\">\n";
							echo "<input type=\"hidden\" name=\"choix1\" value=\"parametrer\">\n";

							//Connexion a la base de donnees
							//$etablissement_connexion_mysql=connexion();

							$query="SELECT * FROM wallpaper WHERE nom='action'";
							$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
							if(mysqli_num_rows($resultat)==0){
									echo "<p>".gettext("La g&#233;n&#233;ration de fond n'est actuellement pas active").".</p>\n";
									$checked1=" checked=\"true\"";
									$checked2="";

									// Etat actuel du dispositif:
									$action="inactif";
							}
							else{
								$ligne=mysqli_fetch_object($resultat);
								$valeur=$ligne->valeur;
								//if($valeur=="activer"){
								if($valeur=="actif"){
									echo "<p>".gettext("La g&#233;n&#233;ration de fond est actuellement active").".</p>\n";
									$checked1="";
									$checked2=" checked=\"true\"";

									// Etat actuel du dispositif:
									$action="actif";
								}
								else{
									echo "<p>".gettext("La g&#233;n&#233;ration de fond n'est actuellement pas active").".</p>\n";
									$checked1=" checked=\"true\"";
									$checked2="";

									// Etat actuel du dispositif:
									$action="inactif";
								}
							}
							//echo "<p><input type=\"radio\" name=\"action\" value=\"activer\"$checked1>Activer/Desactiver<input type=\"radio\" name=\"action\" value=\"desactiver\"$checked2> la generation de fonds.</p>\n";
							echo "<p><input type=\"radio\" name=\"action\" id=\"action_actif\" value=\"actif\"$checked1><label for='action_actif' style='cursor:pointer;'>".gettext("Activer/D&#233;sactiver")."</label><input type=\"radio\" name=\"action\" id=\"action_inactif\" value=\"inactif\"$checked2><label for='action_inactif' style='cursor:pointer;'> ".gettext("la g&#233;n&#233;ration de fonds").".</label><br>\n";

							// Proposer de supprimer les K:\Docs\profil\.fond\fond.jpg dans tous les Home?
							// Ou regenerer le registre.zrn de 'base' et modifier les cles de registre pour vider Wallpaper?
							//=========
							// A FAIRE
							//=========

							echo "<input type=\"submit\" name=\"activation_desactivation\" value=\"".gettext("Valider")."\"></p>\n";
							echo "</form>\n";
						}
						echo "</blockquote>\n";
					}
					else{
						// Si on a atteint le stade registre_zrn c'est que le dispositif est actif:
						$action="actif";
					}


					//********************************************************************************************
					// A FAIRE AUSSI:
					// Le nettoyage des registre.zrn avec une demarche du type:
					/*
					SELECT CleID FROM corresp WHERE chemin='HKEY_CURRENT_USER\\Control Panel\\Desktop\\Wallpaper';
					SELECT groupe FROM restrictions WHERE CleID='...';
					DELETE FROM restrictions WHERE CleID='...';
					INSERT INTO restrictions VALUES('','...','base','%USERPROFILE%\\.fond\\fond.jpg');
					UPDATE corresp SET valeur='%USERPROFILE%\\.fond\\fond.jpg' WHERE CleID='...';

					Et reecrire les registre.zrn
					... ou voir comment ils sont generes par les pages de Sandrine.
					*/
					//********************************************************************************************


					// On ne propose de:
					// - choisir l'utilisateur/groupe
					// - regenerer les registre.zrn
					// que si le dispositif est actif:
					if($action=="actif"){
						// Les antislashes posent des problemes dans les tests/selections vers MySQL via PHP.
						// Trois antislashes doivent suffire, mais bon...

						if(!isset($_POST['registre_zrn']) && !file_exists("/usr/share/se3/logonpy/logon.py")){
							echo "<h3>".gettext("Contr&#244;le des tables MySQL")."</h3>\n";
							echo "<blockquote>\n";



							// Verifications pour voir s'il est necessaire de:
							// - corriger les valeurs dans les tables corresp et restrictions
							// - regenerer les registre.zrn

							// Recuperation de l'identifiant de la cle Wallpaper:
							$query="SELECT * FROM corresp WHERE chemin='HKEY_CURRENT_USER\\\\Control Panel\\\\Desktop\\\\Wallpaper'";
							$resultat=mysqli_query($GLOBALS["___mysqli_ston"], $query);
							$ligne=mysqli_fetch_object($resultat);
							$CleID=$ligne->CleID;
							$valeur_cle_wallpaper=$ligne->valeur;

							// Le test ci-dessous fonctionne (pas de probleme avec les antislashes):
							if($valeur_cle_wallpaper!="%USERPROFILE%\.fond\fond.jpg"){
								//echo "<p>La valeur est erronee: $valeur_cle_wallpaper</p>";
								$valeur_wallpaper_dans_les_tables="a_corriger";
							}
							else{
								//echo "<p>La valeur est correcte: $valeur_cle_wallpaper</p>";
								$valeur_wallpaper_dans_les_tables="correcte";
							}

							if($valeur_wallpaper_dans_les_tables!="a_corriger"){
								$query="SELECT groupe FROM restrictions WHERE CleID='$CleID'";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
								//echo mysql_num_rows($resultat)."<br>";
								if(mysqli_num_rows($resultat)!=1){
									//echo "<p>Le nombre de references a Wallpaper dans la table 'restrictions' ne convient pas: ".mysql_num_rows($resultat)."</p>";
									$valeur_wallpaper_dans_les_tables="a_corriger";
								}
								else{
									$query="SELECT valeur FROM restrictions WHERE CleID='$CleID' AND groupe='base' AND valeur='%USERPROFILE%\\\\.fond\\\\fond.jpg'";
									$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
									if(mysqli_num_rows($resultat)!=1){
										//echo "<p>La valeur de la cle Wallpaper dans la table restrictions n'est pas la bonne.</p>";
										$valeur_wallpaper_dans_les_tables="a_corriger";
									}
									else{
										//echo "<p>La valeur de la cle Wallpaper dans la table restrictions est correcte.</p>";
										$valeur_wallpaper_dans_les_tables="correcte";
									}
								}
							}



							echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\">\n";
							echo "<input type=\"hidden\" name=\"choix1\" value=\"parametrer\">\n";
							echo "<input type=\"hidden\" name=\"CleID\" value=\"$CleID\">\n";
							if($valeur_wallpaper_dans_les_tables=="correcte"){
								echo "<p>".gettext("Les valeurs des cl&#233;s Wallpaper dans MySQL sont correctes.<br>\nSi vous voulez quand m&#234;me reg&#233;n&#233;rer les registre.zrn, cochez cette case")." \n";
								echo "<input type=\"checkbox\" name=\"regenerer_registre_zrn\" value=\"oui\">\n";
								echo " ".gettext("et validez").".</p>\n";
								echo "<p>".gettext("Sinon, validez simplement pour passer au choix du groupe").".</p>\n";
							}
							else{
								echo "<p>".gettext("Les valeurs des cl&#233;s Wallpaper dans MySQL ne conviennent pas.<br>\nEn validant ci-dessous, ces valeurs vont &#234;tre corrig&#233;es et les registre.zrn vont &#234;tre reg&#233;n&#233;r&#233;s").".</p>\n";
								echo "<input type=\"hidden\" name=\"regenerer_registre_zrn\" value=\"oui\">\n";
							}
							echo "<input type=\"submit\" name=\"registre_zrn\" value=\"".gettext("Valider")."\"></p>\n";
							echo "</form>\n";

							echo "</blockquote>\n";
						}
						else{
							// Recuperation des variables:
							$CleID=isset($_POST['CleID']) ? $_POST['CleID'] : "";

							if(isset($_POST['regenerer_registre_zrn'])){
								$regenerer_registre_zrn=$_POST['regenerer_registre_zrn'];
							}
							else{
								$regenerer_registre_zrn="non";
							}

							//============================================================================
							// Modification des cles de registre pour utiliser %USERPROFILE%\\.fond\\fond.jpg
							//============================================================================
							/*
							$query="SELECT CleID FROM corresp WHERE chemin='HKEY_CURRENT_USER\\\\Control Panel\\\\Desktop\\\\Wallpaper'";
							$resultat=mysql_query($query);
							$ligne=mysql_fetch_object($resultat);
							$CleID=$ligne->CleID;
							*/

							//echo "\$CleID=$CleID<br>";

							if($regenerer_registre_zrn!="non"){
								echo "<h3>".gettext("Corrections des valeurs dans les tables MySQL et reg&#233;n&#233;ration des registre.zrn")."</h3>\n";
								echo "<blockquote>\n";

								$query="SELECT groupe FROM restrictions WHERE CleID='$CleID'";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
								//echo mysql_num_rows($resultat)."<br>";
								if(mysqli_num_rows($resultat)>0){
									while($ligne=mysqli_fetch_object($resultat)){
										// Nettoyer les registre.zrn correspondants
										$template=$ligne->groupe;

										$query="DELETE FROM restrictions WHERE cleID='$CleID' AND groupe='$template'";
										$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);

										// Pour 'base', le fichier sera regenere apres une mise a jour des valeurs:
										if(($template!="base")&&(file_exists("/home/templates/$template"))){
											refreshzrn("$template");
										}
									}
								}

								// Suppression des valeurs de la cle Wallpaper:
								//$query="DELETE FROM restrictions WHERE cleID='$CleID'";
								//$resultat = mysql_query($query);

								// Definition de la valeur de la cle pour le template 'base':
								$query="INSERT INTO restrictions VALUES('','$CleID','base','%USERPROFILE%\\\\.fond\\\\fond.jpg')";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								//echo "ID: ".mysql_insert_id()."<br>";

								// Modification de la valeur par defaut de la cle:
								$query="UPDATE corresp SET valeur='%USERPROFILE%\\\\.fond\\\\fond.jpg' WHERE CleID='$CleID'";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								// Generer le nouveau registre.zrn pour le template 'base'
								refreshzrn("base");

								echo "<p>".gettext("Les registre.zrn ont &#233;t&#233; reg&#233;n&#233;r&#233;s pour utiliser un chemin commun pour le fond: %USERPROFILE%\.fond\fond.jpg")."</p>\n";
								// Seuls ceux qui avaient une entree Wallpaper dans 'restrictions' ont ete regeneres.

								echo "</blockquote>\n";
							}



							//==================================
							//      Choix du groupe
							//==================================
							echo "<h3>".gettext("Choix du groupe")."</h3>\n";
							echo "<blockquote>\n";

							// Proposer: admin, Profs, Eleves, Administratifs, Classe_*, overfill

							echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\" name=\"choix_grp\">\n";
							echo "<input type=\"hidden\" name=\"choix1\" value=\"parametrer\">\n";
							echo "<input type=\"hidden\" name=\"CleID\" value=\"$CleID\">\n";

							// Le 'groupe' peut etre l'utilisateur admin
							echo "<p><select name=\"groupe\" onchange=\"document.forms['choix_grp'].submit();\">\n";
							echo "<option value=\"\">".gettext("Choisissez un utilisateur/groupe")."</option>\n";
							echo "<option value=\"admin\">".gettext("admin (<i>l'utilisateur</i>)")."</option>\n";
							echo "<option value=\"overfill\">".gettext("overfill (<i>groupe de ceux qui d&#233;passent leurs quotas</i>)")."</option>\n";
							echo "<option value=\"Administratifs\">".gettext("Administratifs")."</option>\n";
							echo "<option value=\"Profs\">".gettext("Profs")."</option>\n";
							echo "<option value=\"Eleves\">".gettext("Eleves")."</option>\n";

							$filter="";
							$list_groups=search_groups("(&(cn=*) $filter )");
							$j=0;
							for ($loop=0; $loop < count ($list_groups) ; $loop++) {
								if ( preg_match ("/Classe_/", $list_groups[$loop]["cn"]) ) {
									$classe[$j]["cn"] = $list_groups[$loop]["cn"];
									//$classe[$j]["description"] = $list_groups[$loop]["description"];
									echo "<option value=\"".$classe[$j]["cn"]."\">".$classe[$j]["cn"]."</option>\n";
									$j++;
								}
							}
							echo "</select><br>\n";

							echo "<input type=\"submit\" name=\"choix_groupe\" value=\"".gettext("Valider")."\"></p>\n";
							echo "</form>\n";
							echo "</blockquote>\n";

							echo "<p><i>".gettext("NOTE").":</i></p>\n";
							echo "<blockquote>\n";
							echo "<p>".gettext("Si vous d&#233;finissez des fonds pour le groupe 'Eleves' et pour des 'Classe_XXX', les d&#233;finitions de classes seront prioritaires").".</p>\n";
							echo "<p>".gettext("Et si vous d&#233;passez vos quotas (<i>si vous les avez mis en place</i>), les param&#232;tres d&#233;finis pour overfill seront prioritaires sur tous les autres").".</p>\n";
							echo "<p><br></p>\n";
							echo "</blockquote>\n";
						}
					}
				}
				else{
					//======================
					// A CE STADE:
					// Le groupe est choisi.
					$groupe=$_POST['groupe'];
					if($groupe == "admin")
						$wallgrp="Adminse3";
					else
						$wallgrp=$groupe;
					//======================

					$tabcolor[1]="aquamarine";
					$tabcolor[-1]="white";
					$alt=1;


					//===============================
					// Parametres des images communes
					//===============================

					if(!isset($_POST['type_image'])){
						// Parametres pour le groupe choisi
						echo "<h3>".gettext("Param&#232;tres pour")." $groupe</h3>\n";
						echo "<blockquote>\n";
						echo "<h4>".gettext("Fond d'&#233;cran actuel")."</h4>\n";
						
						if (!file_exists("/var/www/se3/Admin/$wallgrp.jpg") and file_exists("/var/se3/Docs/media/fonds_ecran/$wallgrp.jpg"))
							symlink("/var/se3/Docs/media/fonds_ecran/$wallgrp.jpg", "/var/www/se3/Admin/$wallgrp.jpg");
						if (file_exists("/var/se3/Docs/media/fonds_ecran/$wallgrp.jpg"))
							echo "<img src=\"../Admin/$wallgrp.jpg?".rand(1,99999)."\" WIDTH=200 alt=\"Fond\">\n";

						// Formulaire de changement des parametres:
						echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\" enctype=\"multipart/form-data\">\n";
						echo "<input type=\"hidden\" name=\"choix1\" value=\"parametrer\">\n";
						echo "<input type=\"hidden\" name=\"groupe\" value=\"$groupe\">\n";

						// Creer/modifier/supprimer la mise en place d'image pour le groupe choisi:
						$query="SELECT * FROM wallpaper WHERE nom='fond_".$groupe."'";
						$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
						if(mysqli_num_rows($resultat)>0){
							$ligne=mysqli_fetch_object($resultat);
							switch($ligne->valeur){
								case "actif":
									echo "<h4>".gettext("D&#233;sactivation")."</h4>\n";
									echo "<p>".gettext("Pour d&#233;sactiver la mise en place d'image pour")." $groupe, ".gettext("cochez ici")." <input type=\"checkbox\" name=\"suppr_img\" value=\"$groupe\"> ".gettext("et")." <input type=\"submit\" name=\"choix_params0\" value=\"".gettext("validez")."\">.</p>\n";
									break;
								;;
								case "inactif":
									echo "<p><input type=\"hidden\" name=\"suppr_img\" value=\"\">\n";
									break;
								;;
							}
						}
						else{
							echo "<p><input type=\"hidden\" name=\"suppr_img\" value=\"\">\n";
						}

						echo "<h4>".gettext("Choix de l'image")."</h4>\n";
						//Pas de modification des images:
						echo "<p><input type=\"radio\" name=\"type_image\" id=\"type_image_pas_de_modif\" value=\"pas_de_modif\" checked=\"true\"><label for='type_image_pas_de_modif' style='cursor:pointer;'> ".gettext("Ne pas modifier les images existantes").".</label></p>\n";

						//Utiliser l'image fournie:
						echo "<p><input type=\"radio\" name=\"type_image\" id=\"image_fournie\" value=\"image_fournie\"><label for='image_fournie' style='cursor:pointer;'> ".gettext("Utiliser l'image fournie").":</label></p>\n";
						echo "<blockquote>\n";
						echo "<p>".gettext("Image").": <input type=\"file\" name=\"image\" enctype=\"multipart/form-data\" onfocus=\"document.getElementById('image_fournie').checked='true'\"></p>\n";
						echo "</blockquote>\n";

						//Generer des degrades:
						echo "<p><input type=\"radio\" name=\"type_image\" id=\"degrade\" value=\"degrade\"><label for='degrade' style='cursor:pointer;'> ".gettext("G&#233;n&#233;rer un d&#233;grad&#233;").":</label></p>\n";
						echo "<blockquote>\n";
						echo "<table border=\"1\">\n";


						// Couleurs par defaut:
						switch($groupe){
							case "admin":
								$couleur1="red";
								$couleur2="yellow";
								break;
							case "Administratifs":
								$couleur1="blue";
								$couleur2="yellow";
								break;
							case "Eleves":
								$couleur1="cornflowerblue";
								$couleur2="tomato";
								break;
							case "Profs":
								$couleur1="green";
								$couleur2="orange";
								break;
							case "overfill":
								$couleur1="red";
								$couleur2="red";
								break;
							default:
								// Et sinon: Classe_XXX
								$couleur1="coral";
								$couleur2="lime";
						}


						$valeur=recupere_valeur('largeur_'.$groupe,'800');
						echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>".gettext("Largeur de l'image")."</td><td><input type=\"text\" id=\"largeur\"  name=\"largeur\" value=\"$valeur\" onfocus=\"document.getElementById('degrade').checked='true'\"></td></tr>\n";

						$valeur=recupere_valeur('hauteur_'.$groupe,'600');
						echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>".gettext("Hauteur de l'image")."</td><td><input type=\"text\" id=\"hauteur\" name=\"hauteur\" value=\"$valeur\" onfocus=\"document.getElementById('degrade').checked='true'\"></td></tr>\n";

						$alt=$alt*(-1);
						$valeur=recupere_valeur('couleur1_'.$groupe,$couleur1);
						//echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Couleur 1</td><td><input type=\"text\" name=\"couleur1\" id=\"couleur1\" value=\"$valeur\" onfocus=\"document.getElementById('degrade').checked='true'\"></td></tr>\n";
						echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>".gettext("Couleur")." 1</td><td>\n";
						echo "<select name=\"couleur1\" id=\"couleur1\" onfocus=\"document.getElementById('degrade').checked='true'\">\n";
						for($i=0;$i<count($tabcouleur);$i++){
							if($tabcouleur[$i]=="$valeur"){
								$checked=" selected=\"true\"";
							}
							else{
								$checked="";
							}
							echo "<option style=\"background-color: $tabcouleur[$i]\" value=\"$tabcouleur[$i]\"$checked>$tabcouleur[$i]</option>\n";
						}
						echo "</select>\n";
						echo "</td></tr>\n";

						$valeur=recupere_valeur('couleur2_'.$groupe,$couleur2);
						//echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Couleur 2</td><td><input type=\"text\" name=\"couleur2\" id=\"couleur2\" value=\"$valeur\" onfocus=\"document.getElementById('degrade').checked='true'\"></td></tr>\n";
						echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>".gettext("Couleur")." 2</td><td>\n";
						echo "<select name=\"couleur2\" id=\"couleur2\" onfocus=\"document.getElementById('degrade').checked='true'\">\n";
						for($i=0;$i<count($tabcouleur);$i++){
							if($tabcouleur[$i]=="$valeur"){
								$checked=" selected=\"true\"";
							}
							else{
								$checked="";
							}
							echo "<option style=\"background-color: $tabcouleur[$i]\" value=\"$tabcouleur[$i]\"$checked>$tabcouleur[$i]</option>\n";
						}
						echo "</select>\n";
						echo "</td></tr>\n";

						echo "</table>\n";
						echo "</blockquote>\n";

						echo "<input type=\"submit\" name=\"choix_params\" value=\"".gettext("Valider")."\">\n";
						echo "</form>\n";


						// Formulaire de test du choix de couleurs effectue:
						echo "<form action=\"test_degrade.php\" name=\"test_degrade\" method=\"POST\" target=\"_blank\">\n";
						echo "<input type=\"hidden\" name=\"couleur1\" value=\"\">\n";
						echo "<input type=\"hidden\" name=\"couleur2\" value=\"\">\n";
						echo "<input type=\"hidden\" name=\"hauteur\" value=\"\">\n";
						echo "<input type=\"hidden\" name=\"largeur\" value=\"\">\n";
						echo "<input type=\"hidden\" name=\"groupe\" value=\"$groupe\">\n";
						echo "<p>".gettext("Pour tester le d&#233;grad&#233;").": <input type=\"button\" name=\"bouton_test_degrade\" value=\"".gettext("Tester")."\" onClick=\"";
						echo "document.forms['test_degrade'].couleur1.value=document.getElementById('couleur1').value;";
						echo "document.forms['test_degrade'].couleur2.value=document.getElementById('couleur2').value;";
						echo "document.forms['test_degrade'].hauteur.value=document.getElementById('hauteur').value;";
						echo "document.forms['test_degrade'].largeur.value=document.getElementById('largeur').value;";
						echo "document.forms['test_degrade'].submit();";
						echo "\"></p>\n";
						echo "</form>\n";

						echo "</blockquote>\n";

						echo "<p><i>".gettext("NOTES").":</i></p>\n";
						echo "<ul>\n";
						echo "<li><p>".gettext("Ne descendez pas en dessous de 300px de large pour un bon fonctionnement des annotations").".</p></li>\n";
						
						echo "<li><p>".gettext("Les couleurs propos&#233;es ci-dessus sont consultables &#224; l'adresse suivante").":<br>
<a href=\"http://www.commentcamarche.net/html/htmlcouleurs.php3\" target=\"blank\">http://www.commentcamarche.net/html/htmlcouleurs.php3</a>.</p>
<p>".gettext("Notez que certains d&#233;grad&#233;s ont tendance &#224; virer &#224; l'arc-en-ciel (<i>constat&#233; sous Woody</i>)").".</p></li>\n";
						// ou utilisez les codes HTML comme par exemple #ff0000
						// Choix supprime pour eviter des blagues.
						echo "</ul>\n";
						echo "<p><br></p>\n";

						//echo "</blockquote>\n";
					}
					else{

						if((isset($_POST['choix_params']))||(isset($_POST['choix_params0']))){
							//===========================
							//RECUPERATION DES VARIABLES:
							//$action=$_POST['action'];
							$groupe=$_POST['groupe'];

							$type_image=$_POST['type_image'];

							$largeur=$_POST['largeur'];
							$hauteur=$_POST['hauteur'];
							$couleur1=$_POST['couleur1'];
							$couleur2=$_POST['couleur2'];

							// Il faudrait controler les valeurs saisies..
							// - numeriques et entieres pour les dimensions
							// - couleurs non vides et valides

							if($type_image=="image_fournie"){
								$tmp_image=$_FILES['image']['tmp_name'];
								$image=$_FILES['image']['name'];
								$size_image=$_FILES['image']['size'];
							}
							//===========================


							//===========================================================
							//Controle de la version de Samba:
							//Necessaire pour la commande convert lors de l'annotation:
							$fichier=fopen("$chemin_param_fond/version_samba.txt","r");
							//$test_samba=fread($fichier, filesize($fichier));
							$test_samba=fgets($fichier,4096);
							//if($test_samba=="2"){
							if(strstr($test_samba,"2")){
								$prefixe="";
							}
							else{
								$prefixe="jpg:";
							}
							$fermeture=fclose($fichier);
							// En fait ce n'est pas lie a la version de Samba,
							// mais a la version d'ImageMgick qui est passee de 5 a 6 entre Woody et Sarge.
							//===========================================================





							//echo "<p>\$_POST['suppr_img']=".$_POST['suppr_img']."</p>\n";

							if($_POST['suppr_img']=="$groupe"){
								// Supprimer les entrees et fichiers pour $groupe
								//unlink("$chemin_param_fond/parametres_$groupe.sh");

								// Desactiver seulement:
								$fichier=fopen("$chemin_param_fond/fond_$groupe.txt","w+");
								$ecriture=fwrite($fichier,'inactif');
								$fermeture=fclose($fichier);


								echo "<h3>".gettext("D&#233;sactivation de l'utilisation d'image sur")." '$groupe'.</h3>\n";
								echo "<p>".gettext("Cela ne signifie pas que les membres de")." $groupe ".gettext("ne verront pas s'afficher un fond d'&#233;cran, mais seulement que le crit&#232;re")." '$groupe' ".gettext("ne sera pas utilis&#233; pour la g&#233;n&#233;ration de fond lors des logins &#224; venir").".</p>\n";

								$query="DELETE FROM wallpaper WHERE nom='fond_$groupe'";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								$query="INSERT INTO wallpaper VALUES('fond_$groupe','inactif','')";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								//$fermeture=mysql_close();

								// Si on desactive l'utilisation de l'image eventuellement presente
								// dans /var/se3/Docs/media/fonds_ecran pour le groupe Profs,
								// on ne cherche pas non plus a annoter ces images
								$choix_annotations="non";
								$suppr_annotations=$groupe;
							}
							else{
								if($type_image!="pas_de_modif"){
									// Insertion dans la base et generation des fichiers dans /etc/se3/fonds_ecran

									//Connexion a la base de donnees
									//$etablissement_connexion_mysql=connexion();

									//On commence par vider:
									//$query="TRUNCATE TABLE wallpaper";
									//On commence par vider ce qui concerne $groupe:
									/*
									$query="DELETE FROM wallpaper WHERE nom LIKE '%_$groupe'";
									$resultat = mysql_query($query);
									*/

									$liste_nettoye=Array('fond_','type_image_');
									for($i=0;$i<count($liste_nettoye);$i++){
										$query="DELETE FROM wallpaper WHERE nom='".$liste_nettoye[$i].$groupe."'";
										$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
									}



									$query="INSERT INTO wallpaper VALUES('fond_$groupe','actif','')";
									$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

									$fichier=fopen("$chemin_param_fond/fond_$groupe.txt","w+");
									$ecriture=fwrite($fichier,'actif');
									$fermeture=fclose($fichier);



									$query="INSERT INTO wallpaper VALUES('type_image_$groupe','$type_image','')";
									$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);



									if($type_image=="image_fournie"){
										$query="DELETE FROM wallpaper WHERE nom='image_$groupe'";
										$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

										//$query="INSERT INTO wallpaper VALUES('image_$groupe','$image','')";
										$query="INSERT INTO wallpaper VALUES('image_$groupe','$groupe','')";
										$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

										// Generation du fichier de parametres:
										$fichier=fopen("$chemin_param_fond/parametres_$groupe.sh","w+");
										$ecriture=fwrite($fichier,'# L image est fournie (non g&#233;n&#233;r&#233;e)\n');
										$fermeture=fclose($fichier);

										// A VERIFIER:
										// Il faudrait rendre ce script parametres_$groupe.sh executable, non?

										// Mise en place de l'image uploadee:
										if(file_exists("$dossier_upload_images/$groupe.jpg")){
											unlink("$dossier_upload_images/$groupe.jpg");
										}
										echo "<p>";
										if(is_uploaded_file($tmp_image)){
											//unlink("/var/se3/Docs/media/ImageMagick/admin.jpg");
											//$dest_file="/var/se3/Docs/media/ImageMagick/admin.jpg";
											//www-se3 ne va pas avoir le droit de le coller directement la.
											//Meme avec des ACL... parce que www-se3 n'y ecrit pas a travers Samba.
											//Placer dans un dossier temporaire et sudo pour placer l'image.

											$dest_file="$dossier_upload_images/$groupe.jpg";
											$source_file=stripslashes("$tmp_image");
											$res_copy=copy("$source_file" , "$dest_file");
											echo "".gettext("Le fond")." $image ".gettext("va &#234;tre mis en place sous le nom")." $groupe.jpg ".gettext("dans")." I:\\media\\fonds_ecran";
										}
										echo "</p>\n";
										// Mise en place du fichier de $dossier_upload_images vers I:\media\fonds_ecran
										exec("/usr/bin/sudo $chemin_scripts/genere_fond.sh variable_bidon image_fournie $groupe");
									}



									if($type_image=="degrade"){

										$liste_nettoye=Array('largeur_','hauteur_','couleur1_','couleur2_');
										for($i=0;$i<count($liste_nettoye);$i++){
											$query="DELETE FROM wallpaper WHERE nom='".$liste_nettoye[$i].$groupe."'";
											$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
										}

										$query="INSERT INTO wallpaper VALUES('largeur_$groupe','$largeur','')";
										$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
										$query="INSERT INTO wallpaper VALUES('hauteur_$groupe','$hauteur','')";
										$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
										$query="INSERT INTO wallpaper VALUES('couleur1_$groupe','$couleur1','')";
										$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
										$query="INSERT INTO wallpaper VALUES('couleur2_$groupe','$couleur2','')";
										$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

										//Generation du fichier de parametres:
										$fichier=fopen("$chemin_param_fond/parametres_$groupe.sh","w+");
										$ecriture=fwrite($fichier,'largeur='.$largeur.'
hauteur='.$hauteur.'

# Base de l image de fond:
couleur1="'.$couleur1.'"
couleur2="'.$couleur2.'"

# Pour choisir d"autres couleurs, voir:
# http://www.commentcamarche.net/html/htmlcouleurs.php3
# V�rifier si toutes les couleurs sont accept�es par ImageMagick.
');
										$fermeture=fclose($fichier);


										// Nettoyage prealable:
										exec("/usr/bin/sudo $chemin_scripts/genere_fond.sh variable_bidon nettoyer $groupe",$tabretour);
										if(count($tabretour)>0) {
											echo "<p style='color:red'>Nettoyage prealable:<br />exec(\"/usr/bin/sudo $chemin_scripts/genere_fond.sh variable_bidon nettoyer $groupe\",\$tabretour);<br />";
											for($i=0;$i<count($tabretour);$i++){
												echo "\$tabretour[$i]=$tabretour[$i]<br>";
											}
											echo "</p>\n";
										}

										// Generation du degrade:
										exec("/usr/bin/sudo $chemin_scripts/genere_fond.sh variable_bidon genere_base $groupe",$tabretour);
										if(count($tabretour)>0) {
											echo "<p style='color:red'>Generation degrade:<br />exec(\"/usr/bin/sudo $chemin_scripts/genere_fond.sh variable_bidon genere_base $groupe\",\$tabretour);<br />";
											for($i=0;$i<count($tabretour);$i++){
												echo "\$tabretour[$i]=$tabretour[$i]<br>";
											}
											echo "</p>\n";
										}

										//La 'variable_bidon' est la pour passer le test sur $1
										//Il faut juste eviter de creer un dossier '/home/variable_bidon'
										echo "<p>".gettext("Le nouveau fond a &#233;t&#233; g&#233;n&#233;r&#233; dans")." 'I:\\media\\fonds_ecran'.</p>\n";

										// Mise en place d'une copie au format PNG pour l'interface web:
										// Le script fond_jpg2png.sh n'existe pas.
										//echo "exec(\"/usr/bin/sudo $chemin_scripts/fond_jpg2png.sh $groupe\");<br />";
										//exec("/usr/bin/sudo $chemin_scripts/fond_jpg2png.sh $groupe");

										if((file_exists("/var/se3/Docs/media/fonds_ecran/$groupe.bmp"))&&
										(!file_exists("/var/se3/Docs/media/fonds_ecran/$groupe.jpg"))) {
											// Mise en place d'une copie au format JPG pour l'interface web:
											exec("/usr/bin/sudo $chemin_scripts/fond_bmp2jpg.sh $groupe",$tabretour);
											if(count($tabretour)>0) {
												echo "<p style='color:red'>Copie JPG pour consultation web:<br />exec(\"/usr/bin/sudo $chemin_scripts/fond_bmp2jpg.sh $groupe\",\$tabretour);<br />";
												for($i=0;$i<count($tabretour);$i++){
													echo "\$tabretour[$i]=$tabretour[$i]<br>";
												}
											}
										}
									}

									//$fermeture=mysql_close();

								}
								else{
									echo "<p>".gettext("Le fond n'a pas &#233;t&#233; modifi&#233; dans")." 'I:\\media\\fonds_ecran'.</p>\n";
									if(!file_exists("/var/se3/Docs/media/fonds_ecran/$wallgrp.jpg")){
										echo "<p style=\"color:red;\">".gettext("ERREUR: Le fichier")." I:\\media\\fonds_ecran\\$groupe.jpg ".gettext("n'existe pas.<br>\nSi vous ne d&#233;finissez pas d'image, vous risquez de ne pas obtenir ce que vous souhaitez!")."</p>\n";
									}
								}
							}
						}



						//============
						// Annotations
						//============
						$groupe=$_POST['groupe'];
						$type_image=$_POST['type_image'];

						// Si $type_image a change, il faut regenerer les fonds pour les utilisateurs concernes.
						// Idem si les annotations sont supprimees.
						// Idem si les annotations sont modifiees (A TESTER...).
						// Supprimer le fond.txt pour chaque utilisateur suffit.
						if(($type_image!="pas_de_modif")||($suppr_annotations==$groupe)){
							exec("/usr/bin/sudo $chemin_scripts/genere_fond.sh variable_bidon annuler $groupe");
							$regeneration_fond_programmee="oui";
						}


						//if(!isset($choix_annotations)){
						//if(!isset($_POST['choix_annotations'])){
						// $choix_annotation peut-etre initialisee sans soumission de formulaire
						// dans le cas de la desactivation du fond pour un $groupe
						if((!isset($_POST['choix_annotations']))&&(!isset($choix_annotations))){
							// Annotations pour le groupe choisi
							echo "<h3>".gettext("Annotations pour")." $groupe</h3>\n";
							echo "<blockquote>\n";

							echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"POST\">\n";
							echo "<input type=\"hidden\" name=\"choix1\" value=\"parametrer\">\n";
							echo "<input type=\"hidden\" name=\"groupe\" value=\"$groupe\">\n";
							echo "<input type=\"hidden\" name=\"type_image\" value=\"$type_image\">\n";
							echo "<input type=\"hidden\" name=\"regeneration_fond_programmee\" value=\"$regeneration_fond_programmee\">\n";

							echo "<p><label for='suppr_annotations' style='cursor:pointer;'>".gettext("Pour d&#233;sactiver l'annotation des images pour")." $groupe, ".gettext("cochez ici").": </label><input type=\"checkbox\" name=\"suppr_annotations\" id=\"suppr_annotations\" value=\"$groupe\"></p>\n";

							echo "<p>".gettext("Couleur").":</p>\n";
							echo "<blockquote>\n";
							echo "<table border=\"1\">\n";


							// Couleurs par defaut:
							switch($groupe){
								case "admin":
									$couleur_txt="yellow";
									break;
								case "Administratifs":
									$couleur_txt="yellow";
									break;
								case "Eleves":
									$couleur_txt="tomato";
									break;
								case "Profs":
									$couleur_txt="orange";
									break;
								case "overfill":
									$couleur_txt="black";
									break;
								default:
									// Et sinon: Classe_XXX
									$couleur_txt="black";
							}

							$valeur=recupere_valeur('couleur_txt_'.$groupe,$couleur_txt);
							//echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Couleur texte</td><td><input type=\"text\" name=\"couleur_txt\" value=\"$valeur\"></td></tr>\n";
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>".gettext("Couleur texte")."</td><td>\n";
							echo "<select name=\"couleur_txt\">\n";
							for($i=0;$i<count($tabcouleur);$i++){
								if($tabcouleur[$i]=="$valeur"){
									$checked=" selected=\"true\"";
								}
								else{
									$checked="";
								}
								echo "<option style=\"background-color: $tabcouleur[$i]\" value=\"$tabcouleur[$i]\"$checked>$tabcouleur[$i]</option>\n";
							}
							echo "</select>\n";
							echo "</td></tr>\n";

							$valeur=recupere_valeur('taille_police_'.$groupe,"20");
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>".gettext("Taille de la police")."</td><td><input type=\"text\" name=\"taille_police\" value=\"$valeur\"></td></tr>\n";

							echo "</table>\n";
							echo "</blockquote>\n";



							echo "<p>".gettext("Informations &#224; afficher").":</p>\n";
							echo "<blockquote>\n";
							echo "<table border=\"1\">\n";
							$alt=$alt*(-1);

							/*
							$checked=recupere_actif_ou_pas('annotation_login_'.$groupe);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Afficher le login</td><td><input type=\"radio\" name=\"afficher_login\" value=\"1\" $checked[1]>Afficher ou non<input type=\"radio\" name=\"afficher_login\" value=\"0\"$checked[2]></td></tr>\n";

							$checked=recupere_actif_ou_pas('annotation_machine_'.$groupe);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Afficher le nom NETBIOS du poste</td><td><input type=\"radio\" name=\"afficher_machine\" value=\"1\"$checked[1]>Afficher ou non<input type=\"radio\" name=\"afficher_machine\" value=\"0\"$checked[2]></td></tr>\n";

							$checked=recupere_actif_ou_pas('annotation_ip_'.$groupe);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Afficher l'adresse IP du poste</td><td><input type=\"radio\" name=\"afficher_ip\" value=\"1\"$checked[1]>Afficher ou non<input type=\"radio\" name=\"afficher_ip\" value=\"0\"$checked[2]></td></tr>\n";

							$checked=recupere_actif_ou_pas('annotation_arch_'.$groupe);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Afficher l'architecture</td><td><input type=\"radio\" name=\"afficher_arch\" value=\"1\"$checked[1]>Afficher ou non<input type=\"radio\" name=\"afficher_arch\" value=\"0\"$checked[2]></td></tr>\n";

							$checked=recupere_actif_ou_pas('annotation_date_'.$groupe);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Afficher la date</td><td><input type=\"radio\" name=\"afficher_date\" value=\"1\"$checked[1]>Afficher ou non<input type=\"radio\" name=\"afficher_date\" value=\"0\"$checked[2]></td></tr>\n";
							*/

							$checked=recupere_actif_ou_pas('annotation_nom_'.$groupe);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>".gettext("Afficher le nom")."</td><td><input type=\"radio\" name=\"annotation_nom\" value=\"1\"$checked[1]>".gettext("Afficher ou non")."<input type=\"radio\" name=\"annotation_nom\" value=\"0\"$checked[2]></td></tr>\n";

							/*
							$checked=recupere_actif_ou_pas('annotation_prenom_'.$groupe);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Afficher le prenom</td><td><input type=\"radio\" name=\"annotation_prenom\" value=\"1\"$checked[1]>Afficher ou non<input type=\"radio\" name=\"annotation_prenom\" value=\"0\"$checked[2]></td></tr>\n";
							*/

							$checked=recupere_actif_ou_pas('annotation_classe_'.$groupe);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>".gettext("Afficher la classe")."</td><td><input type=\"radio\" name=\"annotation_classe\" value=\"1\"$checked[1]>".gettext("Afficher ou non")."<input type=\"radio\" name=\"annotation_classe\" value=\"0\"$checked[2]></td></tr>\n";

							echo "</table>\n";
							echo "</blockquote>\n";

							/*
							echo "<p>Position des annotations:</p>\n";
							echo "<blockquote>\n";
							echo "<table border=\"1\">\n";
							$alt=$alt*(-1);
							$valeur=recupere_valeur('xtxt_'.$groupe,100);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Abscisse</td><td><input type=\"text\" name=\"xtxt\" value=\"$valeur\"></td></tr>\n";
							$valeur=recupere_valeur('ytxt_'.$groupe,20);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>Ordonnee</td><td><input type=\"text\" name=\"ytxt\" value=\"$valeur\"></td></tr>\n";
							echo "</table>\n";
							echo "</blockquote>\n";
							*/

							$checked=recupere_actif_ou_pas('affiche_photo_'.$groupe);
							echo "<p>".gettext("Pour afficher la photo si elle existe dans")." I:\\trombine: <input type=\"checkbox\" name=\"affiche_photo\" value=\"$groupe\"$checked[1]></p>\n";
							echo "<blockquote>\n";
							echo "<p>".gettext("Si oui").":</p>\n";
							echo "<table border=\"1\">\n";
							$alt=$alt*(-1);
							$valeur=recupere_valeur('dim_photo_'.$groupe,100);
							echo "<tr style=\"background-color: $tabcolor[$alt];\"><td>".gettext("Taille de la photo")."</td><td><input type=\"text\" name=\"dim_photo\" value=\"$valeur\"$checked[2]></td></tr>\n";
							echo "</table>\n";
							echo "</blockquote>\n";

							echo "<input type=\"submit\" name=\"choix_annotations\" value=\"".gettext("Valider")."\">\n";
							echo "</form>\n";
							echo "</blockquote>\n";
							echo "<p><i>".gettext("NOTE").":</i> ".gettext("La photo est assimil&#233;e &#224; une annotation.<br>Si vous d&#233;sactivez l'annotation, aucune photo ne sera ins&#233;r&#233;e m&#234;me si elle existe dans")." I:\\trombine</p>\n";
						}
						else{
							//Connexion a la base de donnees
							//$etablissement_connexion_mysql=connexion();
							//===================================
							// DEBUG:
							//foreach($_POST as $key => $value) {
							//	echo "\$_POST['$key']=$value<br />";
							//}
							//===================================

							// La variable $suppr_annotations peut etre initialisee
							//  sans validation de formulaire lorsqu'on desactive
							// l'utilisation d'image pour le groupe $groupe
							if(!isset($suppr_annotations)){
								$suppr_annotations=isset($_POST['suppr_annotations']) ? $_POST['suppr_annotations'] : "";
							}

							// Validation des choix d'annotation:
							//if($_POST['suppr_annotations']==$groupe){
							if($suppr_annotations==$groupe){
								echo "<h3>".gettext("D&#233;sactivation des annotations pour")." '$groupe'.</h3>\n";
								
								$fichier=fopen("$chemin_param_fond/annotations_$groupe.txt","w+");
								$ecriture=fwrite($fichier,'inactif');
								$fermeture=fclose($fichier);

								// Nettoyage:
								$query="DELETE FROM wallpaper WHERE nom='annotations_".$groupe."'";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								$query="INSERT INTO wallpaper VALUES('annotations_$groupe','inactif','')";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								// Si les annotations sont supprimees, il faut regenerer les fonds pour les utilisateurs concernes.
								// Supprimer le fond.txt pour chaque utilisateur suffit.
								// On lance l'operation si cela n'a pas deja ete fait.
								if($_POST['regeneration_fond_programmee']!="oui"){
									exec("/usr/bin/sudo $chemin_scripts/genere_fond.sh variable_bidon annuler $groupe");
									$regeneration_fond_programmee="oui";
									
								}
							}
							else{
								echo "<blockquote>\n";
								// Activation des annotations:
								echo "<p>".gettext("Activation des annotations pour")." '$groupe'.</p>\n";
								$fichier=fopen("$chemin_param_fond/annotations_$groupe.txt","w+");
								$ecriture=fwrite($fichier,'actif');
								$fermeture=fclose($fichier);

								// Nettoyage:
								$query="DELETE FROM wallpaper WHERE nom LIKE 'annotation%_%_".$groupe."'";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
								$query="DELETE FROM wallpaper WHERE nom LIKE 'couleur_txt_".$groupe."'";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								$query="INSERT INTO wallpaper VALUES('annotations_$groupe','actif','')";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								// DEBUG
								//echo "\$_POST['couleur_txt']=".$_POST['couleur_txt']."<br />";

								// Si la variable est vide, imposer une couleur.
								if($_POST['couleur_txt']==""){
									$couleur_txt="black";
									echo "<p>".gettext("La couleur de texte choisie n'&#233;tait pas valide.<br>\nCouleur impos&#233;e").": $couleur_txt</p>\n";
								}
								// Il faudrait controler les valeurs de couleur_txt d'apres le contenu de $tabcouleur
								$temoin_couleur_valide="non";
								// DEBUG
								//echo "count(\$tabcouleur)=".count($tabcouleur)."<br />";
								for($i=0;$i<count($tabcouleur);$i++){
									// DEBUG
									//echo "$tabcouleur[$i] ";
									if($_POST['couleur_txt']==$tabcouleur[$i]){
										$temoin_couleur_valide="oui";
									}
								}
								if($temoin_couleur_valide=="non"){
									$couleur_txt="black";
									echo "<p>".gettext("La couleur de texte choisie n'&#233;tait pas valide.<br>\nCouleur impos&#233;e").": $couleur_txt</p>\n";
								}



								// Parametres des annotations:
								$fichier=fopen("$chemin_param_fond/annotations_$groupe.sh","w+");
								//$ecriture=fwrite($fichier,'couleur_txt='.$_POST['couleur_txt']."\n");
								$ecriture=fwrite($fichier,'couleur_txt='.$_POST['couleur_txt']."\n");
								$ecriture=fwrite($fichier,'taille_police='.$_POST['taille_police']."\n");

								$ecriture=fwrite($fichier,'annotation_nom='.$_POST['annotation_nom']."\n");
								//$ecriture=fwrite($fichier,'annotation_prenom='.$_POST['annotation_prenom']."\n");
								$ecriture=fwrite($fichier,'annotation_classe='.$_POST['annotation_classe']."\n");

								/*
								$ecriture=fwrite($fichier,'annotation_login='.$_POST['afficher_login']."\n");
								$ecriture=fwrite($fichier,'annotation_machine='.$_POST['afficher_machine']."\n");
								$ecriture=fwrite($fichier,'annotation_ip='.$_POST['afficher_ip']."\n");
								$ecriture=fwrite($fichier,'annotation_arch='.$_POST['afficher_arch']."\n");
								$ecriture=fwrite($fichier,'annotation_date='.$_POST['afficher_date']."\n");
								*/

								$fermeture=fclose($fichier);

								// A VERIFIER:
								// Il faudrait rendre annotations_$groupe.sh executable, non?

								//$query="INSERT INTO wallpaper VALUES('couleur_txt_$groupe','".$_POST['couleur_txt']."','')";
								$query="INSERT INTO wallpaper VALUES('couleur_txt_$groupe','".$_POST['couleur_txt']."','')";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								$query="INSERT INTO wallpaper VALUES('taille_police_$groupe','".$_POST['taille_police']."','')";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								$query="INSERT INTO wallpaper VALUES('annotation_nom_$groupe','".$_POST['annotation_nom']."','')";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
								//$query="INSERT INTO wallpaper VALUES('annotation_prenom_$groupe','".$_POST['annotation_prenom']."','')";
								//$resultat = mysql_query($query);
								$query="INSERT INTO wallpaper VALUES('annotation_classe_$groupe','".$_POST['annotation_classe']."','')";
								$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

								/*
								$query="INSERT INTO wallpaper VALUES('annotation_login_$groupe','".$_POST['afficher_login']."','')";
								//echo "<p>$query</p>\n";
								$resultat = mysql_query($query);
								$query="INSERT INTO wallpaper VALUES('annotation_machine_$groupe','".$_POST['afficher_machine']."','')";
								$resultat = mysql_query($query);
								$query="INSERT INTO wallpaper VALUES('annotation_ip_$groupe','".$_POST['afficher_ip']."','')";
								$resultat = mysql_query($query);
								$query="INSERT INTO wallpaper VALUES('annotation_arch_$groupe','".$_POST['afficher_arch']."','')";
								$resultat = mysql_query($query);
								$query="INSERT INTO wallpaper VALUES('annotation_date_$groupe','".$_POST['afficher_date']."','')";
								$resultat = mysql_query($query);
								*/

								/*
								if(strlen(preg_replace("/[0-9]/","",$_POST['xtxt']))==0){
									$query="DELETE FROM wallpaper WHERE nom='xtxt_$groupe'";
									$resultat = mysql_query($query);

									$query="INSERT INTO wallpaper VALUES('xtxt_$groupe','".$_POST['xtxt']."','')";
									$resultat = mysql_query($query);

									$fichier=fopen("$chemin_param_fond/annotations_$groupe.sh","a+");
									$ecriture=fwrite($fichier,"xtxt=".$_POST['xtxt']."\n");
								}

								if(strlen(preg_replace("/[0-9]/","",$_POST['ytxt']))==0){
									$query="DELETE FROM wallpaper WHERE nom='ytxt_$groupe'";
									$resultat = mysql_query($query);

									$query="INSERT INTO wallpaper VALUES('ytxt_$groupe','".$_POST['ytxt']."','')";
									$resultat = mysql_query($query);

									$fichier=fopen("$chemin_param_fond/annotations_$groupe.sh","a+");
									$ecriture=fwrite($fichier,"ytxt=".$_POST['ytxt']."\n");
								}
								*/




								//echo "<p>Liste des informations affichees lorsque l'annotation est activee:</p>\n";
								echo "<p>".gettext("Liste des informations affich&#233;es sera la suivante").":</p>\n";
								echo "<ul>\n";
								if($_POST['annotation_nom']=="1"){
									echo "<li><p>".gettext("Les 'nom' et 'pr&#233;nom' de l'utilisateur connect&#233;").".</p></li>\n";
								}
								/*
								if($_POST['annotation_prenom']=="1"){
									echo "<li><p>Le 'prenom' de l'utilisateur connecte.</p></li>\n";
								}
								*/
								if($_POST['annotation_classe']=="1"){
									echo "<li><p>".gettext("La 'classe' de l'utilisateur connect&#233; (<i>pour un professeur, ce sera 'Profs'</i>)").".</p></li>\n";
								}
								/*
								if($_POST['afficher_login']=="1"){
									echo "<li><p>Le 'login' de l'utilisateur connecte.</p></li>\n";
								}
								if($_POST['afficher_machine']=="1"){
									echo "<li><p>Le 'nom netbios' de la machine sur laquelle il se connecte.</p></li>\n";
								}
								if($_POST['afficher_ip']=="1"){
									echo "<li><p>L'adresse 'IP' de la machine sur laquelle il se connecte.</p></li>\n";
								}
								if($_POST['afficher_arch']=="1"){
									echo "<li><p>L'architecture':</p>
										<ul>
											<li>'Win95': pour Window$ 95, 98 et Me.</li>
											<li>'WinNT': pour Window$ NT.</li>
											<li>'Win2k': pour Window$ 2k et XP.</li>
										</ul>
									</li>\n";
								}
								if($_POST['afficher_date']=="1"){
									//echo "<li><p>La 'date' de login au format aaaa/mm/jj.</p></li>\n";
									echo "<li><p>La date et l'heure de login.</p></li>\n";
								}
								*/
								echo "</ul>\n";



								// Photo:
								if(isset($_POST['affiche_photo'])){
									$query="DELETE FROM wallpaper WHERE nom='affiche_photo_$groupe'";
									$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

									//$query="INSERT INTO wallpaper VALUES('affiche_photo_$groupe','".$_POST['affiche_photo']."','')";
									$query="INSERT INTO wallpaper VALUES('affiche_photo_$groupe','1','')";
									$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

									if($_POST['affiche_photo']==$groupe){
										$fichier=fopen("$chemin_param_fond/photos_$groupe.txt","w+");
										//$ecriture=fwrite($fichier,'couleur_txt='.$_POST['couleur_txt']."\n");
										$ecriture=fwrite($fichier,"actif");
										$fermeture=fclose($fichier);

										if(strlen(preg_replace("/[0-9]/","",$_POST['dim_photo']))==0){
											$query="DELETE FROM wallpaper WHERE nom='dim_photo_$groupe'";
											$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

											$query="INSERT INTO wallpaper VALUES('dim_photo_$groupe','".$_POST['dim_photo']."','')";
											$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

											$fichier=fopen("$chemin_param_fond/dim_photo_$groupe.sh","a+");
											$ecriture=fwrite($fichier,"dim_photo=".$_POST['dim_photo']."\n");
										}

										echo "<p>".gettext("La photo sera affich&#233;e, si elle existe dans")." I:\\trombine</p>\n";

										echo "<p>Retour au <a href='".$_SERVER['PHP_SELF']."?choix1=parametrer'>Param&#233;trage</a></p>\n";
									}
/*
									else{
										$fichier=fopen("$chemin_param_fond/photos_$groupe.txt","w+");
										//$ecriture=fwrite($fichier,'couleur_txt='.$_POST['couleur_txt']."\n");
										$ecriture=fwrite($fichier,"inactif");
										$fermeture=fclose($fichier);
									}
*/
								}
								else{
									$query="DELETE FROM wallpaper WHERE nom='affiche_photo_$groupe'";
									$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

									$query="INSERT INTO wallpaper VALUES('affiche_photo_$groupe','0','')";
									$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);

									$fichier=fopen("$chemin_param_fond/photos_$groupe.txt","w+");
									//$ecriture=fwrite($fichier,'couleur_txt='.$_POST['couleur_txt']."\n");
									$ecriture=fwrite($fichier,"inactif");
									$fermeture=fclose($fichier);
								}



								echo "</blockquote>\n";

								// Si les annotations sont modifiees, il faut regenerer les fonds pour les utilisateurs concernes.
								// Supprimer le fond.txt pour chaque utilisateur suffit.
								// On lance l'operation si cela n'a pas deja ete fait.
								if($_POST['regeneration_fond_programmee']!="oui"){
									exec("/usr/bin/sudo $chemin_scripts/genere_fond.sh variable_bidon annuler $groupe");
									$regeneration_fond_programmee="oui";
								}

							}

							//$fermeture=mysql_close();
						}
					}
				}
			}
		}
	
	}
	else{
		echo "<p>".gettext("Vous n'&#234;tes pas autoris&#233; &#224; acc&#233;der &#224; cette page").".</p>\n";
	}

	// Fin de la connexion a MySQL:
	$fermeture=((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);

	//Fin de page:
	include ("pdp.inc.php");
?>
