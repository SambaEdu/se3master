<?php


   /**
   
   * Supprime un  parc
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe Tice academie de Caen 

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: parcs/
   * file: delete_parc.php

  */	



include "config.inc.php";
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
include "printers.inc.php";
include "fonc_parc.inc.php";

// traduction
require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');


$parc=$_POST['parc'];
$delparc=$_POST['delparc'];
$delete_parc=$_POST['delete_parc'];
$old_computers=$_POST['old_computers'];
$supprime_all=$_POST['supprime_all'];




/*******************************************************************/

if (is_admin("computers_is_admin",$login)=="Y") {
	//aide
	$_SESSION["pageaide"]="Gestion_des_parcs#Suppression_de_machines";
	// Titre
	echo "<h1>".gettext("Suppression de machine")."</h1>";

	// Affichage du formulaire de selection de parc
	if ((!isset($parc)) && ($delparc!=2)) {
		// La variable parc est vide on affiche une selecion
		// On retourne de choix :
		// delparc = 0 on efface des machines du parc
		// delparc = 1 on efface tout le parc
		echo "<H3>".gettext("S&#233;lection du parc &#224; modifier")."</H3>";
		$list_parcs=search_machines("objectclass=groupOfNames","parcs");
		if ( count($list_parcs)>0) {
			echo "<FORM method=\"post\" action=\"delete_parc.php\">\n"; 
			echo "<SELECT NAME=\"delparc\" SIZE=\"1\">\n";
			echo "<option value=\"0\">".gettext("Effacer des machines du parc\n"); 
			echo "<option value=\"1\">".gettext("Effacer le parc\n");
			echo "<option value=\"2\">".gettext("Effacer une machine sans parc\n"); 
			echo "</SELECT>\n";
			echo "<SELECT NAME=\"parc\" SIZE=\"1\">";
			echo "<option value=\"\">S&#233;lectionner\n";
			for ($loop=0; $loop < count($list_parcs); $loop++) {
				echo "<option value=\"".$list_parcs[$loop]["cn"]."\">".$list_parcs[$loop]["cn"]."\n";
			}
			echo "</SELECT>&nbsp;&nbsp;\n";
			echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
			echo "<u onmouseover=\"return escape".gettext("('<b>Effacer les machines d\'un parc</b> S&#233;lectionner un parc dans lequel vous souhaitez supprimer des machines.<br>La suppression de toutes les machines d\'un parc provoque la suppression du parc.<br><b>Effacer le parc</b> provoque la suppression du parc, mais pas la suppression des machines dans l\'annuaire.<br>Pour supprimer proprement les machines, vous devez aller dans Etat par nom de machine et s&#233;lectionner la machine &#224; supprimer.<br><b>Supprimer une machine sans parc</b> permet de supprimer compl&#233;tement une machine n\'appartenant &#224; aucun parc.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
			echo "</FORM>\n";
		}                     
	} else {
		// Suppression des machines sans parc
		// cas delparc = 2
		if ($delparc == "2") {
			// Filtrage des noms
			echo "<FORM action=\"delete_parc.php\" method=\"post\">\n";
			echo "<P>".gettext("Lister les noms contenant: ");
			echo "<INPUT TYPE=\"text\" NAME=\"filtrecomp\"\n VALUE=\"$filtrecomp\" SIZE=\"8\">";
			echo "<input type=\"hidden\" name=\"delparc\" value=\"2\">\n";
			echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
			echo "</FORM>\n";
			// On recherche les machines sans parc
			$list_computer=search_machines("(&(cn=*)(objectClass=ipHost))","computers");
			if ( count($list_computer)>0) {
				$form = "<form action=\"delete_parc.php\" method=\"post\">\n";
				$form.="<p>".gettext("S&#233;lectionnez les machines sans parc &#224; supprimer : ")."</p>\n";
				$form.="<p><select size=\"".$size."\" name=\"supprime_all[]\" multiple=\"multiple\">\n";
				for ($loopa=0; $loopa < count($list_computer); $loopa++) {
					if($list_computer[$loopa]["cn"]!=$netbios_name) {
						$exist_parc = search_parcs($list_computer[$loopa]["cn"]);
						// Si pas de parc on affiche
						if (($exist_parc[0]["cn"])=="") {
							$mpenc=$list_computer[$loopa]['cn']; 
							// Filtrage selon critere
							if ("$filtrecomp"=="") {
								$form .= "<option value=".$mpenc.">".$mpenc;
							} else {
								//$lmloop=0;
								$mpcount=count($mpenc);
								$mach=$mpenc;
								if (preg_match("/$filtrecomp/",$mach)) {
									$form .= "<option value=".$mpenc.">".$mpenc;
								}
							}
						}
					}
				}
				$form.="</select></p>\n";
				$form.="<input type=\"hidden\" name=\"delparc\" value=\"0\">\n";
				$form.="<input type=\"hidden\" name=\"parc\" value=\"000001\">\n";
				$form.="<input type=\"hidden\" name=\"delete_parc\" value=\"true\">\n";
				$form.="<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
				$form.="</form>\n";
				echo $form;
			}	
			// On quitte
			include ("pdp.inc.php");
			exit;
		}
		// Affichage du formulaire de suppression des machines du parc
		// la variable parc n'est pas vide
		// delparc = 0 
		// On propose les machines a supprimer qui existent dans ce parc
		if ((!$delete_parc )&&($delparc!="1")) {
			// Filtrage des noms
			echo "<FORM action=\"delete_parc.php\" method=\"post\">\n";
			echo "<P>".gettext("Lister les noms contenant: ");
			echo "<INPUT TYPE=\"text\" NAME=\"filtrecomp\"\n VALUE=\"$filtrecomp\" SIZE=\"8\">";
			echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\">\n";
			echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
			echo "</FORM>\n";
			// Lecture des membres du parc
			$mp_all=gof_members($parc,"parcs",1);
			// Filtrage selon critere
			if ("$filtrecomp"=="") {
				$mp=$mp_all;
			} else {
				$lmloop=0;
				$mpcount=count($mp_all);
				for ($loop=0; $loop < count($mp_all); $loop++) {
					$mach=$mp_all[$loop];
					if (preg_match("/$filtrecomp/",$mach)) $mp[$lmloop++]=$mach;
				}
			}
			if ( count($mp)>15) $size=15; else $size=count($mp);
			if ( count($mp)>0) {
				$form = "<form action=\"delete_parc.php\" method=\"post\">\n";
				$form.="<p>".gettext("S&#233;lectionnez les machines &#224; enlever du parc : ")."$parc</p>\n";
				$form.="<p><select size=\"".$size."\" name=\"old_computers[]\" multiple=\"multiple\">\n";
				echo $form;
				for ($loop=0; $loop < count($mp); $loop++) {
					echo "<option value=".$mp[$loop].">".$mp[$loop];
				}
				$form="</select></p>\n";
				// Si il ne reste qu'une seule machine
				if ( count($mp)==1) {
					$form.=gettext("Si vous supprimez la derni&#232;re machine, le parc sera supprim&#233;.");
					$form.="<br>";	
					$form.="<input type=\"hidden\" name=\"delparc\" value=\"1\">\n";
				}
				$form.="<input type=\"hidden\" name=\"delete_parc\" value=\"true\">\n";
				$form.="<input type=\"hidden\" name=\"parc\" value=\"$parc\">\n";
				$form.="<input type=\"reset\" value=\"".gettext("R&#233;initialiser la s&#233;lection")."\">\n";
				$form.="<input type=\"submit\" value=\"".gettext("Valider")."\" onclick= \"return getconfirm();\">\n";
				$form.="</form>\n";
				echo $form;
			} else {
				$message =  gettext("Il n'y a pas de machines &#224; supprimer !");
				echo "<br><br>";
				echo $message;
			}
		} else { 
			// Si on veut supprimer tout le parc sans supprimer les machines de l'annuaire
			// Il faut delparc = 1 et parc non vide
			if ($delparc=="1")  {
				if ($parc=="") {
					echo "Vous devez s&#233;lectionner un parc !";
					echo "<br><br><CENTER>";
					echo "<a href=\"delete_parc.php\">Retour</A>";
					echo "</CENTER>";
					exit;
				} else {	
					// Suppression du parc 
					echo "<H3>".gettext("Suppression du parc")." <U>$parc</U></H3>";             
					$cDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn; 
					exec ("/usr/share/se3/sbin/entryDel.pl \"$cDn\"");
					// on la vire d'italc
					// On relance le script
					exec ("/usr/bin/sudo /usr/share/se3/scripts/italc_generate.sh");
					
					echo "<BR>";
					echo gettext("Le template de ce parc n'a pas &#233;t&#233; supprim&#233;.");
					echo "<BR>";
					echo gettext("Vous devez le faire &#224; la main");
                                        echo "<br><br><CENTER>";
					echo "<a href=\"delete_parc.php\">Retour</A>";
					echo "</CENTER>";
				}
			} else {
				// Suppression des machines dans le parc
				// Il faut parc non vide
				// Si le nombre de machine a supprimer = nombre de machine dans le parc on supprime 
				// aussi le parc
				if ($parc=="000001") {
					echo "<H3>".gettext("Suppression de machine(s) sans parc")."</H3>";
					echo "<P>".gettext("Vous avez s&#233;lectionn&#233; "). count($supprime_all).gettext(" machine(s)")."<BR>\n";
				} else {
					echo "<H3>".gettext("Suppression de machines dans le parc")." <U>$parc</U></H3>";
					echo "<P>".gettext("Vous avez s&#233;lectionn&#233; "). count($old_computers).gettext(" machine(s)")."<BR>\n";
				}
				// On compte si la demande ne porte pas sur toutes les machines
				$mp_all=gof_members($parc,"parcs",1);
				$mpcount=count($mp_all);
				// Si la demande porte sur toutes les machines du parc
				// On vire le parc
				if ($mpcount == count($old_computers)) {
					if($parc!="000001") { // cas des machines sans parc	
						echo "<H3>".gettext("Suppression du parc")." <U>$parc</U></H3>";             
						echo gettext("Vous avez demand&#233; &#224; supprimer toutes les machines du parc $parc");
						echo "<br><br>";
						echo gettext("le parc sera aussi supprim&#233;");
						$cDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn; 
						exec ("/usr/share/se3/sbin/entryDel.pl \"$cDn\"");

						// On vire pour italc
						exec ("/usr/bin/sudo /usr/share/se3/scripts/italc_generate.sh");
						// on reconstruira a la fin
						//	exec ("/usr/share/se3/sbin/printers_group.pl");
					}
				} else {
					// on extrait les machines a virer
					for ($loop=0; $loop < count($old_computers); $loop++) {
						$computer=$old_computers[$loop];
						if($computer==$netbios_name) {
							echo "<span style='color:red'>On ne supprime pas le serveur SE3 lui-même&nbsp;: $netbios_name</span><br />\n";
						}
						else {
							// On verifie si ce n'est pas une imprimante
							$resultat=search_imprimantes("printer-name=$computer","printers");
							$suisje_printer="non";
							for ($loopp=0; $loopp < count($resultat); $loopp++) {
								if ($computer==$resultat[$loopp]['printer-name']) {
									$suisje_printer="yes";	
									continue;
								}	
							}
							$pDn = "cn=".$parc.",".$parcsRdn.",".$ldap_base_dn;
							if ($suisje_printer=="yes") {
								// je suis une imprimante
								echo gettext("Suppression de l'imprimante")." $computer ".gettext("du parc")." <U>$parc</U><BR>\n";
										$cDn = "cn=".$computer.",".$printersRdn.",".$ldap_base_dn;
							} else {
								// je suis un ordinateur
								echo gettext("Suppression de l'ordinateur")." $computer ".gettext("du parc")." <U>$parc</U><BR>\n";
								$cDn = "cn=".$computer.",".$computersRdn.",".$ldap_base_dn;
								// Test la machine prof pour italc
								$machine_prof=search_description_parc("$parc");
								if($computer==$machine_prof) {
									echo "<br>Attention : vous ne disposez plus de machine professeur pour le parc $parc";
									    modif_description_parc ($parc,"0");
								}
							}
							// on supprime
							exec ("/usr/share/se3/sbin/groupDelEntry.pl \"$cDn\" \"$pDn\"");

							// Modif pour italc
							exec ("/usr/bin/sudo /usr/share/se3/scripts/italc_generate.sh");
							echo "<br />";
						}
					}
				}
				// si demande de suppression complete
				if (count($supprime_all)>0) {
					// On teste si la machine appartient a d'autres parcs
					// Si oui il faut verifier que cela n'implique pas la suppression de l'autre parc.
					for ($loopa=0; $loopa < count($supprime_all); $loopa++) {
						if($computer==$netbios_name) {
							echo "<span style='color:red'>On ne supprime pas le serveur SE3 lui-même&nbsp;: $netbios_name</span><br />\n";
						}
						else {
							$computer=$supprime_all[$loopa];
							// On verifie si ce n'est pas une imprimante
							$resultat=search_imprimantes("printer-name=$computer","printers");
							$suisje_printer="non";
							for ($loopp=0; $loopp < count($resultat); $loopp++) {
								if ($computer==$resultat[$loopp]['printer-name']) {
									$suisje_printer="yes";	
									continue;
								}
							}	
							if ($suisje_printer=="yes") {
								echo "<h3>".gettext("Avertissement")."</h3>";
								echo "<br>";
								echo "$computer ";
								echo gettext("est une imprimante.");
								echo "<br>";
								echo gettext("Vous devez passer par le menu imprimante pour la supprimer d&#233;finitivement");
							} else {
								// on a bien une machine, on peut la supprimer
								// On cherche d'abord si elle appartient pas a un autre parc.
								$list_parcs=search_machines("(&(member=cn=$computer,$computersRdn,$ldap_base_dn)(objectClass=groupOfNames))","parcs");
								if (count($list_parcs)>0) {
									echo "<br>";
									echo "<h3>".gettext("Suppression des autres parcs")."</h3>";
									echo "<br>";
									for ($loop=0; $loop < count($list_parcs); $loop++) {
										echo "Suppression du parc : ";
										$parc = $list_parcs[$loop]["cn"];	
										supprime_machine_parc($computer,$parc);
										echo $parc;
										echo "<BR>";
										// Test la machine prof pour italc
										$machine_prof=search_description_parc("$parc");
										if($computer==$machine_prof) {
											echo "Attention : vous ne disposez plus de machine professeur pour le parc $parc";
											    modif_description_parc ($parc,"0");
										}
									}
								}
								// Puis enfin on supprime la machine elle meme de l'annuaire
								echo "<h3>".gettext("Suppression compl&#233;te de ")." $computer ".gettext("de l'annuaire")."</h3>";
								// Nettoyage de l'inventaire
								echo "Suppression de l'inventaire";
								echo "<br>";
								suppr_inventaire($computer);
								// voir si on doit pas nettoyer les connexions
								// Vire dans le dhcp
								// On teste si la table existe
								/*
								$exec = mysql_query("SHOW TABLES FROM `se3db` LIKE 'se3_dhcp'");
								$tables =array();
								while($row = mysql_fetch_row($exec)) {
									$tables[] = $row[0];
								}
								if(in_array('se3_dhcp',$tables)){
									$dhcp_ok = 1;
								}
								if ($dhcp_ok==1) {
								*/
								if ($dhcp=="1") {
									echo "Suppression du dhcp";
									echo "<br>";
									$suppr_query = "DELETE FROM `se3_dhcp` where `name` = '$computer'";
									mysqli_query($GLOBALS["___mysqli_ston"], $suppr_query);
									// On relance dhcp si celui-ci est active.
									exec("sudo /usr/share/se3/scripts/makedhcpdconf",$ret);
								}
						
								// La virer de wpkg 
								    echo "Suppression des rapports wpkg";
								echo "<br>";
								$rapport_computer="/var/se3/unattended/install/wpkg/rapports/".$computer.".txt";
								$log_computer="/var/se3/unattended/install/wpkg/rapports/".$computer.".log";
								if(file_exists($rapport_computer)) { @unlink($rapport_computer); }
								if(file_exists($log_computer)) { @unlink($log_computer);}
							
								// On relance le script pour italc
								echo "Suppression d'italc";
								exec ("/usr/bin/sudo /usr/share/se3/scripts/italc_generate.sh");

							
								exec ("/usr/share/se3/sbin/entryDel.pl cn=$computer,".$dn["computers"],$output,$returnval);
								exec ("/usr/share/se3/sbin/entryDel.pl uid=$computer$,".$dn["computers"]);
								exec("/usr/bin/touch /tmp/csvtodo",$ret);
								exec("sudo /usr/share/se3/sbin/update-csv.sh",$ret);
							}
						}
					}
				}
				// Suppression des delegations sans parc
				echo "<H3>".gettext("Suppression des d&#233;l&#233;gations sans parc")."</H3>";
				nettoie_delegation();
				// NJ 10-2004 reconstruction des partages imprimantes
				exec ("/usr/share/se3/sbin/printers_group.pl");
				// Lance le script de mise a jour pour wpkg
				update_wpkg();
                                echo "<br><br><CENTER>";
				echo "<A HREF=\"show_parc.php?parc=$parc\">Retour</A>";
                                //echo "<a href=\"delete_parc.php\">Retour</A>";
				echo "</CENTER>";
			}
		}
	}
}
include ("pdp.inc.php");
?>
