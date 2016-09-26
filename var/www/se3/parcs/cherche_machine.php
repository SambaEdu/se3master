<?php

   /**
   
   * Recherche une machine par son adresse IP ou son nom
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Philippe Chadefaux

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: parcs/
   * file: cherche_machine.php

  */	





include "entete.inc.php";
require_once ("ldap.inc.php");
require_once ("ihm.inc.php");
require_once ("printers.inc.php");

require_once ("fonc_outils.inc.php");

include("crob_ldap_functions.php"); // Pour les recherches de doublons

// Traduction
require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');

$parc=isset($_POST['parc']) ? $_POST['parc'] : (isset($_GET['parc']) ? $_GET['parc'] : NULL);
//if ($parc=="") { $parc=$_GET['parc']; }
//$parcs=$_POST['parcs'];
$parcs=isset($_POST['parcs']) ? $_POST['parcs'] : NULL;

$creationdossiertemplate=isset($_POST['creationdossiertemplate']) ? $_POST['creationdossiertemplate'] : NULL;
//$mpenc=isset($_POST['mpenc']) ? $_POST['mpenc'] : NULL;
$mpenc=isset($_POST['mpenc']) ? $_POST['mpenc'] : (isset($_GET['mpenc']) ? $_GET['mpenc'] : NULL);

//aide
$_SESSION["pageaide"]="Gestion_des_parcs";

//debug_var();

//echo "netbios_name=$netbios_name<br />";

