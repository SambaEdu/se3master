<?php


   /**
   
   * Lecture des fichiers CSV/XML de Sconet
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Stephane Boireau (Animateur de Secteur pour les TICE sur Bernay/Pont-Audemer (27))
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: lecture_xml_sts_emp.php
   */


/*@set_time_limit(0);


// Initialisations files
require_once("../lib/initialisations.inc.php");

// Resume session
$resultat_session = resumeSession();
if ($resultat_session == 'c') {
header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
die();
} else if ($resultat_session == '0') {
header("Location: ../logout.php?auto=1");
die();
};

if (!checkAccess()) {
header("Location: ../logout.php?auto=1");
die();
}
*/



/**
* Fonction de generation de mot de passe recuperee sur TotallyPHP
* Aucune mention de licence pour ce script...
* @Parametres
* @return le mot de passe genere 

* The letter l (lowercase L) and the number 1
* have been removed, as they can be mistaken
* for each other.

*/


// HTMLPurifier
require_once ("traitement_data.inc.php");


function createRandomPassword() {
	$chars = "abcdefghijkmnopqrstuvwxyz023456789";
	srand((double)microtime()*1000000);
	$i = 0;
	$pass = '' ;

	//while ($i <= 7) {
	while ($i <= 5) {
		$num = rand() % 33;
		$tmp = substr($chars, $num, 1);
		$pass = $pass . $tmp;
		$i++;
	}

	return $pass;
}
//================================================

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Lecture du XML Emploi du temps de Sts-web et g&#233;n&#233;ration de CSV</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="author" content="Stephane Boireau, A.S. RUE de Bernay/Pont-Audemer" />
	<!--link type="text/css" rel="stylesheet" href="../styles.css" /-->
	<link type="text/css" rel="stylesheet" href="../style.css" />
