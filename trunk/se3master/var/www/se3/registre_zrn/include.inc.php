<?php

   /**
   
   * Gestion des cles pour clients Windows (Fonctions pour registre)
   * @Version $Id$ 
   
  
   * @Projet LCS / SambaEdu 
   
   * @auteurs  Sandrine Dangreville
   
   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: registre
   * file: include.inc.php

  */	



require_once ("lang.inc.php");
bindtextdomain('se3-registre',"/var/www/se3/locale");
textdomain ('se3-registre');


/**

* Fonctions Connexion a se3db par include de config.inc.php
	
* @Parametres 
* @Return  
   
*/

Function connexion()   // connexion a une base de donnees
{ #require("conf.php");
	require ("config.inc.php");
}



/**

* Fonctions Affiche un lien Retour au menu
	
* @Parametres 
* @Return  
   
*/
Function retour()
{
	echo"<a href=indexcle.php>".gettext("Retour au menu")."</a>";
}



/**

* Fonctions traitement des URL supprime les doubles barres
	
* @Parametres $string url a traiter
* @Return  $final l'url traite
   
*/

Function enlevedoublebarre($string)
{
        $temp=rawurlencode($string);
        $temp1=preg_replace("/%5C%5C/","%5C",$temp);
        $final=rawurldecode($temp1);
	return $final;
}


/**

* Fonctions Supprime le retour chariot
	
* @Parametres $string ce qu'il faut traiter
* @Return  $final 
   
*/
Function enleveretourchariot($string)
{
        $temp=rawurlencode($string);
        $temp1=preg_replace("/[^ \n\r\t]/","",$temp);
        $final=rawurldecode($temp1);
	return $final;
}	

/**

* Fonctions Ajout une double barre
	
* @Parametres $string ce qu'il faut traiter
* @Return  $final 
   
*/
Function ajoutedoublebarre($string)
{
        $temp=rawurlencode($string);
        $temp1=preg_replace("/%5C/","%5C%5C",$temp);
        $final=rawurldecode($temp1);
	return $final;
}

/**

* Fonctions supprime les antislash
	
* @Parametres $string
* @Return 
   
*/
Function enleveantislash($string)
{
	$temp=rawurlencode($string);
        $temp1=preg_replace("/%5C%27/","%27",$temp);
        $temp2=preg_replace("/%5C%22/","%22",$temp1);
        $final=rawurldecode($temp2);
	return $final;
}

/**

* Fonctions supprime les doubles slash
	
* @Parametres $string
* @Return 
   
*/
Function enlevedoubleslash($string)
{
	//$temp=rawurlencode($string);
        $temp1=preg_replace("////","/",$temp);
        //$temp2=preg_replace("/%5C%22/","%22",$temp1);
        //$final=rawurldecode($temp1);
        $final = $temp1;
        return $final;
}

/**

* Fonctions supprime les crochets
	
* @Parametres $string
* @Return 
   
*/

Function enlevecrochets($string)
{
        $temp=rawurlencode($string);
        $temp1=preg_replace("/%5B/","",$temp);
        $temp2=preg_replace("/%5D/","",$temp1);
        $final=rawurldecode($temp2);
        return $final;
}

/**

* Fonctions supprime les quotes
	
* @Parametres $string
* @Return 
   
*/
Function enlevequotes($string)
{
        $temp=rawurlencode($string);
        $temp1=preg_replace("/%22/","",$temp);
        $final=rawurldecode($temp1);
	return $final;
}


/**

* Fonctions supprime les #
	
* @Parametres $string
* @Return 
   
*/
Function enlevediese($string)
{
	$temp=rawurlencode($string);
        $temp1 = preg_replace("/%23/","",$temp);
        $final=rawurldecode($temp1);
        return $final;
}


/**

* Fonctions fonction permettant de generer les fichiers zrn dans les templates
	
* @Parametres $string
* @Return 
   
*/

