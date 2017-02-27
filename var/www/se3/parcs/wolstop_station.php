<?php


   /**
   
   * Stop - start les machines clientes
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs Equipe Tice academie de Caen
   * @auteurs Sandrine Dangreville

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note modifie par jean navarro - Carip Lyon introduction du choix de l'ordre de tri de l'affichage des connexions
   
   */

   /**

   * @Repertoire: parcs/
   * file: wolstop_station.php
   */		



require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');


?>
<script type="text/javascript">
window.onload=montre;

/**

* Montre
* @language Javascript
* @Parametres  
* @Return
*/


function montre(id) {
var d = document.getElementById(id);
  for (var i = 1; i<=10; i++) {
    if (document.getElementById('smenu'+i)) {document.getElementById('smenu'+i).style.display='none';}
  }
if (d) {d.style.display='block';}
}

/**

* Valide l'arret
* @language Javascript
* @Parametres  
* @Return
*/

function okshutdown()
{
	resultat=confirm('Confirmez l\'arret des postes');
	if(resultat !="1")
	window.history.back()
}
//==================================
/**

* Valide le reboot
* @language Javascript
* @Parametres  
* @Return
*/

function okreboot()
{
	resultat=confirm('Confirmez le reboot des postes');
	if(resultat !="1")
	window.history.back()
}
//==================================

/**

* Valide le demarrge
* @language Javascript
* @Parametres  
* @Return
*/

function okwol()
{
	resultat=confirm('Confirmez l\'allumage des postes');
	if(resultat !="1")
	window.history.back()
}

</script>


<?php
include "entete.inc.php";
require_once "ihm.inc.php";
require_once "ldap.inc.php";
require_once "fonc_parc.inc.php";
require_once "fonc_outils.inc.php";

//aide
$_SESSION["pageaide"]="Gestion_des_parcs#Action_sur_parcs";

//***************Definition des droits de lecture  et aide en ligne

