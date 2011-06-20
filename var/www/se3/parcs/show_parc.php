<?php


   /**
   
   * affiche les parcs et le contenu
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe Tice academie de Caen
   * @auteurs « jLCF >:> » jean-luc.chretien@tice.ac-caen.fr
   * @auteurs « oluve » olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs « wawa »  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs plouf

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: parcs/
   * file: show_parc.php
   */		


						


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
include "printers.inc.php";
require_once ("fonc_outils.inc.php");




// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');

$parc=isset($_POST['parc']) ? $_POST['parc'] : (isset($_GET['parc']) ? $_GET['parc'] : "");
$parcs=isset($_POST['parcs']) ? $_POST['parcs'] : "";
$mpenc=isset($_POST['mpenc']) ? $_POST['mpenc'] : "";
$description=isset($_GET['description']) ? $_GET['description'] : "";
$entree=isset($_GET['entree']) ? $_GET['entree'] : "";

//aide
$_SESSION["pageaide"]="Gestion_des_parcs";



if (is_admin("computers_is_admin",$login)=="Y") {

	//titre
	echo "<h1>".gettext("Liste des parcs")."</h1>";
	
	if ($description=="0") {
		modif_description_parc ($parc,$entree);
		// On relance le script pour italc
		exec ("/usr/bin/sudo /usr/share/se3/scripts/italc_generate.sh");
	}
    	
    echo "<h3>".gettext("S&#233;lectionnez un parc:")."</h3>";
	$list_parcs=search_machines("objectclass=groupOfNames","parcs");
	if ( count($list_parcs)>0) {
		sort($list_parcs);
		echo "<FORM method=\"post\" action=\"show_parc.php\">\n";
		echo "<SELECT NAME=\"parc\" SIZE=\"1\" onchange=submit()>";
		echo "<option value=\"\">S&#233;lectionner</option>";
		for ($loop=0; $loop < count($list_parcs); $loop++) {
			echo "<option value=\"".$list_parcs[$loop]["cn"]."\"";
			if ($parc==$list_parcs[$loop]["cn"]) { echo " selected"; }
			echo ">".$list_parcs[$loop]["cn"]."\n";
			echo "</option>";
		}
		echo "</SELECT>&nbsp;&nbsp;\n";

		echo "</FORM>\n";
	} else {
		echo "<center>";
		echo "Il n'existe encore aucun parc";
		echo "</center>";
		exit;
	}		

	// Test si le parc possede un template
	
	
	
	
	
	
	
	

	// Lecture des membres du parc
	 $mp_all=gof_members($parc,"parcs",1);
	if ((!isset($filtrecomp))||("$filtrecomp"=="")) {$mp=$mp_all;}
			
	$nombre_machine=count($mp);

	/*************************************************************************/
	echo "<script language='javascript' type='text/javascript'>
		
		/**

		* Coche des boutons radio pour selection
		* @language Javascript	
		* @Parametres
		* @Return
		*/

		function coche_delete(mode,statut){
			for(k=0;k<$nombre_machine;k++){
				 if(document.getElementById(mode+'_'+k)){
	        			document.getElementById(mode+'_'+k).checked=statut;
	        			document.getElementById('del_'+k).checked=statut;
				 }
			}
		}
		
		
		/**

		* Coche des boutons radio pour selection de machine
		* @language Javascript
		* @Parametres
		* @Return
		*/

		function coche_machine(mode,statut){
			 if(document.getElementById(mode)){
		       		document.getElementById(mode).checked=statut;
			 }
		}
	</script>\n";
	/*************************************************************************/

	if ( count($mp)>15) $size=15; else $size=count($mp);
	if ( count($mp)>0) {
		sort($mp);
		//	echo "<p>".gettext("Liste des machines dans le parc :")." (".count($mp).")</p>\n";
		echo "<center>\n";
        echo "<script type=\"text/javascript\" src=\"js/jquery.js\"></script>";
        echo "<script type=\"text/javascript\" src=\"js/interface.js\"></script>";
?>
<script type="text/javascript">
	
	$(document).ready(
		function()
		{
			$('#dock').Fisheye(
				{
					maxWidth: 40,
					items: 'a',
					itemsText: 'span',
					container: '.dock-container',
					itemWidth: 40,
					proximity: 50,
					alignment : 'left',
					halign : 'center'
				}
			)
		}
	);

</script>
<?php

	
	
		echo "<div class=\"dock\" id=\"dock\">";
		echo "<div class=\"dock-container\">";
		echo "<a class=\"dock-item\" href=\"create_parc.php?parc=$parc\"><span>Ajouter une machine</span><img src=\"../elements/images/computer_large.png\" alt=\"Machine\" /></a>";
		echo "<a class=\"dock-item\" href=\"../printers/add_printer.php?parc=$parc&amp;list_parc=1\"><span>Ajouter une imprimante</span><img src=\"../elements/images/printer_large.png\" alt=\"Imprimante\" /></a>";
		echo "<a class=\"dock-item\" href=\"../parcs/wolstop_station.php?parc=$parc&amp;action=timing\"><span>Programmer l'arr&#234;t et l'allumage des machines</span><img src=\"../elements/images/xclock.png\" alt=\"Programmer\" /></a>";
		echo "<a class=\"dock-item\" href=\"../parcs/action_parc.php?parc=$parc\"><span>Action sur les machines</span><img src=\"../elements/images/system-run.png\" alt=\"Action\" /></a>";

		// Template 
		if(!file_exists("/home/templates/$parc")){
		    echo "<a class=\"dock-item\" href=\"../parcs/create_parc.php?parc[]=$parc&amp;creationdossiertemplate=oui\"><span>Cr&#233;er le template pour ce parc</span><img src=\"../elements/images/folder-development.png\" alt=\"Template\" /></a>";
		} else {
		    echo "<a class=\"dock-item\" href=\"../registre/affiche_restrictions.php?salles=$parc\"><span>G&#233;rer le template</span><img src=\"../elements/images/preferences-desktop-cryptography.png\" alt=\"Restrictions\" /></a>";
		}

		echo "<a class=\"dock-item\" href=\"../popup/index.php?parc=$parc\"><span>Envoyer un popup aux machines connect&#233;es</span><img src=\"../elements/images/konversation.png\" alt=\"Popup\" /></a>";
		echo "<a class=\"dock-item\" href=\"../parcs/delegate_parc.php?action=new&amp;salles=$parc\"><span>D&#233;l&#233;guer ce parc</span><img src=\"../elements/images/list-add-user.png\" alt=\"Deleguer\" /></a>";

		// Nomme une machine prof pour italc
		$parse=exec("cat /var/se3/unattended/install/wpkg/packages.xml | grep italc > /dev/null && echo 1");
		if($parse==1) {
			echo "&nbsp;&nbsp;&nbsp;";
			if ($description=="1") {
				$description_prof="0";
			} else {
				$description_prof="1";
			}	
		    echo "<a class=\"dock-item\" href=\"../parcs/show_parc.php?parc=$parc&amp;description=$description_prof\"><span>Choisir la machine professeur</span><img src=\"../elements/images/preferences-desktop-user-password.png\" alt=\"italc\" /></a>";
        }
		echo "</div> ";
        echo "</div><br/><br/>";
	
		echo "<FORM action=\"delete_parc.php\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\">\n";
		echo "<input type=\"hidden\" name=\"delparc\" value=\"0\">\n";
			
		echo "<input type=\"hidden\" name=\"delete_parc\" value=\"true\">\n";

		$module_clonage_actif="n";
		$sql="select 1=1 from params where name='clonage' AND value='1';";
		$test_clonage=mysql_query($sql);
		if(mysql_num_rows($test_clonage)>0) {
			$module_clonage_actif="y";
		}

		//echo "<TABLE border=1>";
                echo "<TABLE border=1 width=\"60%\">\n<tr class=menuheader style=\"height: 30\">\n";
		
		if ($description=="1") {
			echo "Cliquer sur <img style=\"border: 0px solid ;\" width=\"20\" height=\"20\" src=\"../elements/images/notify.gif\" title=\"Choisir la machine professeur\"> pour choisir une  machine comme machine professeur";
			echo "<br>ou recliquer sur le menu pour ne plus en avoir";
			echo "<br><br>";
		}

		echo "<tr><td class='menuheader' align=\"center\"><img src='../elements/images/computer_ocs.png'></td>";
		echo "<td class='menuheader' align=\"center\">".gettext("Stations")."</td>";
                echo "<td class='menuheader' align=\"center\">".gettext("Adresse IP")."</td>";
                echo "<td class='menuheader' align=\"center\">".gettext("Derni&#232;re connexion")."</td>";

				if($module_clonage_actif=='y') {
					echo "<td class='menuheader' align=\"center\">".gettext("Dernier rapport TFTP")."</td>";
				}

                echo "<td class='menuheader' align=\"center\">".gettext("Supprimer du parc")."<br><a href=\"javascript:coche_delete('del',true)\">";
	        echo "<img src='../elements/images/enabled.png' alt='Cocher tout' title='Cocher tout' border='0' /></a>";
	        echo " / \n";
	        echo "<a href=\"javascript:coche_delete('del',false)\">";
	        echo "<img src='../elements/images/disabled.gif' alt='D&#233;cocher tout' title='D&#233;cocher tout' border='0' /></a>\n";
		echo "</td>";
                echo "<td class='menuheader' align=\"center\">".gettext("Supprimer compl&#232;tement")."<br>";
                echo "<a href=\"javascript:coche_delete('sup',true)\">";
	        echo "<img src='../elements/images/enabled.png' alt='Cocher tout' title='Cocher tout' border='0' /></a>";
	        echo " / \n";
	        echo "<a href=\"javascript:coche_delete('sup',false)\">";
	        echo "<img src='../elements/images/disabled.gif' alt='D&#233;cocher tout' title='D&#233;cocher tout' border='0' /></a>\n";
                echo "</td></tr>\n";

//                echo "<tr><td class='menuheader' align=\"center\"></td>";
//		echo "<td class='menuheader' align=\"center\"></td>";
//		echo "<td class='menuheader' align=\"center\"></td>";
//		echo "<td class='menuheader' align=\"center\"></td>";
//		echo "<td class='menuheader' align=\"center\">";
//
//
//		echo "<td class='menuheader' align=\"center\">";
//
//		echo "</td></tr>\n";

		// Test la machine prof pour italc
		$machine_prof=search_description_parc("$parc");
                $tableau_printer = "<br>";
                $tableau_printer .= "\n<br>\n<CENTER>\n";

                $tableau_printer .=  "<TABLE border=1 width=\"60%\">\n<tr class=menuheader style=\"height: 30\">\n";
		$tableau_printer .=  "<tr class='menuheader'>\n";
		$tableau_printer .=  "<td class='menuheader'></td>\n";
		$tableau_printer .=  "<td class='menuheader' align=\"center\">Imprimantes</td>\n";
		$tableau_printer .=  "<td class='menuheader' align=\"center\">Adresse IP</td>\n";
                $tableau_printer .=  "<td class='menuheader' align=\"center\">".gettext("Supprimer du parc")."</td>";
                $tableau_printer .=  "<td class='menuheader' align=\"center\">".gettext("Supprimer compl&#232;tement")."</td></tr>\n";
		$tableau_printer .=  "</tr>\n";

		$suisje_printer="0";

		for ($loop=0; $loop < count($mp); $loop++) {
		
			$mpenc=urlencode($mp[$loop]);

			
			// Test si on a une imprimante ou une machine
			$resultat=search_imprimantes("printer-name=$mpenc","printers");
			$suisje_printer="non";
			for ($loopp=0; $loopp < count($resultat); $loopp++) {
				if ($mpenc==$resultat[$loopp]['printer-name']) {
					$suisje_printer="yes";
                                        $printer_in_parc++;
                                        $uri_printer = $resultat[$loopp]['printer-uri'];
					continue;
				}	
			}
			if (file_exists ("/var/www/se3/includes/dbconfig.inc.php")) {
				include_once "fonc_parc.inc.php";
				$sessid=session_id();
	                        $systemid=avoir_systemid($mpenc);
			}
			else {
				$inventaire=0;
			}
			if ($suisje_printer=="yes") {
				//$uri_printer = $resultat[$loopp]['printer-uri'];
                                
                                if (preg_match("/socket:\/\//", $uri_printer)) {
                                    $uri_printer_modif = preg_replace("/socket:\/\//", "", $uri_printer);
                                    $printer_ip = explode(":", $uri_printer_modif);
                                   // echo $uri_printer;
                                   $printer_ip = $printer_ip[0];
                                }
                                else {
                                $printer_ip="none";
                                }
                                // completion tableau par les donnees recuperees
                                $tableau_printer .= "<tr>";
				$tableau_printer .= "<td><img style=\"border: 0px solid ;\" src=\"../elements/images/printer.png\" title=\"Imprimante\" alt=\"Imprimante\" WIDTH=20 HEIGHT=20 ></td>";
                                $tableau_printer .= "<td align=\"center\"><A href='../printers/view_printers.php?one_printer=$mpenc'>$mp[$loop]</A></td>\n";
				$tableau_printer .= "<td align=\"center\">$printer_ip</td>";
                                $tableau_printer .= "<td align=\"center\"><INPUT type=\"checkbox\" name=\"old_computers[]\" id=\"del_$loop\"  value=\"$mpenc\"></td>";
                                $tableau_printer .= "<td align=\"center\"><INPUT type=\"checkbox\" name=\"supprime_all[]\" id=\"sup_$loop\"  value=\"$mpenc\" onClick=\"coche_machine('del_$loop',true)\"></td>\n";
                                $tableau_printer .= "</tr>";
			} else {
                                echo "<tr>";
				if($inventaire=="1") {
		                        // Type d'icone en fonction de l'OS
		                        $retourOs = type_os($mpenc);
		                        if($retourOs == "0") { $icone="computer_disable.png"; }
		                        elseif($retourOs == "Linux") { $icone="linux.png"; }
		                        elseif($retourOs == "XP") { $icone="winxp.png"; }
		                        elseif($retourOs == "98") { $icone="win.png"; }
		                        else { $icone="computer_disable.png"; }
					$ip=avoir_ip($mpenc);
					echo "<td><img style=\"border: 0px solid ;\" src=\"../elements/images/$icone\" title=\"".$retourOs." - ".$ip."\" alt=\"$retourOs\" WIDTH=20 HEIGHT=20 onclick=\"popuprecherche('../ocsreports/machine.php?sessid=$sessid&systemid=$systemid','popuprecherche','scrollbars=yes,width=500,height=500');\">";
				}
				else
					echo "<td><img style=\"border: 0px solid ;\" src=\"../elements/images/computer.png\" alt=\"Ordinateur\" WIDTH=20 HEIGHT=20 >";
				
				
				// On selectionne la machine prof
				if ($description=="1") {
					echo "&nbsp;";
					echo "<A HREF=../parcs/show_parc.php?description=0&parc=$parc&entree=$mpenc><img style=\"border: 0px solid ;\" src=\"../elements/images/notify.gif\" title=\"Machine professeur\" alt=\"Cliquer pour choisir cette machine\" ></A></td>";

				} else {
					// la machine prof est connue	
					if ($machine_prof==$mpenc) {
						echo "&nbsp;";

						echo "<img style=\"border: 0px solid ;\" src=\"../elements/images/notify.gif\" title=\"Machine professeur\" alt=\"Machine professeur\" ></td>";
					}
				}	
				
				echo "<td align=\"center\"><A href='show_histo.php?selectionne=2&amp;mpenc=$mpenc'>$mp[$loop]</A></td>\n";
				$ip = avoir_ip($mpenc);
				//mysql_close();
				$authlink = mysql_connect($dbhost,$dbuser,$dbpass);
				mysql_select_db($dbname,$authlink) or die("Impossible de se connecter &#224; la base $dbname.");
				$query=" select logintime from connexions where netbios_name='$mpenc' order by id desc limit 1";
				//$query .= $cnx_start;
				//$query .= ",10";
                                $last_cnx[0]="none";
				$result = mysql_query($query) or die ('ERREUR '.$requete.' '.mysql_error());
				if (($result)) {
				  while ($r=mysql_fetch_array($result)) {
				    $last_cnx_long=$r["logintime"];
				    $last_cnx = explode(" ", $last_cnx_long);
				    $time_old = mktime(0,0,0,date("m")-1,date("d"),date("Y"));
				    
				    $time_today= time();
				    //$date_today= date("Ymd",mktime(0,0,0,date("m")-1,date("d"),date("Y")));
				    $time_last_cnx_array = explode("-", $last_cnx_long);
				    $time_last_cnx_array2 = explode(" ", $last_cnx_long[2]);
					//echo "\$time_last_cnx_array[1]=$time_last_cnx_array[1]<br />";
					//echo "\$time_last_cnx_array[2]=$time_last_cnx_array[2]<br />";
					//echo "\$time_last_cnx_array[0]=$time_last_cnx_array[0]<br />";
				    //$time_last_cnx = mktime(0,0,0,$time_last_cnx_array[1],$time_last_cnx_array[2],$time_last_cnx_array[0]);
				    $time_last_cnx = mktime(0,0,0,$time_last_cnx_array[1],$time_last_cnx_array2[0],$time_last_cnx_array[0]);
				    
				  }
				} else echo gettext("erreur lors de la lecture de la base se3");
				
			echo "<td align=\"center\">$ip</td>\n";
			if  ($time_last_cnx<$time_old) {
			    echo "<td align=\"center\"><STRONG><FONT color='red'>$last_cnx[0]</FONT></STRONG></td>\n";
			    }
			else	{
			    echo "<td align=\"center\">$last_cnx[0]</td>\n";
			    }

				if($module_clonage_actif=='y') {
					$sql="SELECT * FROM se3_tftp_rapports WHERE name='".$mp[$loop]."' ORDER BY date DESC LIMIT 1;";
					$res_rapport_tftp=mysql_query($sql);
					if(mysql_num_rows($res_rapport_tftp)>0) {
						$lig=mysql_fetch_object($res_rapport_tftp);
						echo "<td align=\"center\">";
						echo "<span style='font-size: x-small;' title='Dernier rapport: $lig->tache ($lig->statut)'><a href=\"../tftp/visu_rapport.php?id_machine=$lig->id\" target='_blank'>".$lig->date."</a></span>\n";
						echo "</td>\n";
					}
					else {
						echo "<td align=\"center\" style='color:red'>".gettext("Aucun rapport")."</td>\n";
					}
				}

			echo "<td align=\"center\"><INPUT type=\"checkbox\" name=\"old_computers[]\" id=\"del_$loop\"  value=\"$mpenc\">";
			echo "</td>\n";

			echo "<td align=\"center\"><INPUT type=\"checkbox\" name=\"supprime_all[]\" id=\"sup_$loop\"  value=\"$mpenc\" onClick=\"coche_machine('del_$loop',true)\"></td>\n";
			echo "</tr>";
			}
			
			//	echo " $ip";
			
			
			
		}
		echo "</TABLE>\n";

                $tableau_printer .=  "</table></center>";
		if (isset($printer_in_parc)) {
                    echo $tableau_printer;
                    echo "<br>";
                    $nb_machines = count($mp)-$printer_in_parc;
                    echo "<h3>".$nb_machines." station(s) et "."$printer_in_parc"." imprimante(s) ".gettext("dans le parc ")."$parc</h3>";
                }
                else {

                    echo "<h3>".count($mp)." station(s) dans le parc "."$parc</h3>";
                }
		echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
		echo "</FORM>\n";
		echo "</center>";
	} else {
		if ($parc!="") {
			echo "<br>";
			$message =  gettext("Il n'y a pas de machines dans ce parc &#224; afficher !");
			echo $message;
		}	
	}
}  

include ("pdp.inc.php");
?>