Function refreshzrn($string)
{
	connexion();
	$query="Select restrictions.valeur,corresp.chemin,corresp.OS,corresp.genre,corresp.type,corresp.valeur,corresp.antidote from restrictions,corresp where restrictions.groupe='$string' and restrictions.cleID=corresp.cleID order by corresp.OS;";
	$resultat = mysql_query($query);
	//au moins un template associe on continue
	if (mysql_num_rows($resultat)) {
		// template vide
		if (!isset($string)) {  
			echo gettext("groupe non inscrit");
		} else {
		//on demarre l'ecriture dans le fichier
		//on verifie que le fichier peut s'ouvrir
    			if (!$handle = fopen("/home/templates/$string/registre.zrn", "w+")) { 
				print gettext("Impossible d'ouvrir le fichier ");
         			exit;
       			}
			
			//on verifie que l'on peut ecrire le nom du template dedans
         		if (!fwrite($handle, "#$string\r\n")) {
				print "Impossible d'&eacute;crire dans le fichier zrn de $string";
            			exit;
            		}
        		while ($row = mysql_fetch_array($resultat)) {
          			$espace = explode(" ", $row[1]);
          			$row[1]="";
				// suppression des espaces en trop ( correction suite a un bug cause: xml , bdd ?)
          			for ($i=0;$i<count($espace);$i++) {
          				if ($espace[$i]){ $row[1]=trim($row[1])." ".trim($espace[$i]);
				}
          		}
			//traitement du cas tous ou par defaut
        		if ((!$row[2]) or ($row[2]=="TOUS")) { $row[2]="TOUS"; }
			if ($row[0]<>"SUPPR") {
				if (($row[4]=="restrict") or ($row[4]=="config")) { $texte="$row[2] @@@ ADD @@@ $row[1] @@@ $row[0] @@@ $row[3]\r\n";}
			} else { $texte= "$row[2] @@@ DEL @@@ $row[1]\r\n"; }
			//ecriture de la ligne ainsi formee
       			fwrite($handle, $texte);
		}
 	}

	fclose($handle);
	} else {
		//on verifie que le fichier peut s'ouvrir
    		if (!$handle = fopen("/home/templates/$string/registre.zrn", "w+")) { 
			print gettext("Impossible d'ouvrir le fichier ");
         		exit;
       		}
		//on verifie que l'on peut ecrire le nom du template dedans
		if (!fwrite($handle, "#$string\r\n")) {
			print gettext("Impossible d'&eacute;crire dans le fichier zrn de")." $string";
         	   	exit;
		}
		fclose($handle);
	}
	echo "<h3>".gettext("Fichiers g&#233n&#233res pour")." $string</h3><br>";

}


/**

* Fonctions fonction permettant d'afficher les cles non presentes dans un template (verifier si obsolete ou si utilisee, certainement pas utilisee)
	
* @Parametres $template 
* @Return 
   
*/

function affiche($template)
{
	connexion();
	$query="SELECT cleID FROM restrictions WHERE groupe='$template';";
    	$resultat = mysql_query($query);
    	$rowserv = mysql_fetch_array($resultat);
    	$values="($rowserv[0]";

        while ( $rowserv = mysql_fetch_array($resultat)) { 
		$values=$values.",$rowserv[0]"; 
	}
    	$values=$values.")";
    	$query = "SELECT cleID,Intitule,OS,chemin FROM corresp WHERE cleID NOT IN $values;";
    	echo "<FORM METHOD=POST ACTION=\"ajout_cle_groupe.php\"><table border=\"1\"><tr>";
    	echo "<td>Prendre</td><td>OS</td><td>Intitul&#233;</td><td>";
	echo "<img src=\"/elements/images/help.png\" alt=\"".gettext("Aide")."\" title=\"$row[3]\" width=\"16\" height=\"18\" border=\"0\" /></td></tr>";
    	$resultat = mysql_query($query);
        while ( $row = mysql_fetch_array($resultat)) {
        	$j++;
        	echo "<tr><td><INPUT TYPE=\"checkbox\" NAME=\"cle$j\" value=\"$row[0]\"></td><td>$row[2]</td><td>$row[1]</td><td>";
        	echo "<a href=\"#\"  onClick=\"windows.open('aide_cle.php?cle=$row[0]','aide','scrollbars=yes,width=600,height=620')\">";
		echo "<img src=\"/elements/images/help.png\" alt=\"$row[3]\" title=\"$row[3]\" width=\"16\" height=\"18\" border=\"0\" /></a>";
        	echo "</tr>";
        }

    	echo "</tr></table>";
    	echo "<INPUT TYPE=\"hidden\" name=\"salles\" value=\"$template\">";
    	echo "<INPUT TYPE=\"hidden\" name=\"nombre\" value=\"$j\">";

    	echo "<INPUT TYPE=\"hidden\" name=\"keygroupe\" value=\"3\" >";
    	echo "<INPUT TYPE=\"submit\" value=\"ok\"></form>";
}


