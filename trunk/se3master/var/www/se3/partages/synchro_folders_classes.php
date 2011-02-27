<?php


   /**
   
   * Permet de synchroniser les partages Classe
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs Equipe TICE Crdp de Caen
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note Ce script est conserve pour le cas d'un serveur esclave avec repertoires classes deportes (utilise admind)
   * @note Remplace par rep_classe.php 
   */

   /**

   * @Repertoire: partages/
   * file: synchro_folders_classes.php

  */	


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-partages',"/var/www/se3/locale");
textdomain ('se3-partages');


$DEBUG = false;

if (is_admin("se3_is_admin",$login)=="Y") {
        // Aide en ligne
                $titre=gettext("Aide en ligne");
                $texte=gettext("<p>Vous &#234tes administrateur du serveur SE3.<BR>Avec le menu ci-dessous, vous pouvez synchroniser les sous r&#233pertoires Classes avec la base des utilisateurs de votre annuaire.<p><u>Contexte d'usage</u> :<br><blockquote><li>Vous avez cr&#233&#233 des ressources classes &#224 partir du menu «Cr&#233er partages classes».<li>Vous avez ult&#233rieurement cr&#233&#233 des membres dans les groupes classes de votre annuaire.</blockquote><p>Avec le menu «synchronisation r&#233pertoire classes» vous programmez un op&#233ration de synchrornisation entre le contenu des groupes Classes de l'annuaire et les sous r&#233pertoires de chaque classe, c'est &#224 dire qu'il y aura cr&#233ation des sous-r&#233pertoires des nouveaux membres des groupes Classes  et application des droits n&#233cessaires aux membres des Equipes.	<p>La proc&#233dure de synchronisation se d&#233roulera en deux phases :<li><u>1&#231re phase</u> : Recherche des ressources &#224 synchroniser,</li><li><u>2&#231me phase</u> : Synchronisation des ressources.</li><p><i>Nota</i> : Le processus de synchronisation (en particulier) dans le cas d'un serveur esclave peut &#234tre assez long, fin de synchronisation un compte rendu s'affichera dans votre navigateur et vous recevrez un mail.</p><hr>");
                mkhelp($titre,$texte);
     // Fin Aide en ligne

echo "<h1>".gettext("Rafraichissement des classes")."</h1>";

#------------------------------------------
    // Definition des messages d'alerte
    $alerte_1="<div class='error_msg'>".gettext("Votre demande de synchronisation des ressources classes n'a pas &#233t&#233 prise en compte car une t&#226;che d'administration est en cours sur le serveur <b>");
    $alerte_2=gettext("</b>, veuillez r&#233it&#233rer votre demande plus tard. Si le probl&#231me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";
    $alerte_3="<div class='error_msg'>".gettext("Votre demande de synchronisation des ressources classes a &#233chou&#233e.&nbsp; Si le probl&#231me persiste, veuillez contacter le super-utilisateur du serveur SE3.")."</div><BR>\n";
    $alerte_4="<div class='error_msg'>".gettext("La recherche des ressources a synchroniser &#224 &#233chou&#233e !&nbsp;Si le probl&#231me persiste, veuillez contacter le super-utilisateur du serveur SE3.")." </div><BR>\n";
    $alerte_5="<div class='error_msg'>".gettext("ERREUR : Impossible de cr&#233er le fichier d'ordonnancement (phase 2) de synchronisation des ressources classes !")."</div><BR>\n";
	$alerte_6="<div  class='error_msg'>".gettext("ERREUR : Impossible de cr&#233er le fichier d'ordonnancement (phase 1) de recherche de synchronisation des ressources classes !")."</div>\n";

     // Definition des messages d'info
    $info_1 = gettext("<b>[phase 1]</b> Une t&#226;che de recherche des ressources classe &#224 synchroniser est ordonnanc&#233e...<BR>");
    $info_2 = gettext("<b>[phase 2]</b> Une t&#226;che de synchronisation est ordonnanc&#233e...<BR>Vous recevrez un m&#232;l de compte rendu de synchronisation dans quelques instants...");
    $info_3 = gettext("<b>[phase 2]</b> Il n'y a pas de ressources Classes &#224 synchroniser sur le serveur&nbsp;");

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
			echo "<H3>".gettext("S&#233lection du serveur ou vous souhaitez cr&#233er la ressource classe :")." </H3>\n";
			$servers=search_computers ("(|(l=esclave)(l=maitre))");
			echo "<form action=\"synchro_folders_classes.php\" method=\"post\">\n";
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

    // Debut procedure de synchro
    if ( $stat_srv)  {
		// [Phase 1] Synchronisation
    	echo "<h3>".gettext("Synchronisation en cours sur les classes :")."</h3>";
		// Fabrication du script admin.sh<td width=50%>Professeurs</td> pour obtenir la liste des sous rep Classes_N
		// ainsi que les acl des professeurs
		$commandes = "#!/bin/bash\n";
		$commandes .="#".gettext("Recherche des ressources classes pr&#233sentes sur le serveur")." $stat_srv $cn_srv\n";
		$commandes .="cd /var/se3/Classes\n";
		$commandes .="for rep in *; do\n";
		$commandes .="  echo Membres:\$rep:>> /tmp/synchro.txt\n";
		$commandes .="  ls \$rep -I _travail -I _profs >> /tmp/synchro.txt\n";
		#$commandes .="  echo Equipe:\$rep:>> /tmp/synchro.txt\n";
		#$commandes .="  getfacl -d  --omit-header \$rep | grep user >> /tmp/synchro.txt\n";
		$commandes .="done\n";
		$commandes.="touch /tmp/end_synchro\n";
		if ( $stat_srv=="maitre") {$commandes.="chown www-se3:$defaultgid /tmp/synchro.txt /tmp/end_synchro\n";}
		else {$commandes.="chown remote_adm:lcs-users /tmp/synchro.txt /tmp/end_synchro\n";}
		$commandes.="chmod 666 /tmp/synchro.txt /tmp/end_synchro\n";
		// Depot du script tmp_$stat_srv.sh sur le serveur maitre
		$fp=@fopen("/var/remote_adm/tmp_".$stat_srv.".sh","w");
		if($fp) {
			fputs($fp,$commandes."\n");
			fclose($fp);
			chmod ("/var/remote_adm/tmp_$stat_srv.sh", 0600);
			// Si serveur maitre  renommage du script  tmp_master.sh en admin.sh
			if ($stat_srv == "maitre" ) {
				// Si pas de presence de admin.sh
				if ( !is_file("/var/remote_adm/admin.sh") ) {
					// Renommage et chmod +x du script sur le maitre
					rename ("/var/remote_adm/tmp_".$stat_srv.".sh", "/var/remote_adm/admin.sh");
					chmod ("/var/remote_adm/admin.sh", 0750);
					if (file_exists("/var/remote_adm/admin.sh")) {
						echo $info_1;
						$flag_sync = true;
					} else {
						echo $alerte_3;
						// Dans ce cas la procedure de synchronisation est interrompue
						exit;
					}
				} else {
					// Message d'alerte  : Presence d'un admin.sh !!
					echo $alerte_1.$stat_srv."&nbsp;".$cn_srv.$alerte_2;
					// Dans ce cas la procedure de synchronisation est interrompue
					exit;
				}
			} elseif ( $stat_srv == "esclave" ) {
				// Si serveur esclave : scp de admin.sh
				//  Recherche de la presence d'un admin.sh sur le serveur esclave
				exec ("ssh -l remote_adm $ipHostNumber 'ls /var/remote_adm/admin.sh'", $AllOutput, $ReturnValue);
				// Si pas de presence de admin.sh sur l'esclave
				if (! $AllOutput[0]) {
					// Copie du script sur l'esclave avec scp
					exec ("/usr/bin/scp /var/remote_adm/tmp_$stat_srv.sh remote_adm@$ipHostNumber:tmp_$stat_srv.sh", $AllOutput, $ReturnValue);
					// chmod +x , renommage du script bash
					exec ("ssh -l remote_adm  $ipHostNumber 'chmod +x /var/remote_adm/tmp_$stat_srv.sh;mv  /var/remote_adm/tmp_$stat_srv.sh /var/remote_adm/admin.sh'", $AllOutput, $ReturnValue);
					if ($ReturnValue==0) {
						echo $info_1;
						$flag_sync = true;
					} else {
						echo $alerte_3;
						// Dans ce cas la procedure de synchronisation est interrompue
						exit;
					}
				} else {
						// Message d'alerte : Presence d'un admin_esclave.sh !!
						echo $alerte_1.$stat_srv."&nbsp;".$cn_srv.$alerte_2;
						// Dans ce cas la procedure de synchronisation est interrompue
						exit;
				}
			}
			// Attente de synchro.txt et traitement du contenu dans le cas de srv esclave
			if ($stat_srv == "esclave" && $flag_sync ) {
				// Attente et transfert sur le srv maitre de synchro.txt dans le cas du srv esclave
				for ($wait=0; $wait<5; $wait++) {
					if ( is_file("/tmp/end_synchro") ) {
						break;
					}
					sleep (2);
					exec ("scp remote_adm@$ipHostNumber:/tmp/synchro.txt /tmp", $AllOutput, $ReturnValue);
				}
				// Effacement de synchro.txt et end_synchro sur srv esclave
				exec ("ssh -l remote_adm $ipHostNumber 'rm /tmp/end_synchro;rm /tmp/synchro.txt' ", $AllOutput, $ReturnValue);
				system("touch /tmp/end_synchro");
			}
			// Attente de synchro.txt sur srv maitre
			if ($flag_sync ) {
   				// Attente de synchro.txt
				$loop=0;
				while ( $loop < 1000000 && !is_file("/tmp/end_synchro") ) {
					$loop++;
				}
			}
			// Interpretation de synchro.txt si il est present dans le cas contraire : Msg d'alerte n°4 !
			if ( is_file( "/tmp/synchro.txt" )  ) {
				// Interpretation de synchro.txt

				##DEBUG1
				if ($DEBUG) {
					echo gettext("interpretation")." synchro.txt<br>";
				} ## Fin DEBUG1

				$loop=0; $loop1=0;$loop2=0;
				// Lecture du fichier de synchro et initialisation du tableau $T_Ressources :
				$file_synchro = @fopen ("/tmp/synchro.txt", "r") ;
				if ($file_synchro ) {
					while (!feof ( $file_synchro)) {
						// Chargement d'un tableau groupe et memberUid
						$res = fgets( $file_synchro, 50);
						if (preg_split("/^Membres:Classe_/", $res)) {
							$tmp = preg_split ("/[\:\]/",$res,3);
							$T_Classes[$loop]=$tmp[1];
							$loop++;
							$loop1=0;
							$loop2=0;
						} elseif ( !preg_match("/^user:/", $res) && !preg_match("^Equipe:Classe", $res) && $res!="") {
							$T_Ressources[$T_Classes[$loop-1]]["memberUid"][$loop1]=$res;
							$loop1++;
						} 
/*
                                                  elseif (preg_match("/^user:/",$res) && !preg_match("/user::rwx/",$res) && $res!="") {
							// elimination du bruit
							$tmp = preg_split ("/[\:\]/",$res,3);
							$T_Ressources[$T_Classes[$loop-1]]["member"][$loop2]=$tmp[1];
							$loop2++;
						}
*/
					}
				}
				// Effacer synchro.txt et le marqueur end_synchro
				exec (" rm /tmp/synchro.txt; rm /tmp/end_synchro");

				## DEBUG
				if ($DEBUG) {
					echo "<u>/var/se3</u> => ".gettext("Nbr Rep Ressources :") .count ($T_Classes)."<br><br>";
					for ($loop=0; $loop<count ($T_Classes); $loop++) {
						echo   $T_Classes[$loop]."<br>";
						echo gettext("Nbr Membres Classe: ").count ($T_Ressources[$T_Classes[$loop]]["memberUid"])."<br>";
						echo "<blockquote>";
						for  ($loop1=0; $loop1<count ($T_Ressources[$T_Classes[$loop]]["memberUid"]); $loop1++) {
							echo $T_Ressources[$T_Classes[$loop]]["memberUid"][$loop1]."<br>";
						}
						echo "</blockquote>";
/*
						echo gettext("Nbr Membres Equipe: ").count ($T_Ressources[$T_Classes[$loop]]["member"])."<br>";
						echo "<blockquote>";
						for  ($loop2=0; $loop2<count ($T_Ressources[$T_Classes[$loop]]["member"]); $loop2++) {
							echo $T_Ressources[$T_Classes[$loop]]["member"][$loop2]."<br>";
						}
						echo "</blockquote>";
*/
					}
				} ## Fin DEBUG

				// Recherche des membres des ressources dans l'annuaire et initialisation du tableau $T_Annuaire :
				for ($loop=0; $loop<count ($T_Classes); $loop++) {
					$uids = search_uids ("(cn=".$T_Classes[$loop].")");
					$T_Annu[$loop]=$T_Classes[$loop];
					for ($loop1=0; $loop1<count($uids); $loop1++) {
						$T_Annu[$T_Classes[$loop]]["memberUid"][$loop1]=$uids[$loop1]["uid"];
					}
				}
/*
				for ($loop=0; $loop<count ($T_Classes); $loop++) {
					$T_Equipe=preg_replace("/Classe_/","Equipe_",$T_Classes[$loop]);
					$uids = search_uids ("(cn=$T_Equipe)");
					for ($loop1=0; $loop1<count($uids); $loop1++) {
						$T_Annu[$T_Classes[$loop]]["member"][$loop1]=$uids[$loop1]["uid"];
					}
				}
*/
				## DEBUG
				if ($DEBUG) {
					echo "<u>".gettext("Annuaire")."</u> => ".gettext("Nbr Rep Ressources : ").count ($T_Classes)."<br><br>";
					for ($loop=0; $loop<count ($T_Classes); $loop++) {
						echo   $T_Classes[$loop]."<br>";
						echo gettext("Nbr Membres Classe: ").count ($T_Annu[$T_Classes[$loop]]["memberUid"])."<br>";
						echo "<blockquote>";
						for  ($loop1=0; $loop1<count ($T_Annu[$T_Classes[$loop]]["memberUid"]); $loop1++) {
							echo $T_Annu[$T_Classes[$loop]]["memberUid"][$loop1]."<br>";
						}
/*
						echo "</blockquote>";
						echo gettext("Nbr Membres Equipe: ").count ($T_Annu[$T_Classes[$loop]]["member"])."<br>";
						echo "<blockquote>";
						for  ($loop2=0; $loop2<count ($T_Annu[$T_Classes[$loop]]["member"]); $loop2++) {
							echo $T_Annu[$T_Classes[$loop]]["member"][$loop2]."<br>";
						}
						echo "</blockquote>";
*/
					}
				} ## Fin DEBUG

				// Calcul d'un tableau differentiel de synchronisation
				for ($loop=0; $loop<count($T_Classes);$loop++) {
					$i=0;$k=0;
					// Cas des membres de la classe
					if ( count($T_Ressources[$T_Classes[$loop]]["memberUid"]) !=0) {
						for ($loop1=0;$loop1<count($T_Annu[$T_Classes[$loop]]["memberUid"]);$loop1++) {
							for ($loop2=0;$loop2<count($T_Ressources[$T_Classes[$loop]]["memberUid"]);$loop2++) {
								if  (preg_match(/$T_Annu[$T_Classes[$loop]]["memberUid"][$loop1]/,$T_Ressources[$T_Classes[$loop]]["memberUid"][$loop2]) ) {
									$found=true;
									break;
								}   else {  $found=false;  }
							}
							if ( !$found) {
								$T_diff[$T_Classes[$loop]]["memberUid"][$i]=$T_Annu[$T_Classes[$loop]]["memberUid"][$loop1] ;
								$i++;
							}
						}
					} elseif (count($T_Annu[$T_Classes[$loop]]["memberUid"]) !=0) {
						// Cas d'une classe creee sans membres dans la classe :(
						if ($DEBUG) echo gettext("Cas d'une classe creee sans membre dans la classe, nbr member : ").count($T_Annu[$T_Classes[$loop]]["memberUid"])."<br>";
						for ($loop1=0;$loop1<count($T_Annu[$T_Classes[$loop]]["memberUid"]);$loop1++) {
							$T_diff[$T_Classes[$loop]]["memberUid"][$loop1]=$T_Annu[$T_Classes[$loop]]["memberUid"][$loop1] ;
						}
					}

					// Cas des membres des equipes
/*
					if ( count($T_Ressources[$T_Classes[$loop]]["member"]) !=0) {
						for ($loop1=0;$loop1<count($T_Annu[$T_Classes[$loop]]["member"]);$loop1++) {
							for ($loop2=0;$loop2<count($T_Ressources[$T_Classes[$loop]]["member"]);$loop2++) {
								if  (preg_match(/$T_Annu[$T_Classes[$loop]]["member"][$loop1]/,$T_Ressources[$T_Classes[$loop]]["member"][$loop2]) ) {
									$found=true;
									break;
								} else {  $found=false;  }
							}
							if ( !$found) {
								$T_diff[$T_Classes[$loop]]["member"][$k]=$T_Annu[$T_Classes[$loop]]["member"][$loop1] ;
								$k++;
							}
						}
					} elseif (count($T_Annu[$T_Classes[$loop]]["member"]) !=0 ) {
						// Cas d'une classe creee sans membres dans l'equipe
						if ($DEBUG) echo gettext("Cas d'une classe creee sans membre dans l'equipe, nbr member : ").count($T_Annu[$T_Classes[$loop]]["member"])."<br>";
						for ($loop1=0;$loop1<count($T_Annu[$T_Classes[$loop]]["member"]);$loop1++) {
							$T_diff[$T_Classes[$loop]]["member"][$loop1]=$T_Annu[$T_Classes[$loop]]["member"][$loop1] ;
						}
					}
*/
				}
				if ($DEBUG) echo "DEBUG >> ".count ($T_diff[$T_Classes[$loop]]["member"])."<br>";

				// Fin Calcul du tableau differentiel de synchronisation
				// Verification si il y a necessite d'une synchro
				for ($loop=0; $loop<count ($T_Classes); $loop++)  {
					if ( count ($T_diff[$T_Classes[$loop]]["memberUid"]) > 0
                                             # || count ($T_diff[$T_Classes[$loop]]["member"]) > 0
						) { $synchro=true; } else $synchro=false;
					if ($synchro) break;
				}
				// [Phase 2] Synchronisation
				if ($synchro) {
					// Affichage du contenu du tableau differentiel de synchronisation
					echo "<p><u>".gettext("Une op&#233ration de synchronisation est n&#233cessaire sur")."</u> :<br><br>";
					for ($loop=0; $loop<count ($T_Classes); $loop++) {
						if ( count ($T_diff[$T_Classes[$loop]]["memberUid"]) > 0 || count ($T_diff[$T_Classes[$loop]]["member"]) > 0 ) {
						    echo "<table border=1px cellspacing=0 cellpadding=0  align=center width=70%>\n<tr>\n<td>\n<h3 style=\"text-align:center\">";
							echo   $T_Classes[$loop]."</h3>\n</td>\n</tr>\n<tr>\n<td width=50%>\n".gettext("Cr&#233;ation des r&#233;pertoire &#233;leves pour :")."</td>\n</tr>\n<tr>";
							if ( count ($T_diff[$T_Classes[$loop]]["memberUid"]) > 0 ) {
								echo "<td>";
								for  ($loop1=0; $loop1<count ($T_diff[$T_Classes[$loop]]["memberUid"]); $loop1++) {
									echo "&nbsp;".$T_diff[$T_Classes[$loop]]["memberUid"][$loop1]."<br>";
								}
								echo "</td>";
							} else echo "<td>".gettext("Pas d'&#233;l&#232;ves &#224; ajouter")."</td>";
/*
							if ( count ($T_diff[$T_Classes[$loop]]["member"]) > 0 ) {
								echo "<td>";
								for  ($loop2=0; $loop2<count ($T_diff[$T_Classes[$loop]]["member"]); $loop2++) {;
									echo "&nbsp;".$T_diff[$T_Classes[$loop]]["member"][$loop2]."<br>";
								}
								echo "</td>";
							} else echo "<td>".gettext("Pas de professeurs &#224 ajouter")."</td>";
*/
							echo "</tr></table><br>";
						}
					}
					// Fin Affichage du tableau differentiel de synchronisation
					// Fabrication du script admin.sh pour synchroniser les partages
					$commandes = "#!/bin/bash\n";
					$commandes .= "#".gettext("Script de synchronisation des partages Classes\n");
#					$commandes.="\n#".gettext("Application des acl posix pour les professeurs de l'equipe pedagogique\n");
					for ($loop=0; $loop<count($T_Classes); $loop++) {
						$folder_classe = "/var/se3/Classes/".$T_Classes[$loop];
/*
						for ($loop1=0; $loop1<count($T_diff[$T_Classes[$loop]]["member"]); $loop1++) {
							$prof = $T_diff[$T_Classes[$loop]]["member"][$loop1];
							$commandes.="setfacl -R -m d:u:$prof:rwx $folder_classe\n";
							$commandes.="setfacl -m u:$prof:rx $folder_classe\n";
							$commandes.="setfacl -R -m u:$prof:rwx $folder_classe/*\n";
							$commandes.="\n";
						}
*/
						if (count($T_diff[$T_Classes[$loop]]["memberUid"])>0) $commandes.="#".gettext("Creation des sous dossiers eleves\n");
						for ($loop1=0; $loop1<count($T_diff[$T_Classes[$loop]]["memberUid"]); $loop1++) {
							$eleve = $T_diff[$T_Classes[$loop]]["memberUid"][$loop1];
							$commandes.="mkdir $folder_classe/$eleve\n";
							$commandes.="chown admin:nogroup $folder_classe/$eleve\n";
							$commandes.="chmod 700 $folder_classe/$eleve\n";
							$commandes.="setfacl -m u:$eleve:rwx $folder_classe/$eleve\n";
							$commandes.="setfacl -m d:u:$eleve:rwx $folder_classe/$eleve\n";
							$commandes.="setfacl -m m::rwx $folder_classe/$eleve\n";
							$commandes.="\n";
						}
					}
					// mel CR de synchronisation des ressources Classes
					$Subject=gettext("[SE3 T&#226;che d'administration] Synchronisation des ressources Classes sur")." $stat_srv $cn_srv\n";
	 				list($user,$groups)=people_get_variables("admin", true);
					$mel_adm=$user["email"];
					$commandes.="\n#".gettext("Mel CR Synchronisation des ressources Classes sur")." $stat_srv $cn_srv\n";
					$commandes.="cat > /tmp/admind.tmp <<-EOF\n";
					$commandes.= gettext("La synchronisation des ressources Classes suivantes :\n");
					for ($loop=0; $loop<count ($T_Classes); $loop++) {
						if ( count ($T_diff[$T_Classes[$loop]]["memberUid"]) > 0 || count ($T_diff[$T_Classes[$loop]]["member"]) > 0 )
							$commandes.=$T_Classes[$loop]."\n";
						}
					$commandes.= gettext("sur le serveur")." $stat_srv $cn_srv ".gettext("a &#233;t&#233; effectu&#233;e avec succ&#232s.\n");
					$commandes.= "\n";
					$commandes.= "EOF\n";
					$commandes.= "mail -s \"$Subject\" $mel_adm < /tmp/admind.tmp\n";
					// Fin Fabrication du script admin.sh pour synchroniser les partages
					## DEBUG
					// Affichage du script admin.sh de synchro
					if ($DEBUG) {
						$tmp=$commandes;
						$tmp=preg_replace("/\n/","<br>",$tmp);
						echo $tmp."<br>";
					}## Fin DEBUG
					// Depot du script admin.sh sur le maitre
					$fp=@fopen("/var/remote_adm/tmp_".$stat_srv.".sh","w");
					if($fp) {
						fputs($fp,$commandes."\n");
						fclose($fp);
						chmod ("/var/remote_adm/tmp_$stat_srv.sh", 0600);
						// Si serveur maitre  renommage du script  tmp_master.sh en admin.sh
						if ($stat_srv == "maitre" ) {
							sleep (2);
							// Si pas de presence de admin.sh
							if ( !is_file("/var/remote_adm/admin.sh") ) {
								// Renommage et chmod +x du script sur le maitre
								rename ("/var/remote_adm/tmp_".$stat_srv.".sh", "/var/remote_adm/admin.sh");
								chmod ("/var/remote_adm/admin.sh", 0750);
								if (file_exists("/var/remote_adm/admin.sh")) {
									echo $info_2;
								} else {
									echo $alerte_3;
									// Dans ce cas la procedure de synchronisation est interrompue
									exit;
								}
							} else {
								// Message d'alerte  : Presence d'un admin.sh !!
								echo $alerte_1.$stat_srv."&nbsp;".$cn_srv.$alerte_2;
								// Dans ce cas la procedure de synchronisation est interrompue
								exit;
							}
						} else {
							// Si serveur esclave : scp de admin.sh sur le srv esclave
							//  Recherche de la presence d'un admin.sh sur le serveur esclave
							exec ("ssh -l remote_adm $ipHostNumber 'ls /var/remote_adm/admin.sh'", $AllOutput, $ReturnValue);
							// Si pas de presence de admin.sh sur l'esclave
							if (! $AllOutput[0]) {
								// Copie du script sur l'esclave avec scp
								exec ("scp /var/remote_adm/tmp_$stat_srv.sh remote_adm@$ipHostNumber:tmp_$stat_srv.sh", $AllOutput, $ReturnValue);
								// chmod +x , renommage du script bash
								exec ("ssh -l remote_adm  $ipHostNumber 'chmod +x /var/remote_adm/tmp_$stat_srv.sh;mv  /var/remote_adm/tmp_$stat_srv.sh /var/remote_adm/admin.sh'", $AllOutput, $ReturnValue);
								if ($ReturnValue==0) {
									echo $info_2;
									// Effacement de tmp_esclave.sh cr&#233;&#233; sur le maitre
									unlink ("/var/remote_adm/tmp_esclave.sh");
								} else {
									echo $alerte_3;
									// Dans ce cas la procedure de synchronisation est interrompue
									exit;
								}
							} else {
								// Message d'alerte : Presence d'un admin_esclave.sh !!
								echo $alerte_1.$stat_srv."&nbsp;".$cn_srv.$alerte_2;
								// Dans ce cas la procedure de synchronisation est interrompue
								exit;
							}
						}
					} else {
						echo $alerte_5;
					}
				} else {
					echo $info_3.$stat_srv."&nbsp;".$cn_srv.".";
				}
			} else {
				echo $alerte_4;
			}
		} else {
			echo $alerte_6;
		}
	}
}  // Fin is_admin
include ("pdp.inc.php");
?>
