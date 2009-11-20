<?php

/* $Id$ */

/* 
Page qui retourne à Ajax le résultat
Philippe Chadefaux
*/

session_start();
$_SESSION["loggeduser"]="$login";

include_once('Sajax.php');
include_once('JSON.php');
//include('config.inc.php');
include('ldap.inc.php');
include('fonc_outils.inc.php');
include "printers.inc.php";
include ("fonction_backup.inc.php");

include "fonc_js.inc.php";


/****************** Traitement *****************************************/
function return_parc($parc,$type,$etat) {

	// Restart ou stop backuppc
	if ($etat=="img_only" && $type=="backuppc_etat") {
		
		// Test l'etat du serveur de sauvegarde
		if (EtatBackupPc ()== "1") {
	        	stopBackupPc();
			$img_dep="../elements/images/disabled.png";
			$affiche_result="";
		} else {
			startBackupPc();
			$img_dep="../elements/images/enabled.png";
			$affiche_result="";
		}
	}			
		
	// Suppression d'une machine d'un parc
	if ($etat=="del" && $type!="") {
  		move_computer_parc($parc,$type);
		$_SESSION['parc_all'][$parc]="0";
		$type="parcs";
  	}

	// Suppression d'une machine complétement (sauf de l'inventaire)
	if ($etat=="del_computer" && $type!="") {
		include "config.inc.php";
		exec ("/usr/share/se3/sbin/entryDel.pl cn=$type,".$dn["computers"],$output,$returnval);
                exec ("/usr/share/se3/sbin/entryDel.pl uid=$type$,".$dn["computers"]);
		$_SESSION['affichage']['Stations']="0";
		$type="computers";
	}				

	// Suppression d'un parc
	if ($etat=="del_parc" && $parc != "") {
		move_parc($parc);
		$_SESSION['affichage']['Parc']="0";
		$parc="all_parc";
	}	


	// Lance cups
	if ($type=="cups" && $etat=="img_only") {
		if (test_cups()=="1") {	
			start_cups();	
			$img_dep = "../elements/images/disabled.png";
			$parc="cups";
			$affiche_result="";
		} else {
       			start_cups();
			$img_dep = "../elements/images/enabled.png";
			$parc="cups";
			$affiche_result="";
		}
	}
	
	// Lance les imprimantes

	if ($etat=="printer_etat") {
		$sys=="";	
  		$sys= exec("/usr/bin/lpstat -p $mpenc | grep enabled");
		if ($sys != "") {
			$status="enable";
		} else {
			$status="disable";
		}	
		stop_start_printer($mpenc,$status);
		$parc="printer";
		$type="printer";
		$_SESSION['affichage']['Imprimantes']="0";
	}


	if ($etat=="printer_etat_parc") {
		$sys=="";	
  		$sys= exec("/usr/bin/lpstat -p $mpenc | grep enabled");
		if ($sys != "") {
			$status="enable";
		} else {
			$status="disable";
		}	
		stop_start_printer($mpenc,$status);
		$parc="printer";
		$type="parcs";
		$_SESSION['affichage']['Parc']="0";
		
		$_SESSION['parc_all'][$parc]="0";
	}

	// Suppression d'une imprimante d'un parc
	if ($etat=="del_printer_parc" && $parc != "") {
		move_printer_parc($parc,$type);
		$_SESSION['affichage']['Parc']="0";
		$type="parcs";
		$_SESSION['parc_all'][$parc]="0";
	}

	// Suppression définitive d'une imprimante
	if ($etat=="del_printer") {
		move_printer($type);
		$_SESSION['affichage']['Imprimantes']=0; 
		$parc="printer";
		$type="printer";
	}

	// Suppression définitive d'une sauvegarde
	if (($etat=="del_sauvegarde") && ($type!="")) {
		$_SESSION['affichage']['Sauvegarde']=0; 
		$rep = "/etc/backuppc/";
        	$file = $rep.$type.".pl";
	        if (file_exists($file)) { // On detruit le fichier de conf de cette machine
			@unlink($file);
		}
		if (HostExist($type)) {
			DeleteHost($type);
		        reloadBackuPpc();
		}
	        DeleteRep($type);
		$type="sauvegarde";
	}

/*********************** Fin des fonctions **********************************************************/	
	/* 
	On recherche les parcs existant
	*/
	if ($parc=="all_parc") {

		if($_SESSION['affichage']['Parc']=="0") {	
			$parc = "all_parc";
       			$affiche_result = "";
  			$list_parcs=search_machines("objectclass=groupOfNames","parcs");
  			if ( count($list_parcs)>0) {
				for ($loop=0; $loop < count($list_parcs); $loop++) {
			
					if ($color_parc=="#E0EEEE") { $color_parc="#B4CDCD"; } else {$color_parc="#E0EEEE"; }
					if ($color_parc=="") { $color_parc="#B4CDCD"; }

	
					$parc = $list_parcs[$loop]["cn"];
					$imgdep = "img_".$parc;
					$affiche_result .= "<tr bgcolor=\"$color_parc\"><td valign=bottom>&nbsp;&nbsp;";
					$affiche_result .=  "<IMG style=\"border: 0px solid ;\" SRC=\"../elements/images/plus.png\" ID=\"".$imgdep."\" onClick=\"return_list('$parc','parcs','affiche')\" />&nbsp;";
	 	 			$affiche_result .=  $list_parcs[$loop]["cn"];	  

	  				$affiche_result .=  "</td>\n";


					/* Menu pour les parcs */
					
					// Ajouter une machine dans le parc	
					$affiche_result .= "<td align=center><span id=\"2\" title=\"Ajouter une machine dans le parc\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/computer.png\" onclick=\"popuprecherche('../parcs/create_parc.php?parc=$parc','popuprecherche','width=500,height=500');\"></span></td>";
					
					// Ajouter une imprimante dans le parc
					$affiche_result .= "<td align=center><span id=\"2\" title=\"Ajouter une imprimante dans le parc\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/printer.png\" onclick=\"popuprecherche('../printers/add_printer.php?parc=$parc','popuprecherche','width=500,height=500');\"></span></td>\n";
	
					// Controler les machines du parc
					$affiche_result .= "<td align=center><span id=\"2\" title=\"Contr&#244;ler les machines du parc\"><img width=\"15\" height=\"15\"  height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/chronometer.png\" onclick=\"popuprecherche('../parcs/action_parc.php?parc=$parc&action=detail','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>\n";

					// Gestion des template	
					$affiche_result .= "<td align=center><span id=\"2\" title=\"Gestion des templates\"><img width=\"15\" height=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/gpo.png\" onclick=\"popuprecherche('../registre/affiche_restrictions.php?salles=$parc','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>\n";
					
					// Délégué ce parc
			//		$affiche_result .= "<td align=center><span id=\"2\" title=\"D#&233l#&233guer ce parc\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/delegueparc.png\" onclick=\"popuprecherche('../parcs/delegate_parc.php?action=new&salles=$parc','popuprecherche','width=500,height=500,scrollbar=yes');\"></span></td>\n";

					// Envoyer un popup
					$affiche_result .= "<td align=center><span id=\"2\" title=\"Envoyer un message aux machines de ce parc\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/edit.png\" onclick=\"popuprecherche('../popup/index.php?parc=$parc','popuprecherche','width=500,height=500');\"></span></td>\n";
			

					// Supprimer un parc

					$mp_all=gof_members($parc,"parcs",1);
                			$mpcount=count($mp_all);
					// Si il y a encore des objets dans le parc on ne peut le supprimer
					if ($mpcount > 0) {
						$affiche_result .= "<td align=center><span id=\"2\" title=\"Supprimer ce parc\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/edittrash.png\"  onClick=\"alert('Impossible de supprimer ce parc. Vous devez d\'abord supprimer les objets qu\'il contient.');\"></span></td>\n";
					} else {
						$affiche_result .= "<td align=center><span id=\"2\" title=\"Supprimer ce parc\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/edittrash.png\"  onClick=\"confirm_del('$parc','parc','del_parc');\"></span></td>\n";
					}

			
					$affiche_result .= "</tr>\n";
					
					// si on veut faire afficher les machines du parc	
					$affiche_result .= "<tr  border=0 width=\"100%\"><td colspan=\"8\">\n";
					$affiche_result .= "<table  ID=\"".$parc."\"  border=0 width=\"100%\">\n";
					$affiche_result .= "</table>\n";
					$affiche_result .= "</td></tr>\n";


  				}
			}

		
			$_SESSION['affichage']['Parc']="1"; 
			$img_dep = "../elements/images/moins.png";

			$parc = "all_parc";
		} else { // Pour fermer
			$_SESSION['affichage']['Parc']="0";
			$affiche_result = "";
			$img_dep = "../elements/images/plus.png";

			$parc = "all_parc";
  		}	

	}	

	// Affiche les machines dans le parc
 	if ($type=="parcs") {
  		if($_SESSION['parc_all'][$parc]=="0") {	
  			$_SESSION['parc_all'][$parc]="1"; 
			$mp_all=gof_members($parc,"parcs",1);
        
			// Filtrage selon critère
       			if ("$filtrecomp"=="") $mp=$mp_all;
       			else {
                		$lmloop=0;
                		$mpcount=count($mp_all);
                		for ($loopi=0; $loopi < count($mp_all); $loopi++) {
                        		$mach=$mp_all[$loopi];
	                		if (ereg($filtrecomp,$mach)) $mp[$lmloop++]=$mach;
                		}
			}
	
	
			if ( count($mp)>15) $size=15; else $size=count($mp);
			if ( count($mp)>0) {
       				sort($mp);
       				$affiche_result = "";
       				for ($loopj=0; $loopj < count($mp); $loopj++) {
     	 				$imp=0;
	 				if ($color=="#E0EEEE") { $color="#B4CDCD"; } else {$color="#E0EEEE"; }
	 				if ($color=="") { $color="#B4CDCD"; }
               				$mpenc=urlencode($mp[$loopj]);
			 
	 				// test si on a une imprimante
					// $resultat=search_imprimantes ("printer-name=$mp[$loopj]","printers");
			
					// Verifie si la machine n'est pas en maintenance 
					
					$inventaire_act=inventaire_actif();
					if($inventaire_act=="1") {					
						$Panne = testMaintenance($mpenc);
          					if ($Panne == "2") { $color="FF7D40"; }
						elseif ($Panne == "1") { $color="EE2C2C"; }
						elseif ($Panne == "0") { $color="FFD700"; }
					}
					$resultat=search_printers("printer-name=*");
					for ($loopp=0; $loopp < count($resultat); $loopp++) {
		   				if ($mpenc==$resultat[$loopp]['printer-name']) {

							// test si l'imprimante est active
							$sys="";
				   			$sys= exec("/usr/bin/lpstat -p $mpenc | grep enabled");
		   					if ($sys != "") {
								$etat_printer="Activée";
								$icone_printer="printer.png";
								$title_printer=gettext("Activer l'imprimante");
		   					} else {
								$etat_printer="Désactivée";
								$icone_printer="printer_r.png";
								$title_printer=gettext("Stopper l'imprimante");
		   					}
			  	 
  		   					$options = array(etat_printer => $etat_printer);
	  					
 							$affiche_result .= "<tr bgcolor=$color><td valign=\"bottom\">&nbsp;&nbsp;&nbsp;&nbsp;<IMG  style=\"border: 0px solid ;\" SRC=\"../elements/images/$icone_printer\" title=\"$title_printer\"  ALT=\"Printer\" ID=\"img_printer_etat\" onClick=\"return_list('$parc','$mpenc','printer_etat_parc');\">&nbsp;&nbsp;";
	  						$affiche_result .= $mp[$loopj]."</u></td><td>".$resultat[$loopp]['printer-location']." (".$resultat[$loopp]['printer-info'].")</td>\n";	  
					
							$printer=$list_imprim[$loopim]['printer-name'];
							
							// Stop ou start l'imprimante
//							$affiche_result .= "<td align=center><span id=\"2\" title=\"Activer ou d#&233sactiver l'imprimante\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/enabled.png\"  onClick=\"return_list('$parc','$mpenc','start_printer');\"></span></td>\n";
$affiche_result .= "<td colspan=\"2\"></td>";								
							// Voir les travaux en cours
							$affiche_result .= "<td align=center><span id=\"2\" title=\"Travaux en cours\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/travaux.png\" onclick=\"popuprecherche('../printers/printer_jobs.php?printer=$printer','popuprecherche','width=500,height=500');\"></span></td>\n";

							// Sortir l'imprimante du parc
							$affiche_result .= "<td align=center><span id=\"2\" title=\"Sortir l'imprimante du parc\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/edittrash.png\"  onClick=\"confirm_del('$parc','$mpenc','del_printer_parc');\"></span></td>\n";

					//		$affiche_result .= "<td>&nbsp;</td>";	
							$affiche_result .= "</tr>\n";
							$imp=1;
			   			}
					}   
			
					// Si ce n'est pas une imprimante
					if ($imp!=1) {

						$icone="computer.png";
						if($inventaire_act=="1") { // Si l'inventaire est active
							$retourOs=type_os($mpenc);
							if($retourOs == "0") { $icone="computer.png"; }
							elseif($retourOs == "Linux") { $icone="linux.png"; }
							elseif($retourOs == "XP") { $icone="winxp.png"; }
							elseif($retourOs == "98") { $icone="win.png"; }
							else { $icone="computer.png"; }
							
							$retour_inventaire=der_inventaire($mpenc);
							if($retour_inventaire=="0") {
								$retour_inventaire=gettext("Pas d'inventaire");
							}
						} else {$retour_inventaire=gettext("Pas d'inventaire"); }	
						


						$affiche_result .= "<tr bgcolor=\"$color\"><td valign=\"bottom\">&nbsp;&nbsp;&nbsp;&nbsp;";
			 			$affiche_result .=  "<span id=\"2\" title=\"$retour_inventaire\"><IMG  style=\"border: 0px solid ;\" SRC=\"../elements/images/$icone\" ALT=\"Station\" \"></span>";
	  					$affiche_result .=  "&nbsp;&nbsp;";
	 	 				$affiche_result .= $mp[$loopj]; 
						

        		                        $affiche_result .= "</td>\n";
					
						$ip_machine=avoir_ip($mpenc);
						if($ip_machine=="0") { $ip_machine=""; } 
						$affiche_result .= "<td>$ip_machine</td>\n";

				
						
						// Voir les connexions	
						$affiche_result .= "<td align=center><span id=\"2\" title=\"Voir les connexions sur cette machine\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/connect.gif\" onclick=\"popuprecherche('../parcs/show_machine.php?mpenc=$mpenc','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>\n";
									
						if($inventaire_act=="1") {					
							// Inventaire 
							$sessid=session_id();
							$systemid=avoir_systemid($mpenc);
							$affiche_result .= "<td align=center><span id=\"2\" title=\"Inventaire\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/inventaire.png\" onclick=\"popuprecherche('../ocsreports/machine.php?sessid=$sessid&systemid=$systemid','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>";

							// Ajout en maintenance
							$affiche_result .= "<td align=center><span id=\"2\" title=\"D&#233clarer une demande de maintenance\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/ack.gif\" onclick=\"popuprecherche('../ocsreports/maintenance.php?mpenc=$mpenc&action=ajout','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>\n";
						}
						
						// Supprimer une machine du parc
						$affiche_result .= "<td align=center><span id=\"2\" title=\"Sortir la machine du parc\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/edittrash.png\"  onClick=\"confirm_del('$parc','$mpenc','del');\"></span></td>";
						$affiche_result .= "</tr>\n";
                        	 	}
        			}

			}
			
			$img_dep = "../elements/images/moins.png";
		} else { // Pour fermer
			$_SESSION['parc_all'][$parc]="0";
			$affiche_result = "";
			$img_dep = "../elements/images/plus.png";
  		}	
	
	} // fin de si parc

//**********************************************************************************//
	// Imprimantes

	if ($type=="printer") {

		 if ($_SESSION['affichage']['Imprimantes']==0)  { 
			$parc="printer";
			$affiche_result = "";
			$list_imprim=search_printers("printer-name=*");
			$loopim=0;
			if ( count($list_imprim)>0) { //si on a des imprimantes avec ou sans parc
	  			for ($loopim=0; $loopim < count($list_imprim); $loopim++) { // liste imprimante 1 par 1
	  				$imp_parc=0;
					$looppa=0;
					$list_parcs=search_machines("objectclass=groupOfNames","parcs");
	 				if ( count($list_parcs)>0) { // si il existe des parcs
		        			for ($looppa=0; $looppa < count($list_parcs); $looppa++) {
							$parc=$list_parcs[$looppa]["cn"];
							$mp=gof_members($parc,"parcs",1); //lecture les membres du parc
							if (count($mp)>0) {
								for ($loopmp=0; $loopmp < count($mp); $loopmp++) {
									$comp=trim($mp[$loopmp]);
									$print=trim($list_imprim[$loopim]['printer-name']);
									if ("$comp" == "$print") { // ok
										$imp_parc=1;
									}			
				        			}
				     			}	
		        			}
		   
		   				// test si l'imprimante est active
		   				$sys="";
		   				$sys= exec("/usr/bin/lpstat -p $print | grep enabled");
		   		
						if ($sys != "") {
							$status="disable";
							$etat_printer="Activée";
							$icone_printer="printer.png";
			   			} else {
							$status="enable";
							$etat_printer="Désactivée";
							$icone_printer="printer_r.png";
		   				}
					      
  		   				$options = array(etat_printer => $etat_printer);
		   
			   			if($imp_parc=="0") { // pas de parc
 							$color = "#CCCCCC";
 							$affiche_result .= "<tr bgcolor=$color><td valign=\"bottom\">&nbsp;&nbsp;&nbsp;&nbsp;<IMG  style=\"border: 0px solid ;\" SRC=\"../elements/images/$icone_printer\" title=\"Activer l'imprimante\"  ALT=\"Printer\" ID=\"img_printer_etat\" onClick=\"return_list('printer','$printer','printer_etat');\">&nbsp;&nbsp;";
							$affiche_result .= "<font color=\"#000000\">";
							$affiche_result .= $list_imprim[$loopim]['printer-name'];
							$affiche_result .= " (Sans parc)";
							$affiche_result .= "</font></td><td>".$list_imprim[$loopim]['printer-location']." (".$list_imprim[$loopim]['printer-info'].")</td>\n";
						} else { // on affiche les imprimantes  ayant un parc
	
							if ($color=="#E0EEEE") { $color="#B4CDCD"; } else {$color="#E0EEEE"; }
			 				if ($color=="") { $color="#B4CDCD"; }
        						$affiche_result .= "<tr bgcolor=$color><td  width=\"12\" valign=\"bottom\">&nbsp;&nbsp;&nbsp;&nbsp;<IMG  style=\"border: 0px solid ;\" SRC=\"../elements/images/$icone_printer\" title=\"Stopper l'imprimante\"  ALT=\"Printer\" ID=\"img_printer_etat\"  onClick=\"return_list('printer','$printer','printer_etat');\">&nbsp;&nbsp;";
							$affiche_result .= "<font color=\"#000000\">";
							$affiche_result .= $list_imprim[$loopim]['printer-name'];
							$affiche_result .= "</font></u></td><td>".$list_imprim[$loopim]['printer-location']." (".$list_imprim[$loopim]['printer-info'].")</td>\n";
						}
						
						$printer=$list_imprim[$loopim]['printer-name'];
						
						// Voir les travaux en cours
						$affiche_result .= "<td align=center><span id=\"2\" title=\"Travaux en cours\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/travaux.png\" onclick=\"popuprecherche('../printers/printer_jobs.php?printer=$printer','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>";

						// Ajouter une imprimante dans le parc
						$affiche_result .= "<td align=center><span id=\"2\" title=\"Ajouter l'imprimante dans un parc\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/parc.png\" onclick=\"popuprecherche('../printers/add_printer.php?add_print=true&printer=$printer','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>";

						// Supprimer l'imprimante
						$affiche_result .= "<td align=center><span id=\"2\" title=\"Supprimer l'imprimante\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/edittrash.png\"  onClick=\"confirm_del('printer','$printer','del_printer');\"></span></td>";

						$affiche_result .= "</tr>";
			 		}
	  			}

	  
			}

		 	$_SESSION['affichage']['Imprimantes']="1"; 
			$img_dep = "../elements/images/moins.png";
			$parc="printer";
		} else { // Pour fermer
			$_SESSION['affichage']['Imprimantes']="0";
			$affiche_result = "";
			$img_dep = "../elements/images/plus.png";
  		}	

	 } //fin imprimantes

//***************************************************************************************//
	// Computers
	if ($type=="computers") {
		$affiche_result = "";
		$Panne = "";
		if ($parc=="all") { $_SESSION['affichage']['Stations']=0; } // Si on veut toutes les machines
		if ($_SESSION['affichage']['Stations']==0) { 
			$list_computer=search_machines("(&(cn=*)(objectClass=ipHost))","computers");
			if ( count($list_computer)>0) {

				for ($loopa=0; $loopa < count($list_computer); $loopa++) {
					$exist_parc = search_parcs($list_computer[$loopa]["cn"]);
					if ($exist_parc[0]["cn"] == "") {
						$color="#CCCCCC";
						$computer_parc="no";
					} else {
			 			if ($color=="#E0EEEE") { $color="#B4CDCD"; } else {$color="#E0EEEE"; }
			 			if ($color=="") { $color="#B4CDCD"; }
						if ($color=="#CCCCCC") { $color="#E0EEEE"; }
						$computer_parc="yes";
					}
					
					
					$mpenc=$list_computer[$loopa]['cn'];

					$icone="computer.png";
					$inventaire_act=inventaire_actif();
					if($inventaire_act=="1") {					
						// Verifie si la machine n'est pas en maintenance
						$Panne = testMaintenance($mpenc);
						if ($Panne == "2") { $color="FF7D40"; }
						elseif ($Panne == "1") { $color="EE2C2C"; }
						elseif ($Panne == "0") { $color="FFD700"; }
						
						// Type d'icone en fonction de l'OS
						$retourOs = type_os($mpenc);
						if($retourOs == "0") { $icone="computer.png"; }
						elseif($retourOs == "Linux") { $icone="linux.png"; }
						elseif($retourOs == "XP") { $icone="winxp.png"; }
						elseif($retourOs == "98") { $icone="win.png"; }
						else { $icone="computer.png"; }
						
						// Retourne quelques données en provenance de l'inventaire
						$retour_inventaire=der_inventaire($mpenc);
						if($retour_inventaire=="0") {
							$retour_inventaire=gettext("Pas d'inventaire");
						}
					} else {$retour_inventaire=gettext("Pas d'inventaire"); }	
					$affiche_result_prov = "<tr bgcolor=$color><td>&nbsp;&nbsp;";
					// $affiche_result_prov .= "<u onmouseover=\"this.T_WIDTH=140;return escape('$retour_inventaire')\"><IMG style=\"border: 0px solid ;\" width=15 height=15 SRC=\"../elements/images/$icone\" ></u>&nbsp;&nbsp;\n";
			
					$affiche_result_prov .= "<span id=\"2\" title=\"$retour_inventaire\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/$icone\"></span>\n";
					$affiche_result_prov .= $list_computer[$loopa]['cn'];
					if ($computer_parc=="no") {
						$affiche_result_prov .= "&nbsp;&nbsp;(Sans parc)";
					} else{
						$affiche_result_prov .= "&nbsp;&nbsp;(";
						$affiche_result_prov .= $exist_parc[0]['cn'];
						$affiche_result_prov .= ")";
						$mpenc=$list_computer[$loopa]['cn'];
					}	

					$ip_machine=avoir_ip($mpenc);
					if($ip_machine=="0") { $ip_machine=""; } 
					$affiche_result_prov .= "<td>$ip_machine</td>\n";
				
					// Voir les connexions	
					$affiche_result_prov .= "<td align=center><span id=\"2\" title=\"Voir les connexions sur cette machine\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/connect.gif\" onclick=\"popuprecherche('../parcs/show_machine.php?mpenc=$mpenc','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>\n";
					// Ajouter une machine dans le parc	
					$affiche_result_prov .= "<td align=center><span id=\"2\" title=\"Ajouter cette machine dans un parc\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/parc.png\" onclick=\"popuprecherche('../parcs/create_parc.php?mpenc=$mpenc&cp=true','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>";

					 if($inventaire_act=="1") { //Si inventaire active
					 
						// Inventaire
						$sessid=session_id();
						$systemid=avoir_systemid($mpenc);
						$affiche_result_prov .= "<td align=center><span id=\"2\" title=\"Inventaire\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/inventaire.png\" onclick=\"popuprecherche('../ocsreports/machine.php?sessid=$sessid&systemid=$systemid','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>";

						// Ajout en maintenance
						$affiche_result_prov .= "<td align=center><span id=\"2\" title=\"Demande de maintenance\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/ack.gif\" onclick=\"popuprecherche('../ocsreports/maintenance.php?mpenc=$mpenc&action=ajout','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>\n";
					}
					
					// Supprimer complétement cette machine 
					$affiche_result_prov .= "<td align=center><span id=\"2\" title=\"Supprimer cette machine\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/edittrash.png\"  onClick=\"confirm_del('computer','$mpenc','del_computer');\"></span></td>";
					$affiche_result_prov .= "</tr>\n";
					
					if ($parc=="all") {
						$affiche_result .= $affiche_result_prov;
					} else {	
						if ($computer_parc=="no") { // n'affiche que les machines sans parc
							$affiche_result .= $affiche_result_prov;			
						} else {
							$affiche_result_prov="";
						}	
					}	
				}
			} 	
		
		 	$_SESSION['affichage']['Stations']="1"; 
			$img_dep = "../elements/images/moins.png";
			$parc="computers";
		} else { // Pour fermer
			$_SESSION['affichage']['Stations']="0";
			$affiche_result = "";
			$img_dep = "../elements/images/plus.png";
  		}	
	}					 


//**************************************************************************************//

	// Maintenance 
	if ($type=="maintenance") {
		$dbnameinvent="ocsweb";
		include("dbconfig.inc.php");
		$authlink_invent=@mysql_connect($_SESSION["SERVEUR_SQL"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
		@mysql_select_db($dbnameinvent) or die("Impossible de se connecter &#224; la base $dbnameinvent.");
		
		$affiche_result = "";
		if ($_SESSION['affichage']['Maintenance']==0) { 

			$query="select * from repairs where STATUT='2' or STATUT='0' order by NAME";
			$result = mysql_query($query,$authlink_invent);
			$ligne=mysql_num_rows($result);
			// Si on a des machines en maintenance
			if ($ligne > "0") {
				while ($row = mysql_fetch_array($result)) {
        				if ($row["STATUT"] == "1") {
						$ETAT="R&#233;par&#233;";
               					$COULEUR="E0EEEE";
	         			} elseif ($row["STATUT"] == "2") {
		        			$ETAT="En attente";
		        			$COULEUR="00FF66";
		 			} elseif ($row["STATUT"] == "3") {
	                			$ETAT="Non r&#233;parable";
	                			$COULEUR="E0EEEE";
					} else {
	               				if ($row["PRIORITE"] == "2") {
		        				$COULEUR="FF7D40";
		        				$ETAT="Urgent";
						} elseif ($row["PRIORITE"] == "1") {
		        				$COULEUR="EE2C2C";
		        				$ETAT="Tr&#233;s urgent";
						} elseif ($row["PRIORITE"] == "0") {
		        				$COULEUR="FFD700";
		        				$ETAT="Normal";
						}
					}	
					$parc_pc=search_parcs($row["NAME"]);

					$affiche_result .= "<tr bgcolor=$COULEUR><td>&nbsp;&nbsp;<span title=\"$ETAT\"><IMG  style=\"border: 0px solid ;\" width=15 height=15 SRC=\"../elements/images/ack.gif\" ALT=\"Maintenance\">&nbsp;&nbsp;".$row["NAME"]." (".$parc_pc[0]['cn'].")</span> </td>\n";
				
					 $affiche_result .= "<td align=center>$row[REQDATE]</td>\n";
					 $affiche_result .= "<td align=center>$row[REQDESC]</td>\n";

					 $affiche_result .= "<td align=center>$row[ACCOUNT]</td>\n";
					// Inventaire
					$affiche_result .= "<td align=center><span id=\"2\" title=\"Inventaire\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/inventaire.png\" onclick=\"popuprecherche('../parcs/show_machine.php?mpenc=$row[NAME]','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>\n";
					
					$affiche_result .= "<td align=center><span id=\"2\" title=\"D#&233tail de la demande\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/zoom.png\" onclick=\"popuprecherche('../ocsreports/maintenance.php?mpenc=$row[NAME]&action=detail&ID=$row[ID]','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>\n";
					$affiche_result  .= "</tr>\n";
				}	
			}
			
			$_SESSION['affichage']['Maintenance']="1";
			$img_dep = "../elements/images/moins.png";

		} else {	

		 	$_SESSION['affichage']['Maintenance']="0"; 
			$img_dep = "../elements/images/plus.png";
			$parc="maintenance";
			$affiche_result = "";
  		}
	}	

	//*********************************************************************************//
	// reseau 
	if ($type=="reseau") {
		$affiche_result = "";
		if ($_SESSION['affichage']['Reseau']==0) { 

			$query="select ID,NAME,NETID,MASK from subnet";
			$result = mysql_query($query,$authlink_modif);
	
			$ligne=mysql_num_rows($result);
			
			// Si des réponses existent
			if ($ligne > "0") {
				while ($row = mysql_fetch_array($result)) {
			 		if ($color=="#E0EEEE") { $color="#B4CDCD"; } else {$color="#E0EEEE"; }
	 				if ($color=="") { $color="#B4CDCD"; }
		        		$affiche_result .= "<tr bgcolor=$color><td>&nbsp;&nbsp;<IMG  style=\"border: 0px solid ;\" width=15 height=15 SRC=\"../elements/images/reseau.gif\" ALT=\"Reseau\">&nbsp;&nbsp;".$row["NAME"]."</td><td>".$row["NETID"]."/".$row["MASK"]."</td></tr>";
				}	
			}
		
			$_SESSION['affichage']['Reseau']="1";
			$affiche_result = "";
			$img_dep = "../elements/images/moins.png";
		} else {	

		 	$_SESSION['affichage']['Reseau']="0"; 
			$img_dep = "../elements/images/plus.png";
			$parc="reseau";


		}
	}	


	//***********************************************************************************//
	// Sauvegarde 
	if ($type=="sauvegarde") {
		$affiche_result = "";
		if ($_SESSION['affichage']['Sauvegarde']==0) { 

			$i="0";
			$dir = "/etc/backuppc";
			if(is_dir($dir)) {
				if ($liste = opendir($dir)) {
					while (($file = readdir($liste)) != false) {

						if ($color_parc=="#E0EEEE") { $color_parc="#B4CDCD"; } else {$color_parc="#E0EEEE"; }
						if ($color_parc=="") { $color_parc="#B4CDCD"; }

						if ((preg_match("/.pl$/",$file)) and ($file != "config.pl")) {
							$Host = substr ("$file",0,-3);
				
							// recherche le type de sauvegarde
							if (GetTypeServer($Host) != "Archive") {
								$i="1";   
				  				// verifie si tout est ok
				  				if (HostExist($Host) == "true") {
				  					if (EtatDesactive($Host) == "true") { $im = "info.png"; } else { $im = "recovery.png"; }
				  				} else { $im="critical.png"; }

									$affiche_result .= "<tr bgcolor=$color_parc><td>$Host</td>\n";
				  				if ($im == "info.png") {
									$affiche_result .= "<td align=center><span id=\"2\" title=\"Etat de la sauvegarde\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/".$im."\" onclick=\"popuprecherche('../sauvegarde/modif_host.php?HostServer=$Host&action=active','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>\n";
								} else {
									$affiche_result .= "<td align=center><span id=\"2\" title=\"Etat de la sauvegarde\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/".$im."\" onclick=\"popuprecherche('../sauvegarde/modif_host.php?HostServer=$Host','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>\n";
								}


								$affiche_result .= "<td align=center><span id=\"2\" title=\"Parcourir la sauvegarde\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/logrotate.png\" onclick=\"popuprecherche('../backuppc/index.cgi?action=browse&host=$Host','popuprecherche','scrollbars=yes,width=500,height=500');\"></span></td>";
								//Suppression de la sauvegarde
								$affiche_result .= "<td align=center><span id=\"2\" title=\"Supprimer la sauvegarde\"><img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/edittrash.png\"  onClick=\"confirm_del('$parc','$Host','del_sauvegarde');\"></span></td>";
				  				$affiche_result .= "</td></tr>\n";
							}  
						}	
					}
//	closedir($dir);
				}
			}

			$_SESSION['affichage']['Sauvegarde']="1";
//			$affiche_result = "";
			$img_dep = "../elements/images/moins.png";
		} else {	

		 	$_SESSION['affichage']['Sauvegarde']="0"; 
			$img_dep = "../elements/images/plus.png";
			$parc="sauvegarde";
			$affiche_result = "";

		}
	}	


	//*******************************************************************************//
	// Retour du résultat
	$result = array($parc,$affiche_result,$img_dep,$type,$etat);
	$json = new Services_JSON();
	$out = $json->encode($result);
	
// print_r ($out);

	return $out;
};


?>