/**

* Fonctions fonction permettant d'appliquer un modele a un template ( pas de refreshzrn dans cette fonction (gere a part)
	
* @Parametres  
* @Return 
   
*/

function applique_modele($mod,$salle,$affiche)
{
	connexion();
	$query="SELECT `cle`,`etat` FROM `modele` WHERE `mod`= '$mod' ;";
	echo "<h2>".gettext("Application du groupe de cl&#233")." $mod</h2><br>";
	$resultat = mysql_query($query);
	while ($row=mysql_fetch_row($resultat)) {
		$cle=$row[0];
                $query = "SELECT cleID,Intitule,valeur,antidote,type FROM corresp WHERE cleID='$cle';";
                $insert = mysql_query($query);
                $row1 = mysql_fetch_row($insert);
                $query = "SELECT cleID,valeur FROM restrictions WHERE cleID='$cle' AND groupe='$salle';";
                $verif = mysql_query($query);
                $row2=mysql_fetch_row($verif);
                $query3 = "SELECT cleID,resID FROM restrictions WHERE cleID='$cle' AND groupe='base';";
                $verif3 = mysql_query($query3);
                $row3=mysql_fetch_row($verif3);

	if ($row1[4]=="config") {
		$row1[2]=ajoutedoublebarre($row1[2]);
		if ($row2[0]) {
			$query = "UPDATE `restrictions` SET `valeur` = '$row1[2]' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
			$insert = mysql_query($query);
		} else {
			$query="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[2]','$row[0]','$salle');";
			$insert = mysql_query($query);
		}
	} else 	{
	//cas: cette cle est dans le template, dans le template base et existe bien : mise a jour et pas insertion
		if (($row3[0]) and ($row1[1]) and ($row2[0])) {
		//elle est deja dans le modele :
		//il faut donc appliquer la valeur et pas l'antidote (seulement dans base)
		//dans un autre template, il faut si la cle est au rouge dans le modele, il faut la supprimer du template
		//si elle est au vert dans le modele, il faut positionner a vert

		//ici la cle est au rouge activee
                	if ($row[1]) {
				//la salle est base : on active dans base
				$row1[2]=ajoutedoublebarre($row1[2]);
                		if ($salle=="base") {
                			echo gettext("Mise &#224 jour de la cl&#233  dans base :")." $row1[1] ".gettext("active")."<br>";
                			$query = "UPDATE `restrictions` SET `valeur` = '$row1[2]' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
                		} else {
					//la salle n'est pas base
					//on doit supprimer la cle du template
                			echo gettext("Effacement de la cl&#233  dans")." $salle : $row1[1]  <br>";
                			$query = "DELETE FROM `restrictions` WHERE `cleID`='$cle' AND `groupe`='$salle';";
                		}
                	} else {
				//la cle est au vert
				//elle n'est  pas activee dans le modele : il faut donc appliquer l'antidote et ceci independamment des salles
                  		echo gettext("Mise &#224 jour de la cl&#233 :")." $row1[1] : ".gettext("inactive")."<br>";
                  		$query = "UPDATE `restrictions` SET `valeur` = '$row1[3]' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
                  	}
		}
		//la cle n'est pas dans le template , elle existe bien dans corresp
                if (($row1[1]) and (!$row2[0])) {
			//il va falloir supprimer l'entree nulle eventuelle
			//la cle est active dans le modele
                    	if ($row[1]) {
			// on ne l'ajoute que dans base
                   		if ($salle=="base") {
                     			echo gettext("Insertion de la cl&#233 :")." $row1[1] ".gettext("(active) dans la salle")." $salle <br>";
                     			$query="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[2]','$row[0]','$salle');";
                    		} else {
					//dans une autre salle que base
					//la cle n'est pas presente dans base , il faut l'ajouter
                     			if (!$row3[1]) {
                          			$query3="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[2]','$row[0]','base');";
                          			$result3 = mysql_query($query3);
                          			echo gettext("Insertion de la cl&#233 :")." $row1[1] ".gettext("(active ou cle de config) dans base")." <br>";
                          			$testbase++;
                      			}
					//de plus on n'a rien a ajouter dans le template , par defaut c'est la restriction de base qui va s'appliquer
                      		}
                      	} else {
			//la cle est inactive dans le modele : on ajoute dans le template la cle desactivee
                       		if (!$row3[1]) {
                          		$query3="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[2]','$row[0]','base');";
                          		$result3 = mysql_query($query3);
                             		echo gettext("Insertion de la cl&#233 :")." $row1[1] ".gettext("(active) dans base")." <br>";
                             		$testbase++;
                      		}
                     		echo gettext("Insertion de la cl&#233 :")." $row1[1] ".gettext("(inactive ou cle de config)")." <br>";
                     		$query="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[3]','$row[0]','$salle');";
				//de plus la cle n'existe pas dans base , on l'ajoute (activee)

                      	}
		}
                $insert = mysql_query($query);
	}
   }
}



