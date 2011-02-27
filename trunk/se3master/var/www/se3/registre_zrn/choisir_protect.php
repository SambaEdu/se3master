<?php

   /**
   
   * Gestion des cles pour clients Windows (affiche les modeles a appliquer aux templates)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: choisir_protect.php

  */	



require "include.inc.php";
connexion();
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

if ($testniveau==1) { $affiche="non"; } else { $affiche="oui"; }
echo "<h1>".gettext("Gestion des templates")."  (".afficheniveau($testniveau).")</h1>";
$template=$_POST['salles'];
$mod=$_POST['mod'];
if (!$template) { $template=$_GET['salles'];}

if ($template) { 
	echo"<h2>".gettext("Modification pour le template")." $template</h2><br><br>";
  	if (!$mod) {
		echo "<br><div align=\"center\">".gettext("Choisir le niveau de s&#233curit&#233 d&#233sir&#233")."<br><br><form action=\"choisir_protect.php\" name=\"choix niveau\" method=\"post\"><select name=\"mod\" size=\"1\">";


      		$query="SELECT `mod` FROM modele GROUP BY `mod`;";
      		$resultat = mysql_query($query);
      		echo "<option value=\"nobase\">".gettext("Aucune protection")."</option>";
      		while ($row = mysql_fetch_array($resultat)) {    
			echo" <option value=\"$row[0]\">$row[0]</option>"; 
		}

      		echo "</select><input type=\"hidden\" name=\"salles\" value=\"$template\" />";
      		echo "<input type=\"submit\" value=\"".gettext("J'ai choisi ce niveau de s&#233curit&#233")."\" /></form></div><br><br>";
      		if ($testniveau>1) {
       			if ($testniveau>2) {
      				echo"<HEAD>  <META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=affiche_restrictions.php?salles=$template\">
  </HEAD>";
      			}
       			echo "<p><div align=\"center\"><FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\" >";
         		echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$template\">";
         		echo "<INPUT TYPE=\"hidden\" name=\"retour\" value=\"choisir_protect.php\">";
         		echo "<INPUT TYPE=\"hidden\" name=\"keygroupe\" value=\"4\" >";
         		echo "<INPUT TYPE=\"submit\" value=\"".gettext("Incorporer des groupes de cl&#233s")."\" name=\"modifcle\">";
			echo "</form></div></p><br><br><br><p><blockquote>".gettext("Attention, choisir un niveau de s&#233curit&#233 r&#233initialise les modifications eventuelles effectu&#233es en mode confirm&#233 sur ce template. </p><li> Par exemple, si vous avez d&#233fini le proxy en mode confirm&#233 uniquement pour ce template , la valeur choisie sera effac&#233e et remplac&#233e par la valeur par d&#233faut. </p></li><li>Pour &#234tre s&#251;r de conserver ces r&#233glages, d&#233finissez la valeur par d&#233faut de votre proxy au niveau du menu \"gestion des cl&#233s\" , mode confirm&#233. </p></li><li>Au contraire , incorporer un groupe ne r&#233initialise pas les valeurs du template mais \"ajoute\" simplement un groupe de cl&#233s. Dans ce cas , il n'y a &#233crasement d'une valeur que si cette valeur est pr&#233sente dans le groupe de cl&#233s.</p></blockquote></li>");
		}
	} else {
  		if ($template<>'base') {
  			$query4="DELETE FROM restrictions WHERE groupe='$template';";
  			$resultat4 = mysql_query($query4);
  			echo "<p>".gettext("Remise a z&#233ro du template")."</p>";
  			//si le but est d'interdire , on applique les restrictions de base donc on s'arrete la
  			
			if ($mod == "fullbase") {
  				refreshzrn($template);
  				echo"<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"10; URL=choisir_protect.php?salles=$template\"></HEAD>";
  				echo gettext("Modification effectu&#233e pour le template :")." $template<br> ".gettext("Commandes prises en compte !  Le mod&#233le en cours est de $mod  le template doit etre vide afin que les restrictions de base s'appliquent");
  				exit();
  			}
  		} else {  
			echo "<p><h1>".gettext("Pour remettre &#224 z&#233ro le template base, supprimer les cl&#233s une &#224 une")."</h1></p>";  
		}
  		
		$query1="SELECT cleID FROM restrictions WHERE groupe='base' ;";
  		$resultat1=mysql_query($query1);
  		$num=mysql_num_rows($resultat1)+1;

  		while ($num) {
  			$num=$num-1;
  			$row1=mysql_fetch_array($resultat1);
   			if ($row1[0]) {
  				$query3="Select Intitule,valeur,antidote,type from corresp where CleID='$row1[0]'";
  				$resultat3=mysql_query($query3);
  				$row3=mysql_fetch_row($resultat3);
  				if ($row3[3]=="restrict") {
 					// $row3[2]=ajoutedoublebarre($row3[1]);

 					// }
   					// $row3[1]=ajoutedoublebarre($row3[1]);
    					//$query2 = "DELETE FROM `restrictions` WHERE `cleID`='$row1[0]' AND `groupe`='$salle';";
    
  					$query2="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row3[2]','$row1[0]','$template');";
  					echo gettext("Insertion de la cl&#233")." $row3[0] ".gettext("dans")." $template  <br>";
  					$resultat2=mysql_query($query2);
   				}

  				$i++;
  			}

  		}

  		echo "<p>".gettext("Application du niveau choisi")." $mod</p>";
  		if (($mod<>"nobase") and ($mod<>"fullbase")) {
  			applique_modele($mod,$template,"oui");
  		}
 		
		// la manip a deja ete faite dans ce cas
 		//il faut vider le template afin que les restrictions de base s'appliquent
  		refreshzrn($template);
  		refreshzrn('base');
  		echo "<HEAD><META HTTP-EQUIV=\"refresh\" CONTENT=\"10; URL=choisir_protect.php?salles=$template\"></HEAD>";
  		echo gettext("Modification effectu&#233e pour le template :")." $template<br>".gettext("Commandes prises en compte ! Le mod&#233le en cours est de")." $mod";
  	}
} else {
	echo gettext("Choisir un template &#224 modifier")."<br>";
  	$handle=opendir('/home/templates');
        while ($file = readdir($handle)) {
        	if ($file<>'.' and $file<>'..' and $file<>'registre.vbs' and $file<>'skeluser') {
                	echo "<a href=\"choisir_protect.php?salles=$file\">$file</a><br>";
		}
	}
}

include("pdp.inc.php");

?>
