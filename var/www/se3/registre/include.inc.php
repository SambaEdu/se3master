<?php

   /**
   
   * Gestion des cles pour clients Windows (Fonctions pour registre)
   * @Version $Id: include.inc.php 3034 2008-06-14 15:14:14Z plouf $ 
   
  
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

* Fonctions fonction permettant d'appliquer un modele a un template ( pas de refreshzrn dans cette fonction (gere a part)
	
* @Parametres  
* @Return 
   
*/

function applique_modele($mod,$salle,$affiche)
{
	connexion();
	$query="SELECT `cle`,`etat` FROM `modele` WHERE `mod`= '$mod' ;";
	$resultat = mysql_query($query);
	while ($row=mysql_fetch_row($resultat)) {
		$cle=$row[0];
		$query = "SELECT cleID,Intitule,valeur,antidote,type FROM corresp WHERE cleID='$cle';";
		$insert = mysql_query($query);
		$row1 = mysql_fetch_row($insert);
		$query = "SELECT cleID,valeur FROM restrictions WHERE cleID='$cle' AND groupe='$salle';";
		$verif = mysql_query($query);
		$row2=mysql_fetch_row($verif);
		
		if ($row[1] == "1") {
			$row1[2]=ajoutedoublebarre($row1[2]);
			if ($row2[0]) {
				$query = "UPDATE `restrictions` SET `valeur` = '$row1[2]' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
				$insert = mysql_query($query);
			} else {
				$query="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[2]','$row[0]','$salle');";
				$insert = mysql_query($query);
			}
		}
		else{
			if ($row1[4] == "config") {
				$query="DELETE FROM restrictions where cleID='$cle' and `groupe` = '$salle';";
				$insert = mysql_query($query);
			}
			else {
				if ($row2[0]) {
					$query = "UPDATE `restrictions` SET `valeur` = '$row1[3]' WHERE `cleID` = '$cle' AND `groupe` = '$salle';";
					$insert = mysql_query($query);
				} else {
					$query="INSERT INTO restrictions (resID,valeur,cleID,groupe) VALUES ('','$row1[3]','$row[0]','$salle');";
					$insert = mysql_query($query);
				}
			}
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
			if ($row[0] == $cat)
				echo "<td class=\"enabledheader\" width=\"130\" height=\"30\" align=\"center\">";
			else
				echo "<td class=\"menuheader\" width=\"130\" height=\"30\" align=\"center\">";
			echo "<a href=\"$cible&cat=$row[0]\" >$row[0]</a></td>";
      			if (($i % 7)==0) {
      				echo "</tr><tr>";
      			}
		}
		$i++;
	}
	if ($cat == "tout")
		echo "<td class=\"enabledheader\" width=\"130\" height=\"30\" align=\"center\">";
	else
		echo "<td class=\"menuheader\" width=\"130\" height=\"30\" align=\"center\">";
	echo "<a href=\"$cible&cat=tout\">".gettext("Tout")."</a></td>";
	echo "</tr></table><br>";

	//affichage des sous-categories (si la categorie est choisie)
	if ($cat) {
		$query="Select distinct sscat from corresp where '$cat'=categorie group by sscat;";
		$resultat = mysql_query($query);
		$i=1;
		echo "<table><tr>";
		while ($row=mysql_fetch_row($resultat)) {
			if ($row[0]) {
				echo "<td class=\"menucell\" width=\"130\" height=\"30\" align=\"center\">";
				echo "<a href=\"$cible&cat=$cat&sscat=$row[0]\" >$row[0]</a></td>";
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
       			echo "<img src=\"/elements/images/system-help.png\" alt=\"".gettext("Aide")."\" title=\"$row[7]\" width=\"16\" height=\"18\" border=\"0\" /></a></td>";
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
				echo "<a href=\"$cible&modifkey=$row[1] \"><td BGCOLOR=\"#a5d6ff\"><DIV ALIGN=CENTER>";
            			echo "&nbsp;$row[2]</DIV> </td></a>";
				echo "<a href=\"$cible&$getcible1=$row[1]\"><td BGCOLOR=\"#e0dfde\">";
				echo "<DIV ALIGN=CENTER>$row[5]</DIV></td></a>";
            		} else {
            			echo "<td BGCOLOR=\"#FF0000\"><DIV ALIGN=CENTER>";
            			echo "&nbsp;$row[2]</DIV> </td><td BGCOLOR=\"#e0dfde\"><DIV ALIGN=CENTER>$row[5]</DIV></td>";
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
		echo "<a href=\"cle-maj.php?action=maj\">".gettext("Effectuer la mise a jour de la base de cl&#233s ?")."</a><br>";
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
