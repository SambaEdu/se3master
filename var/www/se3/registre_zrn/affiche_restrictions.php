<?php

   /**
   
   * Gestion des cles pour clients Windows (affiche les restrictions pour un groupe donne)
   * @Version $Id$ 
   
  
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

connexion();

echo "<h1>".gettext("Gestion des templates")."  (".afficheniveau($testniveau).")</h1>\n";

//si la salle est passe en parametre !
if ($salle) {
	echo "<h2 align='center'>Template $salle</h2>\n";

	echo "<br><div align=\"center\">".gettext("Choisir le niveau de s&#233curit&#233 d&#233sir&#233")."<br><br>\n";
        echo "<form action=\"choisir_protect.php\" name=\"choix niveau\" method=\"post\">\n";
        echo "<select name=\"mod\" size=\"1\"><option value=\"nobase\">".gettext("Aucune protection")."</option>\n";

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

	$query="Select groupe,cleID from restrictions where restrictions.groupe='$salle'";
	$resultat = mysql_query($query);
	$rowserv = mysql_fetch_row($resultat);
	
	if (!$rowserv[0]) {
		//cette salle n'est pas encore inscrite, creation , creation du repertoire non faite !!!!
     		if ($rest!="yes") {
     			echo "<br><h3>".gettext("Le template")." $salle ".gettext("n'a aucune restrictions d&#233finies actuellement")."</h3> <br>\n";
     			echo "<FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\" >\n";
     			echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$salle\">\n";
     			echo "<INPUT TYPE=\"submit\" value=\"Ajouter une cl&#233;\" name=\"ajoutcle\"></FORM>\n";
     		} else {
         		refreshzrn($salle);
         		if ($salle=='base') {
         			applique_modele(gettext("Restrictions par d&#233faut"),$salle,"oui");
         			$handlelecture=opendir('/home/templates');
         			
				while ($file = readdir($handlelecture)) {
         				if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser' and $file<>'base') { 
						refreshzrn($file);  
					}
         			}
         			closedir($handlelecture);
         			echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_restrictions.php?salles=$salle\">\n";
         			echo "</HEAD>".gettext("Modification effectu&#233e pour le groupe :")." $salle<br>\n";
         			echo gettext("Commandes prises en compte ! ");
         		}
      		}
 	} else {
		affichelistecat("affiche_restrictions.php?salles=$salle",$testniveau,$cat);
		if (($cat) and !($cat=="tout")) {   
			$ajout=" and corresp.categorie = '$cat'";
        		if ($_GET['sscat']) {$ajoutsscat=" AND corresp.sscat='$sscat';";
        		echo "<h3>".gettext("Sous-cat&#233gorie")." : $sscat</h3>\n";
         		} else {
				$ajoutsscat=""; 
			}
        		
			if (($testniveau==2) and !($sscat)) { $ajoutpasaffiche=" and corresp.sscat= '' "; }
    		} else {
       			echo "<h3>".gettext("Choisissez une cat&#233gorie ci-dessus")."</h3><br>\n";
       			$ajout=" and corresp.categorie = '' ";
       			if ($cat=="tout") {
       				$ajout="";
       				if ($sscat) {$ajoutsscat=""; }
       			}
     		}
		
		connexion();
		echo "<H2>".gettext("Restrictions en cours pour")." $salle</H2>\n";
		$query="Select corresp.CleID,restrictions.valeur from restrictions,corresp where restrictions.groupe='$salle' and corresp.CleID=restrictions.cleID ".$ajout.$ajoutsscat.$ajoutpasaffiche;
		$n=0;
		$resultat = mysql_query($query);
		
		if (mysql_num_rows($resultat)) {
			//affichage de l'en-tete du tableau en fonction des cas
			//affichage different selon les templates :affichage dans base avec valeur modifiable
			$possmodif="<td><img src=\"/elements/images/edit.png\" alt=\"".gettext("Modifier")."\" title=\"Modifier\" width=\"16\" height=\"16\" border=\"0\" /></td>\n";
			
			if ($cat=="tout") { 
				$affichetout="<td><DIV ALIGN=CENTER>".gettext("Cat&#233gorie")."</DIV></td><td><DIV ALIGN=CENTER>".gettext("Sous-Cat&#233gorie")."</DIV></td>\n"; 
			}
			
			echo "<table border=\"1\" ><tr><td><img src=\"/elements/images/help.png\" alt=\"".gettext("Aide")."\" title=\"Aide\" width=\"16\" height=\"18\" border=\"0\" />\n";
			echo "</td>$affichetout <td><DIV ALIGN=CENTER>".gettext("Intitul&#233")."</DIV></td>\n";
			echo "<td>".gettext("OS")."</td><td><DIV ALIGN=CENTER>".gettext("Valeur")."</DIV></td>\n";
			echo "$possmodif<td><img src=\"/elements/images/edittrash.png\" alt=\"".gettext("Supprimer")."\" title=\"Supprimer\" width=\"16\" height=\"16\" border=\"0\" /></td></tr>\n";
		} else {   
			echo gettext("Pas de restrictions d&#233finies pour cette s&#233lection<br>");
    			retour();
    			echo "</p></p><FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\" >\n";
             		echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$salle\">\n";
             		echo "<INPUT TYPE=\"hidden\" name=\"keygroupe\" value=\"4\" >\n";
             		echo "<INPUT TYPE=\"submit\" value=\"".gettext("Incorporer des groupes de cl&#233s")."\" name=\"modifcle\">\n";
             		echo "</form>\n";
             		echo "</p><FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\" >\n";
             		echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$salle\">\n";
             		echo "<INPUT TYPE=\"submit\" value=\"".gettext("Ajouter une cl&#233")."\" name=\"ajoutcle\">\n";
             		echo "</FORM>\n";
    			exit;
		}


		//cas ou il y a des restrictions on va les afficher
		while ($row1 = mysql_fetch_array($resultat)) {
    			$n++;
    			//on verifie qu'elles ne sont pas nulles
    			if ($row1[0]==0) { //il n'y a pas encore de restrictions
     			}  else  {
   				//il y a deja des restrictions posees non nulles
          			$query="Select Intitule,cleID,valeur,genre,OS,type,antidote,chemin,categorie,sscat from corresp where corresp.CleID='$row1[0]'";
          			$resultat1 = mysql_query($query);

          			while ( $row = mysql_fetch_array($resultat1)) {
   					//pour la liste des cles a modifier ou a supprimer
           				$liste=$row[1]."-".$liste;
           				$valeur="Non d&#233;finie";
   					//pour la couleur de fond verte ou rouge
           				$couleur="#FFFFFF";
           				
					if ($row1[1]==$row[6]) { 
						$valeur=$row1[1];
           					$etat=gettext("Cl&#233 d&#233sactiv&#233e");
           					$couleur="#00FF00";
					}
           				
					if ($row1[1]==$row[2]) { 
						$valeur=$row1[0];
           					$etat=gettext("Cl&#233 activ&#233e");
           					$couleur="#FF0000";
           				}
           				
					if (($salle=="base") or ($row[5]=="config")) {
           					$modif="<td><a href=\"ajout_cle_groupe.php?salles=$salle&modif=$row1[0]&keygroupe=11\" title=\"modifier cette cl&#233\"><img src=\"/elements/images/edit.png\" alt=\"".gettext("Modifier la valeur")."\" title=\"Modifier la valeur\" width=\"16\" height=\"16\" border=\"0\" /></a></td>\n";
           				} else  {
						$modif="<td><img src=\"/elements/images/editpale.png\" alt=\"".gettext("Valeur non modifiable")."\" title=\"Valeur non modifiable\" width=\"16\" height=\"16\" border=\"0\" /></a></td>\n";
           				}

   					//bouton aide
         				echo "<tr><td><a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row1[0]','aide','scrollbars=yes,width=600,height=620')\">\n";
					echo "<img src=\"/elements/images/help.png\" alt=\"aide\" title=\"$row[7]\" width=\"15\" height=\"15\" border=\"0\"></a></td>\n";
     					//  <a href=\"aide_cle.php?cle=$row1[0]\" target=\"_blank\" ><img src=\"/elements/images/help.png\"
     					//    alt=\"aide\" title=\"$row[7]\" width=\"16\" height=\"18\" border=\"0\" /></a></td>\n";
   //eventuel affichage des categories
         
	 				if ($cat=="tout"){
						echo "<td><DIV ALIGN=CENTER>$row[8]</DIV></td><td><DIV ALIGN=CENTER>$row[9]</DIV></td>\n";
					}
         				
					echo "<td><DIV ALIGN=CENTER>$row[0]</DIV></td><td><DIV ALIGN=CENTER>&nbsp;$row[4]</DIV></td>\n";
         				if ($row[5]=="config") { echo "<td>$row1[1]</td>\n";}
         				if ($row[5]=="restrict"){ echo "<td BGCOLOR=$couleur>$row1[1]</td>\n"; }
         				
					echo "$modif <td><a href=\"ajout_cle_groupe.php?salles=$salle&suppr=$row1[0]&keygroupe=12\" >\n";
         				echo "<img src=\"/elements/images/edittrash.png\" alt=\"".gettext("Supprimer")."\" title=\"Supprimer\"  width=\"16\" height=\"16\" border=\"0\" /></a></td></tr>\n";
         			}

     			}

		}
		echo"</table> ";

		if ($salle=="base") {
			echo "<a href=\"ajout_cle_groupe.php?salles=$salle&keygroupe=1&listemodif=$liste\">".gettext("Modifier les cl&#233s affich&#233es")."</a><br>\n";
		}

		echo "<a href=\"ajout_cle_groupe.php?salles=$salle&keygroupe=1&listesuppr=$liste\">".gettext("Supprimer les cl&#233s affich&#233es")."</a>\n";
		echo "</p>\n<FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\" >\n";
         	echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$salle\">\n";
         	echo "<INPUT TYPE=\"submit\" value=\"".gettext("Ajouter une cl&#233")."\" name=\"ajoutcle\">\n";
         	echo "</FORM>\n";
	}
} else { echo gettext(" Choisir un template")." <br>\n"; }

mysql_close();
retour();

include("pdp.inc.php");
?>