</head>
<body>
	<div class="content">
		<?php
			//$nom_corrige = preg_replace("/[^.a-zA-Z0-9_=-]+/", "_", $sav_file['name']);

			/*
			Pour GIBII, il faudrait generer des lignes du type:
			code#mdp;nom;prenom;dateNaiss;liste des classes separees par des virgules
			ou code semble etre le login
			*/

			if(isset($_GET['nettoyage'])){
				echo "<h1 align='center'>Suppression des CSV</h1>\n";

				$doss_date=$_GET['date'];
				//echo "strlen(preg_replace("\[0-9_]\",\"\",$doss_date))=".strlen(preg_replace("/[0-9_]/","",$doss_date))."<br />";
				//echo "strlen(preg_replace(\"[0-9_]\",\"\",$doss_date))=strlen(".preg_replace("/[0-9_]/","",$doss_date).")<br />";
				//echo "\$doss_date=$doss_date<br />";
				if(strlen(preg_replace("/[0-9_.]/","",$doss_date))!=0){
					echo "<p style='color:red;'>Erreur! Le param&#232;tre date fourni n'est pas correct.</p>\n";
					echo "<p>Retour &#224; l'<a href='".$_SERVER['PHP_SELF']."'>index</a></p>\n";
					echo "</div></body></html>\n";
				}
				else{
					$dossiercsv="csv/".$doss_date;
				}

				echo "<p>Si des fichiers CSV existent, ils seront supprim&#233;s...</p>\n";
				//$tabfich=array("f_wind.csv","f_men.csv","f_gpd.csv","f_div.csv","f_tmt.csv","profs.html");
				$tabfich=array("f_wind.csv","f_men.csv","f_gpd.csv","f_div.csv","f_tmt.csv","profs.html","f_wind.txt","f_men.txt","f_div.txt");
				for($i=0;$i<count($tabfich);$i++){
					if(file_exists("$dossiercsv/se3/$tabfich[$i]")){
						echo "<p>Suppression de se3/$tabfich[$i]... ";
						if(unlink("$dossiercsv/se3/$tabfich[$i]")){
							echo "r&#233;ussie.</p>\n";
						}
						else{
							echo "<font color='red'>Echec!</font> V&#233;rifiez les droits d'&#233;criture sur le serveur.</p>\n";
						}
					}
					if(file_exists("$dossiercsv/gepi/$tabfich[$i]")){
						echo "<p>Suppression de gepi/$tabfich[$i]... ";
						if(unlink("$dossiercsv/gepi/$tabfich[$i]")){
							echo "r&#233;ussie.</p>\n";
						}
						else{
							echo "<font color='red'>Echec!</font> V&#233;rifiez les droits d'&#233;criture sur le serveur.</p>\n";
						}
					}
				}
				if(file_exists("$dossiercsv/gepi")){
					if(!rmdir("$dossiercsv/gepi")){echo "ERREUR de suppression de $dossiercsv/gepi<br />";}
				}
				if(file_exists("$dossiercsv/se3")){
					rmdir("$dossiercsv/se3");
				}
				if(file_exists("$dossiercsv")){
					rmdir("$dossiercsv");
				}
				echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a>.</p>\n";
			}
			else{
				echo "<h1 align='center'>Lecture du XML Emploi du temps de Sts-web et g&#233;n&#233;ration de CSV</h1>\n";
				if(!isset($_POST['is_posted'])){
					echo "<p>Cette page permet de remplir des tableaux PHP avec les informations professeurs, mati&#232;res<br />\n";
					echo "<b>Attention:</b>Les liaisons profs/mati&#232;res/classes ne sont remplies dans le fichier XML de STS qu'une fois l'emploi du temps remont&#233;.</p>";
					echo "<p>Cette page g&#233;n&#232;re des fichiers CSV:</p>\n";
					echo "<ul>\n";
						echo "<li>\n";
							echo "<p><b>Pour SambaEdu3:</b></p>\n";
							echo "<ul>\n";
							echo "<li>f_wind.txt</li>\n";
							echo "<li>f_div.txt</li>\n";
							echo "<li>f_men.txt</li>\n";
							echo "</ul>\n";
						echo "</li>\n";
						echo "<li>\n";
							echo "<p><b>Pour Gepi:</b></p>\n";
							echo "<ul>\n";
							echo "<li>f_wind.csv</li>\n";
							echo "<li>f_div.csv</li>\n";
							echo "<li>f_men.csv</li>\n";
							echo "<li>f_gpd.csv</li>\n";
							echo "<li>f_tmt.csv</li>\n";
							echo "</ul>\n";
						echo "</li>\n";
					echo "</ul>\n";
					echo "<p>Il faut lui fournir un Export XML r&#233;alis&#233; depuis l'application STS-web.<br />Demandez gentillement &#224; votre secr&#233;taire d'acc&#233;der &#224; STS-web et d'effectuer 'Mise &#224; jour/Exports/Emplois du temps'.</p>\n";
					echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
					echo "<p>Veuillez fournir le fichier XML: \n";
					echo "<p><input type=\"file\" size=\"80\" name=\"xml_file\">\n";
					echo "<input type='hidden' name='is_posted' value='yes'>\n";
					echo "</p>\n";
					echo "<p> Pour GEPI:<br />\n";
					echo "<input type=\"radio\" name=\"mdp\" value=\"alea\" checked> G&#233;n&#233;rer un mot de passe al&#233;atoire pour chaque professeur.<br />\n";
					echo "<input type=\"radio\" name=\"mdp\" value=\"date\"> Utiliser plut&#244;t la date de naissance au format 'aaaammjj' comme mot de passe initial (<i>il devra &#234;tre modifi&#233; au premier login</i>).</p>\n";
					echo "<input type='hidden' name='is_posted' value='yes'>\n";
					//echo "</p>\n";
					echo "<p><input type='submit' value='Valider'></p>\n";
					echo "</form>\n";
				}
				else{
					// Initialisation du repertoire actuel de sauvegarde
					// Pour integration dans Gepi:
					//$dirname = getSettingValue("backup_directory");
					//$dossiercsv="../backup/$dirname/csv";

					//$dossiercsv="csv/".strtr(substr(microtime(),1)," ","_");
					$doss_date=$_SERVER['REMOTE_ADDR'].strtr(substr(microtime(),2)," ","_");
					//$dossiercsv="csv/".$doss_date;
					$dossiercsv="csv/".$doss_date;
					//mkdir($dossiercsv);
					//echo "\$dossiercsv=$dossiercsv<br />\n";

					$temoin_creation_fichiers="oui";
					if(!file_exists("csv")){
						//if(!mkdir("$dossiercsv","0770")){
						if(!mkdir("csv")){
/*
							echo "<p style='color:red;'>Erreur! Le dossier csv n'a pas pu &#234;tre cr&#233;&#233;.</p>\n";
							echo "<p>Retour &#224; l'<a href='".$_SERVER['PHP_SELF']."'>index</a></p>\n";
							echo "</div></body></html>\n";
							die();
*/
							echo "<p style='color:red;'>Erreur! Le dossier csv n'a pas pu &#234;tre cr&#233;&#233;.<br />Les fichiers ne seront pas g&#233;n&#233;r&#233;s, mais vous pourrez remplir vos fichiers par copier/coller depuis cette page.</p>\n";
							$temoin_creation_fichiers="non";
						}
					}

					//if(!file_exists("$dossiercsv")){
					//if(!file_exists("$dossiercsv/se3")){
					if(!file_exists("$dossiercsv")){
						//if(!mkdir("$dossiercsv","0770")){
						if(!mkdir("$dossiercsv")){
							echo "<p style='color:red;'>Erreur! Le dossier csv n'a pas pu &#234;tre cr&#233;&#233;.<br />Les fichiers ne seront pas g&#233;n&#233;r&#233;s, mais vous pourrez remplir vos fichiers par copier/coller depuis cette page.</p>\n";
							//echo "<p>Retour &#224; l'<a href='".$_SERVER['PHP_SELF']."'>index</a></p>\n";
							//echo "</div></body></html>\n";
							//die();
							$temoin_creation_fichiers="non";
						}
						else{
							if(!file_exists("$dossiercsv/se3")){
								if(!mkdir("$dossiercsv/se3")){
									echo "<p style='color:red;'>Erreur! Le dossier csv/se3 n'a pas pu &#234;tre cr&#233;&#233;.<br />Les fichiers ne seront pas g&#233;n&#233;r&#233;s, mais vous pourrez remplir vos fichiers par copier/coller depuis cette page.</p>\n";
									//echo "<p>Retour &#224; l'<a href='".$_SERVER['PHP_SELF']."'>index</a></p>\n";
									//echo "</div></body></html>\n";
									//die();
									$temoin_creation_fichiers="non";
								}
							}
							if(!file_exists("$dossiercsv/gepi")){
								if(!mkdir("$dossiercsv/gepi")){
									echo "<p style='color:red;'>Erreur! Le dossier csv/gepi n'a pas pu &#234;tre cr&#233;&#233;.<br />Les fichiers ne seront pas g&#233;n&#233;r&#233;s, mais vous pourrez remplir vos fichiers par copier/coller depuis cette page.</p>\n";
									//echo "<p>Retour &#224; l'<a href='".$_SERVER['PHP_SELF']."'>index</a></p>\n";
									//echo "</div></body></html>\n";
									//die();
									$temoin_creation_fichiers="non";
								}
							}
						}
					}

					$temoin_au_moins_un_prof_princ="";

					$xml_file = isset($_FILES["xml_file"]) ? $_FILES["xml_file"] : NULL;
					$fp=fopen($xml_file['tmp_name'],"r");
					if($fp){
						echo "<h2>Premi&#232;re phase...</h2>\n";
						echo "<blockquote>\n";
						echo "<h3>Lecture du fichier...</h3>\n";
						echo "<blockquote>\n";
						while(!feof($fp)){
							$ligne[]=fgets($fp,4096);
						}
						fclose($fp);
						echo "<p>Termin&#233;.</p>\n";
						echo "<p>Aller &#224; la <a href='#se3'>section SambaEdu3</a></p>\n";
						echo "<p>Aller &#224; la <a href='#gepi'>section GEPI</a><br />Si vous patientez, des liens directs seront propos&#233;s pour t&#233;l&#233;charger les fichiers.</p>\n";
						echo "</blockquote>\n";

						echo "<h3>Affichage du XML</h3>\n";
						echo "<blockquote>\n";
						echo "<table border='0'>\n";
						$cpt=0;
						while($cpt<count($ligne)){
							echo "<tr>\n";
							echo "<td style='color: blue;'>$cpt</td><td>".htmlentities($ligne[$cpt])."</td>\n";
							echo "</tr>\n";
							$cpt++;
						}
						echo "</table>\n";
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";
						echo "</blockquote>\n";



						echo "<h2>Etablissement</h2>\n";
						echo "<blockquote>\n";
						echo "<h3>Analyse du fichier pour extraire les param&#232;tres de l'&#233;tablissement...</h3>\n";
						echo "<blockquote>\n";
						$cpt=0;
						$etablissement=array();
						$temoin_param=0;
						$temoin_academie=0;
						$temoin_annee=0;
						while($cpt<count($ligne)){
							//echo htmlentities($ligne[$cpt])."<br />\n";
							if(strstr($ligne[$cpt],"<PARAMETRES>")){
								echo "D&#233;but de la section PARAMETRES &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_param++;
							}
							if(strstr($ligne[$cpt],"</PARAMETRES>")){
								echo "Fin de la section PARAMETRES &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_param++;
							}
							if($temoin_param==1){
								// On analyse maintenant matiere par matiere:
								/*
								if(strstr($ligne[$cpt],"<UAJ CODE=")){
									unset($tabtmp);
									$tabtmp=explode('"',$ligne[$cpt]);
									$etablissement["code"]=$tabtmp[1];
									$temoin_uaj=1;
									//echo "\$temoin_uaj=$temoin_uaj &#224; la ligne $cpt et \$tabtmp[1]=$tabtmp[1]<br />\n";
								}
								*/
								if(strstr($ligne[$cpt],"<UAJ ")){
									unset($tabtmp);
									$tabtmp=explode('"',strstr($ligne[$cpt]," CODE="));
									$etablissement["code"]=$tabtmp[1];
									$temoin_uaj=1;
									//echo "\$temoin_uaj=$temoin_uaj &#224; la ligne $cpt et \$tabtmp[1]=$tabtmp[1]<br />\n";
								}
								if(strstr($ligne[$cpt],"</UAJ>")){
									$temoin_uaj=0;
								}
								if($temoin_uaj==1){
									if(strstr($ligne[$cpt],"<ACADEMIE>")){
										$temoin_academie=1;
										$etablissement["academie"]=array();
									}
									if(strstr($ligne[$cpt],"</ACADEMIE>")){
										$temoin_academie=0;
									}
									if($temoin_academie==1){
										if(strstr($ligne[$cpt],"<CODE>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["academie"]["code"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<LIBELLE>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["academie"]["libelle"]=$tabtmp[2];
										}
									}
									else{
										if(strstr($ligne[$cpt],"<SIGLE>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["sigle"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<DENOM_PRINC>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["denom_princ"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<DENOM_COMPL>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["denom_compl"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<CODE_NATURE>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["code_nature"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<CODE_CATEGORIE>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["code_categorie"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<ADRESSE>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["adresse"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<COMMUNE>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["commune"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<CODE_POSTAL>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["code_postal"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<BOITE_POSTALE>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["boite_postale"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<CEDEX>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["cedex"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<TELEPHONE>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["telephone"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<STATUT>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["statut"]=$tabtmp[2];
										}
										if(strstr($ligne[$cpt],"<ETABLISSEMENT_SENSIBLE>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$etablissement["etablissement_sensible"]=$tabtmp[2];
										}
									}
								}


								/*
								if(strstr($ligne[$cpt],"<ANNEE_SCOLAIRE ANNEE=")){
									unset($tabtmp);
									$tabtmp=explode('"',$ligne[$cpt]);
									$etablissement["annee"]=array();
									$etablissement["annee"]["annee"]=$tabtmp[1];
									$temoin_annee=1;
								}
								*/
								if(strstr($ligne[$cpt],"<ANNEE_SCOLAIRE ")){
									unset($tabtmp);
									$tabtmp=explode('"',strstr($ligne[$cpt]," ANNEE"));
									$etablissement["annee"]=array();
									$etablissement["annee"]["annee"]=$tabtmp[1];
									$temoin_annee=1;
								}
								if(strstr($ligne[$cpt],"</ANNEE_SCOLAIRE>")){
									$temoin_annee=0;
								}
								if($temoin_annee==1){
									if(strstr($ligne[$cpt],"<DATE_DEBUT>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										$etablissement["annee"]["date_debut"]=$tabtmp[2];
									}
									if(strstr($ligne[$cpt],"<DATE_FIN>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										$etablissement["annee"]["date_fin"]=$tabtmp[2];
									}
								}
							}
							$cpt++;
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";

						echo "<h3>Affichage des donn&#233;es PARAMETRES &#233;tablissement extraites:</h3>\n";
						echo "<blockquote>\n";
						echo "<table border='1'>\n";
						echo "<tr>\n";
						//echo "<th style='color: blue;'>&nbsp;</th>\n";
						echo "<th>Code</th>\n";
						echo "<th>Code academie</th>\n";
						echo "<th>Libelle academie</th>\n";
						echo "<th>Sigle</th>\n";
						echo "<th>Denom_princ</th>\n";
						echo "<th>Denom_compl</th>\n";
						echo "<th>Code_nature</th>\n";
						echo "<th>Code_categorie</th>\n";
						echo "<th>Adresse</th>\n";
						echo "<th>Code_postal</th>\n";
						echo "<th>Boite_postale</th>\n";
						echo "<th>Cedex</th>\n";
						echo "<th>Telephone</th>\n";
						echo "<th>Statut</th>\n";
						echo "<th>Etablissement_sensible</th>\n";
						echo "<th>Annee</th>\n";
						echo "<th>Date_debut</th>\n";
						echo "<th>Date_fin</th>\n";
						echo "</tr>\n";
						//$cpt=0;
						//while($cpt<count($etablissement)){
							echo "<tr>\n";
							//echo "<td style='color: blue;'>$cpt</td>\n";
							//echo "<td style='color: blue;'>&nbsp;</td>\n";
							echo "<td>".$etablissement["code"]."</td>\n";
							echo "<td>".$etablissement["academie"]["code"]."</td>\n";
							echo "<td>".$etablissement["academie"]["libelle"]."</td>\n";
							echo "<td>".$etablissement["sigle"]."</td>\n";
							echo "<td>".$etablissement["denom_princ"]."</td>\n";
							echo "<td>".$etablissement["denom_compl"]."</td>\n";
							echo "<td>".$etablissement["code_nature"]."</td>\n";
							echo "<td>".$etablissement["code_categorie"]."</td>\n";
							echo "<td>".$etablissement["adresse"]."</td>\n";
							echo "<td>".$etablissement["code_postal"]."</td>\n";
							echo "<td>".$etablissement["boite_postale"]."</td>\n";
							echo "<td>".$etablissement["cedex"]."</td>\n";
							echo "<td>".$etablissement["telephone"]."</td>\n";
							echo "<td>".$etablissement["statut"]."</td>\n";
							echo "<td>".$etablissement["etablissement_sensible"]."</td>\n";
							echo "<td>".$etablissement["annee"]["annee"]."</td>\n";
							echo "<td>".$etablissement["annee"]["date_debut"]."</td>\n";
							echo "<td>".$etablissement["annee"]["date_fin"]."</td>\n";
							echo "</tr>\n";
							$cpt++;
						//}
						echo "</table>\n";
						echo "</blockquote>\n";
						echo "</blockquote>\n";











						echo "<h2>Mati&#232;res</h2>\n";
						echo "<blockquote>\n";
						echo "<h3>Analyse du fichier pour extraire les mati&#232;res...</h3>\n";
						echo "<blockquote>\n";
						$cpt=0;
						$temoin_matieres=0;
						$matiere=array();
						$i=0;
						$temoin_mat=0;
						while($cpt<count($ligne)){
							//echo htmlentities($ligne[$cpt])."<br />\n";
							if(strstr($ligne[$cpt],"<MATIERES>")){
								echo "D&#233;but de la section MATIERES &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_matieres++;
							}
							if(strstr($ligne[$cpt],"</MATIERES>")){
								echo "Fin de la section MATIERES &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_matieres++;
							}
							if($temoin_matieres==1){
								// On analyse maintenant matiere par matiere:
								/*
								if(strstr($ligne[$cpt],"<MATIERE CODE=")){
									$matiere[$i]=array();
									unset($tabtmp);
									//$tabtmp=explode("=",preg_replace("/>/","",preg_replace("/</","",$ligne[$cpt])));
									$tabtmp=explode('"',$ligne[$cpt]);
									$matiere[$i]["code"]=$tabtmp[1];
									$temoin_mat=1;
								}
								*/
								if(strstr($ligne[$cpt],"<MATIERE ")){
									$matiere[$i]=array();
									unset($tabtmp);
									//$tabtmp=explode("=",preg_replace("/>/","",preg_replace("/</","",$ligne[$cpt])));
									$tabtmp=explode('"',strstr($ligne[$cpt]," CODE="));
									$matiere[$i]["code"]=$tabtmp[1];
									$temoin_mat=1;
								}
								if(strstr($ligne[$cpt],"</MATIERE>")){
									$temoin_mat=0;
									$i++;
								}
								if($temoin_mat==1){
									if(strstr($ligne[$cpt],"<CODE_GESTION>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										//$matiere[$i]["code_gestion"]=$tabtmp[2];
										$matiere[$i]["code_gestion"]=preg_replace("/[^a-zA-Z0-9&_. -]/","",html_entity_decode($tabtmp[2]));
									}
									if(strstr($ligne[$cpt],"<LIBELLE_COURT>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										//$matiere[$i]["libelle_court"]=$tabtmp[2];
										$matiere[$i]["libelle_court"]=preg_replace("/[^a-zA-Z0-9ÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/","",html_entity_decode($tabtmp[2]));
									}
									if(strstr($ligne[$cpt],"<LIBELLE_LONG>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										$matiere[$i]["libelle_long"]=$tabtmp[2];
									}
									if(strstr($ligne[$cpt],"<LIBELLE_EDITION>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										$matiere[$i]["libelle_edition"]=$tabtmp[2];
									}
								}
							}

							$cpt++;
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";

						echo "<h3>Affichage des donn&#233;es MATIERES extraites:</h3>\n";
						echo "<blockquote>\n";
						echo "<table border='1'>\n";
						echo "<tr>\n";
						echo "<th style='color: blue;'>&nbsp;</th>\n";
						echo "<th>Code</th>\n";
						echo "<th>Code_gestion</th>\n";
						echo "<th>Libelle_court</th>\n";
						echo "<th>Libelle_long</th>\n";
						echo "<th>Libelle_edition</th>\n";
						echo "</tr>\n";
						$cpt=0;
						while($cpt<count($matiere)){
							echo "<tr>\n";
							echo "<td style='color: blue;'>$cpt</td>\n";
							echo "<td>".$matiere[$cpt]["code"]."</td>\n";
							echo "<td>".$matiere[$cpt]["code_gestion"]."</td>\n";
							echo "<td>".$matiere[$cpt]["libelle_court"]."</td>\n";
							echo "<td>".$matiere[$cpt]["libelle_long"]."</td>\n";
							echo "<td>".$matiere[$cpt]["libelle_edition"]."</td>\n";
							echo "</tr>\n";
							$cpt++;
						}
						echo "</table>\n";
						echo "</blockquote>\n";
						echo "</blockquote>\n";














						echo "<h2>Civilit&#233;s</h2>\n";
						echo "<blockquote>\n";
						echo "<h3>Analyse du fichier pour extraire les civilit&#233;s...</h3>\n";
						echo "<blockquote>\n";
						$cpt=0;
						$temoin_civilites=0;
						$civilites=array();
						$i=0;
						while($cpt<count($ligne)){
							//echo htmlentities($ligne[$cpt])."<br />\n";
							if(strstr($ligne[$cpt],"<CIVILITES>")){
								echo "D&#233;but de la section CIVILITES &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_civilites++;
							}
							if(strstr($ligne[$cpt],"</CIVILITES>")){
								echo "Fin de la section CIVILITES &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_civilites++;
							}
							if($temoin_civilites==1){
								/*
								if(strstr($ligne[$cpt],"<CIVILITE CODE=")){
									$civilites[$i]=array();
									unset($tabtmp);
									$tabtmp=explode('"',$ligne[$cpt]);
									$civilites[$i]["code"]=$tabtmp[1];
									$temoin_civ=1;
								}
								*/
								if(strstr($ligne[$cpt],"<CIVILITE ")){
									$civilites[$i]=array();
									unset($tabtmp);
									$tabtmp=explode('"',strstr($ligne[$cpt]," CODE="));
									$civilites[$i]["code"]=$tabtmp[1];
									$temoin_civ=1;
								}
								if(strstr($ligne[$cpt],"</CIVILITE>")){
									$temoin_civ=0;
									$i++;
								}
								if($temoin_civ==1){
									if(strstr($ligne[$cpt],"<LIBELLE_COURT>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										$civilites[$i]["libelle_court"]=$tabtmp[2];
									}
									if(strstr($ligne[$cpt],"<LIBELLE_LONG>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										$civilites[$i]["libelle_long"]=$tabtmp[2];
									}
								}
							}
							$cpt++;
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";

						echo "<h3>Affichage des donn&#233;es CIVILITES extraites:</h3>\n";
						echo "<blockquote>\n";
						echo "<table border='1'>\n";
						echo "<tr>\n";
						echo "<th style='color: blue;'>&nbsp;</th>\n";
						echo "<th>Code</th>\n";
						echo "<th>Libelle_court</th>\n";
						echo "<th>Libelle_long</th>\n";
						echo "</tr>\n";
						$cpt=0;
						while($cpt<count($civilites)){
							echo "<tr>\n";
							echo "<td style='color: blue;'>$cpt</td>\n";
							echo "<td>".$civilites[$cpt]["code"]."</td>\n";
							echo "<td>".$civilites[$cpt]["libelle_court"]."</td>\n";
							echo "<td>".$civilites[$cpt]["libelle_long"]."</td>\n";
							echo "</tr>\n";
							$cpt++;
						}
						echo "</table>\n";
						echo "</blockquote>\n";
						echo "</blockquote>\n";










						echo "<h2>Personnels</h2>\n";
						echo "<blockquote>\n";
						echo "<h3>Analyse du fichier pour extraire les professeurs,...</h3>\n";
						echo "<blockquote>\n";
						$cpt=0;
						$temoin_professeurs=0;
						$prof=array();
						$i=0;
						$temoin_prof=0;
						while($cpt<count($ligne)){
							//echo htmlentities($ligne[$cpt])."<br />\n";
							if(strstr($ligne[$cpt],"<INDIVIDUS>")){
								echo "D&#233;but de la section INDIVIDUS &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_professeurs++;
							}
							if(strstr($ligne[$cpt],"</INDIVIDUS>")){
								echo "Fin de la section INDIVIDUS &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_professeurs++;
							}
							if($temoin_professeurs==1){
								// On analyse maintenant matiere par matiere:
								/*
								if(strstr($ligne[$cpt],"<INDIVIDU ID=")){
									$prof[$i]=array();
									unset($tabtmp);
									$tabtmp=explode('"',$ligne[$cpt]);
									$prof[$i]["id"]=$tabtmp[1];
									$prof[$i]["type"]=$tabtmp[3];
									$temoin_prof=1;
								}
								*/
								if(strstr($ligne[$cpt],"<INDIVIDU ")){
									$prof[$i]=array();
									unset($tabtmp);
									$tabtmp=explode('"',strstr($ligne[$cpt]," ID="));
									$prof[$i]["id"]=$tabtmp[1];
									$tabtmp=explode('"',strstr($ligne[$cpt]," TYPE="));
									$prof[$i]["type"]=$tabtmp[1];
									$temoin_prof=1;
								}
								if(strstr($ligne[$cpt],"</INDIVIDU>")){
									$temoin_prof=0;
									$i++;
								}
								if($temoin_prof==1){
									if(strstr($ligne[$cpt],"<SEXE>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										//$prof[$i]["sexe"]=$tabtmp[2];
										$prof[$i]["sexe"]=preg_replace("/[^1-2]/","",$tabtmp[2]);
									}
									if(strstr($ligne[$cpt],"<CIVILITE>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										//$prof[$i]["civilite"]=$tabtmp[2];
										$prof[$i]["civilite"]=preg_replace("/[^1-3]/","",$tabtmp[2]);
									}
									if(strstr($ligne[$cpt],"<NOM_USAGE>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										//$prof[$i]["nom_usage"]=$tabtmp[2];
										$prof[$i]["nom_usage"]=preg_replace("/[^a-zA-Z -]/","",$tabtmp[2]);
									}
									if(strstr($ligne[$cpt],"<NOM_PATRONYMIQUE>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										//$prof[$i]["nom_patronymique"]=$tabtmp[2];
										$prof[$i]["nom_patronymique"]=preg_replace("/[^a-zA-Z -]/","",$tabtmp[2]);
									}
									if(strstr($ligne[$cpt],"<PRENOM>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										//$prof[$i]["prenom"]=$tabtmp[2];
										$prof[$i]["prenom"]=preg_replace("/[^a-zA-Z0-9ÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/","",$tabtmp[2]);
									}
									if(strstr($ligne[$cpt],"<DATE_NAISSANCE>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										//$prof[$i]["date_naissance"]=$tabtmp[2];
										$prof[$i]["date_naissance"]=preg_replace("/[^0-9-]/","",$tabtmp[2]);
									}
									if(strstr($ligne[$cpt],"<GRADE>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										$prof[$i]["grade"]=$tabtmp[2];
									}
									if(strstr($ligne[$cpt],"<FONCTION>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										$prof[$i]["fonction"]=$tabtmp[2];
									}



									if(strstr($ligne[$cpt],"<PROFS_PRINC>")){
										$temoin_profs_princ=1;
										//$prof[$i]["prof_princs"]=array();
										$j=0;
									}
									if(strstr($ligne[$cpt],"</PROFS_PRINC>")){
										$temoin_profs_princ=0;
									}

									if($temoin_profs_princ==1){

										if(strstr($ligne[$cpt],"<PROF_PRINC>")){
											$temoin_prof_princ=1;
											$prof[$i]["prof_princ"]=array();
										}
										if(strstr($ligne[$cpt],"</PROF_PRINC>")){
											$temoin_prof_princ=0;
											$j++;
										}

										if($temoin_prof_princ==1){
											if(strstr($ligne[$cpt],"<CODE_STRUCTURE>")){
												unset($tabtmp);
												$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
												$prof[$i]["prof_princ"][$j]["code_structure"]=$tabtmp[2];
												$temoin_au_moins_un_prof_princ="oui";
											}

											if(strstr($ligne[$cpt],"<DATE_DEBUT>")){
												unset($tabtmp);
												$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
												$prof[$i]["prof_princ"][$j]["date_debut"]=$tabtmp[2];
											}
											if(strstr($ligne[$cpt],"<DATE_FIN>")){
												unset($tabtmp);
												$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
												$prof[$i]["prof_princ"][$j]["date_fin"]=$tabtmp[2];
											}
										}
									}




									if(strstr($ligne[$cpt],"<DISCIPLINES>")){
										$temoin_disciplines=1;
										$prof[$i]["disciplines"]=array();
										$j=0;
									}
									if(strstr($ligne[$cpt],"</DISCIPLINES>")){
										$temoin_disciplines=0;
									}



									if($temoin_disciplines==1){
										/*
										if(strstr($ligne[$cpt],"<DISCIPLINE CODE=")){
											$temoin_disc=1;
											unset($tabtmp);
											$tabtmp=explode('"',$ligne[$cpt]);
											$prof[$i]["disciplines"][$j]["code"]=$tabtmp[1];
										}
										*/
										if(strstr($ligne[$cpt],"<DISCIPLINE ")){
											$temoin_disc=1;
											unset($tabtmp);
											$tabtmp=explode('"',strstr($ligne[$cpt]," CODE="));
											$prof[$i]["disciplines"][$j]["code"]=$tabtmp[1];
										}
										if(strstr($ligne[$cpt],"</DISCIPLINE>")){
											$temoin_disc=0;
											$j++;
										}

										if($temoin_disc==1){
											if(strstr($ligne[$cpt],"<LIBELLE_COURT>")){
												unset($tabtmp);
												$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
												$prof[$i]["disciplines"][$j]["libelle_court"]=$tabtmp[2];
											}
											if(strstr($ligne[$cpt],"<NB_HEURES>")){
												unset($tabtmp);
												$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
												$prof[$i]["disciplines"][$j]["nb_heures"]=$tabtmp[2];
											}
										}
									}


								}
							}



							// On va recuperer les divisions et associations profs/matieres...
							if(strstr($ligne[$cpt],"<STRUCTURE>")){
								echo "D&#233;but de la section STRUCTURE &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_structure++;
							}
							if(strstr($ligne[$cpt],"</STRUCTURE>")){
								echo "Fin de la section STRUCTURE &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_structure++;
							}
							if($temoin_structure==1){
								if(strstr($ligne[$cpt],"<DIVISIONS>")){
									echo "D&#233;but de la section DIVISIONS &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
									$temoin_divisions++;
									$divisions=array();
									$i=0;
								}
								if(strstr($ligne[$cpt],"</DIVISIONS>")){
									echo "Fin de la section DIVISIONS &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
									$temoin_divisions++;
								}
								if($temoin_divisions==1){
									/*
									if(strstr($ligne[$cpt],"<DIVISION CODE=")){
										$temoin_div=1;
										unset($tabtmp);
										$tabtmp=explode('"',$ligne[$cpt]);
										$divisions[$i]["code"]=$tabtmp[1];
									}
									*/
									if(strstr($ligne[$cpt],"<DIVISION ")){
										$temoin_div=1;
										unset($tabtmp);
										$tabtmp=explode('"',strstr($ligne[$cpt]," CODE="));
										$divisions[$i]["code"]=$tabtmp[1];
									}
									if(strstr($ligne[$cpt],"</DIVISION>")){
										$temoin_div=0;
										$i++;
									}

									if($temoin_div==1){
										if(strstr($ligne[$cpt],"<SERVICES>")){
											$temoin_services=1;
											$j=0;
										}
										if(strstr($ligne[$cpt],"</SERVICES>")){
											$temoin_services=0;
										}

										if($temoin_services==1){
											/*
											if(strstr($ligne[$cpt],"<SERVICE CODE_MATIERE=")){
												$temoin_disc=1;
												unset($tabtmp);
												$tabtmp=explode('"',$ligne[$cpt]);
												$divisions[$i]["services"][$j]["code_matiere"]=$tabtmp[1];
											}
											*/
											if(strstr($ligne[$cpt],"<SERVICE ")){
												$temoin_disc=1;
												unset($tabtmp);
												$tabtmp=explode('"',strstr($ligne[$cpt]," CODE_MATIERE="));
												$divisions[$i]["services"][$j]["code_matiere"]=$tabtmp[1];
											}
											if(strstr($ligne[$cpt],"</SERVICE>")){
												$temoin_disc=0;
												$j++;
											}

											if($temoin_disc==1){
												if(strstr($ligne[$cpt],"<ENSEIGNANTS>")){
													$temoin_enseignants=1;
													$divisions[$i]["services"][$j]["enseignants"]=array();
													$k=0;
												}
												if(strstr($ligne[$cpt],"</ENSEIGNANTS>")){
													$temoin_enseignants=0;
												}
												if($temoin_enseignants==1){
													/*
													if(strstr($ligne[$cpt],"<ENSEIGNANT ID=")){
														//$temoin_ens=1;
														unset($tabtmp);
														$tabtmp=explode('"',$ligne[$cpt]);
														$divisions[$i]["services"][$j]["enseignants"][$k]["id"]=$tabtmp[1];
													}
													*/
													if(strstr($ligne[$cpt],"<ENSEIGNANT ")){
														//$temoin_ens=1;
														unset($tabtmp);
														$tabtmp=explode('"',strstr($ligne[$cpt]," ID="));
														$divisions[$i]["services"][$j]["enseignants"][$k]["id"]=$tabtmp[1];
													}
													if(strstr($ligne[$cpt],"</ENSEIGNANT>")){
														//$temoin_ens=0;
														$k++;
													}
												}
											}
										}
									}
								}






								if(strstr($ligne[$cpt],"<GROUPES>")){
									echo "D&#233;but de la section GROUPES &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
									$temoin_groupes++;
									$groupes=array();
									$i=0;
								}
								if(strstr($ligne[$cpt],"</GROUPES>")){
									echo "Fin de la section GROUPES &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
									$temoin_groupes++;
								}
								if($temoin_groupes==1){
									/*
									if(strstr($ligne[$cpt],"<GROUPE CODE=")){
										$temoin_grp=1;
										unset($tabtmp);
										$tabtmp=explode('"',$ligne[$cpt]);
										$groupes[$i]=array();
										$groupes[$i]["code"]=$tabtmp[1];
										$j=0;
										$m=0;
									}
									*/
									if(strstr($ligne[$cpt],"<GROUPE ")){
										$temoin_grp=1;
										unset($tabtmp);
										$tabtmp=explode('"',strstr($ligne[$cpt]," CODE="));
										$groupes[$i]=array();
										$groupes[$i]["code"]=$tabtmp[1];
										$j=0;
										$m=0;
									}
									if(strstr($ligne[$cpt],"</GROUPE>")){
										$temoin_grp=0;
										$i++;
									}

									if($temoin_grp==1){
										if(strstr($ligne[$cpt],"<LIBELLE_LONG>")){
											unset($tabtmp);
											$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
											$groupes[$i]["libelle_long"]=$tabtmp[2];
										}

										if(strstr($ligne[$cpt],"<DIVISIONS_APPARTENANCE>")){
											$temoin_div_appart=1;
										}
										if(strstr($ligne[$cpt],"</DIVISIONS_APPARTENANCE>")){
											$temoin_div_appart=0;
										}

										if($temoin_div_appart==1){
											/*
											if(strstr($ligne[$cpt],"<DIVISION_APPARTENANCE CODE=")){
												unset($tabtmp);
												$tabtmp=explode('"',$ligne[$cpt]);
												$groupes[$i]["divisions"][$j]["code"]=$tabtmp[1];
												$j++;
											}
											*/
											if(strstr($ligne[$cpt],"<DIVISION_APPARTENANCE ")){
												unset($tabtmp);
												$tabtmp=explode('"',strstr($ligne[$cpt]," CODE="));
												$groupes[$i]["divisions"][$j]["code"]=$tabtmp[1];
												$j++;
											}
										}


										//<SERVICE CODE_MATIERE="020100" CODE_MOD_COURS="CG">
										/*
										if(strstr($ligne[$cpt],"<SERVICE CODE_MATIERE=")){
											unset($tabtmp);
											$tabtmp=explode('"',$ligne[$cpt]);
											$groupes[$i]["code_matiere"]=$tabtmp[1];
										}
										*/
										if(strstr($ligne[$cpt],"<SERVICE ")){
											unset($tabtmp);
											$tabtmp=explode('"',strstr($ligne[$cpt]," CODE_MATIERE="));
											$groupes[$i]["code_matiere"]=$tabtmp[1];
										}


										//<ENSEIGNANT TYPE="epp" ID="31762">
										// Ameliorer la recup de l'attribut ID...
										// ...decouper en un tableau avec ' '
										// et rechercher quel champ du tableau commence par ID=

									       //<ENSEIGNANT ID="11508" TYPE="epp">

										//if(strstr($ligne[$cpt],"<ENSEIGNANT TYPE=")){
										/*
										if(strstr($ligne[$cpt],"<ENSEIGNANT ID=")){
											unset($tabtmp);
											$tabtmp=explode('"',$ligne[$cpt]);
											//$groupes[$i]["enseignant"][$m]["id"]=$tabtmp[3];
											$groupes[$i]["enseignant"][$m]["id"]=$tabtmp[1];
											$m++;
										}
										*/
										if(strstr($ligne[$cpt],"<ENSEIGNANT ")){
											unset($tabtmp);
											$tabtmp=explode('"',strstr($ligne[$cpt]," ID="));
											//$groupes[$i]["enseignant"][$m]["id"]=$tabtmp[3];
											$groupes[$i]["enseignant"][$m]["id"]=$tabtmp[1];
											$m++;
										}

									}
								}

							}

							$cpt++;
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";












/*
						echo "<h2>Programmes</h2>\n";
						echo "<blockquote>\n";
						echo "<h3>Analyse du fichier pour extraire les programmes...</h3>\n";
						echo "<blockquote>\n";
						echo "<p>Il s'agit ici de remplir un tableau des liens entre les MEFS et les MATIERES.</p>\n";
						$cpt=0;
						$temoin_programmes=0;
						$programme=array();
						$i=0;
						$temoin_mat=0;
						while($cpt<count($ligne)){
							//echo htmlentities($ligne[$cpt])."<br />\n";
							if(strstr($ligne[$cpt],"<PROGRAMMES>")){
								echo "D&#233;but de la section PROGRAMMES &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_programmes++;
							}
							if(strstr($ligne[$cpt],"</PROGRAMMES>")){
								echo "Fin de la section PROGRAMMES &#224; la ligne <span style='color: blue;'>$cpt</span><br />\n";
								$temoin_programmes++;
							}
							if($temoin_programmes==1){
								// On analyse maintenant matiere par matiere:
								if(strstr($ligne[$cpt],"<PROGRAMME>")){
									$programme[$i]=array();
									$temoin_prog=1;
								}
								if(strstr($ligne[$cpt],"</PROGRAMME>")){
									$temoin_prog=0;
									$i++;
								}
								if($temoin_prog==1){
									if(strstr($ligne[$cpt],"<CODE_MEF>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										$programme[$i]["code_mef"]=$tabtmp[2];
									}
									if(strstr($ligne[$cpt],"<CODE_MATIERE>")){
										unset($tabtmp);
										$tabtmp=explode(">",preg_replace("/</",">",$ligne[$cpt]));
										$programme[$i]["code_matiere"]=$tabtmp[2];
									}
								}
							}

							$cpt++;
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";
*/





						echo "<h3>Affichage des donn&#233;es PROFS,... extraites:</h3>\n";
						echo "<blockquote>\n";
						echo "<table border='1'>\n";
						echo "<tr>\n";
						echo "<th style='color: blue;'>&nbsp;</th>\n";
						echo "<th>Id</th>\n";
						echo "<th>Type</th>\n";
						echo "<th>Sexe</th>\n";
						echo "<th>Civilite</th>\n";
						echo "<th>Nom_usage</th>\n";
						echo "<th>Nom_patronymique</th>\n";
						echo "<th>Prenom</th>\n";
						echo "<th>Date_naissance</th>\n";
						echo "<th>Grade</th>\n";
						echo "<th>Fonction</th>\n";
						echo "<th>Disciplines</th>\n";
						echo "</tr>\n";
						$cpt=0;
						while($cpt<count($prof)){
							echo "<tr>\n";
							echo "<td style='color: blue;'>$cpt</td>\n";
							echo "<td>".$prof[$cpt]["id"]."</td>\n";
							echo "<td>".$prof[$cpt]["type"]."</td>\n";
							echo "<td>".$prof[$cpt]["sexe"]."</td>\n";
							echo "<td>".$prof[$cpt]["civilite"]."</td>\n";
							echo "<td>".$prof[$cpt]["nom_usage"]."</td>\n";
							echo "<td>".$prof[$cpt]["nom_patronymique"]."</td>\n";
							echo "<td>".$prof[$cpt]["prenom"]."</td>\n";
							echo "<td>".$prof[$cpt]["date_naissance"]."</td>\n";
							echo "<td>".$prof[$cpt]["grade"]."</td>\n";
							echo "<td>".$prof[$cpt]["fonction"]."</td>\n";

							echo "<td align='center'>\n";

							if($prof[$cpt]["fonction"]=="ENS"){
								echo "<table border='1'>\n";
								echo "<tr>\n";
								echo "<th>Code</th>\n";
								echo "<th>Libelle_court</th>\n";
								echo "<th>Nb_heures</th>\n";
								echo "</tr>\n";
								for($j=0;$j<count($prof[$cpt]["disciplines"]);$j++){
									echo "<tr>\n";
									echo "<td>".$prof[$cpt]["disciplines"][$j]["code"]."</td>\n";
									echo "<td>".$prof[$cpt]["disciplines"][$j]["libelle_court"]."</td>\n";
									echo "<td>".$prof[$cpt]["disciplines"][$j]["nb_heures"]."</td>\n";
									echo "</tr>\n";
								}
								echo "</table>\n";
							}

							echo "</td>\n";
							echo "</tr>\n";
							$cpt++;
						}
						echo "</table>\n";
						echo "</blockquote>\n";

						echo "<p style='color:red;'><b>A faire</b>: un fichier profs pour GEPI...</p>\n";






						$temoin_au_moins_une_matiere="";
						$temoin_au_moins_un_prof="";
						// Affichage des infos Enseignements et divisions:
						echo "<a name='divisions'></a><h3>Affichage des divisions</h3>\n";
						echo "<blockquote>\n";
						for($i=0;$i<count($divisions);$i++){
							//echo "<p>\$divisions[$i][\"code\"]=".$divisions[$i]["code"]."<br />\n";
							echo "<h4>Classe de ".$divisions[$i]["code"]."</h4>\n";
							echo "<ul>\n";
							for($j=0;$j<count($divisions[$i]["services"]);$j++){
								//echo "\$divisions[$i][\"services\"][$j][\"code_matiere\"]=".$divisions[$i]["services"][$j]["code_matiere"]."<br />\n";
								echo "<li>\n";
								for($m=0;$m<count($matiere);$m++){
									if($matiere[$m]["code"]==$divisions[$i]["services"][$j]["code_matiere"]){
										//echo "\$matiere[$m][\"code_gestion\"]=".$matiere[$m]["code_gestion"]."<br />\n";
										echo "Mati&#232;re: ".$matiere[$m]["code_gestion"]."<br />\n";
										$temoin_au_moins_une_matiere="oui";
									}
								}
								echo "<ul>\n";
								for($k=0;$k<count($divisions[$i]["services"][$j]["enseignants"]);$k++){
								//$divisions[$i]["services"][$j]["enseignants"][$k]["id"]
									for($m=0;$m<count($prof);$m++){
										if($prof[$m]["id"]==$divisions[$i]["services"][$j]["enseignants"][$k]["id"]){
											//echo $prof[$m]["nom_usage"]." ".$prof[$m]["prenom"]."|";
											echo "<li>\n";
											echo "Enseignant: ".$prof[$m]["nom_usage"]." ".$prof[$m]["prenom"];
											echo "</li>\n";
											$temoin_au_moins_un_prof="oui";
										}
									}
								}
								echo "</ul>\n";
								//echo "<br />\n";
								echo "</li>\n";
							}
							echo "</ul>\n";
							//echo "</p>\n";
						}
						echo "</blockquote>\n";
						echo "</blockquote>\n";











						echo "<h2>Suppression des CSV de SE3 existants</h2>\n";
						echo "<blockquote>\n";
						echo "<p>Si des fichiers CSV ont d&#233;j&#224; &#233;t&#233; g&#233;n&#233;r&#233;s, on va commencer par les supprimer avant d'en g&#233;n&#233;rer de nouveaux...</p>\n";
						//$tabfich=array("f_wind.csv","f_men.csv","f_gpd.csv","f_div.csv","f_tmt.csv","profs.html");
						$tabfich=array("f_wind.csv","f_men.csv","f_gpd.csv","f_div.csv","f_tmt.csv","profs.html","f_wind.txt","f_men.txt","f_div.txt");
						for($i=0;$i<count($tabfich);$i++){
							if(file_exists("$dossiercsv/se3/$tabfich[$i]")){
								echo "<p>Suppression de se3/$tabfich[$i]... ";
								if(unlink("$dossiercsv/se3/$tabfich[$i]")){
									echo "r&#233;ussie.</p>\n";
								}
								else{
									echo "<font color='red'>Echec!</font> V&#233;rifiez les droits d'&#233;criture sur le serveur.</p>\n";
								}
							}
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";



						echo "<a name='se3'></a><h2>G&#233;n&#233;ration du CSV (F_WIND.txt) des profs pour SE3</h2>\n";
						echo "<blockquote>\n";
						$cpt=0;
						if($temoin_creation_fichiers!="non"){$fich=fopen("$dossiercsv/se3/f_wind.txt","w+");}
						while($cpt<count($prof)){
							if($prof[$cpt]["fonction"]=="ENS"){
								$date=str_replace("-","",$prof[$cpt]["date_naissance"]);
								$chaine="P".$prof[$cpt]["id"]."|".$prof[$cpt]["nom_usage"]."|".$prof[$cpt]["prenom"]."|".$date."|".$prof[$cpt]["sexe"];
								if($fich){
									//fwrite($fich,$chaine."\n");
									fwrite($fich,html_entity_decode($chaine)."\n");
								}
								echo $chaine."<br />\n";
								//echo "P".$prof[$cpt]["id"]."|".$prof[$cpt]["nom_usage"]."|".$prof[$cpt]["prenom"]."|".$date."|".$prof[$cpt]["sexe"]."<br />\n";
							}
							$cpt++;
						}
						if($temoin_creation_fichiers!="non"){fclose($fich);}
						echo "<p>Vous pouvez copier/coller ces lignes dans un fichier texte pour effectuer l'import des comptes profs dans SambaEdu3.</p>\n";
						echo "</blockquote>\n";




						echo "<a name='f_div'></a><h2>G&#233;n&#233;ration d'un CSV du F_DIV pour SambaEdu3</h2>\n";
						echo "<blockquote>\n";
						if($temoin_creation_fichiers!="non"){$fich=fopen("$dossiercsv/se3/f_div.txt","w+");}
						for($i=0;$i<count($divisions);$i++){
							$numind_pp="";
							for($m=0;$m<count($prof);$m++){
								for($n=0;$n<count($prof[$m]["prof_princ"]);$n++){
									if($prof[$m]["prof_princ"][$n]["code_structure"]==$divisions[$i]["code"]){
										$numind_pp="P".$prof[$m]["id"];
									}
								}
							}
							$chaine=$divisions[$i]["code"]."|".$divisions[$i]["code"]."|".$numind_pp;
							if($fich){
								//fwrite($fich,$chaine."\n");
								fwrite($fich,html_entity_decode($chaine)."\n");
							}
							echo $chaine."<br />\n";
							//echo $divisions[$i]["code"]."|".$divisions[$i]["code"]."|".$numind_pp."<br />\n";
						}
						if($temoin_creation_fichiers!="non"){fclose($fich);}
						if($temoin_au_moins_un_prof_princ!="oui"){
							echo "<p>Il semble que votre fichier ne comporte pas l'information suivante:<br />Qui sont les profs principaux?<br />Cela n'emp&#234;che cependant pas l'import du CSV dans SambaEdu3.</p>\n";
						}
						echo "</blockquote>\n";




						echo "<a name='f_men'></a><h2>G&#233;n&#233;ration d'un CSV du F_MEN pour SambaEdu3</h2>\n";
						echo "<blockquote>\n";
						if(($temoin_au_moins_une_matiere=="")||($temoin_au_moins_un_prof=="")){
							echo "<p>Votre fichier ne comporte pas suffisamment d'informations pour g&#233;n&#233;rer ce CSV.<br />Il faut que les emplois du temps soient remont&#233;s vers STS pour que le fichier XML permette de g&#233;n&#233;rer ce CSV.</p>\n";
						}
						else{
							if($temoin_creation_fichiers!="non"){$fich=fopen("$dossiercsv/se3/f_men.txt","w+");}
							for($i=0;$i<count($divisions);$i++){
								//$divisions[$i]["services"][$j]["code_matiere"]
								$classe=$divisions[$i]["code"];
								for($j=0;$j<count($divisions[$i]["services"]);$j++){
									$mat="";
									for($m=0;$m<count($matiere);$m++){
										if($matiere[$m]["code"]==$divisions[$i]["services"][$j]["code_matiere"]){
											$mat=$matiere[$m]["code_gestion"];
										}
									}
									if($mat!=""){
										for($k=0;$k<count($divisions[$i]["services"][$j]["enseignants"]);$k++){
											$chaine=$mat."|".$classe."|P".$divisions[$i]["services"][$j]["enseignants"][$k]["id"];
											if($fich){
												//fwrite($fich,$chaine."\n");
												fwrite($fich,html_entity_decode($chaine)."\n");
											}
											echo $chaine."<br />\n";
											//echo $mat."|".$classe."|P".$divisions[$i]["services"][$j]["enseignants"][$k]["id"]."<br />\n";
										}
									}
								}
							}
							if($temoin_creation_fichiers!="non"){fclose($fich);}
						}
						echo "</blockquote>\n";

	//==================================================================

						echo "<h2>Suppression des CSV de GEPI existants</h2>\n";
						echo "<blockquote>\n";
						echo "<p>Si des fichiers CSV ont d&#233;j&#224; &#233;t&#233; g&#233;n&#233;r&#233;s, on va commencer par les supprimer avant d'en g&#233;n&#233;rer de nouveaux...</p>\n";
						$tabfich=array("f_wind.csv","f_men.csv","f_gpd.csv","f_div.csv","f_tmt.csv","profs.html");
						for($i=0;$i<count($tabfich);$i++){
							if(file_exists("$dossiercsv/gepi/$tabfich[$i]")){
								echo "<p>Suppression de gepi/$tabfich[$i]... ";
								if(unlink("$dossiercsv/gepi/$tabfich[$i]")){
									echo "r&#233;ussie.</p>\n";
								}
								else{
									echo "<font color='red'>Echec!</font> V&#233;rifiez les droits d'&#233;criture sur le serveur.</p>\n";
								}
							}
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";



						echo "<a name='gepi'></a>\n";

						echo "<a name='f_wind_gepi'></a><h2>G&#233;n&#233;ration du CSV (F_WIND.CSV) des profs pour GEPI</h2>\n";
						echo "<blockquote>\n";
						$cpt=0;
						if($temoin_creation_fichiers!="non"){$fich=fopen("$dossiercsv/gepi/f_wind.csv","w+");}
						$chaine="AINOMU;AIPREN;AICIVI;NUMIND;FONCCO;INDNNI";
						if($fich){
							//fwrite($fich,$chaine."\n");
							fwrite($fich,html_entity_decode($chaine)."\n");
						}
						echo $chaine."<br />\n";

						if($temoin_creation_fichiers!="non"){
						if($_POST['mdp']=="alea"){
							$fich2=fopen("$dossiercsv/gepi/profs.html","w+");
							fwrite($fich2,"<?php
@set_time_limit(0);

// Initialisations files
require_once('../lib/initialisations.inc.php');

// Resume session
\$resultat_session = resumeSession();
if (\$resultat_session == 'c') {
header('Location: ../utilisateurs/mon_compte.php?change_mdp=yes');
die();
} else if (\$resultat_session == '0') {
header('Location: ../logout.php?auto=1');
die();
};

if (!checkAccess()) {
header('Location: ../logout.php?auto=1');
die();
}
?>
<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
<html>
<head>
	<title>Fichier profs</title>
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
	<meta name='author' content='Stephane Boireau, A.S. RUE de Bernay/Pont-Audemer' />
	<link type='text/css' rel='stylesheet' href='../../style.css' />
</head>
<body>
<h1 align='center'>Fichier des mots de passe initiaux des professeurs</h1>
<table border='1'>
<tr>
<th>Nom</th>
<th>Pr&#233;nom</th>
<th>Civilit&#233;</th>
<th>Mot de passe</th>
</tr>\n");
						}
						}

						while($cpt<count($prof)){
							if($prof[$cpt]["fonction"]=="ENS"){

								if($prof[$cpt]["sexe"]=="1"){
									$civi="M.";
								}
								else{
									$civi="MM";
								}

								switch($prof[$cpt]["civilite"]){
									case 1:
										$civi="M.";
										break;
									case 2:
										$civi="MM";
										break;
									case 3:
										$civi="ML";
										break;
								}

								if($_POST['mdp']=="alea"){
									$mdp=createRandomPassword();
								}
								else{
									$date=str_replace("-","",$prof[$cpt]["date_naissance"]);
									$mdp=$date;
								}
								//echo $prof[$cpt]["nom_usage"].";".$prof[$cpt]["prenom"].";".$civi.";"."P".$prof[$cpt]["id"].";"."ENS".";".$date."<br />\n";
								$chaine=$prof[$cpt]["nom_usage"].";".$prof[$cpt]["prenom"].";".$civi.";"."P".$prof[$cpt]["id"].";"."ENS".";".$mdp;
								if($fich){
									fwrite($fich,html_entity_decode($chaine)."\n");
								}
								if($_POST['mdp']=="alea"){
									fwrite($fich2,"<tr>
<td>".$prof[$cpt]["nom_usage"]."</td>
<td>".$prof[$cpt]["prenom"]."</td>
<td>$civi</td>
<td>$mdp</td>
</tr>\n");
								}
								echo $chaine."<br />\n";
							}
							$cpt++;
						}
						if($temoin_creation_fichiers!="non"){fclose($fich);}
						if($temoin_creation_fichiers!="non"){
						if($_POST['mdp']=="alea"){
							fwrite($fich2,"</table>
<p>Imprimez cette page, puis supprimez-la en proc&#233;dant au nettoyage comme indiqu&#233; &#224; la page pr&#233;c&#233;dente.</p>
</body>
</html>\n");
							fclose($fich2);
						}
						}
						echo "</blockquote>\n";



						echo "<a name='f_men_gepi'></a><h2>G&#233;n&#233;ration d'un CSV du F_MEN pour GEPI</h2>\n";
						echo "<blockquote>\n";
						if(($temoin_au_moins_une_matiere=="")||($temoin_au_moins_un_prof=="")){
							echo "<p>Votre fichier ne comporte pas suffisamment d'informations pour g&#233;n&#233;rer ce CSV.<br />Il faut que les emplois du temps soient remont&#233;s vers STS pour que le fichier XML permette de g&#233;n&#233;rer ce CSV.</p>\n";
						}
						else{
							if($temoin_creation_fichiers!="non"){$fich=fopen("$dossiercsv/gepi/f_men.csv","w+");}
							$chaine="MATIMN;NUMIND;ELSTCO";
							if($fich){
								fwrite($fich,html_entity_decode($chaine)."\n");
							}
							echo $chaine."<br />\n";
							for($i=0;$i<count($divisions);$i++){
								//$divisions[$i]["services"][$j]["code_matiere"]
								$classe=$divisions[$i]["code"];
								for($j=0;$j<count($divisions[$i]["services"]);$j++){
									$mat="";
									for($m=0;$m<count($matiere);$m++){
										if($matiere[$m]["code"]==$divisions[$i]["services"][$j]["code_matiere"]){
											$mat=$matiere[$m]["code_gestion"];
										}
									}
									if($mat!=""){
										for($k=0;$k<count($divisions[$i]["services"][$j]["enseignants"]);$k++){
											//echo $mat."|".$classe."|P".$divisions[$i]["services"][$j]["enseignants"][$k]["id"]."<br />\n";
											//echo $mat.";P".$divisions[$i]["services"][$j]["enseignants"][$k]["id"].";".$classe."<br />\n";
											$chaine=$mat.";P".$divisions[$i]["services"][$j]["enseignants"][$k]["id"].";".$classe;
											if($fich){
												fwrite($fich,html_entity_decode($chaine)."\n");
											}
											echo $chaine."<br />\n";
										}
									}
								}
							}

							//echo "<hr width='200' />\n";
							for($i=0;$i<count($groupes);$i++){
								$grocod=$groupes[$i]["code"];
								//echo "<p>Groupe $i: \$grocod=$grocod<br />\n";
								for($m=0;$m<count($matiere);$m++){
									//echo "\$matiere[$m][\"code\"]=".$matiere[$m]["code"]." et \$groupes[$i][\"code_matiere\"]=".$groupes[$i]["code_matiere"]."<br />\n";
									if($matiere[$m]["code"]==$groupes[$i]["code_matiere"]){
										//$matimn=$programme[$k]["code_matiere"];
										$matimn=$matiere[$m]["code_gestion"];
										//echo "<b>Trouv&#233;: mati&#232;re n�$m: \$matimn=$matimn</b><br />\n";
									}
								}
								//$groupes[$i]["enseignant"][$m]["id"]
								//$groupes[$i]["divisions"][$j]["code"]
								if($matimn!=""){
									for($j=0;$j<count($groupes[$i]["divisions"]);$j++){
										$elstco=$groupes[$i]["divisions"][$j]["code"];
										//echo "\$elstco=$elstco<br />\n";
										if(count($groupes[$i]["enseignant"])==0){
											$chaine="$matimn;;$elstco";
											if($fich){
												fwrite($fich,html_entity_decode($chaine)."\n");
											}
											echo $chaine."<br />\n";
										}
										else{
											for($m=0;$m<count($groupes[$i]["enseignant"]);$m++){
												$numind=$groupes[$i]["enseignant"][$m]["id"];
												//echo "$matimn;P$numind;$elstco<br />\n";
												$chaine="$matimn;P$numind;$elstco";
												if($fich){
													fwrite($fich,html_entity_decode($chaine)."\n");
												}
												echo $chaine."<br />\n";
											}
										}
										//echo $grocod.";".$groupes[$i]["divisions"][$j]["code"]."<br />\n";
									}
								}


/*
								$matimn="";
								//for($j=0;$j<count($groupes[$i]["mef"]);$j++){
									//$mef=$groupes[$i]["mef"][$j];
									$mef=$groupes[$i]["mef"][0];
									for($k=0;$k<count($programme);$k++){
										if($mef==$programme[$k]["code_mef"]){
											for($m=0;$m<count($matiere);$m++){
												if($matiere[$m]["code"]==$programme[$k]["code_matiere"]){
													//$matimn=$programme[$k]["code_matiere"];
													$matimn=$matiere[$m]["code_gestion"];
												}
											}
										}
									}
								//}
								if($matimn!=""){
									// Rechercher le NUMIND...
									//...
									//$groupes[$i]["enseignant"][$m]["id"]

									// Et enfin pour la liste des classes, on affiche une ligne:
									for($j=0;$j<count($groupes[$i]["divisions"]);$j++){
										$elstco=$groupes[$i]["divisions"][$j]["code"];
										for($m=0;$m<count($groupes[$i]["enseignant"]);$m++){
											$numind=$groupes[$i]["enseignant"][$m]["id"];
											echo "$matimn;P$numind;$elstco<br />\n";
										}
										//echo $grocod.";".$groupes[$i]["divisions"][$j]["code"]."<br />\n";
									}
								}
*/
							}
							if($temoin_creation_fichiers!="non"){fclose($fich);}
						}
						echo "<p>Je ne sais pas trop pour le pr&#233;fixe P.<br />Il n'est pas dans le fichier XML, mais est utilis&#233; par SE3...<br />Et par contre, sur les F_WIND.DBF g&#233;n&#233;r&#233;s par AutoSco, il y a un pr&#233;fixe E.</p>";
						echo "</blockquote>\n";













						echo "<a name='f_gpd_gepi'></a><h2>G&#233;n&#233;ration d'un CSV du F_GPD pour GEPI</h2>\n";
						echo "<blockquote>\n";
						echo "\$temoin_creation_fichiers=$temoin_creation_fichiers<br />";
	/*
						if(($temoin_au_moins_une_matiere=="")||($temoin_au_moins_un_prof=="")){
							echo "<p>Votre fichier ne comporte pas suffisamment d'informations pour g&#233;n&#233;rer ce CSV.<br />Il faut que les emplois du temps soient remont&#233;s vers STS pour que le fichier XML permette de g&#233;n&#233;rer ce CSV.</p>\n";
						}
						else{
	*/
							//echo "GROCOD;DIVCOD<br />\n";
							if($temoin_creation_fichiers!="non"){$fich=fopen("$dossiercsv/gepi/f_gpd.csv","w+");}
							$chaine="GROCOD;DIVCOD";
							if($fich){
								fwrite($fich,html_entity_decode($chaine)."\n");
							}
							echo $chaine."<br />\n";

							for($i=0;$i<count($groupes);$i++){
								//$divisions[$i]["services"][$j]["code_matiere"]
								$grocod=$groupes[$i]["code"];
								for($j=0;$j<count($groupes[$i]["divisions"]);$j++){
									//echo $grocod.";".$groupes[$i]["divisions"][$j]["code"]."<br />\n";
									$chaine=$grocod.";".$groupes[$i]["divisions"][$j]["code"];
									if($fich){
										fwrite($fich,html_entity_decode($chaine)."\n");
									}
									echo $chaine."<br />\n";
								}
							}
	//					}
						if($temoin_creation_fichiers!="non"){fclose($fich);}
						echo "</blockquote>\n";



						echo "<a name='f_tmt_gepi'></a><h2>G&#233;n&#233;ration d'un CSV du F_TMT pour GEPI</h2>\n";
						echo "<blockquote>\n";
						//echo "MATIMN;MATILC<br />\n";
						if($temoin_creation_fichiers!="non"){$fich=fopen("$dossiercsv/gepi/f_tmt.csv","w+");}
						$chaine="MATIMN;MATILC";
						if($fich){
							fwrite($fich,html_entity_decode($chaine)."\n");
						}
						echo $chaine."<br />\n";
						for($i=0;$i<count($matiere);$i++){
							//echo $matiere[$i]["code_gestion"].";".$matiere[$i]["libelle_court"]."<br />\n";
							$chaine=$matiere[$i]["code_gestion"].";".$matiere[$i]["libelle_court"];
							if($fich){
								fwrite($fich,html_entity_decode($chaine)."\n");
							}
							echo $chaine."<br />\n";
						}
						if($temoin_creation_fichiers!="non"){fclose($fich);}
						echo "</blockquote>\n";



						echo "<a name='f_div_gepi'></a><h2>G&#233;n&#233;ration d'un CSV du F_DIV pour GEPI</h2>\n";
						echo "<blockquote>\n";
						if($temoin_creation_fichiers!="non"){$fich=fopen("$dossiercsv/gepi/f_div.csv","w+");}
						$chaine="DIVCOD;NUMIND";
						if($fich){
							fwrite($fich,html_entity_decode($chaine)."\n");
						}
						echo $chaine."<br />\n";
						for($i=0;$i<count($divisions);$i++){
							$numind_pp="";
							for($m=0;$m<count($prof);$m++){
								for($n=0;$n<count($prof[$m]["prof_princ"]);$n++){
									if($prof[$m]["prof_princ"][$n]["code_structure"]==$divisions[$i]["code"]){
										$numind_pp="P".$prof[$m]["id"];
									}
								}
							}
							//echo $divisions[$i]["code"].";".$divisions[$i]["code"].";".$numind_pp."<br />\n";
							$chaine=$divisions[$i]["code"].";".$numind_pp;
							if($fich){
								fwrite($fich,html_entity_decode($chaine)."\n");
							}
							echo $chaine."<br />\n";
						}
						if($temoin_creation_fichiers!="non"){fclose($fich);}
						echo "<p>Ce CSV est destin&#233; &#224; renseigner les Professeurs Principaux...</p>\n";
						echo "</blockquote>\n";

						if($temoin_creation_fichiers!="non"){
							//echo "<div style='position:absolute; top: 50px; left: 50px; width: 300px; height: 200px; background: yellow; border: 1px solid black;'>\n";
							echo "<div style='position:absolute; top: 70px; left: 300px; width: 300px; background: yellow; border: 1px solid black; padding-left: 5px; padding-right: 5px; padding-top: 0; '>\n";
							echo "<h4 style='margin:0; padding:0; text-align:center;'>GEPI</h4>\n";
							//echo "<p style='margin-top: 0;'>Effectuez un Clic-droit/Enregistrer la cible du lien sous... pour chacun des fichiers ci-dessous.</p>\n";
							echo "<p style='margin-top: 0;'>R&#233;cup&#233;rez les CSV suivants pour GEPI:</p>\n";
							echo "<table border='0'>\n";
							echo "<tr><td>Fichier Profs:</td><td><a href='$dossiercsv/gepi/f_wind.csv'>f_wind.csv</a></td></tr>\n";
							echo "<tr><td>Fichier Classes/mati&#232;res/profs:</td><td>\n";
							if(file_exists("$dossiercsv/gepi/f_men.csv")){
								echo "<a href='$dossiercsv/gepi/f_men.csv'>f_men.csv</a>";
							}
							else{
								echo "Fichier non g&#233;n&#233;r&#233;.<br />L'emploi du temps n'est sans doute pas encore remont&#233;.";
							}
							echo "</td></tr>\n";
							echo "<tr><td>Fichier Groupes/classes:</td><td>\n";
							if(file_exists("$dossiercsv/gepi/f_gpd.csv")){
								echo "<a href='$dossiercsv/gepi/f_gpd.csv'>f_gpd.csv</a>";
							}
							else{
								echo "Fichier non g&#233;n&#233;r&#233;.<br />L'emploi du temps n'est sans doute pas encore remont&#233;.";
							}
							echo "</td></tr>\n";
							echo "<tr><td>Fichier Mati&#232;res:</td><td><a href='$dossiercsv/gepi/f_tmt.csv'>f_tmt.csv</a></td></tr>\n";
							echo "<tr><td>Fichier Profs principaux:</td><td><a href='$dossiercsv/gepi/f_div.csv'>f_div.csv</a></td></tr>\n";
							echo "</table>\n";
							if($_POST['mdp']=="alea"){
								echo "<p>Voici &#233;galement une <a href='$dossiercsv/profs.html' target='_blank'>page des mots de passe initiaux des professeurs</a> &#224; imprimer avant de proc&#233;der au nettoyage ci-dessous.</p>\n";
							}
							echo "<hr width='200' align='center' />\n";
							echo "<p style='margin-top: 0;'>R&#233;cup&#233;rez les CSV suivants pour SE3:</p>\n";
							echo "<table border='0'>\n";
							echo "<tr><td>Fichier Profs:</td><td><a href='$dossiercsv/se3/f_wind.txt'>f_wind.txt</a></td></tr>\n";
							echo "<tr><td>Fichier Classes/mati&#232;res/profs:</td><td>";
							if(file_exists("$dossiercsv/se3/f_men.txt")){
								echo "<a href='$dossiercsv/se3/f_men.txt'>f_men.txt</a>";
							}
							else{
								echo "Fichier non g&#233;n&#233;r&#233;.<br />L'emploi du temps n'est sans doute pas encore remont&#233;.";
							}
							echo "</td></tr>\n";
							echo "<tr><td>Fichier Profs principaux:</td><td><a href='$dossiercsv/se3/f_div.txt'>f_div.txt</a></td></tr>\n";
							echo "</table>\n";

	/*
							echo "<ul>\n";
							echo "<li>Fichier Profs: <a href='$dossiercsv/f_wind.csv'>f_wind.csv</a></li>\n";
							echo "<li>Fichier Classes/mati&#232;res/profs: <a href='$dossiercsv/f_men.csv'>f_men.csv</a></li>\n";
							echo "<li>Fichier Groupes/classes: <a href='$dossiercsv/f_gpd.csv'>f_gpd.csv</a></li>\n";
							echo "<li>Fichier Mati&#232;res: <a href='$dossiercsv/f_tmt.csv'>f_tmt.csv</a></li>\n";
							echo "<li>Fichier Profs principaux: <a href='$dossiercsv/f_div.csv'>f_div.csv</a></li>\n";

							echo "<li>Fichier Profs: <a href='save_csv.php?fileid=0'>f_wind.csv</a></li>\n";
							echo "<li>Fichier Classes/mati&#232;res/profs: <a href='save_csv.php?fileid=1'>f_men.csv</a></li>\n";
							echo "<li>Fichier Groupes/classes: <a href='save_csv.php?fileid=2'>f_gpd.csv</a></li>\n";
							echo "<li>Fichier Mati&#232;res: <a href='save_csv.php?fileid=3'>f_tmt.csv</a></li>\n";
							echo "<li>Fichier Profs principaux: <a href='save_csv.php?fileid=4'>f_div.csv</a></li>\n";
							echo "</ul>\n";
							if($_POST['mdp']=="alea"){
								echo "<p>Voici &#233;galement une <a href='$dossiercsv/profs.html' target='_blank'>page des mots de passe initiaux des professeurs</a> &#224; imprimer avant de proc&#233;der au nettoyage ci-dessous.</p>\n";
							}
	*/
							echo "<p>Pour supprimer les fichiers apr&#232;s r&#233;cup&#233;ration: <a href='".$_SERVER['PHP_SELF']."?nettoyage=oui&amp;date=$doss_date'>Nettoyage</a></p>\n";
							echo "</div>\n";
						}
					}
					else{
						echo "<p>ERREUR!<br /><a href='".$_SERVER['PHP_SELF']."'>Retour</a>.</p>\n";
					}
				}
			}
		?>
		<!--p>Retour &#224; l'<a href="index.php">index</a></p-->
	</div>
</body>
</html>