// Verifie les droits
if ((is_admin("computers_is_admin",$login)=="Y") or (is_admin("parc_can_view",$login)=="Y") or (is_admin("parc_can_manage",$login)=="Y") or (is_admin("inventaire_can_read",$login)=="Y")) {


	//affichage de la suite
	// echo "<div id=main>";

	//*****************cas des parcs delegues***********************************/
	if ((is_admin("computers_is_admin",$login)=="N") and ((is_admin("parc_can_view",$login)=="Y") or (is_admin("parc_can_manage",$login)=="Y"))) {
		echo "<h3>".gettext("Votre d&#233;l&#233;gation a &#233;t&#233; prise en compte pour l'affichage de cette page.</h3>");
		$acces_restreint=1;

	}

	/************************* Declaration des variables ************************************/
	$action=$_POST['action'];
	if (!$action) { $action=$_GET['action'];}

	$parc=$_POST['parc'];
	if (!$parc) { $parc=$_GET['parc'];}
	if (!$parc && $action!='timing') { echo "<div id=main>choisir un parc</div>"; exit; }
	if ($acces_restreint)  {  if ((!this_parc_delegate($login,$parc,"manage")) and (!this_parc_delegate($login,$parc,"view"))) { exit; } }

	//$force=$_POST['force'];

	//echo "action : $action";
	switch ($action) {
	
	// Arret de toutes les machines
	case "stop":
		if (($parc)  and ($parc<>"SELECTIONNER")) {
			if ($acces_restreint)  {  if ((!this_parc_delegate($login,$parc,"manage")) and (!this_parc_delegate($login,$parc,"view"))) { continue; } }
			echo "<h1>".gettext("Extinction des machines")."</h1>";
			echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"15; URL=action_parc.php?parc=$parc&action=detail\">";
			echo "</HEAD>".gettext("Commandes prises en compte pour le parc")." <b>$parc</b><br>";
//        		echo gettext("! <br>");
        		//echo gettext(" Commandes prises en compte ! ");
        		echo "<h3>".gettext("Arr&#234;t lanc&#233; pour le parc")." <b>$parc</b></h3>\n";
			echo"<br>";
//			echo gettext("(Ne concerne que les machines XP/2000)");
			$commandes=start_parc("shutdown", $parc);

 		} else { echo gettext("Vous devez choisir un parc"); }
	break;
        //==============================
	// Reboot de toutes les machines
	case "reboot":
		if (($parc)  and ($parc<>"SELECTIONNER")) {
			if ($acces_restreint)  {  if ((!this_parc_delegate($login,$parc,"manage")) and (!this_parc_delegate($login,$parc,"view"))) { continue; } }
			echo "<h1>".gettext("Redémarrage des machines")."</h1>";
			echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"15; URL=action_parc.php?parc=$parc&action=detail\">";
			echo "</HEAD>".gettext("Commandes prises en compte pour le parc")." <b>$parc</b><br>";
//        		echo gettext(" Commandes prises en compte ! <br>");
        		echo "<h3>".gettext("Reboot lanc&#233; pour le parc")." <b>$parc</b></h3>\n";
			echo "<br>";
//			echo gettext("(Ne concerne que les machines XP/2000)");
			$commandes=start_parc("reboot", $parc);

 		} else { echo gettext("Vous devez choisir un parc"); }
	break;
	//==============================

	// Essaye de demarrer les machines
	case "start":
		if (($parc)  and ($parc<>"SELECTIONNER")) {
			if ($acces_restreint)  {  if ((!this_parc_delegate($login,$parc,"manage")) and (!this_parc_delegate($login,$parc,"view"))) { continue; } }
			echo "<h1>".gettext("Démarrage des machines")."</h1>";
  			echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"15; URL=action_parc.php?parc=$parc&action=detail\">";
			echo "</HEAD>".gettext("Commandes prises en compte pour le parc")." <b>$parc</b><br>";
//			echo "Commandes prises en compte ! ";
			echo "<br>";
			echo "<h3>".gettext("Demarrage effectu&#233; pour le parc")." <b>$parc</b>. ".gettext("(Ne concerne que les machines equip&#233;es du syst&#232;me 'wake on lan')</h3>\n<br>");

	 		$commandes=start_parc("wol", $parc);
		} else { echo gettext("Vous devez choisir un parc"); }
	break;



	case "pose_heure":
		//pour obliger a passer par le formulaire
		//if ($force<>"pose_heure_wol") { exit; }

		//si parc non defini
		if ((!$parc) or ($parc=="SELECTIONNER")) { echo "<h1 align=center>".gettext("Vous devez choisir un parc!")."</h1>"; exit;}

		//si la personne n'a pas le droit d'effectuer cette action
		if ($acces_restreint)  {  if ((!this_parc_delegate($login,$parc,"manage")) and (!this_parc_delegate($login,$parc,"view"))) { continue; } }

		$result_delete=mysqli_query( $authlink, "DELETE FROM `actionse3` WHERE action='wol' and parc='$parc';") or die(gettext("Impossible d'effectuer la requete 1"));
		$result_delete=mysqli_query( $authlink, "DELETE FROM `actionse3` WHERE action='stop' and parc='$parc';") or die(gettext("Impossible d'effectuer la requete 2"));

		$wol=$_POST['wol'];
		$stop=$_POST['stop'];
		$heure_jour_wol=$_POST['time_day_wol'];
		$heure_jour_stop=$_POST['time_day_stop'];

		$jours=array('l','ma','me','j','v','s','d');
		foreach ($jours as $jour) {

			if ($wol[$jour]) {
				//echo "<br>3 $jour:INSERT INTO actionse3 values ('wol','$parc','$jour','$heure_jour_wol[$jour]');<br>";

				$result_insert=mysqli_query( $authlink, "INSERT INTO actionse3 values ('wol','$parc','$jour','$heure_jour_wol[$jour]');") or die(gettext("Impossible d'effectuer la requete 3 pour le jour")." $jour ");
			}

		if ($stop[$jour]) {
			//echo "4: $jour INSERT INTO actionse3 values ('stop','$parc','$jour','$heure_jour_stop[$jour]');<br>";

			$result_insert=mysqli_query( $authlink, "INSERT INTO actionse3 values ('stop','$parc','$jour','$heure_jour_stop[$jour]');") or die(gettext("Impossible d'effectuer la requete 4 pour le jour")." $jour");
		}
	}

	echo "<div id=main_txt>".gettext("Modifications enregistr&#233;es")."</div>";
	//break;

// On affiche le tableau avec la possibilite de programmer les arrets 
// et demarrages des stations
case "timing" :

		echo "<h1>".gettext("Programmation de l'allumage et de l'extinction des machines")."</h1>";
		$list_parcs=search_machines("objectclass=groupOfNames","parcs");
        	if ( count($list_parcs)>0) {
	                sort($list_parcs);
		        echo "<CENTER>";
		        echo "<FORM method=\"post\" action=\"wolstop_station.php\">\n";
			echo "<input type=\"hidden\" name=\"action\" value=\"timing\" />\n";
			echo "<SELECT NAME=\"parc\" SIZE=\"1\" onchange=submit()>";
		        echo "<option value=\"SELECTIONNER\">S&#233;lectionner</option>";
			if ($acces_restreint=="1") {
				$list_delegate=list_parc_delegate($login);
				foreach ($list_delegate as $info_parc_delegate) {
					echo "<option value=\"$info_parc_delegate\"";
					if ($parc==$info_parc_delegate) { echo " selected"; }
					echo ">$info_parc_delegate</option>";
				}
			} else {	
		        	for ($loop=0; $loop < count($list_parcs); $loop++) {
			        	echo "<option value=\"".$list_parcs[$loop]["cn"]."\"";
		                	if ($parc==$list_parcs[$loop]["cn"]) { echo " selected"; }
		                	echo ">".$list_parcs[$loop]["cn"]."\n";
		                	echo "</option>";
		       		}
			}	
			echo "</SELECT>&nbsp;&nbsp;\n";
			echo "<u onmouseover=\"return escape".gettext("('S&#233;lectionner un parc.')")."\"><img name=\"action_image2\"  src=\"../elements/images/system-help.png\" alt=\"help\"></u> ";
			// echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
			echo "</FORM>\n";
			echo "</CENTER>\n";
			echo "<br>";
		} else {
			echo "<br><center>";
			echo gettext("Il n'existe pas encore de parc");
			echo "</center>\n";
		}	


		if (($parc!="") && ($parc!="SELECTIONNER")) {
			echo "<CENTER><table align=center><tr>\n";
			echo "<td><form action=\"wolstop_station.php\" method=\"post\">\n";
   			echo "<input type=\"hidden\" name=\"wolstop_station.php\" value=\"shutdown\" />";
  			echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
  			echo "<input type=\"hidden\" name=\"action\" value=\"stop\" />";
  			echo "<input  type=\"submit\" value=\"".gettext("Eteindre tous les postes")."\" onclick=\"if (window.confirm('Etes-vous sur de vouloir &#233;teindre le parc $parc ?')) {return true;} else {return false;}\"/>";
   			echo"</form></td>";
       			echo "<td><form action=\"wolstop_station.php\" method=\"post\">\n";
   			echo "<input type=\"hidden\" name=\"action_poste\" value=\"wol\" />";
   			echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
   			echo "<input type=\"hidden\" name=\"action\" value=\"start\" />";
			echo "<input  type=\"submit\" value=\"".gettext("Allumer tous les postes")."\" />";
   			echo "</form></td>";
       			echo "<td><form action=\"action_parc.php\" method=\"post\">\n";
   			echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
			echo "<input type=\"submit\" value=\"".gettext("Contr&#244;ler")."\" />";
   			echo"</form>\n";
		
		
			echo "</td></tr></table>\n";
			echo "<br>";	
		}
	echo "<table align=center width=\"60%\">\n";
	echo "<tr><TD height=\"30\" align=\"center\" class=\"menuheader\" >\n";
      	echo gettext("Allumer les postes ( uniquement avec l'option wake on lan )")."</TD></tr>\n";
        echo "<TR><TD height=\"30\" align=\"center\" class=\"menuheader\" >".gettext("Eteindre les postes")."</TD></TR>\n";
	echo "</table>\n";

	if (($parc!="") && ($parc!="SELECTIONNER")) {
	$type_action="wol";
	$type_action2="stop";

	echo "<script language='javascript' type='text/javascript'>
	function coche_jours(mode,statut){
		for(k=0;k<7;k++){
			if(document.getElementById(mode+'_'+k)){
				document.getElementById(mode+'_'+k).checked=statut;
			}
		}
	}

	function recopie_heure(wakeorstop){
		index=document.getElementById('time_'+wakeorstop+'_0').selectedIndex;
		for(k=1;k<7;k++){
			document.getElementById('time_'+wakeorstop+'_'+k).selectedIndex=index;
		}
	}
	</script>\n";

	echo "<form action=\"wolstop_station.php\" method=\"post\">\n";

	echo "<CENTER><TABLE border=\"1\" width=\"60%\">\n";
	echo "<TR><TD  height=\"30\" align=\"center\"  class=\"menuheader\">";
	echo gettext("Planification par semaine : ");
	echo "</TD>\n<td  class=\"menuheader\" align=\"center\">";
	echo gettext("Allumage");
	echo "<br />\n";
	echo "<a href=\"javascript:coche_jours('allumage',true)\">";
	echo "<img src='../elements/images/enabled.png' alt='Cocher tout' title='Cocher tout' border='0' /></a>";
	echo " / \n";
	echo "<a href=\"javascript:coche_jours('allumage',false)\">";
	echo "<img src='../elements/images/disabled.png' alt='D&#233;cocher tout' title='D&#233;cocher tout' border='0' /></a>\n";
	echo "</td>\n";
	echo "<td  class=\"menuheader\" align=\"center\">";
	echo gettext("Heure");
	echo "<br />\n";
	echo "<a href=\"javascript:recopie_heure('wake');\">";
	echo "<img src='../elements/images/magic.png' alt='Recopie de l heure du lundi' title='Recopie de l heure du lundi' border='0' />";
	echo "</a>";
	echo " </td>\n";
	echo "<td  class=\"menuheader\" align=\"center\">".gettext("Extinction");
	echo "<br />\n";
	echo "<a href=\"javascript:coche_jours('extinction',true)\">";
	echo "<img src='../elements/images/enabled.png' alt='Cocher tout' title='Cocher tout' border='0' /></a>";
	echo " / \n";
	echo "<a href=\"javascript:coche_jours('extinction',false)\">";
	echo "<img src='../elements/images/disabled.png' alt='D&#233;cocher tout' title='D&#233;cocher tout' border='0' /></a>\n";
	echo "</td>\n";
	echo "<td  class=\"menuheader\" align=\"center\">".gettext("Heure");
	echo "<br />\n";
	echo "<a href=\"javascript:recopie_heure('stop');\">";
	echo "<img src='../elements/images/magic.png' alt='Recopie de l heure du lundi' title='Recopie de l heure du lundi' border='0' />";
	echo "</a>";
	echo " </td></tr>\n";

	echo "<tr><td align=\"center\">".gettext("Lundi")."</td>\n";
	echo "<td align=\"center\"><input type=\"checkbox\" id='allumage_0' name=\"wol[l]\" value=\"l\" ".jour_check($parc,"l",$type_action)."/></td>\n";
	echo "<td align=\"center\"><select id='time_wake_0' name=\"time_day_wol[l]\" size=\"1\">\n";
	heure_deroulante($parc,"l",$type_action);
	echo "</select></td>\n";
	echo "<td align=\"center\"><input type=\"checkbox\" id='extinction_0' name=\"stop[l]\" value=\"l\" ".jour_check($parc,"l",$type_action2)."/></td>\n";
	echo "<td align=\"center\"><select id='time_stop_0' name=\"time_day_stop[l]\" size=\"1\">\n";
	heure_deroulante($parc,"l",$type_action2);
	echo "</select></td></tr>\n";
	echo "<tr ><td align=\"center\">".gettext("Mardi")."</td><td align=\"center\"><input type=\"checkbox\" id='allumage_1' name=\"wol[ma]\" value=\"ma\" ".jour_check($parc,"ma",$type_action)."/></td>\n";
	echo "<td align=\"center\"><select id='time_wake_1' name=\"time_day_wol[ma]\" size=\"1\">\n";
	heure_deroulante($parc,"ma",$type_action);
	echo "</select></td>\n";
	echo "<td align=\"center\"><input type=\"checkbox\" id='extinction_1' name=\"stop[ma]\" value=\"l\" ".jour_check($parc,"ma",$type_action2)."/></td>\n";
	echo "<td align=\"center\"><select id='time_stop_1' name=\"time_day_stop[ma]\" size=\"1\">\n";
	heure_deroulante($parc,"ma",$type_action2);
	echo "</select></td></tr>\n";
	echo "<tr ><td align=\"center\">".gettext("Mercredi")."</td><td align=\"center\"><input type=\"checkbox\" id='allumage_2' name=\"wol[me]\" value=\"me\" ".jour_check($parc,"me",$type_action)."/></td>\n";
	echo "<td align=\"center\"><select id='time_wake_2' name=\"time_day_wol[me]\" size=\"1\">\n";
	heure_deroulante($parc,"me",$type_action);
	echo "</select></td>\n";
	echo "<td align=\"center\"><input type=\"checkbox\" id='extinction_2' name=\"stop[me]\" value=\"l\" ".jour_check($parc,"me",$type_action2)."/></td>\n";
	echo "<td align=\"center\"><select id='time_stop_2' name=\"time_day_stop[me]\" size=\"1\">\n";
	heure_deroulante($parc,"me",$type_action2);
	echo "</select></td></tr>\n";
	echo "<tr><td align=\"center\">".gettext("Jeudi")."</td><td align=\"center\"><input type=\"checkbox\" id='allumage_3' name=\"wol[j]\" value=\"j\" ".jour_check($parc,"j",$type_action)."/></td>\n";
	echo "<td align=\"center\"><select id='time_wake_3' name=\"time_day_wol[j]\" size=\"1\">\n";
	heure_deroulante($parc,"j",$type_action);
	echo "</select></td>\n";
	echo "<td align=\"center\"><input type=\"checkbox\" id='extinction_3' name=\"stop[j]\" value=\"l\" ".jour_check($parc,"j",$type_action2)."/></td>\n";
	echo "<td align=\"center\"><select id='time_stop_3' name=\"time_day_stop[j]\" size=\"1\">\n";
	heure_deroulante($parc,"j",$type_action2);
	echo "</select></td></tr>\n";
	echo "<tr><td align=\"center\">".gettext("Vendredi")."</td><td align=\"center\"><input type=\"checkbox\" id='allumage_4' name=\"wol[v]\" value=\"v\" ".jour_check($parc,"v",$type_action)."/></td>\n";
	echo "<td align=\"center\"><select id='time_wake_4' name=\"time_day_wol[v]\" size=\"1\">\n";
	heure_deroulante($parc,"v",$type_action);
	echo "</select></td>\n";
	echo "<td align=\"center\"><input type=\"checkbox\" id='extinction_4' name=\"stop[v]\" value=\"l\" ".jour_check($parc,"v",$type_action2)."/></td>";
	echo "<td align=\"center\"><select id='time_stop_4' name=\"time_day_stop[v]\" size=\"1\">\n";
	heure_deroulante($parc,"v",$type_action2);
	echo "</select></td></tr>";

	echo "<tr><td align=\"center\">".gettext("Samedi")."</td><td align=\"center\"><input type=\"checkbox\" id='allumage_5' name=\"wol[s]\" value=\"s\" ".jour_check($parc,"s",$type_action)."/></td>";

	echo "<td align=\"center\"><select id='time_wake_5' name=\"time_day_wol[s]\" size=\"1\">\n";
	heure_deroulante($parc,"s",$type_action);
	echo "</select></td>\n";
	echo "<td align=\"center\"><input type=\"checkbox\" id='extinction_5' name=\"stop[s]\" value=\"l\" ".jour_check($parc,"s",$type_action2)."/></td>";
	echo "<td align=\"center\"><select id='time_stop_5' name=\"time_day_stop[s]\" size=\"1\">\n";
	heure_deroulante($parc,"s",$type_action2);
	echo "</select></td></tr>\n";
	echo "<tr><td align=\"center\">".gettext("Dimanche")."</td><td align=\"center\"><input type=\"checkbox\" id='allumage_6' name=\"wol[d]\" value=\"d\" ".jour_check($parc,"d",$type_action)."/></td>";

	echo "<td align=\"center\"><select id='time_wake_6' name=\"time_day_wol[d]\" size=\"1\">\n";
	heure_deroulante($parc,"d",$type_action);

	echo "</select></td>\n";
	echo "<td align=\"center\"><input type=\"checkbox\" id='extinction_6' name=\"stop[d]\" value=\"l\" ".jour_check($parc,"d",$type_action2)."/></td>";
	echo "<td align=\"center\"><select id='time_stop_6' name=\"time_day_stop[d]\" size=\"1\">\n";
	heure_deroulante($parc,"d",$type_action2);
	echo "</select></td></tr>\n";

	echo "</tr><table>\n";


	echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"pose_heure\" />\n";
 	echo "</td></tr>\n";
	echo "<tr><td align=center><input type=\"hidden\" name=\"force\" value=\"pose_heure_wol\" />";
	echo "<input type=\"submit\" value=\"".gettext("Enregistrer mes modifications")."\" />";
	echo "</form><br><br></td></tr>\n";
	echo "</table>\n";
	}
break;


}

//echo "</div>";

if ($detail) {  detail_parc($parc); }

}

require ("pdp.inc.php");
?>
