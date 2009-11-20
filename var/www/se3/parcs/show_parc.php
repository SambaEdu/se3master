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

$parc=$_POST['parc'];
if ($parc=="") { $parc=$_GET['parc']; }
$parcs=$_POST['parcs'];
$mpenc=$_POST['mpenc'];
$description=$_GET['description'];
$entree=$_GET['entree'];

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
	if ("$filtrecomp"=="") $mp=$mp_all;
			
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
			
		echo "<TABLE>";
		
		if ($description=="1") {
			echo "Cliquer sur <img style=\"border: 0px solid ;\" width=\"20\" height=\"20\" src=\"../elements/images/notify.gif\" title=\"Choisir la machine professeur\"> pour choisir une  machine comme machine professeur";
			echo "<br>ou recliquer sur le menu pour ne plus en avoir";
			echo "<br><br>";
		}

		echo "<TR><TD class='menuheader' align=\"center\"></TD>";
		echo "<TD class='menuheader' align=\"center\">".gettext("Nom")."</TD><TD class='menuheader'>".gettext("Supprimer du parc")."</TD><TD class='menuheader'>".gettext("Supprimer compl&#233;tement")."</TD></TR>\n";
		echo "<TR><TD class='menuheader' align=\"center\"></TD>";
		echo "<TD class='menuheader' align=\"center\">".count($mp)."</TD>";
		echo "<TD class='menuheader' align=\"center\">";
		echo "<a href=\"javascript:coche_delete('del',true)\">";
	        echo "<img src='../elements/images/enabled.png' alt='Cocher tout' title='Cocher tout' border='0' /></a>";
	        echo " / \n";
	        echo "<a href=\"javascript:coche_delete('del',false)\">";
	        echo "<img src='../elements/images/disabled.png' alt='D&#233;cocher tout' title='D&#233;cocher tout' border='0' /></a>\n";
		echo "</TD>";
				
		echo "<TD class='menuheader' align=\"center\">";
		echo "<a href=\"javascript:coche_delete('sup',true)\">";
	        echo "<img src='../elements/images/enabled.png' alt='Cocher tout' title='Cocher tout' border='0' /></a>";
	        echo " / \n";
	        echo "<a href=\"javascript:coche_delete('sup',false)\">";
	        echo "<img src='../elements/images/disabled.png' alt='D&#233;cocher tout' title='D&#233;cocher tout' border='0' /></a>\n";
		echo "</TD></TR>\n";

		// Test la machine prof pour italc
		$machine_prof=search_description_parc("$parc");

		for ($loop=0; $loop < count($mp); $loop++) {
			$mpenc=urlencode($mp[$loop]);

			echo "<TR>";
			// Test si on a une imprimante ou une machine
			$resultat=search_imprimantes("printer-name=$mpenc","printers");
			$suisje_printer="non";
			for ($loopp=0; $loopp < count($resultat); $loopp++) {
				if ($mpenc==$resultat[$loopp]['printer-name']) {
					$suisje_printer="yes";	
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
				echo "<TD><img style=\"border: 0px solid ;\" src=\"../elements/images/printer.png\" title=\"Imprimante\" alt=\"Imprimante\" WIDTH=20 HEIGHT=20 ></TD>";

				echo "<TD align=\"center\"><A href='../printers/view_printers.php?one_printer=$mpenc'>$mp[$loop]</A></TD>\n";
			} else {
				if($inventaire=="1") {
		                        // Type d'icone en fonction de l'OS
		                        $retourOs = type_os($mpenc);
		                        if($retourOs == "0") { $icone="computer.png"; }
		                        elseif($retourOs == "Linux") { $icone="linux.png"; }
		                        elseif($retourOs == "XP") { $icone="winxp.png"; }
		                        elseif($retourOs == "98") { $icone="win.png"; }
		                        else { $icone="computer.png"; }
					$ip=avoir_ip($mpenc);
					echo "<TD><img style=\"border: 0px solid ;\" src=\"../elements/images/$icone\" title=\"".$retourOs." - ".$ip."\" alt=\"$retourOs\" WIDTH=20 HEIGHT=20 onclick=\"popuprecherche('../ocsreports/machine.php?sessid=$sessid&systemid=$systemid','popuprecherche','scrollbars=yes,width=500,height=500');\">";
				}
				else
					echo "<TD><img style=\"border: 0px solid ;\" src=\"../elements/images/computer.png\" alt=\"Ordinateur\" WIDTH=20 HEIGHT=20 >";
				
				
				// On selectionne la machine prof
				if ($description=="1") {
					echo "&nbsp;";
					echo "<A HREF=../parcs/show_parc.php?description=0&parc=$parc&entree=$mpenc><img style=\"border: 0px solid ;\" src=\"../elements/images/notify.gif\" title=\"Machine professeur\" alt=\"Cliquer pour choisir cette machine\" ></A></TD>";

				} else {
					// la machine prof est connue	
					if ($machine_prof==$mpenc) {
						echo "&nbsp;";

						echo "<img style=\"border: 0px solid ;\" src=\"../elements/images/notify.gif\" title=\"Machine professeur\" alt=\"Machine professeur\" ></TD>";
					}
				}	
				
				echo "<TD align=\"center\"><A href='show_histo.php?selectionne=2&amp;mpenc=$mpenc'>$mp[$loop]</A></TD>\n";
			}
			echo "<TD align=\"center\"><INPUT type=\"checkbox\" name=\"old_computers[]\" id=\"del_$loop\"  value=\"$mpenc\">";
			echo "</TD>\n";

			echo "<TD align=\"center\"><INPUT type=\"checkbox\" name=\"supprime_all[]\" id=\"sup_$loop\"  value=\"$mpenc\" onClick=\"coche_machine('del_$loop',true)\"></TD>\n";
			echo "</TR>";
		}
		echo "</TABLE>\n";

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
