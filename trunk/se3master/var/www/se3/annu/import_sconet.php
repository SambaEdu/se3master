<?php


   /**

   * Page d'import des comptes depuis les fichiers CSV/XML de Sconet
   * @Version $Id$

   * @Projet LCS / SambaEdu

   * @auteurs Stephane Boireau (Animateur de Secteur pour les TICE sur Bernay/Pont-Audemer (27))
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr Portage LCSorSE3

   * @Licence Distribue selon les termes de la licence GPL

   * @note
   * @sudo /usr/share/se3/script/import_comptes.php
   * @sudo /usr/share/se3/script/run_import_comptes.sh
   */

   /**

   * @Repertoire: annu
   * file: import_sconet.php
   */



	include "se3orlcs_import_sconet.php";

	if (is_admin("Annu_is_admin",$login)=="Y") {
                require ( $pathlcsorse3."config.inc.php");
		require ( $path_crob_ldap_functions."crob_ldap_functions.php");

		// echo "<h2>Import des comptes, groupes,...</h2>\n";


		if((isset($_GET['nettoyage']))&&(isset($_GET['dossier']))){
			//nettoyage=oui&amp;dossier=".$timestamp."_".$randval."

			echo "<h2>Suppression des fichiers CSV g&#233;n&#233;r&#233;s</h2>\n";
			echo "<blockquote>\n";
			// Filtrer le $_GET['dossier']... A FAIRE
			if(file_exists($racine_www.$chemin_csv."/".$_GET['dossier'])){

				if(is_dir($racine_www.$chemin_csv."/".$_GET['dossier'])){
					echo "<p>Suppression de:</p>\n";
					echo "<ul>\n";
					if(file_exists($racine_www.$chemin_csv."/".$_GET['dossier']."/f_ele.txt")){
						echo "<li>f_ele.txt: ";
						if(unlink($racine_www.$chemin_csv."/".$_GET['dossier']."/f_ele.txt")){
							echo "<font color='green'>SUCCES</font>";
						}
						else{
							echo "<font color='red'>ECHEC</font>";
						}
						echo "</li>\n";
					}
					if(file_exists($racine_www.$chemin_csv."/".$_GET['dossier']."/f_div.txt")){
						echo "<li>f_div.txt: ";
						if(unlink($racine_www.$chemin_csv."/".$_GET['dossier']."/f_div.txt")){
							echo "<font color='green'>SUCCES</font>";
						}
						else{
							echo "<font color='red'>ECHEC</font>";
						}
						echo "</li>\n";
					}
					if(file_exists($racine_www.$chemin_csv."/".$_GET['dossier']."/f_men.txt")){
						echo "<li>f_men.txt: ";
						if(unlink($racine_www.$chemin_csv."/".$_GET['dossier']."/f_men.txt")){
							echo "<font color='green'>SUCCES</font>";
						}
						else{
							echo "<font color='red'>ECHEC</font>";
						}
						echo "</li>\n";
					}
					if(file_exists($racine_www.$chemin_csv."/".$_GET['dossier']."/f_wind.txt")){
						echo "<li>f_wind.txt: ";
						if(unlink($racine_www.$chemin_csv."/".$_GET['dossier']."/f_wind.txt")){
							echo "<font color='green'>SUCCES</font>";
						}
						else{
							echo "<font color='red'>ECHEC</font>";
						}
						echo "</li>\n";
					}
					//if(disk_total_space($racine_www."/Admin/csv/".$_GET['dossier'])==0){
						echo "<li>Dossier ".$racine_www.$chemin_csv."/".$_GET['dossier'].": ";
						if(rmdir($racine_www.$chemin_csv."/".$_GET['dossier'])){
							echo "<font color='green'>SUCCES</font>";
						}
						else{
							echo "<font color='red'>ECHEC</font>";
						}
						echo "</li>\n";
					//}
					echo "</ul>\n";
					echo "<p>Termin&#233;.</p>\n";
				}
				else{
					echo "<p style='color:red;'>Le dossier propos&#233; n'a pas l'air d'&#234;tre un dossier!?</p>\n";
				}
			}
			else{
				echo "<p style='color:red;'>Le dossier propos&#233; n'existe pas.</p>\n";
			}
			echo "</blockquote>\n";

			include $pathlcsorse3."pdp.inc.php";
			exit();
		}

		if(!isset($_POST['is_posted'])){
			$deverrouiller=isset($_GET['deverrouiller']) ? $_GET['deverrouiller'] : 'n';

			// Deverrouillage si un import etait annonce deja en cours:
			if($deverrouiller=='y'){
				$sql="UPDATE params SET value='n' WHERE name='imprt_cmpts_en_cours'";
				$res0=mysql_query($sql);

				if($res0){
					echo "<p>D&#233;verrouillage r&#233;ussi!</p>\n";
				}
				else{
					echo "<p style='color:red;'>Echec du d&#233;verrouillage!</p>\n";
				}
			}

			// Un import est-il deja en cours?
			$sql="SELECT value FROM params WHERE name='imprt_cmpts_en_cours'";
			$res1=mysql_query($sql);
			if(mysql_num_rows($res1)==0){
				$imprt_cmpts_en_cours="n";
			}
			else{
				$ligtmp=mysql_fetch_object($res1);
				$imprt_cmpts_en_cours=$ligtmp->value;
			}

			if($imprt_cmpts_en_cours=="y"){
				echo "<p><b>ATTENTION:</b> Il semble qu'un import soit d&#233;j&#224; en cours";

				$sql="SELECT value FROM params WHERE name='dernier_import'";
				$res2=mysql_query($sql);
				if(mysql_num_rows($res1)>0){
					$ligtmp=mysql_fetch_object($res2);
					echo ":<br />\n<a href='$urlse3/Admin/result.".$ligtmp->value.".html' target='_blank'>$urlse3/Admin/result.".$ligtmp->value.".html</a>";
				}

    echo "<br />\n";
				echo "Si vous &#234;tes certain que ce n'est pas le cas, vous pouvez faire sauter le verrou.<br />Sinon, il vaut mieux patienter quelques minutes.</p>\n";

				echo "<p><a href='".$_SERVER['PHP_SELF']."?deverrouiller=y'>Faire sauter le verrou</a>.</p>\n";
			}
			else{
				echo "<h3>Choix des fichiers source</h3>\n";

				// ===========================================================
				// AJOUTS: 20070914 boireaus

				exec("ldapsearch -xLLL ou=Trash",$retour_recherche_branche_Trash);
				if(count($retour_recherche_branche_Trash)>0){
					$attribut=array("uid");
					$test_tab=get_tab_attribut("trash", "uid=*", $attribut);
					if(count($test_tab)){
						echo "<p><span style='color:red; font-weight:bold;'>ATTENTION:</span> Il semble que la Corbeille contienne des comptes.<br />Conserver des comptes avant un import peut &#234;tre g&#234;nant:</p>\n";
						echo "<ul>\n";
						echo "<li>Cela peut causer une p&#233;nurie d'uidNumber libres pour les nouveaux comptes &#224; cr&#233;er.</li>\n";
						echo "<li>Cela rallonge le temps de traitement.</li>\n";
						echo "</ul>\n";
						echo "<p>Il est donc recommand&#233; de proc&#233;der au Nettoyage des comptes (<i>dans le menu Annuaire</i>) avant d'effectuer l'import de nouveaux comptes.</p>\n";
					}
				}
				// ===========================================================


				echo "<h4>Fichier &#233;l&#232;ves</h4>\n";

				echo "<form enctype='multipart/form-data' name='formulaire1' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
				echo "<table border='0' width='100%'>\n";

				echo "<tr>\n";
				echo "<td width='45%'><input type='radio' id='type_csv' name='type_fichier_eleves' value='csv' onchange=\"document.getElementById('id_csv').style.display='';document.getElementById('id_xml').style.display='none';\" /> Export CSV de Sconet";
				echo "&nbsp;&nbsp;";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<b>Le cheminement pour r&#233;aliser cette extraction depuis Sconet est:</b><br />Application Sconet/Acc&#232;s Base Eleves.<br />Choisir l\'ann&#233;e \(<i>en cours ou en pr&#233;paration selon que la bascule est ou non effectu&#233;e</i>\) Exploitation-Extraction et choisir personnalis&#233;e.<br />Les champs requis sont:<ul><li>Nom</li><li>Pr&#233;nom 1</li><li>Date de naissance</li><li>N° Interne</li><li>Sexe</li><li>Division</li>')")."\"><img name=\"action_image1\"  src=\"$helpinfo\"></u>\n";

				echo "</td>\n";
				echo "<td width='10%' align='center'>ou</td>\n";
				echo "<td width='45%'><input type='radio' id='type_xml' name='type_fichier_eleves' value='xml' checked onchange=\"document.getElementById('id_csv').style.display='none';document.getElementById('id_xml').style.display='';\" /> Export XML de Sconet";
				echo "&nbsp;&nbsp;";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<b>Le cheminement pour r&#233;aliser cette extraction depuis Sconet est:</b><br />Application Sconet/Acc&#232;s Base Eleves/Extractions/Exports standard/Exports XML g&#233;n&#233;riques/<b>El&#232;ves sans adresses</b><br /><br /><b>Attention:</b> Ces exports XML ne sont actuellement possibles qu\'avant 9H le matin et apr&#232;s 17H le soir.</p><p>Ce fichier permet une meilleure g&#233;n&#233;ration des groupes Cours pour les groupes correspondant &#224; des options.')")."\"><img name=\"action_image2\"  src=\"$helpinfo\"></u>\n";
				echo "</td>\n";
				echo "</tr>\n";


				echo "<tr>\n";
				/*
				echo "<td>\n";
				echo "<p>Les champs requis sont:</p>\n";
				echo "<ul>\n";
				echo "<li>Nom</li>\n";
				echo "<li>Pr&#233;nom 1</li>\n";
				echo "<li>Date de naissance</li>\n";
				echo "<li>N° Interne</li>\n";
				echo "<li>Sexe</li>\n";
				echo "<li>Division</li>\n";
				echo "</ul>\n";

				echo "<p>Veuillez fournir le fichier CSV:<br />\n";
				echo "<input type=\"file\" size=\"30\" name=\"eleves_csv_file\">\n";
				echo "</p>\n";

				echo "</td>\n";

				echo "<td>&nbsp;</td>\n";

				echo "<td>\n";
				*/
				echo "<td colspan='3' align='center'>\n";
				//echo "<p>Veuillez fournir le fichier XML: ElevesSansAdresses.xml<br />\n";
				echo "<p>Veuillez fournir le fichier &#233;l&#232;ves (<i>CSV ou XML selon le choix effectu&#233; ci-dessus</i>).<br />\n";
				echo "<span id='id_csv' style='display:none; background-color: lightgreen; border: 1px solid black;'>CSV:</span> ";
				echo "<span id='id_xml' style='display:none; background-color: lightblue; border: 1px solid black;'>XML:</span> ";
				echo "<input type=\"file\" size=\"30\" name=\"eleves_file\" />\n";
				//echo "<input type=\"file\" size=\"30\" name=\"eleves_xml_file\">\n";
				//echo "<input type=\"file\" size=\"30\" name=\"nomenclature_xml_file\">\n";
				echo "</p>\n";
				echo "<script type='text/javascript'>
	if(document.getElementById('type_csv').checked){
		document.getElementById('id_csv').style.display=\"\";
		document.getElementById('id_xml').style.display=\"none\";
	}
	if(document.getElementById('type_xml').checked){
		document.getElementById('id_csv').style.display=\"none\";
		document.getElementById('id_xml').style.display=\"\";
	}
</script>";
				echo "</td>\n";

				echo "</tr>\n";
				echo "</table>\n";

				echo "<h4>Fichier professeurs et emploi du temps</h4>\n";

				echo "<p>Veuillez fournir le fichier XML <i>(STS_emp_RNE_ANNEE.xml)</i>:<br />\n";
				echo "<span style='background-color: lightblue; border: 1px solid black;'>XML:</span> ";
				echo "<input type=\"file\" size=\"80\" name=\"sts_xml_file\" id=\"sts_xml_file\" />\n";
				echo "&nbsp;&nbsp;";
			        echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<b>Le cheminement pour effectuer cet export depuis Sconet est:</b><br />STS-web/Mise &#224; jour/Exports/Emplois du temps')")."\"><img name=\"action_image1\"  src=\"$helpinfo\"></u>\n";

				echo "</p>\n";
				echo "<br />\n";

				echo "<h4>Fichier optionnel de logins</h4>\n";

				echo "<p>Vous pouvez fournir, si vous le souhaitez, un fichier de correspondances 'employeeNumber;login' pour imposer des logins aux nouveaux utilisateurs <i>(f_uid.txt)</i>:<br />\n";
				echo "<span id='id_csv' style='background-color: lightgreen; border: 1px solid black;'>CSV:</span> ";
				echo "<input type=\"file\" size=\"80\" name=\"f_uid_file\" id=\"f_uid_file\" />\n";
				echo "&nbsp;&nbsp;";
			        echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<b>Le fichier doit &#234;tre format&#233; ainsi:</b><br />P1234;zebest<br />P2345;zeone<br />3456;toto<br />Avec le pr&#233;fixe P sur les employeeNumber des Profs et pas de pr&#233;fixe pour les Eleves.')")."\"><img name=\"action_image1\"  src=\"$helpinfo\"></u>\n";
				echo "</p>\n";
				echo "<br />\n";

				echo "<h3>Configuration de l'import</h3>";

				echo "<h4>Pr&#233;fixe &#233;ventuel : <input type='text' name='prefix' size='5' maxlength='5' value='' />\n";
				echo "&nbsp;&nbsp;";
                               echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<b>Ex : LEP</b><br />Ce pr&#233;fixe est utilis&#233; dans les noms de groupes \(ex.: Classe_<b>LEP</b>_3D, Equipe_<b>LEP</b>_3D, Cours_<b>LEP</b>_AGL1_3D, Matiere_<b>LEP</b>_MATHS\)<br />Cela est utile dans les &#233;tablissements mixtes, avec un lyc&#233;e et un LP par exemple.')")."\"><img name=\"action_image3\"  src=\"$helpinfo\"></u>\n";
				echo "</h4>";
				echo "<h4><label for='annuelle' style='cursor: pointer;'>Importation de d&#233;but d'ann&#233;e ? </label><input name='annuelle' id='annuelle' type='checkbox' value='y' />\n";
				echo "&nbsp;&nbsp;";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Vous devez cocher cette case pour la premi&#232;re importation de l\'ann&#233;e. Cela va d&#233;truire les classes existantes.')")."\"><img name=\"action_image4\"  src=\"$helpinfo\"></u>\n";

				echo "</h4>";

				echo "<h4><label for='simulation' style='cursor: pointer;'>Simulation ? </label><input name='simulation' id='simulation' type='checkbox' value='y' />\n";

				echo "&nbsp;&nbsp;";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Dans le mode simulation, les nouveaux comptes et comptes retrouv&#233;s (cr&#233;&#233;s auparavant &#224; la main et pour lesquels l\'employeeNumber n\'est pas renseign&#233;) sont affich&#233;s sans &#234;tre pour autant cr&#233;&#233;s.')")."\"><img name=\"action_image5\"  src=\"$helpinfo\"></u>\n";
				echo "</h4>";


				echo "<h4><label for='temoin_creation_fichiers' style='cursor: pointer;'>G&#233;n&#233;rer des fichiers CSV ? </label><input name='temoin_creation_fichiers' id='temoin_creation_fichiers' type='checkbox' value='oui' />\n";

				echo "&nbsp;&nbsp;";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('L\'import fonctionne tr&#232;s bien automatiquement (sans eux), mais si vous y tenez.')")."\"><img name=\"action_image5\"  src=\"$helpinfo\"></u>\n";
				echo "</h4>";

				echo "<h4><label for='chrono' style='cursor: pointer;'>Afficher les dates et heures dans l'import? </label><input name='chrono' id='chrono' type='checkbox' value='y' />\n";

				echo "&nbsp;&nbsp;";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Pour estimer la dur&#233;e de l\'importation.')")."\"><img name=\"action_image5\"  src=\"$helpinfo\"></u>\n";
				echo "</h4>";


				// ===========================================================
				// AJOUTS: 20070914 boireaus
				echo "<h4>Param&#232;tres de l'import</h4>\n";
				echo "<ul>\n";
				echo "<li>\n";
				echo "<label for='creer_equipes_vides' style='cursor: pointer;'>Cr&#233;er les Equipes sans les peupler ? </label><input name='creer_equipes_vides' id='creer_equipes_vides' type='checkbox' value='y' />\n";
				echo "&nbsp;&nbsp;";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('En d&#233;but d\'ann&#233;e, le fichier XML STS_emp peut contenir des informations erron&#233;es (<i>ne prenant pas encore en compte les nouvelles associations professeurs/mati&#232;res/classes</i>).<br />La remont&#233;e de l\'emploi du temps vers STS r&#232;glera ce probl&#232;me.<br />En attendant, pour cr&#233;er les &#233;quipes sans y mettre les professeur de l\'ann&#233;e pr&#233;c&#233;dente, vous pouvez cocher cette case.<br />Les &#233;quipes cr&#233;&#233;es, vous pourrez y affecter manuellement les professeurs pour lesquels l\'acc&#232;s aux dossiers de Classes est urgent.')")."\"><img name=\"action_image5\"  src=\"$helpinfo\"></u>\n";
				echo "</li>\n";

				echo "<li>\n";
				echo "<label for='creer_cours' style='cursor: pointer;'>Ne pas cr&#233;er les groupes Cours ? </label><input name='creer_cours' id='creer_cours' type='checkbox' value='n' checked />\n";
				echo "&nbsp;&nbsp;";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('La plupart du temps, les groupes Cours sont inutiles. Il vaut mieux ne pas les cr&#233;er pour ne pas alourdir l\'annuaire.')")."\"><img name=\"action_image5\"  src=\"$helpinfo\"></u>\n";
				echo "</li>\n";

				echo "<li>\n";
				echo "<label for='creer_matieres' style='cursor: pointer;'>Ne pas cr&#233;er les groupes Mati&#232;res ? </label><input name='creer_matieres' id='creer_matieres' type='checkbox' value='n' />\n";
				echo "&nbsp;&nbsp;";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('En d&#233;but d\'ann&#233;e, le fichier XML STS_emp peut contenir des informations erron&#233;es (<i>ne prenant pas encore en compte les nouvelles associations professeurs/mati&#232;res/classes</i>).<br />La remont&#233;e de l\'emploi du temps vers STS r&#232;glera ce probl&#232;me.<br />Vous pouvez ne pas cr&#233;er les groupes Mati&#232;res en attendant.<br />Cependant, les Mati&#232;res changent assez peu d\'une ann&#233;e sur l\'autre  et les professeurs changent assez peu de mati&#232;re d\'une ann&#233;e sur l\'autre.')")."\"><img name=\"action_image5\"  src=\"$helpinfo\"></u>\n";
				echo "</li>\n";
				// ===========================================================

				echo "<li>\n";
				echo "<label for='corriger_gecos_si_diff' style='cursor: pointer;'>Corriger les nom, pr&#233;nom, sexe, date de naissance des comptes existants ? </label><input name='corriger_gecos_si_diff' id='corriger_gecos_si_diff' type='checkbox' value='y' />\n";
				echo "&nbsp;&nbsp;";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Il peut arriver que les comptes existants comportent des informations erron&#233;es<br />(<i>Sconet mal rempli, changement de nom d\'un professeur qui se marie,...</i>)<br />En cochant la case, vous autorisez les corrections des attributs cn, sn, givenName et gecos si des changements sont rep&#233;r&#233;s.<br />Le login/uid n\'est en revanche pas modifi&#233;.')")."\"><img name=\"action_image5\"  src=\"$helpinfo\"></u>\n";
				echo "</li>\n";

				echo "</ul>\n";
				// ===========================================================


				echo "<input type='hidden' name='is_posted' value='yes'>\n";

				//echo "<p><input type='submit' value='Valider'></p>\n";
				echo "<p><input type='button' value='Valider' onClick='verif_et_valide()' /></p>\n";

				echo "</form>\n";

	// Fonction javascript

	/**
	* Verifie et valide un from
	* @language Javascript
	* @Parametres
	* @return true ou false
	*/

	echo "<script type='text/javascript'>
	function verif_et_valide(){
		temoin_pb='n';
		if(document.getElementById('annuelle').checked==true){
			if(document.getElementById('sts_xml_file').value==''){
				temoin_pb='o';
			}
		}
		confirmation=true;
		if(temoin_pb=='o'){
			confirmation=confirm(\"Vous avez choisi une importations annuelle sans fournir de fichier de STS. Si vous confirmez ce choix les professeurs existants ne seront plus membres du groupe Profs en fin d'import. Voulez-vous confirmer ce choix inhabituel?\");
		}

		if(confirmation==true){document.forms['formulaire1'].submit();}else{return false;}
	}
	</script>\n";

	// Fin de fonction javascript


			}
			include $pathlcsorse3."pdp.inc.php";
		}
		else{


			// Un import est-il deja en cours?
			$sql="SELECT value FROM params WHERE name='imprt_cmpts_en_cours'";
			$res1=mysql_query($sql);
			if(mysql_num_rows($res1)==0){
				$imprt_cmpts_en_cours="n";
			}
			else{
				$ligtmp=mysql_fetch_object($res1);
				$imprt_cmpts_en_cours=$ligtmp->value;
			}

			if($imprt_cmpts_en_cours=="y"){
				echo "<p><b>ATTENTION:</b> Il semble qu'un import soit d&#233;j&#224; en cours";

				$sql="SELECT value FROM params WHERE name='dernier_import'";
				$res2=mysql_query($sql);
				if(mysql_num_rows($res1)>0){
					$ligtmp=mysql_fetch_object($res2);
					echo ":<br />\n<a href='$urlse3/Admin/result.".$ligtmp->value.".html' target='_blank'>$urlse3/Admin/result.".$ligtmp->value.".html</a>";
				}

				echo "<br />\n";
				echo "Si vous &#234;tes certain que ce n'est pas le cas, vous pouvez faire sauter le verrou.<br />Sinon, il vaut mieux patienter quelques minutes.</p>\n";

				echo "<p><a href='".$_SERVER['PHP_SELF']."?deverrouiller=y'>Faire sauter le verrou</a>.</p>\n";
				include $pathlcsorse3."pdp.inc.php";
				exit();
			}


			$nouveaux_comptes=0;
			$comptes_avec_employeeNumber_mis_a_jour=0;
			$nb_echecs=0;

			$tab_nouveaux_comptes=array();
			$tab_comptes_avec_employeeNumber_mis_a_jour=array();

			// Creation d'un temoin de mise a jour en cours.
			$sql="SELECT value FROM params WHERE name='imprt_cmpts_en_cours'";
			$res1=mysql_query($sql);
			if(mysql_num_rows($res1)==0){
				$sql="INSERT INTO params SET name='imprt_cmpts_en_cours',value='y'";
				$res0=mysql_query($sql);
			}
			else{
				// Si la valeur est deja a y, c'est qu'on a fait F5... un import est deja en cours.
				$ligtmp=mysql_fetch_object($res1);
				if($ligtmp->value=="y"){
					echo("<p>Un import est d&#233;j en cours");

					$sql="SELECT value FROM params WHERE name='dernier_import'";
					$res2=mysql_query($sql);
					if(mysql_num_rows($res1)>0){
						$ligtmp=mysql_fetch_object($res2);
						echo ": <a href='$urlse3/Admin/result.".$ligtmp->value.".html' target='_blank'>$urlse3/Admin/result.".$ligtmp->value.".html</a>";
					}
					else{
						echo ".";
					}

					echo("<br />Veuillez patienter.</p>\n");
					echo("<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a>.</p>\n");
					echo("</body>\n</html>\n");
					exit();
				}

				$sql="UPDATE params SET value='y' WHERE name='imprt_cmpts_en_cours'";
				$res0=mysql_query($sql);
			}

			$timestamp=preg_replace("/ /","_",microtime());

			$sql="SELECT value FROM params WHERE name='dernier_import'";
			$res1=mysql_query($sql);
			if(mysql_num_rows($res1)==0){
				$sql="INSERT INTO params SET name='dernier_import',value='$timestamp'";
				$res0=mysql_query($sql);
			}
			else{
				$sql="UPDATE params SET value='$timestamp' WHERE name='dernier_import'";
				$res0=mysql_query($sql);
			}




			if(($_FILES["eleves_file"]["name"]=="")&&($_FILES["sts_xml_file"]["name"]=="")){
				echo "<p style='color:red;'><b>ERREUR:</b> Aucun fichier n'a &#233;t&#233; fourni!</p>\n";
				echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a>.</p>\n";
				echo "</body>\n</html>\n";
				exit();
			}








			$type_fichier_eleves=$_POST['type_fichier_eleves'];

			$tmp_eleves_file=$HTTP_POST_FILES['eleves_file']['tmp_name'];
			$eleves_file=$HTTP_POST_FILES['eleves_file']['name'];
			$size_eleves_file=$HTTP_POST_FILES['eleves_file']['size'];

            /*
            //===============================================
            echo "\$tmp_eleves_file=$tmp_eleves_file<br />";
            echo "\$eleves_file=$eleves_file<br />";
            echo "\$size_eleves_file=$size_eleves_file<br />";
            //===============================================
            */

            if(($eleves_file!='')&&($tmp_eleves_file=='')) {
                echo "<p>L'upload du fichier <span style='color:red;'>$eleves_file</span> a semble-t-il &eacute;chou&eacute;.</p>";

                $upload_max_filesize=ini_get('upload_max_filesize');
                $post_max_size=ini_get('post_max_size');

                echo "<p>Il se peut que le fichier fourni ait &eacute;t&eacute; trop volumineux.<br />PHP est actuellement param&eacute;tr&eacute; avec:<br />\n";
                echo "</p>\n";
                echo "<blockquote>\n";
                echo "<span style='color:blue;'>upload_max_filesize</span>=<span style='color:green;'>".$upload_max_filesize."</span><br />\n";
                echo "<span style='color:blue;'>post_max_size</span>=<span style='color:green;'>".$post_max_size."</span><br />\n";
                echo "</blockquote>\n";
                echo "<p>\n";
                echo "Si ces valeurs sont insuffisantes pour vos fichiers XML, il est possible de modifier les valeurs limites dans <span style='color:green;'>/etc/php5/apache2/php.ini</span>\n";
                echo "</p>\n";

                die();
            }


			$dest_file="$dossier_tmp_import_comptes/fichier_eleves";
			// SUR CA, IL VAUDRAIT SANS DOUTE MIEUX FORCER LE NOM DESTINATION POUR EVITER DES SALES BLAGUES
			if(file_exists($dest_file)){
				unlink($dest_file);
			}

			if(is_uploaded_file($tmp_eleves_file)){
				$source_file=stripslashes("$tmp_eleves_file");
				$res_copy=copy("$source_file" , "$dest_file");

				// Si jamais un XML non dézippé a été fourni
				$extension_fichier_emis=strtolower(strrchr($eleves_file,"."));
				if (($extension_fichier_emis==".zip")||($HTTP_POST_FILES['eleves_file']['type']=="application/zip")) {

					//if(!file_exists($racine_www."/includes/pclzip.lib.php")) {
					if(!file_exists($chemin_www_includes."/pclzip.lib.php")) {
						echo "<p style='color:red;'>Erreur : Un fichier ZIP a été fourni, mais la bibliothèque de dézippage est absente.</p>\n";
						require("../includes/pdp.inc.php");
						die();
					}
					else {
						//$unzipped_max_filesize=getSettingValue('unzipped_max_filesize')*1024*1024;

						// On considère un XML élève de 20Mo maxi
						$unzipped_max_filesize=20*1024*1024;

						// $unzipped_max_filesize = 0    pas de limite de taille pour les fichiers extraits
						// $unzipped_max_filesize < 0    extraction zip désactivée
						if($unzipped_max_filesize>=0) {
							//require_once('../lib/pclzip.lib.php');
							require_once('pclzip.lib.php');
							$archive = new PclZip($dest_file);

							if (($list_file_zip = $archive->listContent()) == 0) {
								echo "<p style='color:red;'>Erreur : ".$archive->errorInfo(true)."</p>\n";
								require("../includes/pdp.inc.php");
								die();
							}

							if(sizeof($list_file_zip)!=1) {
								echo "<p style='color:red;'>Erreur : L'archive contient plus d'un fichier.</p>\n";
								require("../includes/pdp.inc.php");
								die();
							}

							/*
							echo "<p>\$list_file_zip[0]['filename']=".$list_file_zip[0]['filename']."<br />\n";
							echo "\$list_file_zip[0]['size']=".$list_file_zip[0]['size']."<br />\n";
							echo "\$list_file_zip[0]['compressed_size']=".$list_file_zip[0]['compressed_size']."</p>\n";
							*/
							//echo "<p>\$unzipped_max_filesize=".$unzipped_max_filesize."</p>\n";

							if(($list_file_zip[0]['size']>$unzipped_max_filesize)&&($unzipped_max_filesize>0)) {
								echo "<p style='color:red;'>Erreur : La taille du fichier extrait (<i>".$list_file_zip[0]['size']." octets</i>) dépasse la limite paramétrée (<i>$unzipped_max_filesize octets</i>).</p>\n";
								require("../includes/pdp.inc.php");
								die();
							}

							$res_extract=$archive->extract(PCLZIP_OPT_PATH, "$dossier_tmp_import_comptes/");
							if ($res_extract != 0) {
								echo "<p>Le fichier uploadé a été dézippé.</p>\n";
								$fichier_extrait=$res_extract[0]['filename'];
								$res_copy=rename("$fichier_extrait" , "$dest_file");
							}
							else {
								echo "<p style='color:red'>Echec de l'extraction de l'archive ZIP.</p>\n";
								require("../includes/pdp.inc.php");
								die();
							}
						}
					}

				}

			}

            //====================================================

			$tmp_sts_file=$HTTP_POST_FILES['sts_xml_file']['tmp_name'];
			$sts_file=$HTTP_POST_FILES['sts_xml_file']['name'];
			$size_sts_file=$HTTP_POST_FILES['sts_xml_file']['size'];


            if(($sts_file!='')&&($tmp_sts_file=='')) {
                echo "<p>L'upload du fichier <span style='color:red;'>$eleves_file</span> a semble-t-il &eacute;chou&eacute;.</p>";

                $upload_max_filesize=ini_get('upload_max_filesize');
                $post_max_size=ini_get('post_max_size');

                echo "<p>Il se peut que le fichier fourni ait &eacute;t&eacute; trop volumineux.<br />PHP est actuellement param&eacute;tr&eacute; avec:\n";
                echo "</p>\n";
                echo "<blockquote>\n";
                echo "<span style='color:blue;'>upload_max_filesize</span>=<span style='color:green;'>".$upload_max_filesize."</span><br />\n";
                echo "<span style='color:blue;'>post_max_size</span>=<span style='color:green;'>".$post_max_size."</span><br />\n";
                echo "</blockquote>\n";
                echo "<p>\n";
                echo "Si ces valeurs sont insuffisantes pour vos fichiers XML, il est possible de modifier les valeurs limites dans <span style='color:green;'>/etc/php5/apache2/php.ini</span>\n";
                echo "</p>\n";

                die();
            }


			$dest_file="$dossier_tmp_import_comptes/fichier_sts";
			// SUR CA, IL VAUDRAIT SANS DOUTE MIEUX FORCER LE NOM DESTINATION POUR EVITER DES SALES BLAGUES
			if(file_exists($dest_file)){
				unlink($dest_file);
			}

			if(is_uploaded_file($tmp_sts_file)){
				$source_file=stripslashes("$tmp_sts_file");
				$res_copy=copy("$source_file" , "$dest_file");
			}

			//==========================================

			// Fichier optionnel f_uid_file
			$tmp_f_uid_file=$HTTP_POST_FILES['f_uid_file']['tmp_name'];
			$f_uid_file=$HTTP_POST_FILES['f_uid_file']['name'];
			$size_f_uid_file=$HTTP_POST_FILES['f_uid_file']['size'];

			$dest_file="$dossier_tmp_import_comptes/f_uid.txt";
			if(file_exists($dest_file)){
				unlink($dest_file);
			}

			$temoin_f_uid="n";
			if(is_uploaded_file($tmp_f_uid_file)){
				$source_file=stripslashes("$tmp_f_uid_file");
				$res_copy=copy("$source_file" , "$dest_file");

				$temoin_f_uid="y";
			}


			//$timestamp=preg_replace("/ /","_",microtime());
			$echo_file="$racine_www/Admin/result.$timestamp.html";
			$dest_mode="file";
			$fich=fopen("$echo_file","w+");
			fwrite($fich,"<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<style type='text/css'>
body{
    background: url($background) ghostwhite bottom right no-repeat fixed;
}
</style>
<!--head-->
<title>Import de comptes</title>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
<!--meta http-equiv='Refresh' CONTENT='120;URL=result.$timestamp.html#menu' /-->
<link type='text/css' rel='stylesheet' href='$stylecss' />
<body>
<h1 style='text-align:center;'>Import de comptes</h1>

<div id='decompte' style='float: right; border: 1px solid black;'></div>

<script type='text/javascript'>
cpt=120;
compte_a_rebours='y';


/**
* Decompte le temps mis pour l'import sconet
* @language Javascript
* @Parametres
* @return le decompte qui s'affiche
*/


function decompte(cpt){
	if(compte_a_rebours=='y'){
		document.getElementById('decompte').innerHTML=cpt;
		if(cpt>0){
			cpt--;
		}
		else{
			document.location='result.$timestamp.html';
		}

		setTimeout(\"decompte(\"+cpt+\")\",1000);
	}
	else{
		document.getElementById('decompte').style.display='none';
	}
}

decompte(cpt);
</script>\n");
			fclose($fich);





			$chrono=isset($_POST['chrono']) ? $_POST['chrono'] : "n";



			// ===========================================================
			// AJOUTS: 20070914 boireaus
			$creer_equipes_vides=isset($_POST['creer_equipes_vides']) ? $_POST['creer_equipes_vides'] : 'n';
			$creer_cours=isset($_POST['creer_cours']) ? $_POST['creer_cours'] : 'y';
			$creer_matieres=isset($_POST['creer_matieres']) ? $_POST['creer_matieres'] : 'y';
			// ===========================================================
			$corriger_gecos_si_diff=isset($_POST['corriger_gecos_si_diff']) ? $_POST['corriger_gecos_si_diff'] : 'n';


			// Dossier pour les CSV

			//$temoin_creation_fichiers="non";
			//$temoin_creation_fichiers="oui";
			$temoin_creation_fichiers=isset($_POST['temoin_creation_fichiers']) ? $_POST['temoin_creation_fichiers'] : "non";
			if($temoin_creation_fichiers!="non"){
				if(!file_exists($racine_www.$chemin_csv)){
					mkdir($racine_www.$chemin_csv);
				}
				mt_srand((float) microtime()*1000000);
				$randval = mt_rand();
				$chemin_http_csv=$chemin_csv."/".$timestamp."_".$randval;
				$dossiercsv=$racine_www."/".$chemin_http_csv;
				if(!mkdir($dossiercsv)){$temoin_creation_fichiers="non";}
			}

			//my_echo("disk_total_space($dossiercsv)=".disk_total_space($dossiercsv)."<br />");


			// Date et heure...
			$aujourdhui = getdate();
			$annee_aujourdhui = $aujourdhui['year'];
			$mois_aujourdhui = sprintf("%02d",$aujourdhui['mon']);
			$jour_aujourdhui = sprintf("%02d",$aujourdhui['mday']);
			$heure_aujourdhui = sprintf("%02d",$aujourdhui['hours']);
			$minute_aujourdhui = sprintf("%02d",$aujourdhui['minutes']);
			$seconde_aujourdhui = sprintf("%02d",$aujourdhui['seconds']);

			my_echo("<p>Import du $jour_aujourdhui/$mois_aujourdhui/$annee_aujourdhui &#224; $heure_aujourdhui:$minute_aujourdhui:$seconde_aujourdhui<br />\n(<i>l'op&#233;ration d&#233;marre 2min apr&#232;s; vous pouvez alors commencer &#224; jouer avec la touche F5 pour suivre le traitement</i>)</p>\n");


			// Importation annuelle
			$annuelle=isset($_POST['annuelle']) ? $_POST['annuelle'] : "n";

			// Mode simulation
			$simulation=isset($_POST['simulation']) ? $_POST['simulation'] : "n";

			// Prefixe LP/LEGT,...
			$prefix=isset($_POST['prefix']) ? $_POST['prefix'] : "";
			#$prefix=strtoupper(preg_replace("/[^A-Za-z0-9_]/", "", strtr(remplace_accents($prefix)," ","_")));
                        $prefix=strtoupper(preg_replace("/[^A-Za-z0-9]/", "", remplace_accents($prefix)));
			if(strlen(preg_replace("/_/","",$prefix))==0) $prefix="";
			if (strlen($prefix)>0) $prefix=$prefix."_";

/*
			echo "\$resultat=exec(\"/usr/bin/sudo $php $chemin/import_comptes.php '$type_fichier_eleves' '$chemin_fich/fichier_eleves' '$chemin_fich/fichier_sts' '$prefix' '$annuelle' '$simulation' '$timestamp'\",$retour);";

			$resultat=exec("/usr/bin/sudo $php $chemin/import_comptes.php '$type_fichier_eleves' '$chemin/fichier_eleves' '$chemin_fich/fichier_sts' '$prefix' '$annuelle' '$simulation' '$timestamp'",$retour);

*/

/*
			echo "\$resultat=exec(\"/usr/bin/sudo $chemin/import_comptes.php '$type_fichier_eleves' '$chemin_fich/fichier_eleves' '$chemin_fich/fichier_sts' '$prefix' '$annuelle' '$simulation' '$timestamp'\",$retour);";

			$resultat=exec("/usr/bin/sudo $chemin/import_comptes.php '$type_fichier_eleves' '$chemin/fichier_eleves' '$chemin_fich/fichier_sts' '$prefix' '$annuelle' '$simulation' '$timestamp'",$retour);
*/

			$fich=fopen("$dossier_tmp_import_comptes/import_comptes.sh","w+");
			//fwrite($fich,"#!/bin/bash\n/usr/bin/sudo $chemin/import_comptes.php '$type_fichier_eleves' '$chemin_fich/fichier_eleves' '$chemin_fich/fichier_sts' '$prefix' '$annuelle' '$simulation' '$timestamp' '$randval' '$temoin_creation_fichiers'\n");

			// ===========================================================
			// AJOUTS: 20070914 boireaus
			//fwrite($fich,"#!/bin/bash\n/usr/bin/php $chemin/import_comptes.php '$type_fichier_eleves' '$chemin_fich/fichier_eleves' '$chemin_fich/fichier_sts' '$prefix' '$annuelle' '$simulation' '$timestamp' '$randval' '$temoin_creation_fichiers' '$chrono'\n");

			//fwrite($fich,"#!/bin/bash\n/usr/bin/php $chemin/import_comptes.php '$type_fichier_eleves' '$chemin_fich/fichier_eleves' '$chemin_fich/fichier_sts' '$prefix' '$annuelle' '$simulation' '$timestamp' '$randval' '$temoin_creation_fichiers' '$chrono' '$creer_equipes_vides' '$creer_cours' '$creer_matieres'\n");

			//fwrite($fich,"#!/bin/bash\n/usr/bin/php $chemin/import_comptes.php '$type_fichier_eleves' '$chemin_fich/fichier_eleves' '$chemin_fich/fichier_sts' '$prefix' '$annuelle' '$simulation' '$timestamp' '$randval' '$temoin_creation_fichiers' '$chrono' '$creer_equipes_vides' '$creer_cours' '$creer_matieres' '$corriger_gecos_si_diff'\n");

			fwrite($fich,"#!/bin/bash\n/usr/bin/php $chemin/import_comptes.php '$type_fichier_eleves' '$chemin_fich/fichier_eleves' '$chemin_fich/fichier_sts' '$prefix' '$annuelle' '$simulation' '$timestamp' '$randval' '$temoin_creation_fichiers' '$chrono' '$creer_equipes_vides' '$creer_cours' '$creer_matieres' '$corriger_gecos_si_diff' '$temoin_f_uid'\n");

			//echo "<p>#!/bin/bash<br />\n/usr/bin/php $chemin/import_comptes.php '$type_fichier_eleves' '$chemin_fich/fichier_eleves' '$chemin_fich/fichier_sts' '$prefix' '$annuelle' '$simulation' '$timestamp' '$randval' '$temoin_creation_fichiers' '$chrono' '$creer_equipes_vides' '$creer_cours' '$creer_matieres' '$corriger_gecos_si_diff'</p>\n";
			// ===========================================================



			fclose($fich);
			//chmod("/var/remote_adm/import_comptes.sh",750);
			chmod("$dossier_tmp_import_comptes/import_comptes.sh",0750);

			$d_minute_aujourdhui=sprintf("%02d",$minute_aujourdhui+2);

			//echo "\$resultat=exec(\"/usr/bin/at -f /var/remote_adm/import_comptes.sh $heure_aujourdhui:$d_minute_aujourdhui\",$retour);";
			//$resultat=exec("/usr/bin/at -f $dossier_tmp_import_comptes/import_comptes.sh $heure_aujourdhui:$d_minute_aujourdhui",$retour);
                        // sudo
                        //echo "DBG >>/usr/bin/sudo $chemin/run_import_comptes.sh $dossier_tmp_import_comptes<br />";
                        $resultat=exec("/usr/bin/sudo $chemin/run_import_comptes.sh $dossier_tmp_import_comptes", $retour);

			if(count($retour)>0){
				echo "<p>Il semble que la programmation ait &#233;chou&#233;...";
				for($i=0;$i<count($retour);$i++){
					echo "\$retour[$i]=$retour[$i]<br />\n";
				}
				echo "</p>\n";
			}




			echo "<p>Lancement de l'import de comptes,... en mode <b>";
			if($simulation=="y"){echo "simulation";}else{echo "cr&#233;ation";}
			echo "</b>";
			if($prefix!=""){echo " avec le pr&#233;fixe <b>$prefix</b>";}
			echo ".</p>\n";

			echo "<p>Patientez un peu, puis suivez ce lien: <a href='../Admin/result.$timestamp.html' target='_blank'>R&#233;sultat</a></p>\n";

			echo("<p><i>NOTES:</i></p>\n");
			echo("<ul>\n");
			//echo("<li>Pour le moment la variable \$prefix='$prefix' n'est pas geree... A FAIRE</li>\n");
			echo("<li><p>Les changements de classe, suppressions de membres de groupes, en dehors de l'import annuel, ne sont pas g&#233;r&#233;s.<br />Dans le cas de changement de classe d'un &#233;l&#232;ve, il risque d'apparaitre membre de plusieurs classes...<br />Il faut faire le m&#233;nage &#224; la main.<br />On pourrait par contre ajouter un test pour lister les comptes membres de plusieurs classes<br />... contr&#244;ler aussi qu'aucun compte n'est &#224; la fois dans plusieurs parmi les groupes Profs, Eleves, Administratifs.</p></li>\n");
			echo("<li><p>Le mode simulation ne simule que la cr&#233;ation/r&#233;cup&#233;ration d'utilisateurs.<br />Cela permet d&#233;j&#224; de rep&#233;rer si les cr&#233;ations annonc&#233;es sont conformes &#224; ce que l'on attendait.<br />Les uid g&#233;n&#233;r&#233;s/simul&#233;s peuvent par contre &#234;tre erron&#233;s si jamais deux nouveaux utilisateurs correspondent &#224; des uid en doublon, il se peut qu'ils obtiennent en simulation le m&#234;me uid.<br /><i>Exemple:</i> Deux nouveaux arrivants Alex T&#233;rieur et Alain T&#233;rieur donneront tous deux l'uid 'terieura' en simulation alors qu'en mode cr&#233;ation, le premier obtiendrait l'uid 'terieura' et le deuxi&#232;me 'terieur2'.<br />Et si l'annuaire contenait d&#233;j&#224; un compte 'terieura' (<i>pour Anabelle Terieur</i>), les deux nouveaux comptes paraitraient recevoir en mode simultation l'uid 'terieur2' alors qu'en mode cr&#233;ation, le premier obtiendrait l'uid 'terieur2' et le deuxi&#232;me 'terieur3'.</p><p><i>Remarque:</i> Le mode simulation permet tout de m&#234;me la g&#233;n&#233;ration de fichiers f_ele.txt, f_div.txt, f_men.txt et f_wind.txt</p></li>\n");

			echo("</ul>\n");

			include $pathlcsorse3."pdp.inc.php";
			flush();






/*
			//echo "On va lancer le script PHP.";

			$type_fichier_eleves=$_POST['type_fichier_eleves'];

			$tmp_eleves_file=$HTTP_POST_FILES['eleves_file']['tmp_name'];
			$eleves_file=$HTTP_POST_FILES['eleves_file']['name'];
			$size_eleves_file=$HTTP_POST_FILES['eleves_file']['size'];

			if(is_uploaded_file($tmp_eleves_file)){
				$dest_file="tmp/$eleves_file";
				// SUR CA, IL VAUDRAIT SANS DOUTE MIEUX FORCER LE NOM DESTINATION POUR EVITER DES SALES BLAGUES

				$source_file=stripslashes("$tmp_eleves_file");
				$res_copy=copy("$source_file" , "$dest_file");

				$php="/usr/bin/php";
				$chemin="/home/www/html/steph/test_php-cli";
				$resultat=exec("$php $chemin/traitement.php $type_fichier_eleves $chemin/$dest_file",$retour);
				for($i=0;$i<count($retour);$i++){
					echo "\$retour[$i]=$retour[$i]<br />";
				}

	//mer fev 28 12:53:29 steph@fuji:~/2007_02_21/se3
	//$ cat /tmp/rapport_test.txt
	//$type_fichier_eleves=csv
	//$eleves_file=/home/www/html/steph/test_php-cli/tmp/exportCSVExtraction_20061018.csv
	//mer fev 28 12:53:33 steph@fuji:~/2007_02_21/se3
	//$


	//On va lancer le script PHP.$retour[0]=



			}
*/

		}

		// Dans la version PHP4-CLI, envoyer le rapport par mail.
		// Envoyer le contenu de la page aussi?

		// Peut-etre forcer une sauvegarde de l'annuaire avant de proceder a une oepration qui n'est pas une simulation.
		// Ou placer le fichier de sauvegarde?
		// Probleme de l'encombrement a terme.
	}
	//include $pathlcsorse3."pdp.inc.php";
?>