/**

* Fonctions permettant d'afficher le choix du niveau (obsolete)
	
* @Parametres  
* @Return 
   
*/

function choixniveau($but,$testniveau,$affiche)
{
	if ($testniveau) {  
		if ($affiche=="oui") {
 			echo "<br>".gettext("Actuellement , le niveau choisi est ");
 			if ($testniveau==1){echo gettext("D&#233butant"); }
  			if ($testniveau==2){echo gettext("Interm&#233diaire"); }
   			if ($testniveau==3){echo gettext("Confirm&#233");}  
		}
	} else {
   		echo gettext("Choisissez un niveau :");
   	}
 	
	echo "<br /><div align=\"center\">&nbsp;";
	echo "<form action=\"$but\" name=\"niv\" method=\"post\">".gettext("Choix du niveau :")."  &nbsp;";
	echo "<select name=\"niveau\" size=\"1\">";
	echo "<option value=\"1\" ";
	if ($testniveau==1){echo "selected";}
	echo">".gettext("D&eacute;butant")."</option><option value=\"2\" ";
	if ($testniveau==2){echo "selected";}
	echo ">".gettext("Interm&eacute;diaire")."</option><option value=\"3\" ";
	if ($testniveau==3){echo "selected";}
	echo ">".gettext("Confirm&eacute;")."</option>";
	echo "</select><input type=\"submit\" name=\"submit\" value=\"OK\" /></form></div><br />";
}


/**

* Fonctions permettant de recuperer le niveau (obsolete)
	
* @Parametres  
* @Return 
   
*/


function niveau()
{
	$niveau=$_POST['niveau'];
	$testniveau=$HTTP_COOKIE_VARS["NiveauChoisiSE3"];
	if ($niveau) {
		$testniveau=$niveau;
		setcookie ("NiveauChoisiSE3", "", time() - 36000);
		setcookie("NiveauChoisiSE3",$niveau,time()+36000);
	}
}

/**

* Fonctions retourne le niveau de l'interface
	
* @Parametres $testniveau permettait de modifier le comportement (en particulier, l'affichage de la categorie tout) 
* @Return 
   
*/

function afficheniveau($testniveau)
{
	if ($testniveau==1) { $afficheniveau="D&#233;butant"; }
	if ($testniveau==2) { $afficheniveau="Interm&#233;diaire"; }
	if ($testniveau==3) { $afficheniveau="Confirm&#233;"; }
	return $afficheniveau;
}


/**

* Fonctions fonction permettant d'afficher les categories et sous-categories
	
* @Parametres $cible permet de renvoyer vers la bonne page (affiche_cle, affiche_restrictions,....) -  $testniveau permettait de modifier le comportement (en particulier, l'affichage de la categorie tout) (obsolete)
* @Return  HTML
   
*/

function affichelistecat($cible,$testniveau,$cat)

