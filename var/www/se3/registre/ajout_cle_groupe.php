<?php

   /**
   
   * Gestion des cles pour clients Windows (effectue les actions sur la table restrictions c'est a dire sur les templates)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: ajout_cle_groupe.php

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


echo "<h1>".gettext("Gestion des cl&#233;")."</h1>";

$testniveau=getintlevel();
$act=$_POST['ajoutcle'];
$autre=$_POST['modifcle'];
$salle=$_POST['salles'];

if (!$salle) { $salle=$_GET['salles']; }

connexion();

if (! isset($_POST['groups'])) {
	//incorporation d'un modele
	$query="SELECT `mod` FROM modele GROUP BY `mod`;";
	$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
	echo gettext("Choisir le modele &#224 incorporer au groupe")." $salle <br><FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\" >";
	$i=0;
	while ($row = mysqli_fetch_array($resultat)) {    
		echo " <input type=\"checkbox\" name=\"modele$i\" id=\"modele$i\" value=\"$row[0]\"/><label for='modele$i'> $row[0]</label><br/>\n";
		$choix[$i]=$row[0];
		$i++;
	}
	echo "</select>";
	echo "<INPUT TYPE=\"hidden\" name=\"groups\" value=\"1\">";
	echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$salle\">";
	echo "<INPUT TYPE=\"hidden\" name=\"nombre\" value=\"$i\">";
	echo "<INPUT TYPE=\"submit\" name=\"inscrire\" value=\"".gettext("Ajouter ces groupes de cl&#233s au template")."\"></FORM>";

	echo "<br>".gettext("Attention, toute cl&#233 non pr&#233sente dans base y sera &#233galement ajout&#233e afin de respecter la coh&#233rence de vos restrictions")." <br>";
}
else {
	$nombre=$_POST['nombre'];
        $priorite = priorite($salle);
	for ($n=0;$n<$nombre;$n++) {
		$mod=$_POST['modele'.$n];
		$query="SELECT `cle`,`etat` FROM `modele` WHERE `mod`= '$mod' ;";
		$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
		while ($row=mysqli_fetch_row($resultat)) {
			$cle=$row[0];
			$query = "SELECT cleID,Intitule,valeur,antidote,type FROM corresp WHERE cleID='$cle';";
			$insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
			$row1 = mysqli_fetch_row($insert);
			$query = "SELECT cleID,valeur FROM restrictions WHERE cleID='$cle' AND groupe='$salle';";
			$verif = mysqli_query($GLOBALS["___mysqli_ston"], $query);
			$row2=mysqli_fetch_row($verif);
			
			if ($row[1] == "1") {
				$row1[2]=ajoutedoublebarre($row1[2]);
				if ($row2[0]) {
					$query = "UPDATE `restrictions` SET `valeur` = '$row1[2]',priorite='$priorite' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
					$insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
				} else {
					$query="INSERT INTO restrictions (resID,valeur,cleID,groupe,priorite) VALUES ('','$row1[2]','$row[0]','$salle','$priorite');";
					$insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
				}
			}
			else{
				if ($row1[4] == "config") {
					$query="DELETE FROM restrictions where cleID='$cle';";
					$insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
				}
				else {
					if ($row2[0]) {
						$query = "UPDATE `restrictions` SET `valeur` = '$row1[3]',priorite='$priorite' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
						$insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
					} else {
						$query="INSERT INTO restrictions (resID,valeur,cleID,groupe,priorite) VALUES ('','$row1[3]','$row[0]','$salle','$priorite');";
						$insert = mysqli_query($GLOBALS["___mysqli_ston"], $query);
					}
				}
			}
		}
	}
	echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_restrictions.php?salles=$salle\"></HEAD>";
}


include("pdp.inc.php");
?>