if (is_admin("computers_is_admin",$login)=="Y") {

	//titre
	echo "<h1>".gettext("Rechercher")."</h1>";
	
	// Affichage des machines sans parc
	if ($_POST['sansparc']=="oui") {
		echo "<h3>Machines sans parc</h3>\n";
		echo "<br />\n";

		echo "<FORM method=\"post\" action=\"cherche_machine.php\">\n";
		echo "<input type=\"hidden\" name=\"sansparc\" value=\"oui\">\n";
		if ((isset($_POST['affiche_all']))&&($_POST['affiche_all']=="yes")) {
			echo "<input type=\"submit\" value=\"".gettext("Voir uniquement les machines sans parc")."\">\n";
		} else {
			echo "<input type=\"hidden\" name=\"affiche_all\" value=\"yes\">\n";
			echo "<input type=\"submit\" value=\"".gettext("Voir toutes les machines")."\">\n";
		}
		echo "</form>\n";
		echo "<br /><br />\n";

		echo "<form method=\"post\" action=\"create_parc.php\">\n";
		//echo "<table>\n";
		$list_computer=search_machines("(&(cn=*)(objectClass=ipHost))","computers");
		//echo "count(\$list_computer)=".count($list_computer)."<br />\n";
		if (count($list_computer)>0) {
			$color="#B4CDCD";
			echo "<table>\n";
			for ($loopa=0; $loopa < count($list_computer); $loopa++) {
				if($list_computer[$loopa]['cn']!=$netbios_name) {
					//echo "<p>\$list_computer[$loopa]['cn']=".$list_computer[$loopa]["cn"]."<br />\n";
					$exist_parc = search_parcs($list_computer[$loopa]["cn"]);
					//echo "\$exist_parc[0]['cn']=".$exist_parc[0]["cn"]."<br />\n";
					if ((!isset($exist_parc[0]["cn"]))||($exist_parc[0]["cn"]=="")) {
						$computer_parc="no";
					} else {
						$computer_parc="yes";
					}
					//echo "\$computer_parc=$computer_parc<br />";
	
					$mpenc=$list_computer[$loopa]['cn'];
					$icone="computer.png";
					// $inventaire_act=inventaire_actif();
					// Initialisation
					$retourOs="";
					if($inventaire=="1") {
						// Type d'icone en fonction de l'OS
						$retourOs = type_os($mpenc);
						if($retourOs == "0") { $icone="computer.png"; }
						elseif($retourOs == "Linux") { $icone="linux.png"; }
						elseif($retourOs == "XP") { $icone="winxp.png"; }
						elseif($retourOs == "98") { $icone="win.png"; }
						elseif($retourOs == "10") { $icone="win10.png"; }
						elseif($retourOs == "vista") { $icone="winvista.png"; }
						elseif($retourOs == "7") { $icone="win7.png"; }
						else { $icone="computer.png"; }
					}

					$ip=avoir_ip($mpenc);
					if ((isset($_POST['affiche_all']))&&($_POST['affiche_all']=="yes")) {
						if ($color=="#E0EEEE") { $color="#B4CDCD"; } else {$color="#E0EEEE"; }
						$affiche_result_prov = "<tr bgcolor=$color><td>&nbsp;&nbsp;";
						$affiche_result_prov .= "<img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/$icone\" title=\"$retourOs\">\n";
						$affiche_result_prov .= $list_computer[$loopa]['cn'];
						echo "$affiche_result_prov";
						echo "</td><td>$ip";
						echo "</td></tr>\n";
					} else {
						if ($computer_parc=="no") {
							if ($color=="#E0EEEE") { $color="#B4CDCD"; } else {$color="#E0EEEE"; }
							$affiche_result_prov = "<tr bgcolor=$color><td>&nbsp;&nbsp;";
							$affiche_result_prov .= "<input type=\"checkbox\" name=\"new_computers[]\" id=\"new_computers_$loopa\" value=\"$mpenc\"></td><td>&nbsp;&nbsp;";


							$affiche_result_prov .= "<input type=\"hidden\" name=\"create_parc\" value=\"true\">\n";
							$affiche_result_prov .= "<img width=\"15\" height=\"15\" style=\"border: 0px solid ;\" src=\"../elements/images/$icone\" title=\"$retourOs\">\n";

							$affiche_result_prov .= "<label for=\"new_computers_$loopa\">$mpenc</label>";
							echo "$affiche_result_prov";
							echo "</td><td><label for=\"new_computers_$loopa\">$ip</label>";
							echo "</td></tr>\n";
						}
					}
				}
			}
			echo "</table>\n";

			if ((!isset($_POST['affiche_all']))||($_POST['affiche_all']!="yes")) {
				echo "<p><a href='javascript: checkAll();'>Tout cocher</a> / <a href='javascript:UncheckAll();'>Tout d&eacute;cocher</a></p>";

				echo "<script type='text/javascript'>
	function checkAll(){
		champs_input=document.getElementsByTagName('input');
		for(i=0;i<champs_input.length;i++){
			type=champs_input[i].getAttribute('type');
			if(type==\"checkbox\"){
				champs_input[i].checked=true;
			}
		}
	}

	function UncheckAll(){
		champs_input=document.getElementsByTagName('input');
		for(i=0;i<champs_input.length;i++){
			type=champs_input[i].getAttribute('type');
			if(type==\"checkbox\"){
				champs_input[i].checked=false;
			}
		}
	}
</script>";

			}

			echo "<input type=\"submit\" value=\"".gettext("Ajouter &#224; un parc")."\">\n";

			echo "</form>\n";

		} else { echo "Il n'y a aucune machine"; }	
	}
	else {

		if(isset($_POST['suppr_doublons_ldap'])) {
			$suppr=isset($_POST['suppr']) ? $_POST['suppr'] : NULL;
		
			$tab_attr_recherche=array('cn');
			for($i=0;$i<count($suppr);$i++) {
				if(get_tab_attribut("computers","cn=$suppr[$i]",$tab_attr_recherche)) {
					if(!del_entry("cn=$suppr[$i]","computers")) {
						echo "Erreur lors de la suppression de l'entr&#233;e $suppr[$i]<br />\n";
					}
				}

				// Faut-il aussi supprimer les uid=$suppr[$i]$ ? OUI
				if(get_tab_attribut("computers","uid=$suppr[$i]$",$tab_attr_recherche)) {
					if(!del_entry("uid=$suppr[$i]$","computers")) {
						echo "Erreur lors de la suppression de l'entr&#233;e uid=$suppr[$i]$<br />\n";
					}
				}
			}
		}

		// On traite le nom de la machine
		//Si ce nom est bon on affiche les parcs de cette machine
		if ($mpenc != "") {
			//On chercche si on a pas une adresse ip 
			$computer_ip=search_machines("(&(ipHostNumber=$mpenc)(objectClass=ipHost))","computers");
			if (count($computer_ip)==1) {
				$ipHost=$mpenc;
				$mpenc=avoir_nom($ipHost);
			} else {
				$computer=search_machines("(&(cn=$mpenc)(objectClass=ipHost))","computers");	
			}
			if ((count($computer)==1) || (count($computer_ip)==1)) {
				$ipHost=avoir_ip($mpenc);
				echo "<a href=show_histo.php?selectionne=2&mpenc=$mpenc>$mpenc</a> ($ipHost) se trouve dans les parcs&nbsp;: ";
				for ($loopa=0; $loopa < 1; $loopa++) {
				//	echo $computer[$loopa]["cn"];
					echo "<br /><br />\n";
					$list_parcs=search_machines("(&(member=cn=$mpenc,$computersRdn,$ldap_base_dn)(objectClass=groupOfNames))","parcs");
					if (count($list_parcs)>0) {
						for ($loop=0; $loop < count($list_parcs); $loop++) {
							$parc=$list_parcs[$loop]["cn"];
							echo "<A HREF=\"show_parc.php?parc=$parc\">".$list_parcs[$loop]["cn"]."</A>";
							echo "<br />\n";
						}
					}
					if (count($list_parcs)==0) {
						echo "<br />\n";
						echo "La machine $mpenc ne se trouve dans aucun parc";
						echo "<br /><br /><center>";
						echo "<a href=../parcs/cherche_machine.php>Retour</a>\n";
						echo "</center>\n";
					}
				}

				include "pdp.inc.php";

				exit;
			}
		}



	
		
		// Recherche les parcs d'une machine
		echo "<h3>".gettext("Rechercher &#224; quel(s) parc(s) appartient une machine")."</h3>\n";
		echo "<FORM method=\"post\" action=\"cherche_machine.php\">\n";
		echo gettext("Nom ou adresse IP de la machine : ");

		// Si un debut de re/ponse
		echo " <INPUT TYPE=\"text\" NAME=\"mpenc\" VALUE=\"$mpenc\" SIZE=\"12\">";
	
		if ($mpenc != "") {

			$list_computer_ip=search_machines("(&(ipHostNumber=$mpenc*)(objectClass=ipHost))","computers");
			if (count($list_computer_ip)>0) {
				echo "<SELECT NAME=\"mpenc\" SIZE=\"1\">";	
				for ($loop=0; $loop < count($list_computer_ip); $loop++) {
					echo "<option value=\"".$list_computer[$loop]["cn"]."\">".$list_computer[$loop]["cn"]."\n";
				}

				echo "</SELECT>&nbsp;&nbsp;\n";
			}
			
			$list_computer=search_machines("(&(cn=$mpenc*)(objectClass=ipHost))","computers");
			if (count($list_computer)>0) {
				echo "<SELECT NAME=\"mpenc\" SIZE=\"1\">";	
				for ($loopa=0; $loopa < count($list_computer); $loopa++) {
					echo "<option value=\"".$list_computer[$loopa]["cn"]."\">".$list_computer[$loopa]["cn"]."\n";
				}

				echo "</SELECT>&nbsp;&nbsp;\n";
			}

		}	

	        echo " <input type=\"submit\" value=\"".gettext("Valider")."\">\n";
	
		echo "<u onmouseover=\"return escape".gettext("('Donner le d&#233;but du nom ou de l\'adresse IP de la machine que vous souhaitez trouver.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";

		echo "</FORM>\n";
		echo "<br>";

		echo "<h3>".gettext("Afficher toutes les machines sans parc")."</h3>\n";

		echo "<FORM method=\"post\" action=\"cherche_machine.php\">\n";
		echo "Afficher toutes les machines sans parc ";
		echo "<input type=\"hidden\" name=\"sansparc\" value=\"oui\">\n";
	        echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
		echo "</form>";

		echo "<br />\n";
		echo "<h3>".gettext("Recherche des doublons")."</h3>\n";

		search_doublons_mac();

	}
}

include "pdp.inc.php";
?>