{
	//affichage des cles attribuees au groupe
	connexion();
	echo "<table><tr>";
	$query="Select DISTINCT categorie from corresp group by categorie;";
	$resultat = mysql_query($query);
	$i=1;

	while ($row=mysql_fetch_row($resultat)) {
		if ($row[0]) {
			echo "<td class=\"menuheader\" width=\"130\" height=\"30\" align=\"center\">";
			echo "<a href=\"$cible&cat=$row[0]\" >$row[0]</a></td>";
      			if (($i % 7)==0) {
      				//passage a la ligne suivante dans le tableau
      				echo "</tr><tr>";
      			}
		}
		$i++;
	}
	echo "<td class=\"menuheader\" width=\"130\" height=\"30\" align=\"center\">";
	echo "<a href=\"$cible&cat=tout\">".gettext("Tout")."</a></td>";
	echo "</tr></table><br>";

	//affichage des sous-categories (si la categorie est choisie)
     	if ($cat) {
		echo"<h3>".gettext("Cat&#233gorie :")." $cat </h3>";
           	$query="Select distinct sscat from corresp where '$cat'=categorie group by sscat;";
           	$resultat = mysql_query($query);
           	$i=1;
           	echo "<table><tr>";
          	while ($row=mysql_fetch_row($resultat)) {
               		if ($row[0]) {
               			echo "<td class=\"menucell\" width=\"130\" height=\"30\" align=\"center\">";
				echo "<a href=\"$cible&cat=$cat&sscat=$row[0]\" >$row[0]</a></td>";
               		
               			//passage a la ligne au bout de sept cases
				if (($i % 7)==0) { echo "</tr><tr>"; }
               		}
               		$i++;
          	}
        	echo "</tr></table>";
    	}
}



/**

* Fonctions fonction permettant d'afficher les cles dans affiche_cle.php
	
* @Parametres $cible,$getcible1,$getcible2,$query,$affichetitle,$testniveau
* @Return  HTML
   
*/


function affichelisteget($cible,$getcible1,$getcible2,$query,$affichetitle,$testniveau)
{
	connexion();
	//$query="Select Intitule,cleID,valeur,genre,OS,antidote,type,chemin,categorie,sscat from corresp ".$ajout.$ajoutsscat;
    	//echo $query;
    	$resultat = mysql_query($query);
      	if (mysql_num_rows($resultat)) { 
		echo $affichetitle;
    		while ( $row = mysql_fetch_array($resultat)) {
        		echo "<tr><td><DIV ALIGN=CENTER>";
        		echo "<a href=\"#\" onClick=\"window.open('aide_cle.php?cle=$row[1]','aide','scrollbars=yes,width=600,height=620')\">";
       			echo "<img src=\"/elements/images/help.png\" alt=\"".gettext("Aide")."\" title=\"$row[7]\" width=\"16\" height=\"18\" border=\"0\" /></a></td>";
        		if ($row[8]) { echo "<td>$row[8]</td><td>$row[9]</td>";}
        		echo "<td>$row[0]&nbsp;</DIV></td><td><DIV ALIGN=CENTER>&nbsp;$row[4]</DIV></td>";
        		if ($row[6]=="config") {
            			if ($testniveau>2) {
					echo "<a href=\"$cible&$getcible1=$row[1]\"><td>";
            				echo "<DIV ALIGN=CENTER>&nbsp;$row[2]</DIV> </td></a><td>&nbsp;</td>";
               			} else {
               				echo"<td><DIV ALIGN=CENTER>&nbsp;$row[2]</DIV><td>&nbsp;</td>";
               			}   
			}

        	if ($row[6]=="restrict") {
            		if ($testniveau>2) {
				echo "<a href=\"$cible&modifkey=$row[1] \"><td BGCOLOR=\"#FF0000\"><DIV ALIGN=CENTER>";
            			echo "&nbsp;$row[2]</DIV> </td></a>";
				echo "<a href=\"$cible&$getcible1=$row[1]\"><td BGCOLOR=\"#00FF00\">";
				echo "<DIV ALIGN=CENTER>$row[5]</DIV></td></a>";
            		} else {
            			echo "<td BGCOLOR=\"#FF0000\"><DIV ALIGN=CENTER>";
            			echo "&nbsp;$row[2]</DIV> </td><td BGCOLOR=\"#00FF00\"><DIV ALIGN=CENTER>$row[5]</DIV></td>";
            		}
            	}
            
	    	if ($testniveau>2) {
            		echo "<td><DIV ALIGN=CENTER>";
			echo "<a href=\"$cible&$getcible1=$row[1]\">";
			echo "<img src=\"/elements/images/edit.png \" alt=\"".gettext("Modifier la valeur")."\" title=\"".gettext("Modifier la valeur")."\" width=\"16\" height=\"16\" border=\"0\" /></a></DIV></td><td >";
			echo "<a href=\"$cible&$getcible2=$row[1]&$getcible1=$row[1]\">";
			echo "<img src=\"/elements/images/edittrash.png\" alt=\"".gettext("Supprimer la cl&eacute;")."\" title=\"".gettext("Supprimer la cl&eacute;")."\" width=\"16\" height=\"16\" border=\"0\" /></a></td>";
             	} else {
              		echo" <td><DIV ALIGN=CENTER><img src=\"/elements/images/editpale.png\" alt=\"".gettext("Valeur non modifiable")."\" title=\"".gettext("Valeur non modifiable")."\" width=\"16\" height=\"16\" border=\"0\" /></DIV></td><td >";
			echo "<img src=\"/elements/images/edittrash.png\" alt=\"".gettext("Valeur non modifiable")."\" title=\"".gettext("Valeur non modifiable")."\" width=\"16\" height=\"16\" border=\"0\" /></td>";
             	}
    	}
    	echo"</tr></table>    ";}
}

