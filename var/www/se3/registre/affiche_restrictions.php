<?php

   /**
   
   * Gestion des cles pour clients Windows (affiche les restrictions pour un groupe donne)
   * @Version $Id: affiche_restrictions.php 4126 2009-06-10 13:53:01Z crob $ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: affiche_restrictions.php

  */	



$cat=$_GET['cat'];
if (!$cat) { $cat=$_POST['cat']; }
$sscat=$_GET['sscat'];
if (!$cat) { $cat=$HTTP_COOKIE_VARS["Categorie"]; }
if ($cat) {
	setcookie ("Categorie", "", time() - 3600);
	setcookie("Categorie",$cat,time()+3600);
}
if (!$sscat) { $sscat=$HTTP_COOKIE_VARS["Sous-Categorie"]; }
if ($sscat) {
	setcookie ("Sous-Categorie", "", time() - 3600);
	setcookie("Sous-Categorie",$sscat,time()+3600);
}

require "include.inc.php";
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');

if (ldap_get_right("computers_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");
	
$_SESSION["pageaide"]="Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";


$testniveau=getintlevel();
$salle=$_POST['salles'];
$rest=$_POST['poser'];
$salle1=$_GET['salles'];
$rest1=$_POST['poser'];

if (!$rest) { $rest=$rest1;};
if (!$salle) {$salle=$salle1;}

if (isset($_GET['clean'])) {
	connexion ();
	$deleteSQL = "delete from restrictions where groupe='$salle';";
	mysql_query($deleteSQL);
	mysql_close();
}
connexion();

echo "<h1>".gettext("Gestion du template ").$salle."</h1>\n";

//si la salle est passe en parametre !
if ($salle) {
	echo "<center><form action=\"choisir_protect.php\" name=\"choix niveau\" method=\"post\">\n";
	echo "<select name=\"mod\" size=\"1\"><option value=\"norestrict\">".gettext("Aucune protection")."</option>\n";

	$query="SELECT `mod` FROM modele GROUP BY `mod`;";
	$resultat = mysql_query($query);
	
	while ($row = mysql_fetch_array($resultat)) { 
          if ($salle == "base" or $row[0] <> "norestrict") {
			echo "<option value=\"$row[0]\">$row[0]</option>\n";   
		}
	}
      	
	echo "</select>\n<input type=\"hidden\" name=\"salles\" value=\"$salle\" />\n";
	echo "<input type=\"submit\" value=\"J'ai choisi ce niveau de s&#233;curit&#233;\" />\n</form>\n</div>\n";
	echo "<div align=\"center\">\n";
	echo "<FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\" >\n";
	echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$salle\">\n";
	echo "<INPUT TYPE=\"hidden\" name=\"retour\" value=\"choisir_protect.php\">\n";
	echo "<INPUT TYPE=\"hidden\" name=\"keygroupe\" value=\"4\" >\n";
	echo "<INPUT TYPE=\"submit\" value=\"".gettext("Incorporer des groupes de cl&#233s")."\" name=\"modifcle\">\n";
	echo "</form>\n</div>\n";

	affichelistecat("affiche_restrictions.php?salles=$salle",$testniveau,$cat);
	if ($cat != "" and $cat != "tout") {   
		$ajout="where corresp.categorie = '$cat'";
			if ($_GET['sscat']) {
				$ajoutsscat=" AND corresp.sscat='$sscat';";
			} else {
				$ajoutsscat=""; 
			}
			
		if (($testniveau==2) and !($sscat)) { $ajoutpasaffiche=" and corresp.sscat= '' "; }
    } else if ($cat=="tout") {
			
			if ($cat=="tout") {
				$ajout="";
				if ($sscat) {$ajoutsscat=""; }
			}
	}
	else {
		echo "<h3>".gettext("Choisissez une cat&#233gorie ci-dessus")."</h3><br>\n";
		exit (0);
	}

	connexion();
	$query="Select Intitule,cleID,valeur,genre,OS,antidote,type,chemin from corresp ".$ajout.$ajoutsscatvide.$ajoutsscat;
	$queryRest="Select cleID, valeur from restrictions where groupe = '".$salle."';";
	$n=0;
	$resultat = mysql_query($query);
	$rest     = mysql_query($queryRest);

	if (mysql_num_rows($resultat)) {
		//affichage de l'en-tete du tableau en fonction des cas
		echo "<table border=\"1\" ><tr BGCOLOR=#fff9d3><td><img src=\"/elements/images/system-help.png\" alt=\"".gettext("Aide")."\" title=\"Aide\" width=\"16\" height=\"18\" border=\"0\" />\n";
		echo "</td>$affichetout <td><DIV ALIGN=CENTER>".gettext("Intitul&#233")."</DIV></td>\n";
		echo "<td><DIV ALIGN=CENTER>".gettext("OS")."</DIV></td><td><DIV ALIGN=CENTER>".gettext("Etat")."</DIV></td><td><DIV ALIGN=CENTER>".gettext("Editer")."</DIV></td>\n";
	}

	while ($row = mysql_fetch_array($resultat)) {
		//bouton aide
		echo "<tr><td><a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row[1]','aide','scrollbars=yes,width=600,height=620')\">\n";
		echo "<img src=\"/elements/images/system-help.png\" alt=\"aide\" title=\"$row[7]\" width=\"15\" height=\"15\" border=\"0\"></a></td>\n";
		echo "<td><DIV ALIGN=CENTER>$row[0]</DIV></td>\n";
		echo "<td><DIV ALIGN=CENTER>$row[4]</DIV></td>\n";
		$act=False;
		while ($rowRest = mysql_fetch_array($rest)) {
			if ($rowRest[0] == $row[1]) {
				if ($row[6] == "config") {
					echo "<td BGCOLOR=#a5d6ff><DIV ALIGN=CENTER>Active</DIV></td>";
					$state = 1;
				}
				else if ($rowRest[1] == $row[5]) {
					echo "<td BGCOLOR=#e0dfde><DIV ALIGN=CENTER>Inactive</DIV></td>";
					$state = 0;
				}
				else {
					echo "<td BGCOLOR=#a5d6ff><DIV ALIGN=CENTER>Active</DIV></td>";
					$state = 1;
				}
				$act=True;
				break;
			}
		}
		if (mysql_num_rows ($rest) > 0)
			mysql_data_seek($rest, 0);
		if ($act == False) {
			echo "<td><DIV ALIGN=CENTER>Non configur&eacute;e</DIV></td>";
			$state = -1;
		}

		echo "<td><DIV ALIGN=CENTER><a href=\"#\" onClick=\"window.open('edit_cle.php?cle=$row[1]&template=$salle&state=$state&value=$rowRest[1]','Editer','scrollbars=yes,width=600,height=620')\">\n";
		echo "<img src=\"/elements/images/edit.png\" alt=\"Editer\" title=\"$row[7]\" width=\"15\" height=\"15\" border=\"0\"></a></DIV></td>\n";
	}
}
echo"</table> ";

mysql_close();
echo "<br/><a href=\"affiche_restrictions.php?clean=1&salles=$salle\" onclick=\"return getconfirm();\"> Remise &agrave; z&eacute;ro</a><br/><br/>\n";
echo "</center>\n";

include("pdp.inc.php");
?>
