<?php

   /**
   
   * Action sur un parc (arret - start) 
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  sandrine dangreville matice creteil 2005

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: parcs/
   * file: action_parc.php

  */	




include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
include "fonc_parc.inc.php";

// Internationnalisation
require_once ("lang.inc.php");
bindtextdomain('se3-parcs',"/var/www/se3/locale");
textdomain ('se3-parcs');

//aide
$_SESSION["pageaide"]="Gestion_des_parcs#Action_sur_parcs";

     
//***************Definition des droits de lecture  et aide en ligne

// Verifie les droits
if ((is_admin("computers_is_admin",$login)=="Y") or (is_admin("parc_can_view",$login)=="Y") or (is_admin("parc_can_manage",$login)=="Y") or
(is_admin("inventaire_can_read",$login)=="Y")) {



	//affichage du menu haut
	// affiche_action($parc);


	echo "<h1>".gettext("Action sur les stations")."</h1>";

	//affichage de la suite
	// echo "<div id=main>";

	//*****************cas des parcs delegues***********************************/
	if ((is_admin("computers_is_admin",$login)=="N") and ((is_admin("parc_can_view",$login)=="Y") or (is_admin("parc_can_manage",$login)=="Y"))) { 
		echo "<h3>".gettext("Votre d&#233;l&#233;gation a &#233;t&#233; prise en compte pour l'affichage de cette page.")."</h3>"; $acces_restreint=1;

		$list_delegate=list_parc_delegate($login);
		
		if (count($list_delegate)>0) {
			$delegate="yes";	
		} else {
			echo "<center>";
			echo "Vous n'avez pas de parc d&#233;l&#233;gu&#233;";
			echo "</center>\n";
			exit;
			
		}
	} 

	/************************* Declaration des variables ************************************/
	$action=$_POST['action'];
	if (!$action) { $action=$_GET['action'];}
	$parc=$_POST['parc'];
	if (!$parc) { $parc=$_GET['parc'];}

	if ($action=="") { $action="detail"; }
	if ($action=="choix_time") {
                $action="detail";
                echo "<center>Veuillez patienter, puis rafraichir la page, le temps que la machine d&#233;marre ou stop";
                echo "</center><br>\n";
        }

	switch ($action) {

	case "detail":

		$list_parcs=search_machines("objectclass=groupOfNames","parcs");
       		if ( count($list_parcs)>0) {
	                sort($list_parcs);
		        echo "<CENTER>";
		        echo "<FORM method=\"post\" action=\"action_parc.php\">\n";
		        echo "<SELECT NAME=\"parc\" SIZE=\"1\" onchange=submit()>";
		        echo "<option value=\"SELECTIONNER\">S&#233;lectionner</option>";
			if ($delegate=="yes") {

				foreach ($list_delegate as $info_parc_delegate) {
			        	echo "<option value=\"".$info_parc_delegate."\"";
		                	if ($parc==$info_parc_delegate) { echo " selected"; }
					echo ">$info_parc_delegate</option>\n";

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
			//      echo "<input type=\"submit\" value=\"".gettext("Valider")."\">\n";
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

			// Test le niveau de delegation
			// manage ou view
			// Si manage on peut aller sur programmer
			$niveau_delegation = niveau_parc_delegate($login,$parc);
       			if ($niveau_delegation != "view") { 
				echo "<td><form action=\"wolstop_station.php\" method=\"post\">\n";
   				echo "<input type=\"hidden\" name=\"programmation\" value=\"check\" />";
   				echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
   				echo "<input type=\"hidden\" name=\"action\" value=\"timing\" />";
				echo "<input type=\"submit\" value=\"".gettext("Programmer")."\" />";
   				echo"</form>\n";
				echo "</td>\n";
			}	
				
			echo "</tr>\n";
		
			echo "<tr>\n";	
       			echo "<td colspan=\"3\" align=\"center\"><form action=\"action_parc.php\" method=\"post\">\n";
   			echo "<input type=\"hidden\" name=\"action_poste\" value=\"check\" />";
   			echo "<input type=\"hidden\" name=\"parc\" value=\"$parc\" />";
   			echo "<input type=\"hidden\" name=\"action\" value=\"detail\" />";
			echo "<input type=\"submit\" value=\"".gettext("Rafraichir la page")."\" />";
   			echo"</form>\n";
		
			echo "</td></tr></table>\n";
		
			echo "</center>\n";
  		
			detail_parc($parc);
			echo "<br>";
			detail_parc_printer($parc);
  			//$heure_act=date("H");
   			$nomjour=date("l");
			//  echo $nomjour;
   	
		}

	switch ($nomjour) {
	
		case "Monday":
		$nomjour="l";
		break;
		
		case "Tuesday":
		$nomjour="ma";
		break;
	
		case "Wednesday":
		$nomjour="me";
		break;
	
		case "Thursday":
		$nomjour="j";
		break;
	
		case "Friday":
		$nomjour="v";
		break;
	
		case "Saturday":
		$nomjour="s";
		break;
	
		case "Sunday":
		$nomjour="d";
		break;
	} 
   	
	$resultf=mysqli_query( $authlink, "select heure,action from actionse3 where parc='$parc' and jour='$nomjour' ;") or die("Impossible d'effectuer la requete");
	if ($resultf) {
		if (mysqli_num_rows($resultf)>0) {
			while ($row=mysqli_fetch_row($resultf)) {
				if ($row[1]=="wol") { 
					echo "<h3>".gettext("Allumage des stations pr&eacute;vu &agrave;")." $row[0] ".gettext("ce jour")."</h3>"; 
				}
				if ($row[1]=="stop") { 
					echo "<h3>".gettext("Extinction des stations pr&eacute;vu &agrave;")." $row[0] ".gettext("ce jour")."</h3>"; 
				}
			}
 		} else { 
			if (($parc!="")	&& ($parc!="SELECTIONNER")) {
				echo "<h3>".gettext("Pas d'actions pr&eacute;vues aujourd'hui sur le parc")." $parc</h3>";
 			}
		}
	}
  	break;
}

// echo "</div>";

}

require ("pdp.inc.php");

?>
