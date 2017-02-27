<?php

   /**
   
   * Gestion des cles pour clients Windows (acces a l'aide sur une cle particuliere)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: aide_cle.php

  */	


include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');

if (ldap_get_right("computers_is_admin",$login)!="Y")
        die (gettext("Vous n'avez pas les droits suffisants pour acc&#233;der &#224; cette fonction")."</BODY></HTML>");

	$_SESSION["pageaide"]="Gestion_des_clients_windows#Description_du_processus_de_configuration_du_registre_Windows";


require "include.inc.php";

$num=$_GET['cle'];
$case=$_GET['act'];
connexion();
echo "<title>".gettext("Commentaires sur les cl&#233s de registre")."</title>";
switch ($case) {
	//cas 2 : faire ecrire le nouveau commentaire
	//cas 3 :ajout dans la base du nouveau ommentaire
	//par default affichage du commentaires sur la cle

	//faire ecrire le nouveau commentaire
	case "2":
    		$query="SELECT comment,Intitule FROM corresp WHERE cleID='$num'";
    		$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    		$row = mysqli_fetch_array($resultat);
    		echo" <FORM METHOD=GET ACTION=\"aide_cle.php\">";
    		echo "<TEXTAREA NAME=\"newcom\" ROWS=\"30\" COLS=\"30\">$row[0]</TEXTAREA>";
    		echo "<INPUT TYPE=\"hidden\" name=\"act\" value=\"3\">";
    		echo "<INPUT TYPE=\"hidden\" name=\"cle\" value=\"$num\">";
    		echo "<INPUT TYPE=\"submit\" value=\"".gettext("Modifier")."\">";
    		echo "</FORM>";
		break;

	//ajout dans la base du nouveau commentaire
	case "3":
    		$newcomok=$_GET['newcom'];
    		$clef=$_GET['clef'];
    		$query="UPDATE corresp SET comment='$newcomok' where cleID='$num';";
    		$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    		echo gettext("Commentaire mis &#224 jour")."<br>";
    		$query="SELECT comment FROM corresp WHERE cleID='$num'";
    		$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    		$row = mysqli_fetch_array($resultat);
    		echo $row[0];
		//attention pas de break car affichage des cles a la suite

	//par default affichage du commentaires sur la cle
	default :
    		$query="SELECT comment,Intitule,chemin,OS,categorie,sscat,type FROM corresp WHERE CleID='$num'";
    		$resultat = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    		$row = mysqli_fetch_array($resultat);
    		echo "<br><H3>".gettext(" Cle :").$row[1]." </H3><br> Type : ".$row[6];
    		if ($row[4]) {
    			echo"<h2>".gettext("Cat&#233gorie :")." $row[4]</h2>"; 
		}
     		if ($row[5]) {
    			echo"<h3>".gettext("Sous- Cat&#233gorie :")." $row[5]</h3>"; 
		}
    		echo "<br>Chemin : $row[2]<br> Os : $row[3]<br>";
    		echo "Commentaire : ".$row[0]."<br>";
    		echo "<br><a href=\"aide_cle.php?cle=$num&act=2\">".gettext("Modifier le commentaire")."</a><br>";
		break;
}
	
echo gettext("Liste des templates concern&#233s par cette cl&#233");
$query1="SELECT restrictions.groupe,restrictions.valeur FROM restrictions WHERE restrictions.cleID = '$num'";
$chercher = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
$i=0;
echo "<table border=\"1\"><tr><td>".gettext("Templates concern&#233s</td><td>Valeur actuelle dans le template")."</td></tr>";
while ($liste=mysqli_fetch_row($chercher)) {
	echo "<tr><td><a href=affiche_restrictions.php?salles=$liste[0]&poser=yes\" ><div align=\"center\">$liste[0]</div></a></td><td><div align=\"center\">$liste[1]</div></td></tr>";
}

echo "</table>";
echo gettext("Liste des groupes de cl&#233s concern&#233s par cette cl&#233");
$query1="SELECT `mod` , `etat` FROM `modele` WHERE `cle` = '$num'";
$chercher = mysqli_query($GLOBALS["___mysqli_ston"], $query1);
$i=0;
echo "<table border=\"1\"><tr><td>".gettext("Groupes concern&#233s")."</td><td>".gettext("Etat dans le groupe de cl&#233")."</td></tr>";

while ($liste=mysqli_fetch_row($chercher)) {
	echo "<tr><td><a href=affiche_modele.php?mod=$liste[0]&modact=yes \" ><div align=\"center\">$liste[0]</div></a></td><td><div align=\"center\">$liste[1]</div></td></tr>";
}

echo "</table>";
retour();
((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);


include("pdp.inc.php");

?>
