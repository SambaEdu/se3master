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
   * file: lecture_csv_sconet.php
   */



?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Lecture du CSV de Sconet</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="author" content="Stephane Boireau, A.S. RUE de Bernay/Pont-Audemer" />
	<link type="text/css" rel="stylesheet" href="styles.css" />
</head>
<body>
	<div class="content">
		<h1 align="center">Lecture du CSV de Sconet</h1>

		<?php

                        // HTMLPurifier
                        require_once ("traitement_data.inc.php");
                
			if(isset($_GET['nettoyage'])){
				echo "<h1 align='center'>Suppression des CSV</h1>\n";

				$doss_date=$_GET['date'];
				//echo "strlen(preg_replace('/\"[0-9_]\"/',\"\",$doss_date))=".strlen(preg_replace("/[0-9_]/","",$doss_date))."<br />";
				//echo "strlen(preg_replace('/\"[0-9_]\"/',\"\",$doss_date))=strlen(".preg_replace("/[0-9_]/","",$doss_date).")<br />";
				if(strlen(preg_replace("/[0-9_.]/","",$doss_date))!=0){
					echo "<p style='color:red;'>Erreur! Le param&#232;tre date fourni n'est pas correct.</p>\n";
					echo "<p>Retour &#224; l'<a href='".$_SERVER['PHP_SELF']."'>index</a></p>\n";
					echo "</div></body></html>\n";
				}
				else{
					$dossiercsv="csv/".$doss_date;
				}

				echo "<p>Si des fichiers CSV existent, ils seront supprim&#233;s...</p>\n";
				//$tabfich=array("f_ele.csv","f_men.csv","f_gpd.csv","f_div.csv","f_tmt.csv","profs.html","f_wind.txt","f_men.txt","f_div.txt");
				$tabfich=array("f_ele.txt","f_ele.csv","eleves.txt");
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
					if(file_exists("$dossiercsv/gibii/$tabfich[$i]")){
						echo "<p>Suppression de gibii/$tabfich[$i]... ";
						if(unlink("$dossiercsv/gibii/$tabfich[$i]")){
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
				if(file_exists("$dossiercsv/gibii")){
					rmdir("$dossiercsv/gibii");
				}
				if(file_exists("$dossiercsv")){
					rmdir("$dossiercsv");
				}
				echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a>.</p>\n";
			}
			else{
				if(!isset($_POST['is_posted'])){
					echo "<p>Cette page est destin&#233;e &#224; r&#233;aliser des fichiers CSV concernant des donn&#233;es El&#232;ves.</p>\n";

					echo "<p>Pour utiliser cette page, il faut fournir un Export <b>personnalis&#233;</b> Sconet avec au moins les champs suivants:</p>\n";
					echo "<ul>\n";
					echo "<li>Nom</li>\n";
					echo "<li>Pr&#233;nom 1</li>\n";
					echo "<li>Date de naissance</li>\n";
					echo "<li>N° Interne</li>\n";
					echo "<li>Sexe</li>\n";
					echo "<li>Division</li>\n";
					echo "</ul>\n";
					echo "<p>Et pour GEPI, il faut en plus:</p>\n";
					echo "<ul>\n";
					echo "<li>L&#233;gal</li>\n";
					echo "<li>Correspondant</li>\n";
					echo "<li>Nom resp.</li>\n";
					echo "<li>Pr&#233;nom resp.</li>\n";
					echo "<li>Ligne 1 Adresse</li>\n";
					echo "<li>Ligne 2 Adresse</li>\n";
					echo "<li>Ligne 3 Adresse</li>\n";
					echo "<li>Ligne 4 Adresse</li>\n";
					echo "<li>Commune resp.</li>\n";
					echo "<li>Code postal resp.</li>\n";
					echo "<li>INE</li>\n";
					echo "</ul>\n";

					echo "<p>Le cheminement dans Sconet est: 'Application Sconet/Acc&#232;s Base Eleves'.<br />
					Choisir l'ann&#233;e (<i>en cours ou en pr&#233;paration</i>).<br />
					'Exploitation-Extraction' et choisir 'personnalis&#233;e'.</p>\n";

					echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
					echo "<p>Veuillez fournir le fichier CSV: \n";
					echo "<p><input type=\"file\" size=\"80\" name=\"csv_file\">\n";
					echo "<input type='hidden' name='is_posted' value='yes'>\n";
					echo "</p>\n";
					echo "<p><input type='submit' value='Valider'></p>\n";
					echo "</form>\n";
				}
				else{

					
					/**
					* Remplace les accents
					* @Parametres La chaine a traiter
					* @return la chaine traitee
					*/

					
					function remplace_accents($chaine){
						$retour=strtr(mb_ereg_replace("¼","OE",mb_ereg_replace("½","oe",$chaine)),"ÀÄÂÉÈÊËÎÏÔÖÙÛÜÇçàäâéèêëîïôöùûü","AAAEEEEIIOOUUUCcaaaeeeeiioouuu");
						return $retour;
					}




					$doss_date=$_SERVER['REMOTE_ADDR'].strtr(substr(microtime(),2)," ","_");
					//$dossiercsv="csv/".$doss_date;
					$dossiercsv="csv/".$doss_date;

					$temoin_creation_fichiers="oui";
					if(!file_exists("csv")){
						//if(!mkdir("$dossiercsv","0770")){
						if(!mkdir("csv")){
/*
							echo "<p style='color:red;'>Erreur! Le dossier csv n'a pas pu être cr&#233;&#233;.</p>\n";
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
							if(!file_exists("$dossiercsv/gibii")){
								if(!mkdir("$dossiercsv/gibii")){
									echo "<p style='color:red;'>Erreur! Le dossier csv/gibii n'a pas pu &#234;tre cr&#233;&#233;.<br />Les fichiers ne seront pas g&#233;n&#233;r&#233;s, mais vous pourrez remplir vos fichiers par copier/coller depuis cette page.</p>\n";
									//echo "<p>Retour &#224; l'<a href='".$_SERVER['PHP_SELF']."'>index</a></p>\n";
									//echo "</div></body></html>\n";
									//die();
									$temoin_creation_fichiers="non";
								}
							}
						}
					}





					$csv_file = isset($_FILES["csv_file"]) ? $_FILES["csv_file"] : NULL;
					$fp=fopen($csv_file['tmp_name'],"r");
					if($fp){
						echo "<h2>Premi&#232;re phase...</h2>\n";
						echo "<blockquote>\n";
						echo "<h3>Lecture du fichier...</h3>\n";
						echo "<blockquote>\n";
						while(!feof($fp)){
							$ligne[]=fgets($fp,4096);
						}
						fclose($fp);

						// Il faudrait reperer les champs et s'assurer que les champs utiles sont bien presents.
						/*
						// Voici la liste des champs de l'export que j'ai fait:
						Nom;Prenom 1;Date de naissance;N° Interne;INE;Sexe;MEF;Statut;Division;Groupe;Option 1;Option 2;Option 3;Option 4;Option 5;Option 6;Option 7;Option 8;Option 9;Option 10;Option 11;Option 12;Regime;Doublement;Legal;Financier;Correspondant;Civilite resp.;Nom resp.;Prenom resp.;Ligne 1 Adresse;Ligne 2 Adresse;Commune resp.;Code postal resp.
						// A recuperer:
						Nom;Prenom 1;Date de naissance;N° Interne;INE;Sexe;Division;Option 1;...;Option 12;Regime;Doublement;
						MEF: 3EME
						Legal: 0, 1 ou 2
						Financier: VRAI ou FAUX
						Correspondant: VRAI ou FAUX
						Legal;Financier;Correspondant;Civilite resp.;Nom resp.;Prenom resp.;Ligne 1 Adresse;Ligne 2 Adresse;Commune resp.;Code postal resp.

						// Il faut Legal!=0 et Correspondant=VRAI
						*/
						echo "<p>Termin&#233;.</p>\n";
						echo "<p>Aller &#224;:</p>\n";
						echo "<ul>\n";
						echo "<li><a href='#analyse'>la section analyse</a></li>\n";
						echo "<li><a href='#se3'>la section SambaEdu3</a></li>\n";
						echo "<li><a href='#gibii'>la section Gibii</a></li>\n";
						echo "<li><a href='#gepi_resp'>la section GEPI</a> PROBLEME AVEC LE ereno</li>\n";
						//echo "<li><a href='#gepi2'>la section GEPI (bis)</a></li>\n";
						echo "</ul>\n";
						echo "</blockquote>\n";

						echo "<h3>Affichage...</h3>\n";
						echo "<blockquote>\n";
						echo "<p>Les lignes qui suivent sont le contenu du fichier fourni.<br />Ces lignes ne sont l&#224; qu'&#224; des fins de d&#233;buggage.<p>\n";
						echo "<table border='0'>\n";
						$cpt=0;
						while($cpt<count($ligne)){
							echo "<tr valign='top'>\n";
							echo "<td style='color: blue;'>$cpt</td><td>".htmlentities($ligne[$cpt])."</td>\n";
							echo "</tr>\n";
							$cpt++;
						}
						echo "</table>\n";
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";
						echo "</blockquote>\n";



						echo "<a name='analyse'></a>\n";
						echo "<h2>Analyse</h2>\n";
						echo "<blockquote>\n";
						echo "<h3>Rep&#233;rage des champs</h3>\n";
						echo "<blockquote>\n";

						$champ=array("Nom",
						"Pr&#233;nom 1",
						"Date de naissance",
						"N° Interne",
						"Sexe",
						"Division");
						// Analyse:
						// Reperage des champs souhaites:
						//$tabtmp=explode(";",$ligne[0]);
						$tabtmp=explode(";",trim($ligne[0]));
						for($j=0;$j<count($champ);$j++){
							$index[$j]="-1";
							for($i=0;$i<count($tabtmp);$i++){
								if($tabtmp[$i]==$champ[$j]){
									echo "Champ '<font color='blue'>$champ[$j]</font>' rep&#233;r&#233; en colonne/position <font color='blue'>$i</font><br />\n";
									$index[$j]=$i;
								}
							}
							if($index[$j]=="-1"){
								echo "<p><font color='red'>ERREUR: Le champ '<font color='blue'>$champ[$j]</font>' n'a pas &#233;t&#233; trouv&#233;.</font></p>\n";
								echo "</blockquote>";
								echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a>.</p>\n";
								echo "</blockquote></div></body></html>";
								exit();
							}
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";

						echo "<h3>Remplissage des tableaux pour SambaEdu3</h3>\n";
						echo "<blockquote>\n";
						$cpt=1;
						$tabnumero=array();
						$eleve=array();
						$temoin_format_num_interne="";
						while($cpt<count($ligne)){
							if($ligne[$cpt]!=""){
								//$tabtmp=explode(";",$ligne[$cpt]);
								$tabtmp=explode(";",trim($ligne[$cpt]));

								// Si la division/classe n'est pas vide
								if($tabtmp[$index[5]]!=""){
									if(strlen($tabtmp[$index[3]])==11){
										$numero=substr($tabtmp[$index[3]],0,strlen($tabtmp[$index[3]])-6);
									}
									else{
										$temoin_format_num_interne="non_standard";
										if(strlen($tabtmp[$index[3]])==4){
											$numero="0".$tabtmp[$index[3]];
										}
										else{
											$numero=$tabtmp[$index[3]];
										}
									}

									$temoin=0;
									for($i=0;$i<count($tabnumero);$i++){
										if($tabnumero[$i]==$numero){
											$temoin=1;
										}
									}
									if($temoin==0){
										$tabnumero[]=$numero;
										$eleve[$numero]=array();
										$eleve[$numero]["numero"]=$numero;


										//$eleve[$numero]["nom"]=preg_replace("/[^[:space:][:alpha:]]/", "", $tabtmp[$index[0]]);
										$eleve[$numero]["nom"]=preg_replace("/[^a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[0]]);

										//$eleve[$numero]["prenom"]=preg_replace("/[^[:space:][:alpha:]]/", "", $tabtmp[$index[1]]);
										//$eleve[$numero]["prenom"]=preg_replace("/[^[:space:][:alpha:][��������������]]/", "", $tabtmp[$index[1]]);
										//$eleve[$numero]["prenom"]=strtr(preg_replace("/[^a-zA-Z��������������_\s]/", "", strtr($tabtmp[$index[1]],"-","_")),"_","-");
										//$eleve[$numero]["prenom"]=strtr(preg_replace("/[^a-zA-Z��������������_\s]/", "", strtr($tabtmp[$index[1]],"-","_")),"_","-");
										$eleve[$numero]["prenom"]=preg_replace("/[^a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[1]]);

										unset($tmpdate);
										$tmpdate=explode("/",$tabtmp[$index[2]]);
										$eleve[$numero]["date"]=$tmpdate[2].$tmpdate[1].$tmpdate[0];
										$eleve[$numero]["sexe"]=$tabtmp[$index[4]];
										$eleve[$numero]["division"]=preg_replace("/[^a-zA-Z0-9_ -]/", "",remplace_accents($tabtmp[$index[5]]));
									}
								}
							}
							$cpt++;
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";





						echo "<h2>Suppression des CSV de SE3 existants</h2>\n";
						echo "<blockquote>\n";
						echo "<p>Si des fichiers CSV ont d&#233;j&#224; &#233;t&#233;  g&#233;n&#233;r&#233;s, on va commencer par les supprimer avant d'en g&#233;n&#233;rer de nouveaux...</p>\n";
						//$tabfich=array("f_wind.csv","f_men.csv","f_gpd.csv","f_div.csv","f_tmt.csv","profs.html");
						//$tabfich=array("f_ele.txt","f_div.txt");
						$tabfich=array("f_ele.txt");
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






						echo "<a name='se3'></a>\n";
						echo "<h3>Affichage d'un CSV pour SambaEdu3</h3>\n";
						echo "<blockquote>\n";
						if($temoin_format_num_interne!=""){
							echo "<p style='color:red;'>ATTENTION: Le format des num&#233;ros internes des &#233;l&#232;ves n'a pas l'air standard.<br />Veillez &#224; contr&#244;ler que vos num&#233;ros internes ont bien &#233;t&#233; analys&#233;s malgr&#233; tout.</p>\n";
						}
						echo "";
						if($temoin_creation_fichiers!="non"){$fich=fopen("$dossiercsv/se3/f_ele.txt","w+");}
						for($k=0;$k<count($tabnumero);$k++){
							$numero=$tabnumero[$k];
/*
							echo $eleve[$numero]["numero"];
							echo "<font color='red'>|</font>";
							echo $eleve[$numero]["nom"];
							echo "<font color='red'>|</font>";
							echo $eleve[$numero]["prenom"];
							echo "<font color='red'>|</font>";
							echo $eleve[$numero]["date"];
							echo "<font color='red'>|</font>";
							echo $eleve[$numero]["sexe"];
							echo "<font color='red'>|</font>";
							echo $eleve[$numero]["division"];
*/
							$chaine="";
							$chaine.=$eleve[$numero]["numero"];
							$chaine.="|";
							$chaine.=remplace_accents($eleve[$numero]["nom"]);
							$chaine.="|";
							$chaine.=remplace_accents($eleve[$numero]["prenom"]);
							$chaine.="|";
							$chaine.=$eleve[$numero]["date"];
							$chaine.="|";
							$chaine.=$eleve[$numero]["sexe"];
							$chaine.="|";
							$chaine.=$eleve[$numero]["division"];
							if($fich){
								//fwrite($fich,$chaine."\n");
								fwrite($fich,html_entity_decode($chaine)."\n");
							}
							echo $chaine."<br />\n";
						}
						if($temoin_creation_fichiers!="non"){fclose($fich);}
						echo "</blockquote>\n";






						echo "<h2>Suppression des CSV de GiBii existants</h2>\n";
						echo "<blockquote>\n";
						echo "<p>Si des fichiers CSV ont d&#233;j&#224; &#233;t&#233;  g&#233;n&#233;r&#233;s, on va commencer par les supprimer avant d'en g&#233;n&#233;rer de nouveaux...</p>\n";
						//$tabfich=array("f_wind.csv","f_men.csv","f_gpd.csv","f_div.csv","f_tmt.csv","profs.html");
						//$tabfich=array("f_ele.txt","f_div.txt");
						$tabfich=array("eleves.txt");
						for($i=0;$i<count($tabfich);$i++){
							if(file_exists("$dossiercsv/gibii/$tabfich[$i]")){
								echo "<p>Suppression de gibii/$tabfich[$i]... ";
								if(unlink("$dossiercsv/gibii/$tabfich[$i]")){
									echo "r&#233;ussie.</p>\n";
								}
								else{
									echo "<font color='red'>Echec!</font> V&#233;rifiez les droits d'&#233;criture sur le serveur.</p>\n";
								}
							}
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";


						echo "<h3>Remplissage des tableaux pour Gibii</h3>\n";
						echo "<blockquote>\n";
						$cpt=1;
						unset($tabnumero);
						$tabnumero=array();
						unset($eleve);
						$eleve=array();
						while($cpt<count($ligne)){
							if($ligne[$cpt]!=""){
								//$tabtmp=explode(";",$ligne[$cpt]);
								$tabtmp=explode(";",trim($ligne[$cpt]));

								if($tabtmp[$index[5]]!=""){
									//$numero=substr($tabtmp[$index[3]],0,strlen($tabtmp[$index[3]])-6);
									if(strlen($tabtmp[$index[3]])==11){
										$numero=substr($tabtmp[$index[3]],0,strlen($tabtmp[$index[3]])-6);
									}
									else{
										$temoin_format_num_interne="non_standard";
										if(strlen($tabtmp[$index[3]])==4){
											$numero="0".$tabtmp[$index[3]];
										}
										else{
											$numero=$tabtmp[$index[3]];
										}
									}

									$temoin=0;
									for($i=0;$i<count($tabnumero);$i++){
										if($tabnumero[$i]==$numero){
											$temoin=1;
										}
									}
									if($temoin==0){
										$tabnumero[]=$numero;
										$eleve[$numero]=array();

										$eleve[$numero]["numero"]=$numero;
										//$eleve[$numero]["nom"]=ereg_replace("[^[:space:][A-Z][a-z]]", "", $tabtmp[$index[0]]);;
										//$eleve[$numero]["nom"]=ereg_replace("[^[:space:][:alnum:]]", "", $tabtmp[$index[0]]);;
										$eleve[$numero]["nom"]=preg_replace("/[^a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[0]]);
										//$eleve[$numero]["prenom"]=strtr(preg_replace("/[^a-zA-Z��������������_\s]/", "", strtr($tabtmp[$index[1]],"-","_")),"_","-");
										$eleve[$numero]["prenom"]=preg_replace("/[^a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[1]]);

										unset($tmpdate);
										$tmpdate=explode("/",$tabtmp[$index[2]]);
										//$eleve[$numero]["date"]=$tmpdate[2].$tmpdate[1].$tmpdate[0];
										if(strlen($tmpdate[2])==4){
											$tmpdate[2]=substr($tmpdate[2],2,2);
										}
										$eleve[$numero]["date"]=$tmpdate[0]."/".$tmpdate[1]."/".$tmpdate[2];
										$eleve[$numero]["sexe"]=$tabtmp[$index[4]];
										//$eleve[$numero]["division"]=ereg_replace("[^[:space:][A-Z][a-z][0-9]]", "",$tabtmp[$index[5]]);
										$eleve[$numero]["division"]=preg_replace("/[^a-zA-Z0-9_ -]/", "",remplace_accents($tabtmp[$index[5]]));
									}
								}
							}
							$cpt++;
						}
						echo "<p>Termin&#233;.</p>\n";

						echo "</blockquote>\n";

						echo "<a name='gibii'></a>\n";
						echo "<h3>Affichage d'un CSV pour Gibii</h3>\n";
						echo "<blockquote>\n";
						if($temoin_format_num_interne!=""){
							echo "<p style='color:red;'>ATTENTION: Le format des num&#233;ros internes des &#233;l&#232;ves n'a pas l'air standard.<br />Veillez &#224; contr&#244;ler que vos num&#233;ros internes ont bien &#233;t&#233; analys&#233;s malgr&#233; tout.</p>\n";
						}
						if($temoin_creation_fichiers!="non"){$fich=fopen("$dossiercsv/gibii/eleves.txt","w+");}
						for($k=0;$k<count($tabnumero);$k++){
							$numero=$tabnumero[$k];
							$chaine="";
							// Numero
							$chaine.=$eleve[$numero]["numero"];
							$chaine.=";";
							// Civilite/sexe
							$chaine.=$eleve[$numero]["sexe"];
							$chaine.=";";
							$chaine.=$eleve[$numero]["nom"];
							$chaine.=";";
							$chaine.=$eleve[$numero]["prenom"];
							$chaine.=";";
							$chaine.=$eleve[$numero]["division"];
							$chaine.=";";
							$chaine.=$eleve[$numero]["date"];
							$chaine.=";";
							$chaine.=";";
							//echo "<br />\n";
							if($fich){
								//fwrite($fich,$chaine."\n");
								fwrite($fich,html_entity_decode($chaine)."\n");
							}
							echo $chaine."<br />\n";
						}
						if($temoin_creation_fichiers!="non"){fclose($fich);}
						echo "</blockquote>\n";






						echo "<h3>Reperage des champs pour les Responsables</h3>\n";
						echo "<blockquote>\n";

						// Champs du F_ERE.DBF requis pour GEPI:
						// ERENO          numero des sresponsables (en liaison avec F_ELE.DBF)
						// ERENOM         nom  du premier responsable
						// EREPRE         prenom(s)  du premier responsable
						// EREADR         n° + rue   du premier responsable
						// ERECLD         code postal   du premier responsable
						// ERELCOM        nom de la commune  du premier responsable
						// EREANOM        nom du deuxieme responsable
						// EREAPRE        prenom(s) du deuxieme responsable
						// EREAADR        n° + rue  du deuxieme responsable
						// EREADRS        complement adresse
						// EREACLD        code postal  du deuxieme responsable
						// EREALCOM       nom de la commune  du deuxieme responsable



						// ERENO          numero des responsables (en liaison avec F_ELE.DBF)
						// ERENOM         Nom resp.
						// EREPRE         Prenom resp.
						// EREADR         Ligne 1 Adresse;Ligne 2 Adresse;Ligne 3 Adresse;Ligne 4 Adresse
						// ERECLD         Code postal resp.
						// ERELCOM        Commune resp.

						// EREANOM        Nom resp.
						// EREAPRE        Prenom resp.
						// EREAADR        Ligne 1 Adresse
						// EREADRS        Ligne 2 Adresse;Ligne 3 Adresse;Ligne 4 Adresse
						// EREACLD        Code postal resp.
						// EREALCOM       Commune resp.


						// Les champs de GEPI:
						// ereno nom1 prenom1 adr1 adr1_comp commune1 cp1 nom2 prenom2 adr2 adr2_comp commune2 cp2


						// Le CSV de Sconet:
						//Nom;Prenom 1;Date de naissance;N° Interne;INE;Sexe;Division;Option 1;Option 2;Option 3;Option 4;Option 5;Option 6;Option 7;Option 8;Option 9;Option 10;Option 11;Option 12;Regime;Doublement;Legal;Financier;Correspondant;Civilite resp.;Nom resp.;Prenom resp.;Ligne 1 Adresse;Ligne 2 Adresse;Ligne 3 Adresse;Ligne 4 Adresse;Commune resp.;Code postal resp.;Lien de parente;Profession resp.;Situation emploi;Tel maison resp.;Tel travail resp.;Tel mobile resp.;Courriel resp.

	/*
						$champ=array("Nom",
						"Pr&#233;nom 1",
						"Date de naissance",
						"N° Interne",
						"Sexe",
						"Division",
						"L&#233;gal",
						"Correspondant",
						"Civilit&#233; resp.",
						"Nom resp.",
						"Pr&#233;nom resp.",
						"Ligne 1 Adresse",
						"Ligne 2 Adresse",
						"Ligne 3 Adresse",
						"Ligne 4 Adresse",
						"Commune resp.",
						"Code postal resp.",
						"Lien de parent&#233;",
						"Profession resp.",
						"Situation emploi",
						"Tel maison resp.",
						"Tel travail resp.",
						"Tel mobile resp.",
						"Courriel resp.",
						"INE");
	*/
						$champ=array("Nom",
						"Pr&#233;nom 1",
						"Date de naissance",
						"N° Interne",
						"Sexe",
						"Division",
						"L&#233;gal",
						"Correspondant",
						"Nom resp.",
						"Pr&#233;nom resp.",
						"Ligne 1 Adresse",
						"Ligne 2 Adresse",
						"Ligne 3 Adresse",
						"Ligne 4 Adresse",
						"Commune resp.",
						"Code postal resp.",
						"INE");
						// J'ai mis l'INE a la fin pour ne pas devoir redecaler la numerotation dans ce qui suit...

						$temoin_gepi="oui";
						// Analyse:
						// Reperage des champs souhaites:
						//$tabtmp=explode(";",$ligne[0]);
						$tabtmp=explode(";",trim($ligne[0]));
						for($j=0;$j<count($champ);$j++){
							$index[$j]="-1";
							for($i=0;$i<count($tabtmp);$i++){
								if($tabtmp[$i]==$champ[$j]){
									echo "Champ '<font color='blue'>$champ[$j]</font>' rep&#233;r&#233; en colonne/position <font color='blue'>$i</font><br />\n";
									$index[$j]=$i;
								}
							}
							if($index[$j]=="-1"){
								echo "<p><font color='red'>ERREUR: Le champ '<font color='blue'>$champ[$j]</font>' n'a pas &#233;t&#233; trouv&#233;.</font></p>\n";
								$temoin_gepi="non";
								/*
								echo "</blockquote>";
								echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour</a>.</p>\n";
								echo "</blockquote></div></body></html>";
								exit();
								*/
							}
						}
						echo "<p>Termin&#233;.</p>\n";
						echo "</blockquote>\n";

						if($temoin_gepi=="oui"){
							echo "<h3>Remplissage des tableaux pour les Responsables</h3>\n";
							echo "<blockquote>\n";
							$cpt=1;
							$tabnumero=array();
							$eleve=array();
							while($cpt<count($ligne)){
								if($ligne[$cpt]!=""){
									//$tabtmp=explode(";",$ligne[$cpt]);
									$tabtmp=explode(";",trim($ligne[$cpt]));

									// Si la division/classe n'est pas vide
									if($tabtmp[$index[5]]!=""){
										//$numero=substr($tabtmp[$index[3]],0,strlen($tabtmp[$index[3]])-6);
										if(strlen($tabtmp[$index[3]])==11){
											$numero=substr($tabtmp[$index[3]],0,strlen($tabtmp[$index[3]])-6);
										}
										else{
											$temoin_format_num_interne="non_standard";
											if(strlen($tabtmp[$index[3]])==4){
												$numero="0".$tabtmp[$index[3]];
											}
											else{
												$numero=$tabtmp[$index[3]];
											}
										}

										$temoin=0;
										for($i=0;$i<count($tabnumero);$i++){
											if($tabnumero[$i]==$numero){
												$temoin=1;
											}
										}
										if($temoin==0){
											$tabnumero[]=$numero;
											$eleve[$numero]=array();

											$eleve[$numero]["numero"]=$numero;
											$eleve[$numero]["nom"]=preg_replace("/[^a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[0]]);
											$eleve[$numero]["prenom"]=preg_replace("/[^a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[1]]);

											unset($tmpdate);
											$tmpdate=explode("/",$tabtmp[$index[2]]);
											$eleve[$numero]["date"]=$tmpdate[2]."-".$tmpdate[1]."-".$tmpdate[0];
											$eleve[$numero]["sexe"]=$tabtmp[$index[4]];
											//$eleve[$numero]["division"]=ereg_replace("[^[:space:][A-Z][a-z][0-9]]", "",$tabtmp[$index[5]]);
											$eleve[$numero]["division"]=preg_replace("/[^a-zA-Z0-9_ -]/", "",remplace_accents($tabtmp[$index[5]]));

											//$eleve[$numero]["INE"]=preg_replace("/[^a-zA-Z0-9_ -]/", "",$tabtmp[$index[17]]);
											//$eleve[$numero]["INE"]=preg_replace("/[^a-zA-Z0-9_ -]/", "",$tabtmp[$index[24]]);
											$eleve[$numero]["INE"]=preg_replace("/[^a-zA-Z0-9_ -]/", "",$tabtmp[$index[16]]);
		/*
											for($i=6;$i<count($champ);$i++){
												$eleve[$numero][$champ[$i]][]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ .-]/", "", $tabtmp[$index[$i]]);
											}
		*/
											/*
											$eleve[$numero]["L&#233;gal"]
											"Correspondant"
											"Civilit&#233; resp."
											"Nom resp."
											"Pr&#233;nom resp."
											"Ligne 1 Adresse"
											"Ligne 2 Adresse"
											"Ligne 3 Adresse"
											"Ligne 4 Adresse"
											"Commune resp."
											"Code postal resp."
											"Lien de parent&#233;"
											"Profession resp."
											"Situation emploi"
											"Tel maison resp."
											"Tel travail resp."
											"Tel mobile resp."
											"Courriel resp."
											*/
										}


										// On contrôle que c'est un representant legal (!=0) et en meme temps Correspondant (VRAI).
										if(($tabtmp[$index[6]]!="0")&&($tabtmp[$index[7]]=="VRAI")){
		/*
											for($i=6;$i<count($champ);$i++){
												//echo "\$eleve[$numero][$champ[$i]][]=ereg_replace(\"[^0-9a-zA-Z�������������ܽ�����������������_ .-]\", \"\", \$tabtmp[$index[$i]])<br />";
												//if($index[$i]){
												//echo "\$tabtmp[$index[$i]]=".$tabtmp[$index[$i]]."<br />";
												$eleve[$numero][$champ[$i]][]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ .-]/", "", $tabtmp[$index[$i]]);
												//$eleve[$numero][$champ[$i]][]=ereg_replace("[^0-9a-zA-Z������������������������������_ .-]", "", $tabtmp[$index[$i]]);
												//echo "\$index[$i]=|".$index[$i]."|<br />";
												//$eleve[$numero][$champ[$i]][]=$tabtmp[$index[$i]];
												//}
											}
		*/

											if($tabtmp[$index[6]]==1){
		/*
												$eleve[$numero]["erenom1"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ .-]/", "", $tabtmp[$index[9]]);
												$eleve[$numero]["ereprenom1"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ .-]/", "", $tabtmp[$index[10]]);
												$eleve[$numero]["ereadr1"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ .-]/", "", $tabtmp[$index[11]]);
												$chaine_compl_addr=$tabtmp[$index[12]];
												if($tabtmp[$index[13]]!=""){
													$chaine_compl_addr.=",".$tabtmp[$index[13]];
												}
												if($tabtmp[$index[14]]!=""){
													$chaine_compl_addr.=",".$tabtmp[$index[14]];
												}
		*/
												$eleve[$numero]["erenom1"]=preg_replace("/[^0-9a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[8]]);
												$eleve[$numero]["ereprenom1"]=preg_replace("/[^0-9a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[9]]);
												$eleve[$numero]["ereadr1"]=preg_replace("/[^0-9a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[10]]);
												$chaine_compl_addr=$tabtmp[$index[11]];
												if($tabtmp[$index[12]]!=""){
													$chaine_compl_addr.=",".$tabtmp[$index[12]];
												}
												if($tabtmp[$index[13]]!=""){
													$chaine_compl_addr.=",".$tabtmp[$index[13]];
												}


												//$eleve[$numero]["ereadrcomplement1"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ ,.-]/", "", $tabtmp[$index[12]].",".$tabtmp[$index[13]].",".$tabtmp[$index[14]]);
												$eleve[$numero]["ereadrcomplement1"]=preg_replace("/[^0-9a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "",$chaine_compl_addr);
												/*
												$eleve[$numero]["erecommune1"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ .-]/", "", $tabtmp[$index[15]]);
												$eleve[$numero]["erecodepost1"]=preg_replace("/[^0-9]/", "", $tabtmp[$index[16]]);
												*/
												$eleve[$numero]["erecommune1"]=preg_replace("/[^0-9a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[14]]);
												$eleve[$numero]["erecodepost1"]=preg_replace("/[^0-9]/", "", $tabtmp[$index[15]]);
											}
											elseif($tabtmp[$index[6]]==2){
												/*
												$eleve[$numero]["erenom2"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ .-]/", "", $tabtmp[$index[9]]);
												$eleve[$numero]["ereprenom2"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ .-]/", "", $tabtmp[$index[10]]);
												$eleve[$numero]["ereadr2"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ .-]/", "", $tabtmp[$index[11]]);
												$chaine_compl_addr=$tabtmp[$index[12]];
												if($tabtmp[$index[13]]!=""){
													$chaine_compl_addr.=",".$tabtmp[$index[13]];
												}
												if($tabtmp[$index[14]]!=""){
													$chaine_compl_addr.=",".$tabtmp[$index[14]];
												}
												//$eleve[$numero]["ereadrcomplement2"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ ,.-]/", "", $tabtmp[$index[12]].",".$tabtmp[$index[13]].",".$tabtmp[$index[14]]);
												$eleve[$numero]["ereadrcomplement2"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ ,.-]/", "",$chaine_compl_addr);
												$eleve[$numero]["erecommune2"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ .-]/", "", $tabtmp[$index[15]]);
												$eleve[$numero]["erecodepost2"]=preg_replace("/[^0-9]/", "", $tabtmp[$index[16]]);
												*/

												$eleve[$numero]["erenom2"]=preg_replace("/[^0-9a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[8]]);
												$eleve[$numero]["ereprenom2"]=preg_replace("/[^0-9a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[9]]);
												$eleve[$numero]["ereadr2"]=preg_replace("/[^0-9a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[10]]);
												$chaine_compl_addr=$tabtmp[$index[11]];
												if($tabtmp[$index[12]]!=""){
													$chaine_compl_addr.=",".$tabtmp[$index[12]];
												}
												if($tabtmp[$index[13]]!=""){
													$chaine_compl_addr.=",".$tabtmp[$index[13]];
												}
												//$eleve[$numero]["ereadrcomplement2"]=preg_replace("/[^0-9a-zA-Z�������������ܽ�����������������_ ,.-]/", "", $tabtmp[$index[12]].",".$tabtmp[$index[13]].",".$tabtmp[$index[14]]);
												$eleve[$numero]["ereadrcomplement2"]=preg_replace("/[^0-9a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "",$chaine_compl_addr);
												$eleve[$numero]["erecommune2"]=preg_replace("/[^0-9a-zA-ZÀÄÂÉÈÊËÎÏÔÖÙÛÜ½¼Ççàäâéèêëîïôöùûü_ -]/", "", $tabtmp[$index[14]]);
												$eleve[$numero]["erecodepost2"]=preg_replace("/[^0-9]/", "", $tabtmp[$index[15]]);

											}
										}


									}
								}
								$cpt++;
							}
							echo "<p>Termin&#233;.</p>\n";
							echo "</blockquote>\n";

							echo "<a name='gepi_resp'></a>\n";
							echo "<h3>Affichage d'un CSV des Responsables pour GEPI</h3>\n";
							echo "<blockquote>\n";
							if($temoin_format_num_interne!=""){
								echo "<p style='color:red;'>ATTENTION: Le format des num&#233;ros internes des &#233;l&#232;ves n'a pas l'air standard.<br />Veillez &#224; contr&#244;ler que vos num&#233;ros internes ont bien &#233;t&#233; analys&#233;s malgr&#233; tout.</p>\n";
							}
							echo "<p>En fait, il faut l'affichage de deux CSV:<br />Un pour la correspondance ELENOET;ERENO<br />Et l'autre pour ERENO;;;; les infos parents.</p>\n";

							//echo "<p>A FAIRE...</p>\n";

							$ereno=1;
							for($k=0;$k<count($tabnumero);$k++){
								$numero=$tabnumero[$k];

		/*
								echo $eleve[$numero]["numero"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["nom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["prenom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["date"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["sexe"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["division"];
		*/

								// Comparer aux valeurs deja affichees/affectees:
								$temoin_parent=0;
								for($j=0;$j<$k;$j++){
		/*
									if(($eleve[$numero]["Nom resp."]==$eleve[$tabnumero[$j]]["Nom resp."])&&
									($eleve[$numero]["Prénom resp."]==$eleve[$tabnumero[$j]]["Prénom resp."])&&
									($eleve[$numero]["Ligne 1 Adresse"]==$eleve[$tabnumero[$j]]["Ligne 1 Adresse"])&&
									($eleve[$numero]["Ligne 2 Adresse"]==$eleve[$tabnumero[$j]]["Ligne 2 Adresse"])&&
									($eleve[$numero]["Ligne 3 Adresse"]==$eleve[$tabnumero[$j]]["Ligne 3 Adresse"])&&
									($eleve[$numero]["Ligne 4 Adresse"]==$eleve[$tabnumero[$j]]["Ligne 4 Adresse"])&&
									($eleve[$numero]["Commune resp."]==$eleve[$tabnumero[$j]]["Commune resp."])&&
									($eleve[$numero]["Code postal resp."]==$eleve[$tabnumero[$j]]["Code postal resp."])&&
									($eleve[$numero]["Lien de parenté"]==$eleve[$tabnumero[$j]]["Lien de parenté"])&&
									($eleve[$numero]["Profession resp."]==$eleve[$tabnumero[$j]]["Profession resp."])&&
									($eleve[$numero]["Situation emploi"]==$eleve[$tabnumero[$j]]["Situation emploi"])&&
									($eleve[$numero]["Tel maison resp."]==$eleve[$tabnumero[$j]]["Tel maison resp."])&&
									($eleve[$numero]["Tel travail resp."]==$eleve[$tabnumero[$j]]["Tel travail resp."])&&
									($eleve[$numero]["Tel mobile resp."]==$eleve[$tabnumero[$j]]["Tel mobile resp."])&&
									($eleve[$numero]["Courriel resp."]==$eleve[$tabnumero[$j]]["Courriel resp."])){
										$temoin_parent=$tabnumero[$j];
									}
		*/
									if(($eleve[$numero]["erenom1"]==$eleve[$tabnumero[$j]]["erenom1"])&&
									($eleve[$numero]["ereprenom1"]==$eleve[$tabnumero[$j]]["ereprenom1"])&&
									($eleve[$numero]["ereadr1"]==$eleve[$tabnumero[$j]]["ereadr1"])&&
									($eleve[$numero]["ereadrcomplement1"]==$eleve[$tabnumero[$j]]["ereadrcomplement1"])&&
									($eleve[$numero]["erecommune1"]==$eleve[$tabnumero[$j]]["erecommune1"])&&
									($eleve[$numero]["erecodepost1"]==$eleve[$tabnumero[$j]]["erecodepost1"])&&
									($eleve[$numero]["erenom2"]==$eleve[$tabnumero[$j]]["erenom2"])&&
									($eleve[$numero]["ereprenom2"]==$eleve[$tabnumero[$j]]["ereprenom2"])&&
									($eleve[$numero]["ereadr2"]==$eleve[$tabnumero[$j]]["ereadr2"])&&
									($eleve[$numero]["ereadrcomplement2"]==$eleve[$tabnumero[$j]]["ereadrcomplement2"])&&
									($eleve[$numero]["erecommune2"]==$eleve[$tabnumero[$j]]["erecommune2"])&&
									($eleve[$numero]["erecodepost2"]==$eleve[$tabnumero[$j]]["erecodepost2"])){
										$temoin_parent=$tabnumero[$j];
									}
								}

								for($i=0;$i<count($eleve[$numero]["Nom resp."]);$i++){
		/*
									// Comparer aux valeurs deja affichees/affectees:
									$temoin_parent=0;
									for($j=0;$j<$i;$j++){
										if(()&&
										()&&
										()){
											$temoin_parent=1;
										}
									}
		*/
								}

								if($temoin_parent==0){
									$eleve[$numero]["ereno"]=$ereno;
									//echo "<font color='red'>|</font>";
									echo "<font color='green'>".$eleve[$numero]["ereno"]."</font>";
									$ereno++;
								}
								else{
									$eleve[$numero]["ereno"]=$eleve[$temoin_parent]["ereno"];
									//echo "<font color='red'>|</font>";
									echo "<font color='red'>".$eleve[$numero]["ereno"]."</font>";
								}

								echo "<font color='red'>|</font>";
								//echo $eleve[$numero]["Nom resp."][$i];
								echo $eleve[$numero]["erenom1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereprenom1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereadr1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereadrcomplement1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["erecommune1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["erecodepost1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["erenom2"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereprenom2"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereadr2"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereadrcomplement2"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["erecommune2"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["erecodepost2"];

								echo "<br />\n";
							}

							echo "</blockquote>\n";

							echo "<a name='gepi_ele'></a>\n";
							echo "<h3>Affichage d'un CSV des El&#232;ves pour GEPI</h3>\n";
							echo "<blockquote>\n";
							if($temoin_format_num_interne!=""){
								echo "<p style='color:red;'>ATTENTION: Le format des num&#233;ros internes des &#233;l&#232;ves n'a pas l'air standard.<br />Veillez &#224; contr&#244;ler que vos num&#233;ros internes ont bien &#233;t&#233; analys&#233;s malgr&#233; tout.</p>\n";
							}

							for($k=0;$k<count($tabnumero);$k++){
								$numero=$tabnumero[$k];

								// INE
								echo $eleve[$numero]["INE"];
								echo "<font color='red'>|</font>";
								// Login...
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["nom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["prenom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["sexe"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["date"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["numero"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereno"];

								//echo $eleve[$numero]["division"];
								echo "<br />\n";
							}

							//echo "<p>PB: Le champ INE ne contient pas la bonne valeur...</p>";

							echo "</blockquote>\n";

							echo "<a name='gepi_classe'></a>\n";
							echo "<h3>Affichage d'un CSV des Classes pour GEPI</h3>\n";
							echo "<blockquote>\n";

							echo "<p>Les noms de classes permettent de remplir une partie de la table 'classes' (les noms de classes).</p>";
							echo "<p>Il faut le login GEPI de l'&#233;l&#232;ve pour renseigner 'j_eleves_classes'.</p>";
							/*
							for($k=0;$k<count($tabnumero);$k++){
								$numero=$tabnumero[$k];

								// INE
								echo $eleve[$numero]["INE"];
								echo "<font color='red'>|</font>";
								// Login...
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["nom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["prenom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["sexe"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["date"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["numero"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereno"];

								//echo $eleve[$numero]["division"];
							}
							*/

							echo "</blockquote>\n";





























							echo "<a name='gepi2'></a>\n";
							echo "<h3>Affichage d'un CSV des Responsables pour GEPI (bis)</h3>\n";
							echo "<blockquote>\n";
							echo "<p>En fait, il faut l'affichage de deux CSV:<br />Un pour la correspondance ELENOET;ERENO<br />Et l'autre pour ERENO;;;; les infos parents.</p>\n";

							//echo "<p>A FAIRE...</p>\n";

							$ereno=1;
							for($k=0;$k<count($tabnumero);$k++){
								$numero=$tabnumero[$k];

		/*
								echo $eleve[$numero]["numero"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["nom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["prenom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["date"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["sexe"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["division"];
		*/

								// Comparer aux valeurs deja affichees/affectees:
								$temoin_parent=0;
								for($j=0;$j<$k;$j++){
		/*
									if(($eleve[$numero]["Nom resp."]==$eleve[$tabnumero[$j]]["Nom resp."])&&
									($eleve[$numero]["Prénom resp."]==$eleve[$tabnumero[$j]]["Prénom resp."])&&
									($eleve[$numero]["Ligne 1 Adresse"]==$eleve[$tabnumero[$j]]["Ligne 1 Adresse"])&&
									($eleve[$numero]["Ligne 2 Adresse"]==$eleve[$tabnumero[$j]]["Ligne 2 Adresse"])&&
									($eleve[$numero]["Ligne 3 Adresse"]==$eleve[$tabnumero[$j]]["Ligne 3 Adresse"])&&
									($eleve[$numero]["Ligne 4 Adresse"]==$eleve[$tabnumero[$j]]["Ligne 4 Adresse"])&&
									($eleve[$numero]["Commune resp."]==$eleve[$tabnumero[$j]]["Commune resp."])&&
									($eleve[$numero]["Code postal resp."]==$eleve[$tabnumero[$j]]["Code postal resp."])&&
									($eleve[$numero]["Lien de parenté"]==$eleve[$tabnumero[$j]]["Lien de parenté"])&&
									($eleve[$numero]["Profession resp."]==$eleve[$tabnumero[$j]]["Profession resp."])&&
									($eleve[$numero]["Situation emploi"]==$eleve[$tabnumero[$j]]["Situation emploi"])&&
									($eleve[$numero]["Tel maison resp."]==$eleve[$tabnumero[$j]]["Tel maison resp."])&&
									($eleve[$numero]["Tel travail resp."]==$eleve[$tabnumero[$j]]["Tel travail resp."])&&
									($eleve[$numero]["Tel mobile resp."]==$eleve[$tabnumero[$j]]["Tel mobile resp."])&&
									($eleve[$numero]["Courriel resp."]==$eleve[$tabnumero[$j]]["Courriel resp."])){
										$temoin_parent=$tabnumero[$j];
									}
		*/
									if(($eleve[$numero]["erenom1"]==$eleve[$tabnumero[$j]]["erenom1"])&&
									($eleve[$numero]["ereprenom1"]==$eleve[$tabnumero[$j]]["ereprenom1"])&&
									($eleve[$numero]["ereadr1"]==$eleve[$tabnumero[$j]]["ereadr1"])&&
									($eleve[$numero]["ereadrcomplement1"]==$eleve[$tabnumero[$j]]["ereadrcomplement1"])&&
									($eleve[$numero]["erecommune1"]==$eleve[$tabnumero[$j]]["erecommune1"])&&
									($eleve[$numero]["erecodepost1"]==$eleve[$tabnumero[$j]]["erecodepost1"])&&
									($eleve[$numero]["erenom2"]==$eleve[$tabnumero[$j]]["erenom2"])&&
									($eleve[$numero]["ereprenom2"]==$eleve[$tabnumero[$j]]["ereprenom2"])&&
									($eleve[$numero]["ereadr2"]==$eleve[$tabnumero[$j]]["ereadr2"])&&
									($eleve[$numero]["ereadrcomplement2"]==$eleve[$tabnumero[$j]]["ereadrcomplement2"])&&
									($eleve[$numero]["erecommune2"]==$eleve[$tabnumero[$j]]["erecommune2"])&&
									($eleve[$numero]["erecodepost2"]==$eleve[$tabnumero[$j]]["erecodepost2"])){
										$temoin_parent=$tabnumero[$j];
									}
								}

								for($i=0;$i<count($eleve[$numero]["Nom resp."]);$i++){
		/*
									// Comparer aux valeurs deja affichees/affectees:
									$temoin_parent=0;
									for($j=0;$j<$i;$j++){
										if(()&&
										()&&
										()){
											$temoin_parent=1;
										}
									}
		*/
								}

								if($temoin_parent==0){
									$eleve[$numero]["ereno"]=$ereno;
									//echo "<font color='red'>|</font>";
									echo "<font color='green'>".$eleve[$numero]["ereno"]."</font>";
									$ereno++;
								}
								else{
									$eleve[$numero]["ereno"]=$eleve[$temoin_parent]["ereno"];
									//echo "<font color='red'>|</font>";
									echo "<font color='red'>".$eleve[$numero]["ereno"]."</font>";
								}

								echo "<font color='red'>|</font>";
								//echo $eleve[$numero]["Nom resp."][$i];
								echo $eleve[$numero]["erenom1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereprenom1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereadr1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereadrcomplement1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["erecommune1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["erecodepost1"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["erenom2"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereprenom2"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereadr2"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereadrcomplement2"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["erecommune2"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["erecodepost2"];

								echo "<br />\n";
							}

							echo "</blockquote>\n";

							echo "<a name='gepi_ele'></a>\n";
							echo "<h3>Affichage d'un CSV des El&#232;ves pour GEPI</h3>\n";
							echo "<blockquote>\n";

							for($k=0;$k<count($tabnumero);$k++){
								$numero=$tabnumero[$k];

								// INE
								echo $eleve[$numero]["INE"];
								echo "<font color='red'>|</font>";
								// Login...
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["nom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["prenom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["sexe"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["date"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["numero"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereno"];

								//echo $eleve[$numero]["division"];
								echo "<br />\n";
							}

							//echo "<p>PB: Le champ INE ne contient pas la bonne valeur...</p>";

							echo "</blockquote>\n";

							echo "<a name='gepi_classe'></a>\n";
							echo "<h3>Affichage d'un CSV des Classes pour GEPI</h3>\n";
							echo "<blockquote>\n";

							echo "<p>Les noms de classes permettent de remplir une partie de la table 'classes' (les noms de classes).</p>";
							echo "<p>Il faut le login GEPI de l'&#233;l&#232;ve pour renseigner 'j_eleves_classes'.</p>";
							/*
							for($k=0;$k<count($tabnumero);$k++){
								$numero=$tabnumero[$k];

								// INE
								echo $eleve[$numero]["INE"];
								echo "<font color='red'>|</font>";
								// Login...
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["nom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["prenom"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["sexe"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["date"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["numero"];
								echo "<font color='red'>|</font>";
								echo $eleve[$numero]["ereno"];

								//echo $eleve[$numero]["division"];
							}
							*/

							echo "</blockquote>\n";
						}




						if($temoin_creation_fichiers!="non"){
							//echo "<div style='position:absolute; top: 50px; left: 50px; width: 300px; height: 200px; background: yellow; border: 1px solid black;'>\n";
							echo "<div style='position:absolute; top: 70px; left: 300px; width: 300px; background: yellow; border: 1px solid black; padding-left: 5px; padding-right: 5px; padding-top: 0; '>\n";
							echo "<h4 style='margin:0; padding:0; text-align:center;'>SE3</h4>\n";
							if($temoin_format_num_interne!=""){
								echo "<p style='color:red;'>ATTENTION: Le format des num&#233;ros internes des &#233;l&#232;ves n'a pas l'air standard.<br />Veillez &#224; contr&#244;ler que vos num&#233;ros internes ont bien &#233;t&#233; analys&#233;s malgr&#233; tout.</p>\n";
							}
							echo "<p style='margin-top: 0;'>R&#233;cup&#233;rez le(s) CSV suivants pour SambaEdu3:</p>\n";
							echo "<table border='0'>\n";
							echo "<tr><td>Fichier El&#232;ves:</td><td><a href='$dossiercsv/se3/f_ele.txt'>f_ele.txt</a></td></tr>\n";
							/*
							echo "<tr><td>Fichier Classes/mati&#232;res/profs:</td><td>\n";
							if(file_exists("$dossiercsv/gepi/f_men.txt")){
								echo "<a href='$dossiercsv/se3/f_men.txt'>f_men.txt</a>";
							}
							else{
								echo "Fichier non g&#233;n&#233;r&#233;.<br />L'emploi du temps n'est sans doute pas encore remont&#233;.";
							}
							echo "</td></tr>\n";
							*/
							echo "</table>\n";
							echo "<hr width='200' align='center' />\n";
							echo "<p style='margin-top: 0;'>R&#233;cup&#233;rez le CSV suivant pour GiBii:</p>\n";
							echo "<table border='0'>\n";
							echo "<tr><td>Fichier El&#232;ves:</td><td><a href='$dossiercsv/gibii/eleves.txt'>eleves.txt</a></td></tr>\n";
							echo "</table>\n";

							echo "<hr width='200' align='center' />\n";
							echo "<p style='margin-top: 0;'>R&#233;cup&#233;rez les CSV suivants pour GEPI: <font color='red'>A FAIRE</font></p>\n";
/*
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
*/
							echo "<p>Pour supprimer les fichiers apr&#232;s r&#233;cup&#233;ration: <a href='".$_SERVER['PHP_SELF']."?nettoyage=oui&amp;date=$doss_date'>Nettoyage</a></p>\n";
							echo "</div>\n";
						}


						echo "</blockquote>\n";
					}
					else{
						echo "<p>ERREUR!<br /><a href='".$_SERVER['PHP_SELF']."'>Retour</a>.</p>\n";
					}
				}
			}

		?>
	</div>
</body>
</html>
