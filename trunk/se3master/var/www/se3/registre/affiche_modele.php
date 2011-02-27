<?php

   /**
   
   * Gestion des cles pour clients Windows (affiche les restrictions pour un modele)
   * @Version $Id: affiche_restrictions.php 4126 2009-06-10 13:53:01Z crob $ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: affiche_modele.php

  */    


if (isset($_GET['cat']))
	$cat=$_GET['cat'];
if (isset($_POST['cat'])) 
	$cat=$_POST['cat'];
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

if (isset($_POST['modele'])) $modele=$_POST['modele'];
if (isset($_GET['modele'])) $modele=$_GET['modele'];

connexion();

echo "<h1>".gettext("Gestion des groupes")."  (".afficheniveau($testniveau).")</h1>\n";


if (isset($_POST['delete'])) {
	mysql_query("delete from modele where `mod`='".$_POST['delete']."'");
	echo "<a href=\"indexgrp.php\"> Retour aux groupes</a>";
}

//si la salle est passe en parametre !
if ($modele != "") {
    echo "<h2 align='center'>Groupe de cl&#233s \"$modele\"";
	echo "<form method=post action=\"affiche_modele.php\">";
	echo "<input type=\"hidden\" value=\"$modele\" name=\"delete\">";
	echo "<input type=\"submit\" value=\"Supprimer le groupe\">";
	echo "</form></h2>\n";

    affichelistecat("affiche_modele.php?modele=$modele",$testniveau,$cat);
    if (($cat) and !($cat=="tout")) {   
        $ajout="where corresp.categorie = '$cat'";
            if ($_GET['sscat']) {$ajoutsscat=" AND corresp.sscat='$sscat';";
            echo "<h3>".gettext("Sous-cat&#233gorie")." : $sscat</h3>\n";
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
    $queryRest="SELECT `cle`,`etat` FROM `modele`  WHERE `mod` = '$modele' ";
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
                if ($rowRest[1] == '0') {
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

        echo "<td><DIV ALIGN=CENTER><a href=\"#\" onClick=\"window.open('edit_cle_grp.php?cle=$row[1]&modele=$modele&state=$state&etat=$rowRest[1]','Editer','scrollbars=yes,width=600,height=620')\">\n";
        echo "<img src=\"/elements/images/edit.png\" alt=\"Editer\" title=\"$row[7]\" width=\"15\" height=\"15\" border=\"0\"></a></DIV></td>\n";
    }
}
echo"</table> ";

mysql_close();

include("pdp.inc.php");
?>