function test_bdd_registre() {
// Controle l'installation des cles
	connexion();
	$query="select * from corresp";
	$resultat=mysql_query($query);
	$ligne=mysql_num_rows($resultat);
	if($ligne == "0") { 
		// echo "<h1>".gettext("Controle de l'installation du module Clients Windows( Premi&#232;re &#233;tape )")."</h1>";
		echo "<table><TR><TD>";
          	echo gettext("Importation des cl&#233s");
          	echo "</TD><TD align=\"center\"><font color=#FF0000>";
		echo "&nbsp;".gettext("Non effectu&#233e")."</font> :".gettext("Vous devez tout d'abord importer les cl&#233s.");
          	echo "</td><td>&nbsp;<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Vous n\'avez pas install&#233 les cl&#233s des registres,<br>Pour cela vous devez aller dans <a href=\'gestion_interface.php\'>Gestion des clients Windows</a> et cliquer sur effectuer la mise &#224 jour de la base des cl&#233s ')")."\">".gettext("Aide")."</u>";
          	echo "</TD></TR></table>\n";
          	include ("pdp.inc.php");
          	return false;
	}  else {
		return true;
	}
}


/**

* Fonctions test l'installation des vbs
	
* @Parametres 
* @Return  message HTML indiquant s'ils sont installes 
   
*/

function test_zorn_tools()
{
	// Controle l'installation des vbs
	$DIR_VBS="/var/se3/Progs/install/installdll/rejoin_se3_XP.vbs";
	if(@is_dir("/var/se3/Progs/install/installdll")) {
		return true;
	} else {
		// echo "<h1>".gettext("Controle de l'installation du module Clients Windows (Deuxi&#232;me &#233;tape)")."</h1>";
		echo "<table><TR><TD>";
		echo gettext("Contr&#244;le la pr&#233;sence des outils clients windows (VBS)");
		echo "</TD><TD align=\"center\"><font color=#FF0000> &nbsp;".gettext("Non effectu&#233;e")."</font>";
		echo "</TD><TD>";
		echo "&nbsp;<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Vous n\'avez pas install&#233 les scripts vbs afin de pouvoir faire rejoindre le domaine &#224 vos clients Windows. Pour cela aller dans <a href=\'gestion_interface.php\'>Gestion des clients Windows</a> puis effectuer la mise &#224 jour de l\'installeur DLL et de registre.vbs')")."\">".gettext("Aide")."</u>";
		echo "</TD></TR></table>\n";
		include ("pdp.inc.php");
		return false;
	}
}
?>
